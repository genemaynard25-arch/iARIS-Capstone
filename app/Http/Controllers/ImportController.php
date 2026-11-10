<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ImportBatch;
use App\Imports\ApplicantsImport;
use Illuminate\Http\Request;
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
            'file' => 'required|extensions:xlsx,csv',
            'school_year' => 'required|regex:/^\d{4}-\d{4}$/',
        ]);

        $batch = ImportBatch::create([
            'uploaded_by' => $request->user()->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'processing',
        ]);

        $import = new ApplicantsImport($batch, $validated['school_year']);
        Excel::import($import, $request->file('file'));

        $rowCount = Applicant::where('imported_batch_id', $batch->id)->count();
        $errorCount = count($import->errors);

        $batch->update([
            'status' => 'completed',
            'row_count' => $rowCount,
            'error_count' => $errorCount,
            'errors' => $import->errors ?: null,
        ]);

        \App\Models\AuditLog::record(
            'import',
            'Import Data',
            'Data Import',
            "Imported **{$batch->original_filename}** — **{$rowCount} records** added"
                . ($errorCount ? ", **{$errorCount} row" . ($errorCount === 1 ? '' : 's') . " skipped**" : ''),
        );

        return redirect("/import/{$batch->id}");
    }

    public function results(ImportBatch $batch)
    {
        $applicants = Applicant::where('imported_batch_id', $batch->id)->get();

        return view('import-results', [
            'batch' => $batch,
            'applicants' => $applicants,
        ]);
    }
}