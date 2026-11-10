<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LampScholarRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LampCheckController extends Controller
{
    public function update(Request $request, Applicant $applicant): JsonResponse
    {
        abort_unless($request->user()->isLamp() || $request->user()->isAdmin(), 403);
        $data = $request->validate(['action' => ['required', 'in:yes,no,undo']]);
        abort_unless($applicant->applying_for_scholarship
            && is_null($applicant->archived_at)
            && in_array($applicant->admission_test_status, ['passed', 'reconsidered', 'qualified_other_degree'], true), 422, 'Applicant is not eligible for a LAMP check.');

        $record = DB::transaction(function () use ($applicant, $data, $request) {
            $record = LampScholarRecord::where('applicant_id', $applicant->id)->lockForUpdate()->first();
            if ($data['action'] === 'undo') {
                abort_unless($record && $record->decision && $record->iato_status === 'pending', 422, 'Only pending checks can be undone.');
                $record->update(['decision' => null, 'checked_by' => null, 'checked_at' => null]);
                return null;
            }
            abort_if($record && $record->iato_status === 'confirmed', 422, 'IATO already confirmed this check.');
            return LampScholarRecord::updateOrCreate(
                ['applicant_id' => $applicant->id],
                ['decision' => $data['action'], 'checked_by' => $request->user()->id,
                    'checked_at' => now(), 'iato_status' => 'pending',
                    'confirmed_by' => null, 'confirmed_at' => null]
            );
        });

        return response()->json([
            'lamp' => $record?->decision ?? 'wait',
            'at' => $record?->checked_at?->format('M j, Y · g:i A') ?? '',
            'iato_status' => $record?->iato_status,
        ]);
    }

    public function confirm(Request $request, Applicant $applicant): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'iato_staff', 403);
        $record = LampScholarRecord::where('applicant_id', $applicant->id)->firstOrFail();
        abort_unless($record->decision && $record->iato_status === 'pending', 422, 'No pending check to confirm.');
        $record->update(['iato_status' => 'confirmed', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now()]);
        return response()->json(['iato_status' => 'confirmed']);
    }
}
