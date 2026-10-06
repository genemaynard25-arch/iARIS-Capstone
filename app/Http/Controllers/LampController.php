<?php

namespace App\Http\Controllers;

class LampController extends Controller
{
    /**
     * LAMP Office pages: Dashboard, Scholars and Reports.
     *
     * Placeholder data so the UI can be built first. The scholars are the same
     * made-up people as the admin Scholars page (ScholarController::sample()).
     *
     * From LAMP's side a record is in one of four states:
     *   Confirmed Match  types agree and LAMP has confirmed it
     *   Pending Check    types agree but LAMP hasn't confirmed yet
     *   Discrepancy      LAMP has a different type on file
     *   Not Found        the scholar isn't in LAMP's list
     *
     * Later: the confirmed list, reports and replies come from the database, and
     * these routes are limited to users whose role is 'lamp' (plus IATO admins).
     */

    // Matching records LAMP has already confirmed. The other matching ones are "Pending Check".
    private const CONFIRMED = ['APP-2026-0381', 'APP-2026-0382', 'APP-2026-0384', 'APP-2026-0391', 'APP-IS-2026-0105'];

    public function dashboard()
    {
        return view('lamp.dashboard', $this->shared() + [
            'reports' => array_slice($this->reportList(), 0, 3),
            // 'bold' is shown first in bold, then 'text'
            'notifications' => [
                ['tone' => 'danger', 'bold' => 'IATO flagged', 'text' => 'Villareal, Pia V. as not found in LAMP records.', 'at' => 'Jun 12, 2026 · 9:14 AM'],
                ['tone' => 'danger', 'bold' => 'IATO flagged', 'text' => 'Torres, Nicole A. — LAMP shows a different scholarship type.', 'at' => 'Jun 11, 2026 · 3:30 PM'],
                ['tone' => 'warning', 'bold' => 'New report', 'text' => 'from IATO Admin — Scholar Cross-Check Report is ready for review.', 'at' => 'Jun 12, 2026 · 8:00 AM'],
                ['tone' => 'secondary', 'bold' => 'Confirmation received:', 'text' => 'IATO Admin got your confirmation for Santos, Maria Isabel L.', 'at' => 'Jun 10, 2026 · 10:00 AM'],
            ],
        ]);
    }

    public function scholars()
    {
        return view('lamp.scholars', $this->shared());
    }

    public function reports()
    {
        return view('lamp.reports', $this->shared() + [
            'reports' => $this->reportList(),
            'reportTypes' => [
                'Cross-Check' => ['primary', 'bi-file-earmark-check'],
                'Discrepancy' => ['danger', 'bi-exclamation-triangle'],
                'Scholar List' => ['info', 'bi-list-ul'],
            ],
            'reportStatuses' => ['Unread' => 'danger', 'Awaiting Reply' => 'warning', 'Replied' => 'primary', 'Read' => 'secondary'],
        ]);
    }

    // What all three pages (and the LAMP sidebar) need
    private function shared(): array
    {
        $scholars = ScholarController::sample()->map(fn ($s) => $s + [
            'lamp_status' => $s['lamp'] === 'Match'
                ? (in_array($s['id'], self::CONFIRMED) ? 'Confirmed Match' : 'Pending Check')
                : $s['lamp'],
        ]);

        return [
            'academicYear' => '2025–2026',
            'scholars' => $scholars,
            'types' => array_keys(ScholarController::TYPES),
            'typeGroups' => ScholarController::TYPES,
            'typeTones' => ['DLSL' => 'primary', 'Merit' => 'info', 'Government' => 'warning'],
            'scholarshipTones' => ['Active' => 'primary', 'Probationary' => 'warning', 'Cancelled' => 'danger'],
            // Status => [Bootstrap colour, icon], in "needs attention first" order
            'lampBadges' => [
                'Discrepancy' => ['danger', 'bi-exclamation-circle-fill'],
                'Not Found' => ['secondary', 'bi-question-circle-fill'],
                'Pending Check' => ['warning', 'bi-clock-fill'],
                'Confirmed Match' => ['primary', 'bi-check-circle-fill'],
            ],
            // Numbers on the sidebar badges
            'navCounts' => [
                'scholars' => $scholars->whereIn('lamp_status', ['Discrepancy', 'Not Found'])->count(),
                'reports' => collect($this->reportList())->where('status', 'Unread')->count(),
            ],
        ];
    }

