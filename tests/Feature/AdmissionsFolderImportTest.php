<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\ReviewQueueItem;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\OfficeTestCase;

class AdmissionsFolderImportTest extends OfficeTestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->folder = storage_path('framework/testing/admissions-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->folder.'/incoming', 0700, true);
        config(['admissions-import.enabled' => true, 'admissions-import.path' => $this->folder,
            'admissions-import.school_year' => '2025-2026', 'admissions-import.settle_seconds' => 0]);
    }

    protected function tearDown(): void
    {
        if (isset($this->folder)) {
            File::deleteDirectory($this->folder);
        }
        parent::tearDown();
    }

    private function configureUser(string $role = 'admin'): User
    {
        $user = $this->office($role);
        if ($role === 'iato_staff') {
            RolePermission::updateOrCreate(['role' => $role, 'module' => 'import'], ['enabled' => true]);
        }
        config(['admissions-import.user_id' => $user->id]);

        return $user;
    }

    private function incoming(string $name = 'applicants.xlsx'): string
    {
        $path = $this->folder.'/incoming/'.$name;
        copy(base_path('tests/fixtures/import/dummy-applicants-valid.xlsx'), $path);

        return $path;
    }

    public function test_admin_imports_and_renamed_duplicate_does_not_create_another_batch(): void
    {
        $user = $this->configureUser();
        $this->incoming();
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertGreaterThan(0, Applicant::count());
        $count = Applicant::count();
        $batch = ImportBatch::sole();
        $this->assertSame('completed', $batch->status);
        $this->assertSame($user->id, $batch->uploaded_by);
        $this->assertSame($user->id, AuditLog::where('action', 'import')->latest('id')->first()->user_id);
        $this->assertFileExists($this->folder.'/processed/applicants.xlsx');
        $this->incoming('renamed.xlsx');
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(1, ImportBatch::count());
        $this->assertSame($count, Applicant::count());
    }

    public function test_staff_import_waits_for_approval_and_is_not_staged_twice(): void
    {
        $user = $this->configureUser('iato_staff');
        $this->incoming();
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(0, Applicant::count());
        $item = ReviewQueueItem::sole();
        $this->assertSame('waiting', $item->status);
        $this->assertSame($user->id, $item->submitted_by);
        $this->incoming('renamed.xlsx');
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(1, ReviewQueueItem::count());
        $this->actingAs($this->office('admin'))->postJson('/review-queue/'.$item->rq_number.'/approve')->assertOk();
        $this->assertGreaterThan(0, Applicant::count());
    }

    public function test_disabled_and_unauthorized_importers_leave_files_untouched(): void
    {
        $this->configureUser();
        $path = $this->incoming();
        config(['admissions-import.enabled' => false]);
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertFileExists($path);
        config(['admissions-import.enabled' => true]);
        $this->configureUser('registrar');
        $this->artisan('admissions:import-folder')->assertFailed();
        $this->assertFileExists($path);
        $this->assertSame(0, ImportBatch::count());
    }

    public function test_unreadable_file_is_quarantined_and_next_file_still_imports(): void
    {
        $this->configureUser();
        file_put_contents($this->folder.'/incoming/broken.xlsx', 'not an Excel workbook');
        $this->incoming();
        $this->artisan('admissions:import-folder')->assertFailed();
        $this->assertFileExists($this->folder.'/failed/broken.xlsx');
        $this->assertFileExists($this->folder.'/processed/applicants.xlsx');
        $this->assertGreaterThan(0, Applicant::count());
    }

    public function test_incomplete_uploads_are_ignored(): void
    {
        $this->configureUser();
        $path = $this->incoming();
        file_put_contents($this->folder.'/incoming/upload.xlsx.part', 'in progress');
        config(['admissions-import.settle_seconds' => 60]);
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertFileExists($path);
        $this->assertSame(0, ImportBatch::count());
    }

    public function test_undeclared_year_needs_explicit_configuration(): void
    {
        $this->configureUser();
        config(['admissions-import.school_year' => null]);
        $this->incoming();
        $this->artisan('admissions:import-folder')->assertFailed();
        $this->assertFileExists($this->folder.'/failed/applicants.xlsx');
        $this->assertSame(0, Applicant::count());
    }

    public function test_folder_lock_prevents_a_second_worker(): void
    {
        $this->configureUser();
        $path = $this->incoming();
        $lock = Cache::store('file')->lock('admissions-folder-'.hash('sha256', $this->folder), 60);
        $lock->get();
        try {
            $this->artisan('admissions:import-folder')->assertSuccessful();
            $this->assertFileExists($path);
            $this->assertSame(0, ImportBatch::count());
        } finally {
            $lock->release();
        }
    }

    public function test_processing_file_resumes_after_interruption(): void
    {
        $this->configureUser();
        File::ensureDirectoryExists($this->folder.'/processing', 0700, true);
        rename($this->incoming(), $this->folder.'/processing/applicants.xlsx');
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertFileExists($this->folder.'/processed/applicants.xlsx');
        $this->assertSame(1, ImportBatch::count());
    }

    public function test_database_failure_rolls_back_and_allows_a_corrected_retry(): void
    {
        $this->configureUser();
        $row = array_fill(0, 30, '');
        foreach ([0 => 1, 1 => 'FOLDER-FAIL-1', 2 => '2025-06-01', 3 => 'Failure, Test',
            10 => 'Female', 11 => 'Test Program', 13 => '1st Year', 15 => 'Yes', 16 => 'Yes',
            19 => 'Passed', 27 => 'Online'] as $key => $value) {
            $row[$key] = $value;
        }
        $path = $this->folder.'/incoming/retry.csv';
        $write = function (bool $invalid) use ($path, $row): void {
            $stream = fopen($path, 'w');
            fputcsv($stream, ['IPACE'], escape: '');
            fputcsv($stream, $row, escape: '');
            $row[1] = 'FOLDER-FAIL-2';
            $row[27] = $invalid ? str_repeat('x', 300) : 'Online';
            fputcsv($stream, $row, escape: '');
            fclose($stream);
        };
        $write(true);
        $this->artisan('admissions:import-folder')->assertFailed();
        $this->assertSame(0, Applicant::count());
        $this->assertSame(0, DB::table('payments')->count());
        $this->assertSame('failed', ImportBatch::sole()->status);
        $this->assertNull(ImportBatch::sole()->source_hash);
        $write(false);
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(2, Applicant::count());
    }

    public function test_completed_database_import_is_not_replayed_after_archive_interruption(): void
    {
        $this->configureUser();
        $this->incoming();
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $count = Applicant::count();
        rename($this->folder.'/processed/applicants.xlsx', $this->folder.'/processing/applicants.xlsx');
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(1, ImportBatch::count());
        $this->assertSame($count, Applicant::count());
        $this->assertFileExists($this->folder.'/processed/applicants.xlsx');
    }

    public function test_explicit_file_year_overrides_fallback(): void
    {
        $this->configureUser();
        $this->incoming('Applicants SY 2026-2027.xlsx');
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(['2026-2027'], Applicant::distinct()->pluck('school_year')->all());
    }

    public function test_interrupted_uncommitted_batch_can_be_retried(): void
    {
        $user = $this->configureUser();
        $path = $this->incoming();
        $hash = hash('sha256', '2025-2026:'.hash_file('sha256', $path));
        $abandoned = ImportBatch::create(['uploaded_by' => $user->id, 'original_filename' => 'applicants.xlsx',
            'status' => 'processing', 'source_hash' => $hash]);
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame('failed', $abandoned->fresh()->status);
        $this->assertNull($abandoned->fresh()->source_hash);
        $this->assertSame(18, Applicant::count());
        $this->assertSame(2, ImportBatch::count());
    }

    public function test_mixed_rows_keep_existing_validation_and_error_reporting(): void
    {
        $this->configureUser();
        copy(base_path('tests/fixtures/import/dummy-applicants-mixed-errors.xlsx'), $this->folder.'/incoming/mixed.xlsx');
        $this->artisan('admissions:import-folder')->assertSuccessful();
        $this->assertSame(18, Applicant::count());
        $this->assertSame(3, ImportBatch::sole()->error_count);
        $this->assertCount(3, ImportBatch::sole()->errors);
        $this->assertFileExists($this->folder.'/processed/mixed.xlsx');
    }
}
