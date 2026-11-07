<?php

namespace App\Http\Controllers;

class SubmissionsController extends Controller
{
    /**
     * My Submissions (IATO Staff): the edits and imports this staff member sent to the
     * Review Queue, and what happened to each one (waiting, approved, or sent back with a reason).
     *
     * Placeholder data: the IATO Staff items from ReviewQueueController::items(), so both pages
     * tell the same story. Front end only. TODO (backend): read the review_queue table for
     * the signed-in user, and a route to resend a corrected item.
     */
    public function index()
    {
        // Admins use the Review Queue instead
        abort_unless(auth()->user()->role === 'iato_staff', 403);

        return view('submissions.index', ['items' => self::items()]);
    }

    public static function items(): array
    {
        return collect(ReviewQueueController::items())
            ->where('role', 'IATO Staff')
            // The queue is written from the Super Admin's side ("by you"); here it's the other way round
            ->map(fn ($item) => isset($item['reviewed'])
                ? array_merge($item, ['reviewed' => str_replace('by you', 'by Renegado, Randolph', $item['reviewed'])])
                : $item)
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
