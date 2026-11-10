<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\CachedInsight;
use App\Models\ImportBatch;
use App\Models\LampScholarRecord;
use App\Models\ReviewQueueItem;
use App\Support\AnalyticsFilterData;
use App\Support\ImportSchoolYear;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\OfficeTestCase;

class ImportSchoolYearTest extends OfficeTestCase
{
    private function batch(): ImportBatch
    {
        $admin = $this->office('admin');
        $this->actingAs($admin);

        return ImportBatch::create(['uploaded_by' => $admin->id, 'original_filename' => 'iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx', 'status' => 'completed']);
    }

    private function correct(ImportBatch $batch, int $count = 1)
    {
        return $this->postJson('/import/'.$batch->id.'/school-year', ['from_school_year' => '2026-2027', 'school_year' => '2025-2026', 'expected_count' => $count]);
    }

    public function test_explicit_workbook_year_mismatch_is_rejected_before_any_batch_is_created(): void
    {
        $this->actingAs($this->office('admin'));
        $file = new UploadedFile(base_path('docs/test-data/iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx'), 'renamed.xlsx', null, null, true);
        $this->assertSame('2025-2026', ImportSchoolYear::declaredYear($file));
        $this->postJson('/import', ['file' => $file, 'school_year' => '2026-2027'])->assertUnprocessable()->assertJsonValidationErrors('school_year');
        $this->assertSame(0, Applicant::count());
        $this->assertSame(0, ImportBatch::count());
        $this->assertSame('2025-2026', ImportSchoolYear::fromText('iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx'));
        $this->assertNull(ImportSchoolYear::fromText('applicants_2027000001.xlsx'));
    }

    public function test_admin_correction_preserves_ids_payments_and_lamp_records_and_enables_comparisons(): void
    {
        $batch = $this->batch();
        $old = $this->applicant(['imported_batch_id' => $batch->id, 'school_year' => '2026-2027', 'external_app_no' => 'PREVIOUS-1', 'application_status' => 'completed']);
        $current = $this->applicant(['school_year' => '2026-2027']);
        $lamp = LampScholarRecord::create(['applicant_id' => $old->id, 'decision' => 'yes']);
        DB::table('payments')->insert(['applicant_id' => $old->id, 'fee_type' => 'reservation_fee', 'status' => 'paid', 'amount' => 0, 'paid_at' => now(), 'created_at' => now()]);
        CachedInsight::create(['key' => 'analytics', 'payload' => [], 'generated_at' => now()]);
        $this->get('/analytics?year=2026-2027')->assertOk()->assertSee('Review and correct this batch');
        $this->get('/import/'.$batch->id)->assertOk()->assertSee('Correct school year for 1 applicants');
        $this->correct($batch)->assertOk()->assertJsonPath('moved', 1);
        $this->assertSame('2025-2026', $old->fresh()->school_year);
        $this->assertSame($old->reference_number, $old->fresh()->reference_number);
        $this->assertSame('2026-2027', $current->fresh()->school_year);
        $this->assertDatabaseHas('payments', ['applicant_id' => $old->id, 'fee_type' => 'reservation_fee']);
        $this->assertSame('yes', $lamp->fresh()->decision);
        $this->assertDatabaseMissing('cached_insights', ['key' => 'analytics']);
        $this->assertDatabaseHas('audit_logs', ['title' => 'Import School Year Corrected']);
        $feed = AnalyticsFilterData::make();
        $this->assertSame(['2026-2027', '2025-2026'], $feed['years']);
        $this->assertSame(1, collect($feed['rows'])->where('year', '2025-2026')->sum('enrolled'));
        $this->get('/analytics?year=2026-2027')->assertOk()->assertDontSee('Review and correct this batch');
        $this->getJson('/analytics/reports/trends/preview?period=Current%20School%20Year&year=2026-2027')->assertOk()->assertJsonPath('report.rows.5.1', '1')->assertJsonPath('report.rows.5.2', '1');
    }

    public function test_correction_rejects_collisions_stale_counts_and_pending_reviews(): void
    {
        $batch = $this->batch();
        $old = $this->applicant(['imported_batch_id' => $batch->id, 'school_year' => '2026-2027', 'external_app_no' => 'SHARED']);
        $target = $this->applicant(['school_year' => '2025-2026', 'external_app_no' => 'SHARED']);
        $this->correct($batch)->assertConflict();
        $this->assertSame('2026-2027', $old->fresh()->school_year);
        $target->update(['external_app_no' => 'OTHER']);
        $this->correct($batch, 2)->assertConflict();
        ReviewQueueItem::create(['rq_number' => ReviewQueueItem::nextRqNumber(), 'type' => 'lamp', 'title' => 'Pending check', 'applicant_id' => $old->id, 'status' => 'waiting', 'submitted_at' => now()]);
        $this->correct($batch)->assertConflict();
        $this->assertSame('2026-2027', $old->fresh()->school_year);
    }

    public function test_staff_cannot_correct_school_years_and_audit_failure_rolls_back_the_change(): void
    {
        $batch = $this->batch();
        $old = $this->applicant(['imported_batch_id' => $batch->id, 'school_year' => '2026-2027']);
        $this->actingAs($this->office('iato_staff'));
        $this->correct($batch)->assertForbidden();
        $this->actingAs($this->office('admin'));
        AuditLog::creating(function () {
            throw new \RuntimeException('Audit unavailable');
        });
        try {
            $this->correct($batch)->assertStatus(500);
            $this->assertSame('2026-2027', $old->fresh()->school_year);
        } finally {
            AuditLog::flushEventListeners();
            AuditLog::clearBootedModels();
        }
    }
}
