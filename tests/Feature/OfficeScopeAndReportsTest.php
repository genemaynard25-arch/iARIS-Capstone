<?php

namespace Tests\Feature;

use App\Models\RolePermission;
use App\Models\User;
use App\Support\AddressClassifier;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\OfficeTestCase;

class OfficeScopeAndReportsTest extends OfficeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['dean', 'program_chair'] as $role) {
            foreach (['dashboard', 'applicants', 'analytics', 'reports'] as $module) {
                RolePermission::updateOrCreate(['role' => $role, 'module' => $module], ['enabled' => true]);
            }
        }
    }

    public function test_dashboard_pending_and_recent_applicants_only_use_the_current_school_year(): void
    {
        $current = $this->applicant(['school_year' => '2026-2027', 'application_status' => 'pooling', 'created_at' => '2026-01-01']);
        $this->applicant(['school_year' => '2025-2026', 'application_status' => 'pooling', 'created_at' => now()]);
        $this->applicant(['school_year' => '2026-2027', 'application_status' => 'pooling', 'archived_at' => now()]);
        $this->actingAs($this->office('admin'))->get('/home')->assertOk()
            ->assertViewHas('stats', fn ($stats) => collect($stats)->firstWhere('label', 'Pending Applications')['value'] === 1)
            ->assertViewHas('recentApplicants', fn ($rows) => count($rows) === 1 && $rows[0]['reference'] === $current->reference_number);
    }

    public function test_province_recognition_includes_all_82_provinces_and_balanga_without_guessing_similar_names(): void
    {
        $this->assertCount(82, AddressClassifier::PROVINCES);
        foreach (AddressClassifier::PROVINCES as $province) {
            $this->assertSame($province, AddressClassifier::classify('Barangay Test, '.$province));
            $this->assertArrayHasKey($province, AddressClassifier::colors());
        }
        foreach (['Brgy. Batangas, Balanga, Bataan' => 'Bataan', 'Street Bataan, Lipa City, Batangas' => 'Batangas', 'Cotabato City' => 'Other / Unspecified province', 'Balanga, Bataas' => 'Bataan', 'Balanga City, Bataan' => 'Bataan', 'Cagayan de Oro City' => 'Misamis Oriental', 'Tuguegarao, Cagayan' => 'Cagayan', 'Quezon City' => 'Other / Unspecified province', 'Lipa City, Batangas' => 'Batangas', 'Compostela Valley' => 'Davao de Oro'] as $address => $province) {
            $this->assertSame($province, AddressClassifier::classify($address));
        }
    }

    private function chair(): User
    {
        $chair = $this->office('program_chair');
        $chair->update(['assigned_college' => 'CEAS', 'assigned_program' => 'BS Psychology', 'assigned_level' => 'college']);

        return $chair;
    }

    public function test_chair_statistics_applicants_and_reports_respect_both_college_and_program(): void
    {
        $mine = $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'college' => 'College of Education, Arts and Sciences', 'program_or_track' => 'BS Psychology']);
        $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'college' => 'CEAS', 'program_or_track' => 'BS Biology']);
        $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'college' => 'CITE', 'program_or_track' => 'BS Psychology']);
        $this->actingAs($this->chair())->get('/college')->assertOk()->assertViewHas('label', 'BS Psychology')
            ->assertViewHas('programs', fn ($rows) => count($rows) === 1 && array_values($rows)[0]['applicants'][3] === 1);
        $this->get('/college/applicants')->assertOk()->assertViewHas('rows', fn ($rows) => count($rows) === 1 && $rows[0]['id'] === $mine->reference_number);
        RolePermission::updateOrCreate(['role' => 'program_chair', 'module' => 'college'], ['enabled' => true]);
        $this->get('/records/college')->assertOk()->assertViewHas('rows', fn ($rows) => count($rows) === 1 && $rows[0]['id'] === $mine->reference_number);
        $this->getJson('/office-reports/preview/programs')->assertOk()->assertJsonPath('rows.0.0', 'BS Psychology')->assertJsonPath('rows.0.4', 1);
        $this->postJson('/office-reports', ['key' => 'programs', 'format' => 'XLSX', 'tables' => [['rows' => [['forged']]]]])->assertOk();
        $snapshot = json_decode(DB::table('generated_reports')->latest('id')->value('snapshot'), true);
        $this->assertSame(1, $snapshot[0]['rows'][0][4]);
        $this->assertStringNotContainsString('forged', json_encode($snapshot));
    }

    public function test_legacy_links_and_export_routes_cannot_bypass_dean_and_chair_scope(): void
    {
        foreach ([$this->chair(), $this->office('dean')] as $user) {
            foreach (['applicants', 'reports', 'analytics'] as $module) {
                RolePermission::updateOrCreate(['role' => $user->role, 'module' => $module], ['enabled' => true]);
            }
            $this->actingAs($user)->get('/applicants')->assertRedirect('/college/applicants');
            $this->get('/analytics')->assertRedirect('/college/analytics');
            $this->get('/analytics?tab=reports')->assertRedirect('/college/reports');
            $this->get('/reports')->assertRedirect('/college/reports');
            $this->postJson('/analytics/reports/generate', ['key' => 'trends', 'format' => 'XLSX', 'period' => 'Current School Year'])->assertForbidden();
            $this->getJson('/analytics/reports/trends/preview?period=Current%20School%20Year')->assertForbidden();
            $this->get('/analytics/reports/trends/pdf')->assertForbidden();
            $this->postJson('/analytics/reanalyze')->assertForbidden();
        }
    }

    public function test_unassigned_chair_sees_no_demo_scope_and_cannot_generate_reports(): void
    {
        $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'college' => 'CITE', 'program_or_track' => 'BS Computer Science']);
        $this->actingAs($this->office('program_chair'))->get('/college')->assertOk()->assertViewHas('programs', [])->assertSee('No program is assigned');
        $this->postJson('/office-reports', ['key' => 'programs', 'format' => 'XLSX'])->assertUnprocessable();
    }

    public function test_dean_reports_persist_snapshots_download_real_files_and_block_other_users(): void
    {
        $a = $this->applicant(['school_year' => '2026-2027', 'level' => 'college', 'college' => 'CEAS', 'program_or_track' => 'Biology']);
        $dean = $this->office('dean');
        $dean->update(['assigned_college' => 'CEAS']);
        $this->actingAs($dean)->postJson('/office-reports', ['key' => 'programs', 'format' => 'XLSX'])->assertOk();
        $saved = DB::table('generated_reports')->latest('id')->first();
        $this->get('/college/reports')->assertOk()->assertViewHas('history', fn ($rows) => count($rows) === 1 && $rows[0]['id'] === $saved->id);
        $snapshot = $saved->snapshot;
        $a->update(['archived_at' => now()]);
        $response = $this->get('/office-reports/'.$saved->id)->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = tempnam(sys_get_temp_dir(), 'office-xlsx-');
        file_put_contents($path, $response->streamedContent());
        try {
            $book = IOFactory::load($path);
            $rows = $book->getActiveSheet()->toArray();
            $biology = collect($rows)->first(fn ($row) => ($row[0] ?? null) === 'Biology');
            $this->assertSame(1, (int) $biology[4]);
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->get('/office-reports/'.$saved->id.'?format=PDF')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/office-reports/'.$saved->id.'?format=CSV')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertSame($snapshot, DB::table('generated_reports')->where('id', $saved->id)->value('snapshot'));
        $other = $this->office('dean');
        $other->update(['assigned_college' => 'CITE']);
        $this->actingAs($other)->get('/office-reports/'.$saved->id)->assertForbidden();
        $this->actingAs($this->office('lamp'))->postJson('/office-reports', ['key' => 'programs', 'format' => 'XLSX'])->assertForbidden();
    }

    public function test_iato_can_assign_a_chair_program_and_assignments_are_audited(): void
    {
        $chair = $this->office('program_chair');
        $this->actingAs($this->office('admin'))->putJson('/accounts/'.$chair->id, [
            'first' => 'Program', 'last' => 'Chair', 'role' => 'program_chair', 'department' => 'CEAS', 'status' => 'Active',
            'assigned_college' => 'CEAS', 'assigned_program' => 'BS Psychology',
        ])->assertOk()->assertJsonPath('user.assignedProgram', 'BS Psychology');
        $this->assertSame('BS Psychology', $chair->fresh()->assigned_program);
        $this->assertDatabaseHas('audit_logs', ['title' => 'User Account Edited']);
        $this->putJson('/accounts/'.$chair->id, [
            'first' => 'Program', 'last' => 'Chair', 'role' => 'program_chair', 'department' => 'CEAS', 'status' => 'Active',
            'assigned_college' => 'CEAS',
        ])->assertUnprocessable()->assertJsonValidationErrors('assigned_program');
    }
}
