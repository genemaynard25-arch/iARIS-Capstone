<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentApplicantImportTest extends TestCase
{
    public function test_two_independent_import_processes_share_one_active_applicant_and_one_row_per_fee(): void
    {
        $connection = config('database.connections.mysql');
        $database = (string) ($connection['database'] ?? '');
        if (DB::connection()->getDriverName() !== 'mysql' || ! str_ends_with($database, '_test')) {
            $this->markTestSkipped('Requires a migrated isolated MySQL database ending in _test; this test commits fixtures for independent worker connections.');
        }
        $user = User::factory()->create(['role' => 'admin', 'status' => 'Active']);
        $batches = collect([1, 2])->map(fn ($i) => ImportBatch::create(['uploaded_by' => $user->id, 'original_filename' => 'concurrent-test-'.$i.'.csv', 'status' => 'processing']));
        $identity = 'RACE-'.bin2hex(random_bytes(8));
        $gate = tempnam(sys_get_temp_dir(), 'iaris-race-');
        unlink($gate);
        $workers = [];
        $code = <<<'WORKER'
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== $argv[2]) { fwrite(STDERR, 'Worker database does not match the isolated test database'); exit(3); }
file_put_contents($argv[3].'.ready-'.$argv[5], 'ready');
$deadline = microtime(true) + 20;
while (!is_file($argv[3])) { clearstatcache(); if (microtime(true) > $deadline) exit(4); usleep(10000); }
$id = Illuminate\Support\Facades\DB::transaction(function () use ($argv) {
    $fields = ['external_app_no'=>$argv[4], 'school_year'=>'2098-2099', 'level'=>'college', 'first_name'=>'Race '.$argv[5], 'last_name'=>'Applicant', 'gender'=>'female'];
    $a = App\Imports\ApplicantsImport::commitRow($fields, null, ['application_fee'=>true, 'reservation_fee'=>true], (int)$argv[6], '2098-2099', 'college');
    usleep(250000);
    return $a->id;
}, 3);
echo json_encode(['id'=>$id]);
WORKER;
        try {
            foreach ($batches as $i => $batch) {
                $process = new Process([PHP_BINARY, '-r', $code, base_path(), $database, $gate, $identity, (string) $i, (string) $batch->id], base_path(), [
                    'APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'CACHE_STORE' => 'array', 'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'], 'DB_DATABASE' => $database,
                    'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'] ?? '',
                ]);
                $process->setTimeout(35)->start();
                $workers[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (! is_file($gate.'.ready-0') || ! is_file($gate.'.ready-1')) {
                clearstatcache();
                if (microtime(true) > $deadline) {
                    $this->fail('Concurrent import workers did not become ready.');
                }
                usleep(10000);
            }
            file_put_contents($gate, 'go');
            $ids = [];
            foreach ($workers as $worker) {
                $this->assertSame(0, $worker->wait(), $worker->getErrorOutput());
                $ids[] = json_decode($worker->getOutput(), true, 512, JSON_THROW_ON_ERROR)['id'];
            }
            $this->assertSame($ids[0], $ids[1]);
            $this->assertSame(1, Applicant::where('external_app_no', $identity)->count());
            $this->assertSame(2, DB::table('payments')->where('applicant_id', $ids[0])->count());
            $this->assertSame(2, DB::table('payments')->where('applicant_id', $ids[0])->distinct()->count('fee_type'));
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            $ids = Applicant::where('external_app_no', $identity)->pluck('id');
            DB::table('payments')->whereIn('applicant_id', $ids)->delete();
            DB::table('applicants')->whereIn('id', $ids)->delete();
            DB::table('import_batches')->whereIn('id', $batches->pluck('id'))->delete();
            DB::table('users')->where('id', $user->id)->delete();
            foreach ([$gate, $gate.'.ready-0', $gate.'.ready-1'] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_migration_stops_before_changing_existing_duplicate_active_records(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.import_preflight' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        try {
            Schema::connection('import_preflight')->create('applicants', function (Blueprint $table) {
                $table->id();
                $table->string('school_year');
                $table->string('external_app_no');
                $table->timestamp('archived_at')->nullable();
            });
            DB::connection('import_preflight')->table('applicants')->insert([
                ['school_year' => '2026-2027', 'external_app_no' => 'DUPLICATE'],
                ['school_year' => '2026-2027', 'external_app_no' => 'DUPLICATE'],
            ]);
            DB::setDefaultConnection('import_preflight');
            $migration = require database_path('migrations/2026_11_11_101406_protect_active_import_identity_and_index_audit_logs.php');
            try {
                $migration->up();
                $this->fail('A migration must not silently merge or discard existing active duplicates.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('duplicate application number', $exception->getMessage());
                $this->assertSame(2, DB::table('applicants')->count());
                $this->assertFalse(Schema::hasColumn('applicants', 'active_external_app_no'));
            }
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('import_preflight');
        }
    }
}
