<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Support\ImportSchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ImportSchoolYearController extends Controller
{
    public function update(Request $request, ImportBatch $batch): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'from_school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/', 'different:from_school_year'],
            'expected_count' => 'required|integer|min:1',
        ], ['school_year.different' => 'No change was made: this batch is already assigned to the selected school year.']);
        foreach (['from_school_year', 'school_year'] as $field) {
            if ((int) substr($data[$field], 5) !== (int) substr($data[$field], 0, 4) + 1) {
                throw ValidationException::withMessages([$field => 'School years must cover consecutive years, for example 2025-2026.']);
            }
        }
        try {
            $count = ImportSchoolYear::correct($batch, $data['from_school_year'], $data['school_year'], (int) $data['expected_count']);
        } catch (HttpExceptionInterface $exception) {
            if ($request->expectsJson() || ! in_array($exception->getStatusCode(), [409, 422], true)) {
                throw $exception;
            }

            return redirect('/import/'.$batch->id)->withErrors(['school_year' => $exception->getMessage()])->withInput(['school_year' => $data['school_year']]);
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'moved' => $count, 'school_year' => $data['school_year']])
            : redirect('/import/'.$batch->id)->with('success', "Moved {$count} applicants to SY {$data['school_year']}. Analytics and reports now use the corrected school year.");
    }

    public function archiveDuplicates(Request $request, ImportBatch $batch): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['from_school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'], 'expected_count' => 'required|integer|min:1']);
        try {
            $count = ImportSchoolYear::archiveDuplicateCopy($batch, $data['from_school_year'], (int) $data['expected_count']);
        } catch (HttpExceptionInterface $exception) {
            if ($request->expectsJson() || ! in_array($exception->getStatusCode(), [409, 422], true)) {
                throw $exception;
            }

            return redirect('/import/'.$batch->id)->withErrors(['duplicates' => $exception->getMessage()]);
        }

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'archived' => $count])
            : redirect('/import/'.$batch->id)->with('success', "Archived {$count} duplicate records from this batch. The correctly assigned copy remains active; Analytics now excludes the extra copy.");
    }
}
