<?php

namespace App\Http\Controllers;

use App\Imports\ApplicantsImport;
use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\CachedInsight;
use App\Models\ImportBatch;
use App\Models\ReviewQueueItem;
use App\Support\ImportSchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function show()
    {
        // Import History: the latest uploads, with who uploaded them
        $history = ImportBatch::query()
            ->leftJoin('users', 'users.id', '=', 'import_batches.uploaded_by')
            ->orderByDesc('import_batches.created_at')
            ->take(6)
            // How many applicants each import saved (row_count isn't filled in by store())
            ->selectRaw('(select count(*) from applicants where applicants.imported_batch_id = import_batches.id) as saved_count')
            ->selectRaw('(select count(*) from applicants where applicants.imported_batch_id = import_batches.id and applicants.archived_at is null) as active_count')
            ->addSelect(['import_batches.*', 'users.name as uploader'])
            ->get();

        return view('import', ['history' => $history]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // extensions (not mimes): mimes sniffs the file's raw bytes to guess a MIME
            // type, which is unreliable for CSVs re-saved by tools like WPS Office or
            // Excel - a perfectly valid .csv can get misdetected and rejected. The
            // importer itself (Maatwebsite Excel) still fails loudly on genuinely
            // unreadable content, so this isn't loosening what's actually accepted.
            'file' => 'required|file|extensions:xlsx,csv',
            'school_year' => 'required|regex:/^\d{4}-\d{4}$/',
        ]);

        if ((int) substr($validated['school_year'], 5) !== (int) substr($validated['school_year'], 0, 4) + 1) {
            throw ValidationException::withMessages(['school_year' => 'Enter consecutive years, for example 2025-2026.']);
        }
        try {
            $declaredYear = ImportSchoolYear::declaredYear($request->file('file'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages(['file' => 'Unable to read the workbook. Check that it is a valid Excel file.']);
        }
        if ($declaredYear && $declaredYear !== $validated['school_year']) {
            throw ValidationException::withMessages(['school_year' => "This file declares SY {$declaredYear}, but the form selects {$validated['school_year']}. Set School Year to {$declaredYear} before importing."]);
        }

        if ($declaredYear) {
            foreach (ImportSchoolYear::warnings() as $warning) {
                if ($warning['file'] === $request->file('file')->getClientOriginalName() && $warning['declared'] === $declaredYear) {
                    throw ValidationException::withMessages(['file' => "Batch #{$warning['batch']} of this file already has active records assigned to {$warning['assigned']}. Review that batch before importing this file again."]);
                }
            }
        }

        $batch = ImportBatch::create([
            'uploaded_by' => $request->user()->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'processing',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated, $batch) {
                $batch->refresh();
                $needsReview = $request->user()->role === 'iato_staff' && ! $request->user()->isAdmin();

                $import = new ApplicantsImport($batch, $validated['school_year']);
                $import->preview = $needsReview;
                Excel::import($import, $request->file('file'));

                if ($needsReview) {
                    return $this->stageImportForReview($request, $batch, $import, $validated['school_year']);
                }

                $rowCount = Applicant::where('imported_batch_id', $batch->id)->count();
                $errorCount = count($import->errors);

                $batch->update([
                    'status' => 'completed',
                    'row_count' => $rowCount,
                    'error_count' => $errorCount,
                    'errors' => $import->errors ?: null,
                ]);

                CachedInsight::where('key', 'analytics')->delete();

                AuditLog::record(
                    'import',
                    'Import Data',
                    'Data Import',
                    "Imported **{$batch->original_filename}** — **{$rowCount} records** added"
                        .($errorCount ? ", **{$errorCount} row".($errorCount === 1 ? '' : 's').' skipped**' : ''),
                );

                return redirect("/import/{$batch->id}");
            }, 3);
        } catch (\Throwable $exception) {
            report($exception);
            $batch->update(['status' => 'failed']);
            throw ValidationException::withMessages(['file' => 'The import failed. No applicant or payment changes were saved. Check the file format and column lengths.']);
        }
    }

    /**
     * IATO Staff (not an admin): nothing is saved yet. The validated, classified rows and the
     * batch's id travel inside the Review Queue item itself (pending_action), replayed through
     * ApplicantsImport::commitRow() by ReviewQueueController::approve() once a Super Admin signs off.
     */
    protected function stageImportForReview(Request $request, ImportBatch $batch, ApplicantsImport $import, string $schoolYear)
    {
        $new = count(array_filter($import->previewRows, fn ($r) => $r['is_new']));
        $total = count($import->previewRows);

        $item = ReviewQueueItem::create([
            'rq_number' => ReviewQueueItem::nextRqNumber(),
            'type' => 'import',
            'title' => 'Batch Import Requested',
            'subject' => $batch->original_filename,
            'ref' => $total ? 'Mixed levels' : '—',
            'unit' => 'Import Data',
            'submitted_by' => $request->user()->id,
            'submitted_by_name' => $request->user()->name,
            'submitted_by_role' => $request->user()->roleInfo()[0],
            'submitted_at' => now(),
            'status' => 'waiting',
            'import_summary' => ['total' => $total + count($import->errors), 'new' => $new, 'updated' => $total - $new, 'errors' => count($import->errors)],
            'import_sample' => collect($import->previewRows)->take(4)->map(fn ($r) => [$r['name'], $r['program'], $r['is_new'] ? 'New' : 'Updated'])->all(),
            'import_errors' => collect($import->errors)->map(fn ($e) => [$e['row'], $e['name'], '—', '—', $e['reason']])->all(),
            'pending_action' => ['action' => 'commit_import', 'payload' => ['batch_id' => $batch->id, 'school_year' => $schoolYear, 'rows' => $import->previewRows]],
        ]);

        AuditLog::record(
            'import',
            'Import Data',
            'Batch Import Requested',
            "{$request->user()->name} submitted **{$batch->original_filename}** ({$total} record".($total === 1 ? '' : 's').") — sent to Review Queue as **{$item->rq_number}**",
        );

        return redirect('/import')->with('reviewSubmitted', ['rq_number' => $item->rq_number, 'count' => $total]);
    }

    public function results(ImportBatch $batch)
    {
        $applicants = Applicant::where('imported_batch_id', $batch->id)->get();

        return view('import-results', [
            'duplicateSummary' => ImportSchoolYear::duplicateSummary($batch),
            'otherImportYearWarnings' => auth()->user()->isAdmin() ? array_values(array_filter(ImportSchoolYear::warnings(), fn ($warning) => $warning['batch'] !== $batch->id)) : [],
            'batch' => $batch,
            'applicants' => $applicants,
            'assignedYears' => $applicants->pluck('school_year')->unique()->values()->all(),
            'declaredYear' => ImportSchoolYear::fromText($batch->original_filename),
        ]);
    }
}
