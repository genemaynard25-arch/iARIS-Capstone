<?php

namespace Tests\Feature;

use App\Imports\ApplicantsImport;
use App\Models\Applicant;
use App\Models\ImportBatch;
use App\Models\RolePermission;
use App\Support\AdmissionStats;
use App\Support\AnalyticsFilterData;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\OfficeTestCase;

class ReportExportTest extends OfficeTestCase
{
    public function test_template_xlsx_is_a_real_saved_snapshot_for_the_selected_school_year(): void
    {
        $admin = $this->office('admin');
        $old = $this->applicant(['school_year' => '2025-2026', 'last_name' => '=1+1']);
        $this->applicant(['school_year' => '2026-2027']);
        $this->actingAs($admin)->postJson('/analytics/reports/generate', ['key' => 'ipace-exam', 'format' => 'XLSX', 'period' => 'Previous School Year', 'year' => '2026-2027'])->assertOk();
        $saved = DB::table('generated_reports')->latest('id')->first();
        $this->assertSame('2025-2026', $saved->school_year);
        $this->getJson('/analytics/reports/ipace-exam/preview?period=Previous%20School%20Year&year=2026-2027')->assertOk()->assertJsonPath('schoolYear', '2025-2026')->assertJsonPath('report.rows.0.1', '=1+1, Test');
        $old->update(['last_name' => 'Changed after export']);
        $response = $this->get('/analytics/reports/generated/'.$saved->id)->assertOk()->assertDownload('ipace-exam-passers-2025-2026.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'iaris-xlsx-');
        try {
            file_put_contents($path, $response->streamedContent());
            $book = IOFactory::load($path);
            $sheet = $book->getSheet(0);
            $this->assertSame('=1+1, Test', $sheet->getCell('B8')->getValue());
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('B8')->getDataType());
            $this->assertSame(8, $sheet->getHighestRow());
            $book->disconnectWorksheets();
        } finally {
            unlink($path);
        }
        $this->get('/analytics?tab=reports')->assertOk()->assertSee('Saved snapshot');
        $this->get('/analytics/reports/generated/'.$saved->id.'?preview=1')->assertOk()->assertSee('=1+1, Test')->assertDontSee('Changed after export');
        $this->assertDatabaseHas('audit_logs', ['title' => 'Report Generated', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('audit_logs', ['title' => 'Report Downloaded']);
    }

    public function test_trends_export_compares_adjacent_years_and_preserves_numeric_counts(): void
    {
        $this->actingAs($this->office('admin'));
        $this->applicant(['school_year' => '2025-2026']);
        $this->applicant(['school_year' => '2026-2027']);
        $this->applicant(['school_year' => '2026-2027']);
        $this->postJson('/analytics/reports/generate', ['key' => 'trends', 'format' => 'XLSX', 'period' => 'Current School Year', 'year' => '2026-2027'])->assertOk();
        $saved = DB::table('generated_reports')->latest('id')->first();
        $tables = json_decode($saved->snapshot, true);
        $ipace = collect($tables[0]['rows'])->first(fn ($r) => $r[0] === 'iPACE');
        $this->assertSame(['iPACE', '1', '2', '+1', '+100.0%'], $ipace);
        $this->postJson('/analytics/reports/generate', ['key' => 'trends', 'format' => 'PDF', 'period' => 'Previous School Year', 'year' => '2026-2027'])->assertOk();
        $id = DB::table('generated_reports')->latest('id')->value('id');
        $this->assertStringStartsWith('%PDF', $this->get('/analytics/reports/generated/'.$id)->assertOk()->getContent());
    }

    public function test_custom_reports_export_and_history_downloads_are_authorized(): void
    {
        $admin = $this->office('admin');
        $this->actingAs($admin)->postJson('/analytics/reports/generate', [
            'title' => 'Filtered analytics', 'format' => 'XLSX', 'period' => 'This AY · iPACE', 'year' => '2025-2026',
            'tables' => [['title' => 'Key figures', 'columns' => ['Metric', 'Value'], 'rows' => [['Applicants', '3500']]]],
        ])->assertOk();
        $id = DB::table('generated_reports')->latest('id')->value('id');
        RolePermission::updateOrCreate(['role' => 'iato_staff', 'module' => 'reports'], ['enabled' => true]);
        $this->actingAs($this->office('iato_staff'))->get('/analytics/reports/generated/'.$id)->assertForbidden();
        $this->actingAs($this->office('dean'))->postJson('/analytics/reports/generate', ['key' => 'trends', 'format' => 'XLSX', 'period' => 'Current School Year'])->assertForbidden();
        $this->actingAs($admin)->postJson('/analytics/reports/generate', ['key' => 'missing', 'format' => 'XLSX', 'period' => 'Current School Year'])->assertNotFound();
        $this->postJson('/analytics/reports/generate', ['key' => 'trends', 'format' => 'EXE', 'period' => 'Current School Year'])->assertUnprocessable();
    }

    public function test_supplied_format_previous_year_workbook_imports_3500_and_enables_comparison(): void
    {
        $admin = $this->office('admin');
        $this->actingAs($admin);
        $batch = ImportBatch::create(['uploaded_by' => $admin->id, 'original_filename' => 'previous-year.xlsx', 'status' => 'processing']);
        $import = new ApplicantsImport($batch, '2025-2026');
        Excel::import($import, base_path('docs/test-data/iARIS_3500_Synthetic_Applicants_SY_2025-2026.xlsx'));
        $this->assertSame([], $import->errors);
        $this->assertSame(3500, Applicant::where('school_year', '2025-2026')->count());
        $currentFile = getenv('IARIS_CURRENT_TEST_WORKBOOK');
        if ($currentFile) {
            $currentBatch = ImportBatch::create(['uploaded_by' => $admin->id, 'original_filename' => 'supplied-4000.xlsx', 'status' => 'processing']);
            $current = new ApplicantsImport($currentBatch, '2026-2027');
            Excel::import($current, $currentFile);
            $this->assertSame([], $current->errors);
            $this->assertSame(4000, Applicant::where('school_year', '2026-2027')->count());
            $this->assertSame(7500, Applicant::count());
            $summary = AdmissionStats::summary();
            $this->assertSame(4000, $summary['overallCurrent']);
            $this->assertSame(3500, $summary['overallPrevious']);
        } else {
            $this->applicant(['school_year' => '2026-2027']);
        }
        $feed = AnalyticsFilterData::make();
        foreach (['College', 'College of Law', 'ETEEAP', 'Graduate Programs', 'Integrated School', 'iPACE'] as $department) {
            $prior = collect($feed['rows'])->where('year', '2025-2026')->where('department', $department);
            $this->assertGreaterThan(0, $prior->sum('applicants'));
            $this->assertGreaterThan(0, $prior->sum('enrolled'));
        }
    }
}
