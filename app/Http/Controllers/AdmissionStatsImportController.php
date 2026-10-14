<?php

namespace App\Http\Controllers;

use App\Imports\AdmissionStatsImport;
use App\Models\AdmissionStat;
use App\Models\ImportBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AdmissionStatsImportController extends Controller
{
    public function show()
    {
        return view('admission-stats-import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        AdmissionStat::truncate(); // each upload is a fresh snapshot, not an addition to history

        $batch = ImportBatch::create([
            'uploaded_by' => $request->user()->id,
            'original_filename' => $request->file('file')->getClientOriginalName(),
            'status' => 'processing',
        ]);

        Excel::import(new AdmissionStatsImport($batch), $request->file('file'));

        $batch->update(['status' => 'completed']);

        return redirect("/import/admission-stats/{$batch->id}");
    }

    public function results(ImportBatch $batch)
    {
        $stats = AdmissionStat::where('import_batch_id', $batch->id)->get();

        return view('admission-stats-results', [
            'batch' => $batch,
            'stats' => $stats,
        ]);
    }
}