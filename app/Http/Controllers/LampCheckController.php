<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Models\LampScholarRecord;
use App\Models\ReviewQueueItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LampCheckController extends Controller
{
    public function update(Request $request, Applicant $applicant): JsonResponse
    {
        abort_unless($request->user()->isLamp() || $request->user()->isAdmin(), 403);
        $data = $request->validate(['action' => ['required', 'in:yes,no,undo']]);
        $item = DB::transaction(function () use ($request, $applicant, $data) {
            $applicant = Applicant::whereKey($applicant->id)->lockForUpdate()->firstOrFail();
            abort_unless($applicant->applying_for_scholarship
                && is_null($applicant->archived_at)
                && in_array($applicant->admission_test_status, ['passed', 'reconsidered', 'qualified_other_degree'], true), 422, 'Applicant is not eligible for a LAMP check.');

            $name = trim($applicant->last_name.', '.$applicant->first_name);
            $existing = LampScholarRecord::where('applicant_id', $applicant->id)->first();
            abort_if($existing?->decision && $existing->iato_status === 'confirmed', 422, 'IATO already confirmed this check.');
            if ($data['action'] === 'undo') {
                abort_unless($existing?->decision, 422, 'There is no saved LAMP check to undo.');
            }

            $pending = ReviewQueueItem::where('applicant_id', $applicant->id)
                ->where('type', 'lamp')->where('status', 'waiting')->exists();
            abort_if($pending, 409, 'A LAMP action for this applicant is already awaiting Super Admin review.');

            $before = $existing?->decision ?? 'wait';
            $after = $data['action'] === 'undo' ? 'wait' : $data['action'];
            $item = ReviewQueueItem::create([
                'rq_number' => ReviewQueueItem::nextRqNumber(),
                'type' => 'lamp',
                'title' => $data['action'] === 'undo' ? 'LAMP check undo requested' : 'LAMP scholarship check',
                'applicant_id' => $applicant->id,
                'subject' => $name,
                'ref' => $applicant->reference_number,
                'unit' => 'LAMP Office',
                'submitted_by' => $request->user()->id,
                'submitted_by_name' => $request->user()->name,
                'submitted_by_role' => $request->user()->roleInfo()[0],
                'submitted_at' => now(),
                'status' => 'waiting',
                'changes' => [['field' => 'LAMP decision', 'before' => $before, 'after' => $after]],
                'pending_action' => ['action' => 'lamp_check', 'payload' => [
                    'decision' => $data['action'], 'expected_before' => $before,
                ]],
            ]);
            AuditLog::record('edit', 'LAMP', 'LAMP Check Submitted for Review',
                "Submitted {$item->rq_number} for {$name} ({$applicant->reference_number}): {$before} → {$after}.",
                $request->user()->roleInfo()[0], $item->changes);

            return $item;
        });

        return response()->json([
            'ok' => true, 'pending_review' => true, 'review_number' => $item->rq_number,
            'message' => 'Submitted for Super Admin review. The decision is not applied yet.',
        ]);
    }

    public function confirm(Request $request, Applicant $applicant): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || ($request->user()->role === 'iato_staff' && $request->user()->access_type === 'editor' && $request->user()->canAccess('edit')), 403);
        DB::transaction(function () use ($request, $applicant) {
            $applicant = Applicant::whereKey($applicant->id)->lockForUpdate()->firstOrFail();
            abort_unless($applicant->applying_for_scholarship && is_null($applicant->archived_at)
                && in_array($applicant->admission_test_status, ['passed', 'reconsidered', 'qualified_other_degree'], true), 422);
            abort_if(ReviewQueueItem::where('applicant_id', $applicant->id)->where('type', 'lamp')->where('status', 'waiting')->exists(), 409, 'A LAMP action is awaiting review.');
            $record = LampScholarRecord::where('applicant_id', $applicant->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->decision && $record->iato_status === 'pending', 422, 'No pending check to confirm.');
            $record->update(['iato_status' => 'confirmed', 'confirmed_by' => $request->user()->id, 'confirmed_at' => now()]);
            $review = ReviewQueueItem::where('applicant_id', $applicant->id)->where('type', 'lamp')->where('status', 'approved')->latest('reviewed_at')->first();
            AuditLog::record('edit', 'LAMP', 'IATO Confirmed LAMP Decision',
                "Confirmed {$record->decision} for {$applicant->reference_number}; review {$review?->rq_number}.",
                $request->user()->roleInfo()[0], [['field' => 'IATO status', 'before' => 'pending', 'after' => 'confirmed']]);
        });

        return response()->json(['iato_status' => 'confirmed']);
    }
}
