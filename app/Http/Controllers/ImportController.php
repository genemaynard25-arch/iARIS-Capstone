<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ImportBatch;
use App\Services\AdmissionsImportService;
use App\Support\ImportSchoolYear;
use Illuminate\Http\Request;

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

    public function store(Request $request, AdmissionsImportService $imports)
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

        $result = $imports->import($request->file('file'), $validated['school_year'], $request->user());

        if ($result['review']) {
            return redirect('/import')->with('reviewSubmitted', $result['review']);
        }

        return redirect('/import/'.$result['batch']->id);
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