    // Reports IATO sent to the LAMP Office, newest first.
    // 'preview' picks which scholars the report lists: 'all', or only 'issues' (not matching).
    private function reportList(): array
    {
        $iato = 'Renegado, Randolph';

        return [
            ['id' => 'r1', 'title' => 'Scholar Cross-Check Report — June 2026', 'type' => 'Cross-Check', 'sender' => $iato,
                'sent' => '2026-06-12 08:00', 'sent_label' => 'Jun 12, 2026 · 8:00 AM', 'status' => 'Unread', 'needs_reply' => true, 'preview' => 'all',
                'thread' => [['from' => 'iato', 'name' => $iato, 'text' => 'Hi, please review the Scholar Cross-Check Report for June 2026. A few records need your confirmation or a flag.', 'at' => 'Jun 12, 2026 · 8:00 AM']]],
            ['id' => 'r2', 'title' => 'Discrepancy Summary — May 2026', 'type' => 'Discrepancy', 'sender' => $iato,
                'sent' => '2026-06-01 15:00', 'sent_label' => 'Jun 1, 2026 · 3:00 PM', 'status' => 'Unread', 'needs_reply' => true, 'preview' => 'issues',
                'thread' => [['from' => 'iato', 'name' => $iato, 'text' => 'Here is the discrepancy summary for May. Please review and flag anything that doesn\'t match your records.', 'at' => 'Jun 1, 2026 · 3:00 PM']]],
            ['id' => 'r3', 'title' => 'Scholar Cross-Check Report — May 2026', 'type' => 'Cross-Check', 'sender' => $iato,
                'sent' => '2026-05-30 10:00', 'sent_label' => 'May 30, 2026 · 10:00 AM', 'status' => 'Awaiting Reply', 'needs_reply' => true, 'preview' => 'all',
                'thread' => [['from' => 'iato', 'name' => $iato, 'text' => 'Please confirm the scholars listed. Some entries need LAMP validation before we finalize the report.', 'at' => 'May 30, 2026 · 10:00 AM']]],
            ['id' => 'r4', 'title' => 'Scholar List — AY 2025–2026', 'type' => 'Scholar List', 'sender' => $iato,
                'sent' => '2026-05-15 09:00', 'sent_label' => 'May 15, 2026 · 9:00 AM', 'status' => 'Replied', 'needs_reply' => true, 'preview' => 'all',
                'thread' => [
                    ['from' => 'iato', 'name' => $iato, 'text' => 'Please verify the attached scholar list against LAMP Office records.', 'at' => 'May 15, 2026 · 9:00 AM'],
                    ['from' => 'lamp', 'name' => 'LAMP Office', 'text' => 'Most entries are confirmed. Two scholars are not in our records — Villareal and Dela Cruz. We\'ll check with the scholarship committee.', 'at' => 'May 16, 2026 · 10:30 AM'],
                ]],
            ['id' => 'r5', 'title' => 'Discrepancy Summary — April 2026', 'type' => 'Discrepancy', 'sender' => $iato,
                'sent' => '2026-04-30 14:00', 'sent_label' => 'Apr 30, 2026 · 2:00 PM', 'status' => 'Replied', 'needs_reply' => true, 'preview' => 'issues',
                'thread' => [
                    ['from' => 'iato', 'name' => $iato, 'text' => 'Here is the April discrepancy summary. Three records need LAMP confirmation.', 'at' => 'Apr 30, 2026 · 2:00 PM'],
                    ['from' => 'lamp', 'name' => 'LAMP Office', 'text' => 'Two are confirmed matches. Torres appears to be a type mismatch — our records show DLSL Scholar, not CHED. Flagged in the system.', 'at' => 'May 2, 2026 · 9:00 AM'],
                ]],
            ['id' => 'r6', 'title' => 'Scholar List — Second Semester', 'type' => 'Scholar List', 'sender' => $iato,
                'sent' => '2026-01-20 09:30', 'sent_label' => 'Jan 20, 2026 · 9:30 AM', 'status' => 'Read', 'needs_reply' => false, 'preview' => 'all',
                'thread' => [['from' => 'iato', 'name' => $iato, 'text' => 'For your reference: the scholar list for the second semester. No reply needed.', 'at' => 'Jan 20, 2026 · 9:30 AM']]],
        ];
    }
}
