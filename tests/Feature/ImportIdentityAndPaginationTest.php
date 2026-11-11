<?php

namespace Tests\Feature;

use App\Imports\ApplicantsImport;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\LampScholarRecord;
use App\Support\ApplicantEditFlow;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\OfficeTestCase;

class ImportIdentityAndPaginationTest extends OfficeTestCase
{
    public function test_database_rejects_duplicate_active_import_identities_but_keeps_archives_and_history(): void
    {
        $active = $this->applicant(['external_app_no' => 'IDENTITY-1']);
        $this->applicant(['external_app_no' => 'IDENTITY-1', 'school_year' => '2024-2025']);
        $archived = $this->applicant(['external_app_no' => 'IDENTITY-1', 'archived_at' => now()]);
        try {
            $this->applicant(['external_app_no' => 'IDENTITY-1']);
            $this->fail('The database must prevent another active application for this identity.');
        } catch (UniqueConstraintViolationException) {
            $this->assertSame(3, Applicant::where('external_app_no', 'IDENTITY-1')->count());
        }
        try {
            ApplicantEditFlow::applyRestore($archived);
            $this->fail('Restoring a duplicate active identity must be rejected.');
        } catch (ValidationException) {
            $this->assertNotNull($archived->fresh()->archived_at);
            $this->assertNull($active->fresh()->archived_at);
        }
        ApplicantEditFlow::applyDestroy($active);
        ApplicantEditFlow::applyRestore($archived->fresh());
        $this->assertNull($archived->fresh()->archived_at);
    }

    public function test_reimport_serializes_applicant_and_fee_updates_and_does_not_reactivate_archives(): void
    {
        $user = $this->office('admin');
        $one = ImportBatch::create(['uploaded_by' => $user->id, 'original_filename' => 'identity.csv', 'status' => 'completed']);
        $two = ImportBatch::create(['uploaded_by' => $user->id, 'original_filename' => 'identity-2.csv', 'status' => 'completed']);
        $fields = ['external_app_no' => 'IDENTITY-REIMPORT', 'school_year' => '2025-2026', 'level' => 'college', 'first_name' => 'First', 'last_name' => 'Applicant', 'gender' => 'female'];
        $fees = ['application_fee' => true, 'reservation_fee' => true];
        $a = ApplicantsImport::commitRow($fields, null, $fees, $one->id, '2025-2026', 'college');
        $b = ApplicantsImport::commitRow(array_replace($fields, ['first_name' => 'Updated']), null, $fees, $two->id, '2025-2026', 'college');
        $this->assertSame($a->id, $b->id);
        $this->assertSame('Updated', $b->first_name);
        $this->assertSame(2, DB::table('payments')->where('applicant_id', $a->id)->count());
        ApplicantEditFlow::applyDestroy($b);
        $c = ApplicantsImport::commitRow($fields, null, $fees, $one->id, '2025-2026', 'college');
        $this->assertSame($a->id, $c->id);
        $this->assertNotNull($c->archived_at);
    }

    public function test_audit_pages_search_and_exports_cover_all_matching_entries_without_sending_every_row(): void
    {
        $this->actingAs($this->office('admin'));
        for ($i = 0; $i < 27; $i++) {
            AuditLog::record('edit', 'Applicants', 'Pagination QA '.$i, 'Pagination test entry '.$i);
        }
        $this->getJson('/audit-logs?search=Pagination%20QA')->assertOk()->assertJsonCount(10, 'logs')->assertJsonPath('pagination.total', 27)->assertJsonPath('totals.today', 27);
        $this->getJson('/audit-logs?search=Pagination%20QA&page=3')->assertOk()->assertJsonCount(7, 'logs')->assertJsonPath('pagination.page', 3);
        $this->getJson('/audit-logs?search=Pagination%20QA&page=999')->assertOk()->assertJsonPath('pagination.page', 3);
        $csv = $this->get('/audit-logs?search=Pagination%20QA&export=csv')->assertOk()->streamedContent();
        $this->assertSame(28, count(array_filter(explode("\n", trim($csv)))));
        $this->assertStringContainsString('Pagination test entry 0', $csv);
        $this->assertStringContainsString('Pagination test entry 26', $csv);
    }

