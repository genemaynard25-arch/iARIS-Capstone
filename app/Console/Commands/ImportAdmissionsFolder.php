<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Models\ReviewQueueItem;
use App\Models\User;
use App\Services\AdmissionsImportService;
use App\Support\ImportSchoolYear;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ImportAdmissionsFolder extends Command
{
    protected $signature = 'admissions:import-folder';

    protected $description = 'Import completed XLSX/CSV files from the configured private admissions folder';

    public function handle(AdmissionsImportService $imports): int
    {
        if (! config('admissions-import.enabled')) {
            $this->info('Folder importing is disabled. Set ADMISSIONS_IMPORT_ENABLED=true to enable it.');

            return self::SUCCESS;
        }

        $user = User::find(config('admissions-import.user_id'));
        if (! $user || $user->status !== 'Active' || ! $user->canAccess('import')
            || (! $user->isAdmin() && $user->role !== 'iato_staff')) {
            $this->error('Configure ADMISSIONS_IMPORT_USER_ID with an active Super Admin or IATO Staff account allowed to import.');

            return self::FAILURE;
        }

        $root = config('admissions-import.path');
        // File-backed locks also work when the application's default cache is "array".
        $lock = Cache::store('file')->lock('admissions-folder-'.hash('sha256', $root), 86400);
        if (! $lock->get()) {
            $this->info('Another folder import is running.');

            return self::SUCCESS;
        }

        $previousUser = Auth::user();
        Auth::setUser($user);
        $failed = false;
        try {
            foreach (['incoming', 'processing', 'processed', 'failed'] as $folder) {
                File::ensureDirectoryExists($root.'/'.$folder, 0700, true);
            }

            // Resume files claimed before a crash before accepting new ones.
            foreach (['processing', 'incoming'] as $folder) {
                foreach (File::files($root.'/'.$folder) as $file) {
                    if ($file->isLink() || ! in_array(strtolower($file->getExtension()), ['xlsx', 'csv'], true)) {
                        continue;
                    }
                    if ($folder === 'incoming' && $file->getMTime() > time() - config('admissions-import.settle_seconds')) {
                        continue;
                    }
                    $path = $root.'/processing/'.$file->getFilename();
                    if ($folder === 'incoming') {
                        // Never overwrite a file left by an interrupted run.
                        if (File::exists($path)) {
                            $failed = true;

                            continue;
                        }
                        File::move($file->getPathname(), $path);
                    }
                    $failed = ! $this->processFile($imports, $user, $root, $path) || $failed;
                }
            }
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetUser();
            }
            $lock->release();
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function processFile(AdmissionsImportService $imports, User $user, string $root, string $path): bool
    {
        $file = new UploadedFile($path, basename($path), null, null, true);
        try {
            $year = ImportSchoolYear::declaredYear($file) ?? config('admissions-import.school_year');
            if (! is_string($year) || ! preg_match('/^\d{4}-\d{4}$/', $year)) {
                throw ValidationException::withMessages(['school_year' => 'Put SY YYYY-YYYY in the filename or configure ADMISSIONS_IMPORT_SCHOOL_YEAR.']);
            }
            $hash = hash('sha256', $year.':'.hash_file('sha256', $path));
            $existing = ImportBatch::where('source_hash', $hash)->first();
            if ($existing && $existing->status === 'processing') {
                $hasReview = ReviewQueueItem::where('type', 'import')
                    ->where('pending_action->payload->batch_id', $existing->id)->exists();
                if (! $hasReview) {
                    // The command holds the folder lock. A previous process died before
                    // committing its transaction; release its reservation for a retry.
                    $existing->update(['status' => 'failed', 'source_hash' => null]);
                    $existing = null;
                }
            }
            if ($existing) {
                $this->info('Already received: '.basename($path).' (batch #'.$existing->id.').');
            } else {
                $result = $imports->import($file, $year, $user, $hash);
                $this->info('Batch #'.$result['batch']->id.($result['review'] ? ' awaiting approval.' : ' completed.'));
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Failed: '.basename($path).'. '.($exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : 'Check the application log for the failure.'));
            $this->archive($root, 'failed', $path);

            return false;
        }

        // If moving fails after a DB commit, leave it in processing. The fingerprint
        // prevents a second import when the next run attempts to archive it again.
        $this->archive($root, 'processed', $path);

        return true;
    }

    private function archive(string $root, string $folder, string $path): void
    {
        $destination = $root.'/'.$folder.'/'.basename($path);
        if (File::exists($destination)) {
            $destination = $root.'/'.$folder.'/'.bin2hex(random_bytes(8)).'-'.basename($path);
        }
        File::move($path, $destination);
    }
}
