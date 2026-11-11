<?php

namespace Tests\Feature;

use App\Http\Controllers\CollegeController;
use App\Http\Controllers\ScholarController;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\LampScholarRecord;
use App\Models\ReviewQueueItem;
use App\Models\RolePermission;
use App\Support\AddressClassifier;
use App\Support\ApplicantRecords;
use Illuminate\Support\Facades\DB;
use Tests\OfficeTestCase;

class IncrementFiveQaTest extends OfficeTestCase
{
    private function entry(array $overrides = []): array
    {
        return array_replace([
            'scope' => 'college', 'last' => 'QaSurname', 'first' => 'QaGiven', 'mi' => 'K',
            'gender' => 'Female', 'email' => 'qa-entry@example.test', 'contact' => '09171234567',
            'address' => 'Purok 3 Brgy. Kawit, Lipa City, Batangas', 'group' => 'CEAS',
            'program' => 'BS Psychology', 'status' => 'Pending', 'applied' => '2026-01-01',
        ], $overrides);
    }

    public function test_middle_initial_survives_creation_edit_reload_and_staff_approval(): void
    {
        $admin = $this->office('admin');
        $this->actingAs($admin)->postJson('/applicants', $this->entry())->assertOk();
        $applicant = Applicant::latest('id')->firstOrFail();
        $this->assertSame('K', $applicant->middle_initial);
        $this->assertNotSame('K', DB::table('applicants')->where('id', $applicant->id)->value('middle_initial'));
        RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => 'college'], ['enabled' => true, 'can_edit' => true]);
        $this->actingAs($this->office('iato_staff'))->putJson('/applicants/'.$applicant->reference_number, $this->entry(['mi' => 'L']))->assertOk()->assertJsonPath('review', true);
        $this->assertSame('K', $applicant->fresh()->middle_initial);
        $item = ReviewQueueItem::where('applicant_id', $applicant->id)->latest('id')->firstOrFail();
        $this->actingAs($admin)->postJson('/review-queue/'.$item->rq_number.'/approve')->assertOk();
        $this->assertSame('L', $applicant->fresh()->middle_initial);
        $rows = ApplicantRecords::forLevel('college');
        $this->assertSame('L', collect($rows)->firstWhere('id', $applicant->reference_number)['mi']);
    }

    public function test_repeated_manual_application_is_rejected_but_other_people_and_years_are_allowed(): void
    {
        $this->actingAs($this->office('admin'));
        $this->postJson('/applicants', $this->entry())->assertOk();
        $this->postJson('/applicants', $this->entry(['first' => '  qagiven  ']))->assertUnprocessable()->assertJsonValidationErrors('first');
        $this->postJson('/applicants', $this->entry(['first' => 'Sibling']))->assertOk();
        $this->postJson('/applicants', $this->entry(['mi' => 'Z']))->assertOk();
        $current = Applicant::max('school_year');
        Applicant::where('school_year', $current)->update(['school_year' => '2024-2025']);
        $this->applicant(['school_year' => '2026-2027']);
        $this->postJson('/applicants', $this->entry())->assertOk();
    }

    public function test_manual_mobile_numbers_are_validated_on_create_and_edit_and_saved_with_country_code(): void
    {
        $this->actingAs($this->office('admin'));
        foreach (['091712345678', '+6391712345678', '08171234567', '+12025550123', '09abcdefgh9', '123', '+63+9171234567'] as $invalid) {
            $this->postJson('/applicants', $this->entry(['contact' => $invalid]))->assertUnprocessable()->assertJsonValidationErrors('contact');
        }
        $this->postJson('/applicants', $this->entry(['contact' => '09171234567']))->assertOk();
        $applicant = Applicant::latest('id')->firstOrFail();
        $this->assertSame('+639171234567', $applicant->contact_number);
        $this->putJson('/applicants/'.$applicant->reference_number, $this->entry(['contact' => '+639181234567']))->assertOk();
        $this->assertSame('+639181234567', $applicant->fresh()->contact_number);
        $this->putJson('/applicants/'.$applicant->reference_number, $this->entry(['contact' => '+63918123456789']))->assertUnprocessable()->assertJsonValidationErrors('contact');
        $this->assertSame('+639181234567', $applicant->fresh()->contact_number);
        $this->putJson('/applicants/'.$applicant->reference_number, $this->entry(['contact' => '0918 123 4567']))->assertOk();
        $this->assertSame('+639181234567', $applicant->fresh()->contact_number);
    }

    public function test_staff_cannot_queue_the_same_new_application_twice(): void
    {
        RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => 'college'], ['enabled' => true, 'can_edit' => true]);
        $staff = $this->office('iato_staff');
        $this->actingAs($staff)->postJson('/applicants', $this->entry())->assertOk()->assertJsonPath('review', true);
        $this->postJson('/applicants', $this->entry())->assertUnprocessable()->assertJsonValidationErrors('first');
        $item = ReviewQueueItem::where('submitted_by', $staff->id)->latest('id')->firstOrFail();
        $this->actingAs($this->office('admin'))->postJson('/review-queue/'.$item->rq_number.'/approve')->assertOk();
        $this->assertSame(1, Applicant::where('reference_number', 'like', 'APP-%')->get()->where('first_name', 'QaGiven')->count());
    }

    public function test_scholarship_scope_excludes_archive_and_other_years_and_reads_real_decisions(): void
    {
        $prior = $this->applicant(['school_year' => '2025-2026']);
        $pending = $this->applicant(['school_year' => '2026-2027']);
        $checked = $this->applicant(['school_year' => '2026-2027']);
        $confirmed = $this->applicant(['school_year' => '2026-2027']);
        $declined = $this->applicant(['school_year' => '2026-2027']);
        $archived = $this->applicant(['school_year' => '2026-2027', 'archived_at' => now()]);
        foreach ([[$checked, 'yes', 'pending'], [$confirmed, 'yes', 'confirmed'], [$declined, 'no', 'confirmed']] as [$a, $decision, $status]) {
            LampScholarRecord::create(['applicant_id' => $a->id, 'decision' => $decision, 'iato_status' => $status]);
        }
        $rows = ScholarController::sample('2026-2027')->keyBy('id');
        $this->assertCount(4, $rows);
        $this->assertCount(4, ScholarController::sample());
        $this->assertFalse($rows->has($archived->reference_number));
        $this->assertSame('Pending Check', $rows[$pending->reference_number]['lamp']);
        $this->assertSame('Awaiting IATO', $rows[$checked->reference_number]['lamp']);
        $this->assertSame('Match', $rows[$confirmed->reference_number]['lamp']);
        $this->assertSame('Not Scholar', $rows[$declined->reference_number]['lamp']);
        $this->actingAs($this->office('admin'))->get('/records/scholars')->assertOk()->assertViewHas('selectedSchoolYear', '2026-2027');
        $this->get('/records/scholars?year=2025-2026')->assertOk()->assertViewHas('scholars', fn ($rows) => $rows->count() === 1 && $rows->first()['id'] === $prior->reference_number);
        $this->get('/records/scholars?year=2026-2026')->assertUnprocessable();
    }

    public function test_location_buckets_are_provinces_and_do_not_double_count_nested_places(): void
    {
        $this->assertSame('Batangas', AddressClassifier::classify('Purok 3 Brgy. Kawit, Lipa City, Batangas'));
        $this->assertSame('Laguna', AddressClassifier::classify('Calamba City, Laguna'));
        $this->assertSame('Other / Unspecified province', AddressClassifier::classify('Quezon City'));
        $this->assertNotContains('Metro Manila', AddressClassifier::GROUPS);
        $this->assertSame('Quezon', AddressClassifier::classify('Lucena, Quezon'));
        $this->assertSame('Other / Unspecified province', AddressClassifier::classify(null));
        $this->assertNotContains('Lipa City', AddressClassifier::GROUPS);
        $this->assertNotContains('CALABARZON', AddressClassifier::GROUPS);
    }

    public function test_weekly_chart_counts_manual_and_imported_entries_but_excludes_archive_and_prior_year(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11)->setTime(12, 0));
        $this->actingAs($this->office('admin'));
        $batch = ImportBatch::create(['original_filename' => 'qa.xlsx', 'status' => 'completed', 'uploaded_by' => auth()->id()]);
        $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'imported_batch_id' => $batch->id, 'created_at' => '2026-01-01']);
        $this->applicant(['school_year' => '2025-2026', 'level' => 'college', 'imported_batch_id' => $batch->id]);
        $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'imported_batch_id' => $batch->id, 'archived_at' => now()]);
        $this->postJson('/applicants', $this->entry())->assertOk();
        $this->get('/home')->assertOk()->assertViewHas('weekChart', fn ($chart) => array_sum($chart['college']) === 2 && $chart['college'][2] === 2);
        $this->travelBack();
    }

    public function test_account_filter_options_use_actual_departments_and_roles(): void
    {
        $admin = $this->office('admin');
        $registrar = $this->office('registrar');
        $registrar->update(['department' => 'Registrar']);
        $this->office('iato_staff')->update(['department' => 'Admissions QA']);
        $this->actingAs($admin)->get('/accounts')->assertOk()
            ->assertViewHas('filterDepartments', fn ($values) => in_array('Registrar', $values) && in_array('Admissions QA', $values))
            ->assertViewHas('filterRoles', fn ($values) => in_array('registrar', $values) && in_array('iato_staff', $values));
    }

    public function test_dean_has_no_fabricated_received_reports_and_audit_history_is_not_capped_at_500(): void
    {
        $admin = $this->office('admin');
        $this->actingAs($admin);
        AuditLog::record('edit', 'Applicants', 'QA oldest entry', 'Keep this entry accessible.');
        $oldest = AuditLog::latest('id')->firstOrFail();
        for ($i = 0; $i < 501; $i++) {
            AuditLog::record('edit', 'Applicants', 'QA entry '.$i, 'Test entry');
        }
        $this->get('/audit-logs?search=QA%20oldest%20entry')->assertOk()->assertViewHas('logs', fn ($logs) => $logs->contains('id', $oldest->id));
        $this->assertSame([], CollegeController::received(false));
        $dean = $this->office('dean');
        $dean->update(['assigned_college' => 'CEAS']);
        $this->actingAs($dean)->get('/college')->assertOk()->assertViewHas('reports', []);
    }

    public function test_legacy_imported_initial_is_available_to_edit_without_guessing_full_middle_names(): void
    {
        $a = $this->applicant(['first_name' => 'Maria Isabel K']);
        $this->assertSame(['Maria Isabel', 'K'], ApplicantRecords::nameParts($a));
        $a->first_name = 'Maria Isabel';
        $this->assertSame(['Maria Isabel', ''], ApplicantRecords::nameParts($a));
    }
}