    public function test_audit_date_user_action_filters_and_statistics_are_not_page_local(): void
    {
        $user = $this->office('admin');
        $this->actingAs($user);
        AuditLog::record('import', 'Import Data', 'Old import', 'Archived timeline entry');
        DB::table('audit_logs')->where('title', 'Old import')->update(['created_at' => now()->subDays(10)]);
        AuditLog::record('delete', 'Applicants', 'Today delete', 'Current timeline entry');
        $this->getJson('/audit-logs?days=1')->assertOk()->assertJsonCount(1, 'logs')->assertJsonPath('totals.delete', 1);
        $this->getJson('/audit-logs?group=import&user='.$user->id)->assertOk()->assertJsonCount(1, 'logs')->assertJsonPath('logs.0.title', 'Old import');
        $this->getJson('/audit-logs?days=custom&to='.now()->subDays(5)->toDateString())->assertOk()->assertJsonCount(1, 'logs');
        $this->getJson('/audit-logs?days=custom&from=2026-02-02&to=2026-01-01')->assertUnprocessable();
    }

    public function test_scholarship_pages_keep_year_totals_and_search_encrypted_names_beyond_page_one(): void
    {
        $user = $this->office('admin');
        for ($i = 0; $i < 23; $i++) {
            $this->applicant(['first_name' => 'Scholar'.$i, 'last_name' => 'Paging', 'school_year' => '2026-2027', 'college' => 'CEAS', 'level' => 'college']);
        }
        $this->applicant(['first_name' => 'Prior', 'school_year' => '2025-2026']);
        $this->actingAs($user)->get('/records/scholars')->assertOk()->assertViewHas('scholars', fn ($rows) => $rows->count() === 10)->assertViewHas('scholarTotals', fn ($totals) => $totals['total'] === 23);
        $this->getJson('/records/scholars?year=2026-2027&page=3')->assertOk()->assertJsonCount(3, 'scholars')->assertJsonPath('pagination.total', 23);
        $this->getJson('/records/scholars?year=2026-2027&search=Scholar0')->assertOk()->assertJsonCount(1, 'scholars')->assertJsonPath('scholars.0.first', 'Scholar0');
        $csv = $this->get('/records/scholars?year=2026-2027&export=csv')->assertOk()->streamedContent();
        $this->assertSame(24, count(array_filter(explode("\n", trim($csv)))));
        $this->assertStringNotContainsString('Prior', $csv);
    }

    public function test_scholarship_filters_and_record_links_work_across_pages(): void
    {
        $a = $this->applicant(['first_name' => 'Aaron', 'school_year' => '2026-2027', 'level' => 'college', 'college' => 'CEAS']);
        for ($i = 0; $i < 12; $i++) {
            $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'college' => 'CITE']);
        }
        LampScholarRecord::create(['applicant_id' => $a->id, 'decision' => 'yes', 'iato_status' => 'confirmed']);
        $this->actingAs($this->office('admin'))->getJson('/records/scholars?year=2026-2027&unit=CEAS&lamp=Match')->assertOk()->assertJsonCount(1, 'scholars')->assertJsonPath('scholars.0.id', $a->reference_number);
        $this->get('/records/scholars?open='.$a->reference_number)->assertOk()->assertViewHas('openScholar', fn ($row) => $row['id'] === $a->reference_number);
        $this->getJson('/records/scholars?year=2026-2027&sort=az')->assertOk()->assertJsonPath('scholars.0.first', 'Aaron');
        $this->getJson('/records/scholars?year=2026-2027&search=no-such-person')->assertOk()->assertJsonCount(0, 'scholars')->assertJsonPath('pagination.total', 0);
    }
}
