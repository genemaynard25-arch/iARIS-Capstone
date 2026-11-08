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
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $batch = ImportBatch::create([
            'uploaded_by' => $request->user()->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'processing',
        ]);

        Excel::import(new ApplicantsImport($batch), $request->file('file'));

        $batch->update(['status' => 'completed']);

         $rowCount = Applicant::where('imported_batch_id', $batch->id)->count();
            \App\Models\AuditLog::record(
                'import',
                'Import Data',
                'Data Import',
                "Imported **{$batch->original_filename}** — **{$rowCount} records** added",
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