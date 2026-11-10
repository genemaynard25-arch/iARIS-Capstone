<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Support\ImportSchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImportSchoolYearController extends Controller
{
    public function update(Request $request, ImportBatch $batch): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'from_school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'expected_count' => 'required|integer|min:1',
        ]);
        foreach (['from_school_year', 'school_year'] as $field) {
            abort_unless((int) substr($data[$field], 5) === (int) substr($data[$field], 0, 4) + 1, 422, 'School years must cover consecutive years.');
        }
        $count = ImportSchoolYear::correct($batch, $data['from_school_year'], $data['school_year'], (int) $data['expected_count']);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'moved' => $count, 'school_year' => $data['school_year']])
            : redirect('/import/'.$batch->id)->with('success', "Moved {$count} applicants to SY {$data['school_year']}. Analytics and reports now use the corrected school year.");
    }
}
