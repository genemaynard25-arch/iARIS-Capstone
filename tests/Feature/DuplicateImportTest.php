<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\LampRequest;
use App\Models\LampScholarRecord;
use App\Models\ReviewQueueItem;
use App\Support\AdmissionStats;
use App\Support\ImportSchoolYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\OfficeTestCase;

class DuplicateImportTest extends OfficeTestCase
{
    private function copies(int $count = 1): array
    {
        $admin = $this->office('admin');
        $this->actingAs($admin);
        $name = 'iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx';
        $wrong = ImportBatch::create(['uploaded_by' => $admin->id, 'original_filename' => $name, 'status' => 'completed']);
        $correct = ImportBatch::create(['uploaded_by' => $admin->id, 'original_filename' => $name, 'status' => 'completed']);
        for ($n = 1; $n <= $count; $n++) {
            $values = ['external_app_no' => 'DUP-'.$n, 'first_name' => 'Same '.$n, 'application_status' => 'completed'];
            $this->applicant([...$values, 'school_year' => '2026-2027', 'imported_batch_id' => $wrong->id]);
            $this->applicant([...$values, 'school_year' => '2025-2026', 'imported_batch_id' => $correct->id]);
        }

        return [$wrong, $correct];
    }

    private function archive(ImportBatch $batch, int $count = 1)
    {
        return $this->postJson('/import/'.$batch->id.'/archive-duplicates', ['from_school_year' => '2026-2027', 'expected_count' => $count]);
    }

    public function test_warning_identifies_old_copy_when_the_latest_batch_is_correct(): void
    {
        [$wrong, $correct] = $this->copies();
        $warnings = ImportSchoolYear::warnings();
        $this->assertCount(1, $warnings);
        $this->assertSame($wrong->id, $warnings[0]['batch']);
        $this->assertSame([$correct->id], $warnings[0]['target_batches']);
        $this->get('/import/'.$correct->id)->assertOk()->assertSee('Check import batch #'.$wrong->id)->assertSee('/import/'.$wrong->id);
        $this->get('/import/'.$wrong->id)->assertOk()->assertSee('Verify and archive 1 duplicate records');
        $this->archive($correct)->assertConflict();
        $this->assertNull($correct->applicants()->first()->archived_at);
    }

    public function test_archive_preserves_both_copies_and_payments_and_removes_only_extra_active_counts(): void
    {
        [$wrong, $correct] = $this->copies();
        foreach (Applicant::all() as $a) {
            DB::table('payments')->insert(['applicant_id' => $a->id, 'fee_type' => 'reservation_fee', 'status' => 'paid', 'amount' => null, 'paid_at' => now()]);
        }
        $this->applicant(['school_year' => '2026-2027']);
        $beforeIds = Applicant::pluck('id')->all();
        $this->archive($wrong)->assertOk()->assertJsonPath('archived', 1);
        $this->assertSame($beforeIds, Applicant::pluck('id')->all());
        $this->assertSame(2, DB::table('payments')->count());
        $this->assertNotNull($wrong->applicants()->first()->archived_at);
        $this->assertNull($correct->applicants()->first()->archived_at);
        $this->assertSame([], ImportSchoolYear::warnings());
        $this->assertSame(1, AdmissionStats::summary()['overallCurrent']);
        $this->assertSame(1, AdmissionStats::summary()['overallPrevious']);
        $log = AuditLog::where('title', 'Duplicate Import Copy Archived')->firstOrFail();
        $this->assertCount(1, $log->diff);
        $this->get('/import/'.$wrong->id)->assertOk()->assertSee('This batch has no active records')->assertDontSee('id="archiveDuplicateCopyForm"', false);
        $this->archive($wrong)->assertConflict();
    }

    public function test_differing_details_cancel_the_entire_archive(): void
    {
        [$wrong, $correct] = $this->copies(2);
        $correct->applicants()->where('external_app_no', 'DUP-2')->first()->update(['first_name' => 'Edited applicant']);
        $this->archive($wrong, 2)->assertConflict();
        $this->assertSame(0, Applicant::whereNotNull('archived_at')->count());
        $this->assertDatabaseMissing('audit_logs', ['title' => 'Duplicate Import Copy Archived']);
    }

    public function test_payment_changes_and_scholarship_history_require_manual_review(): void
    {
        [$wrong, $correct] = $this->copies();
        $source = $wrong->applicants()->first();
        $target = $correct->applicants()->first();
        DB::table('payments')->insert(['applicant_id' => $source->id, 'fee_type' => 'reservation_fee', 'status' => 'paid', 'amount' => 100]);
        $this->archive($wrong)->assertConflict();
        DB::table('payments')->where('applicant_id', $source->id)->update(['amount' => null]);
        $this->archive($wrong)->assertConflict();
        DB::table('payments')->insert(['applicant_id' => $target->id, 'fee_type' => 'reservation_fee', 'status' => 'paid', 'amount' => null]);
        LampScholarRecord::create(['applicant_id' => $source->id, 'decision' => 'yes']);
        $this->archive($wrong)->assertConflict();
        $this->assertSame(0, Applicant::whereNotNull('archived_at')->count());
    }

    public function test_unauthorized_archive_and_audit_failure_do_not_change_any_records(): void
    {
        [$wrong] = $this->copies();
        $this->actingAs($this->office('iato_staff'));
        $this->archive($wrong)->assertForbidden();
        $this->actingAs($this->office('admin'));
        AuditLog::creating(function () {
            throw new \RuntimeException('Audit unavailable');
        });
        try {
            $this->archive($wrong)->assertStatus(500);
            $this->assertSame(0, Applicant::whereNotNull('archived_at')->count());
        } finally {
            AuditLog::flushEventListeners();
            AuditLog::clearBootedModels();
        }
    }

    public function test_reupload_is_blocked_while_the_earlier_copy_has_the_wrong_year(): void
    {
        [$wrong] = $this->copies();
        $count = Applicant::count();
        $file = new UploadedFile(base_path('docs/test-data/iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx'), 'iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx', null, null, true);
        $this->postJson('/import', ['school_year' => '2025-2026', 'file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertSame($count, Applicant::count());
        $this->assertSame(2, ImportBatch::count());
    }

    public function test_pending_reviews_and_conversations_are_not_auto_archived(): void
    {
        [$wrong, $correct] = $this->copies();
        $source = $wrong->applicants()->first();
        $queue = ReviewQueueItem::create(['rq_number' => ReviewQueueItem::nextRqNumber(), 'type' => 'lamp', 'title' => 'Pending', 'applicant_id' => $source->id, 'submitted_at' => now(), 'status' => 'waiting']);
        $this->archive($wrong)->assertConflict();
        $queue->update(['status' => 'sent']);
        LampRequest::create(['applicant_id' => $source->id, 'name' => 'Applicant', 'status' => 'waiting']);
        $this->archive($wrong)->assertConflict();
        $this->assertSame(0, Applicant::whereNotNull('archived_at')->count());
    }
}
