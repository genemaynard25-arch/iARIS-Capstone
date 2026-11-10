<?php

namespace App\Http\Controllers;

use App\Models\ReviewQueueItem;

class SubmissionsController extends Controller
{
    /**
     * My Submissions (IATO Staff): the edits and imports this staff member sent to the
     * Review Queue, and what happened to each one (waiting, approved, or sent back with a reason).
     */
    public function index()
    {
        // Admins use the Review Queue instead
        abort_unless(auth()->user()->role === 'iato_staff', 403);

        return view('submissions.index', [
            'items' => self::items(),
            // Conversations with the LAMP Office that IATO Staff started (they show under "LAMP requests")
            'lampRequests' => collect(\App\Support\LampRequests::sample())
                ->filter(fn ($t) => ($t['messages'][0]['role'] ?? null) === 'IATO Staff')
                ->values()
                ->all(),
        ]);
    }

    public static function items(): array
    {
        return ReviewQueueItem::where('submitted_by', auth()->id())
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn (ReviewQueueItem $item) => array_merge(ReviewQueueController::shape($item), [
                // e.g. "Nov 8, 2026 · 10:14 AM"
                'when' => \Illuminate\Support\Carbon::parse($item->submitted_at)->format('M j, Y · g:i A'),
            ]))
            ->values()
            ->all();
    }

    /** [waiting, sent back, approved] for the dashboard card and the sidebar. */
    public static function counts(): array
    {
        $items = collect(self::items());

        return [
            'waiting' => $items->where('status', 'waiting')->count(),
            'sent' => $items->where('status', 'sent')->count(),
            'approved' => $items->where('status', 'approved')->count(),
        ];
    }
}
