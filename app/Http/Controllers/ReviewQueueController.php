<?php

namespace App\Http\Controllers;

use App\Imports\ApplicantsImport;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Models\ReviewQueueItem;
use App\Support\ApplicantEditFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReviewQueueController extends Controller
{
    /**
     * Review Queue (Super Admin / Temporary Admin): changes wait here until approved before
     * other users (Deans, Registrar, LAMP, leadership) can see them.
     *
     * Backed by the review_queue table. IATO Staff who aren't (currently) an admin have their
     * applicant edits/imports staged here instead of applied directly - see
     * ApplicantController::stageForReview() and ImportController::stageImportForReview().
     *
     * type: edit (staff edit/create/remove/restore/exam-status), import (batch import)
     * status: waiting, approved, sent (sent back)
     */
    public function index()
    {
        return view('review-queue.index', ['items' => self::items()]);
    }

    /** How many items are waiting (for the sidebar badge). */
    public static function waitingCount(): int
    {
        return ReviewQueueItem::waitingCount();
    }

    public static function items(): array
    {
        return ReviewQueueItem::with('reviewer')->orderByDesc('submitted_at')->get()
            ->map(fn (ReviewQueueItem $item) => self::shape($item))->all();
    }

    public static function shape(ReviewQueueItem $item): array
    {
        $reviewed = null;
        if ($item->reviewed_at) {
            $who = $item->reviewer?->name ?? 'the Super Admin';
            $reviewed = ($item->status === 'approved' ? 'Approved' : 'Sent back') . " by {$who} · " . $item->reviewed_at->format('M j, Y · g:i A');
        }

        return [
            'id' => $item->rq_number,
            'type' => $item->type,
            'title' => $item->title,
            'subject' => $item->subject,
            'ref' => $item->ref,
            'unit' => $item->unit,
            'by' => $item->submitted_by_name,
            'role' => $item->submitted_by_role,
            'at' => $item->submitted_at->format('Y-m-d H:i'),
            'status' => $item->status,
            'reviewedAt' => $item->reviewed_at?->format('Y-m-d H:i'),
            'note' => $item->note,
            'changes' => $item->changes ?? [],
            'summary' => $item->import_summary ?? ['total' => 0, 'new' => 0, 'updated' => 0, 'errors' => 0],
            'sample' => $item->import_sample ?? [],
            'errors' => $item->import_errors ?? [],
            'reviewed' => $reviewed,
            'reason' => $item->review_reason,
        ];
    }

    public function approve(Request $request, ReviewQueueItem $item)
    {
        abort_unless($item->status === 'waiting', 409, 'This item was already decided.');

        $action = $item->pending_action['action'] ?? null;
        $payload = $item->pending_action['payload'] ?? [];

        match ($action) {
            'create' => ApplicantEditFlow::applyCreate($payload['validated'], $payload['level']),
            'update' => $item->applicant && ApplicantEditFlow::applyUpdate($item->applicant, $payload['validated']),
            'destroy' => $item->applicant && ApplicantEditFlow::applyDestroy($item->applicant),
            'restore' => $item->applicant && ApplicantEditFlow::applyRestore($item->applicant),
            'exam_status' => $item->applicant && ApplicantEditFlow::applyExamStatus($item->applicant, $payload['validated']['admission_test_status']),
            'commit_import' => $this->commitImport($payload),
            default => null,
        };

        $item->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);

        AuditLog::record('edit', $item->unit, 'Review Queue Item Approved', "Approved **{$item->rq_number}**: {$item->title} — {$item->subject}");

        return response()->json(['ok' => true]);
    }

    public function sendBack(Request $request, ReviewQueueItem $item)
    {
        abort_unless($item->status === 'waiting', 409, 'This item was already decided.');

        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        $item->update([
            'status' => 'sent',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_reason' => $validated['reason'],
        ]);

        AuditLog::record('edit', $item->unit, 'Review Queue Item Sent Back', "Sent back **{$item->rq_number}**: {$item->title} — {$item->subject}. Reason: {$validated['reason']}");

        return response()->json(['ok' => true]);
    }

    /** Replays a staged import's validated rows (ApplicantsImport::commitRow(), same code the live path uses). */
    protected function commitImport(array $payload): void
    {
        $batch = ImportBatch::find($payload['batch_id']);
        if (! $batch) {
            return;
        }

        foreach ($payload['rows'] as $row) {
            ApplicantsImport::commitRow(
                $row['fields'],
                $row['applied_at'] ? Carbon::parse($row['applied_at']) : null,
                $row['fees'],
                $batch->id,
                $payload['school_year'],
                $row['fields']['level'],
            );
        }

        $batch->update(['status' => 'completed', 'row_count' => count($payload['rows'])]);
    }
}
