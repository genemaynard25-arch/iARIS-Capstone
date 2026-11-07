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
        return view('lamp.scholars', $this->shared() + [
            'iatoRequests' => self::iatoRequests(),
            // What LAMP can tell IATO the right type is
            'correctTypes' => [...array_keys(ScholarController::TYPES), 'Not a scholar'],
        ]);
    }

    /**
     * "Requests from IATO": scholars whose IATO record doesn't match LAMP's, where IATO
     * asked LAMP to check. Each has a short message thread.
     *   waiting  IATO asked, LAMP hasn't answered yet (the amber number on the sidebar)
     *   replied  LAMP answered, IATO hasn't closed it yet
     * Placeholder data, front end only. Later: a table of requests and their messages.
     */
    private static function iatoRequests(): array
    {
        $scholars = ScholarController::sample()->keyBy('id');
        $request = fn ($id, $reason, $status, $messages) => [
            'id' => $id,
            'name' => $scholars[$id]['name'],
            'program' => $scholars[$id]['program'],
            'iato' => $scholars[$id]['type'],
            'lamp' => $scholars[$id]['lamp_record'],
            'reason' => $reason,
            'status' => $status,
            'messages' => $messages,
        ];
        $iato = fn ($name, $role, $at, $text) => ['from' => 'iato', 'name' => $name, 'role' => $role, 'at' => $at, 'text' => $text];
        $lamp = fn ($at, $text) => ['from' => 'lamp', 'name' => 'LAMP Office', 'role' => 'LAMP Office', 'at' => $at, 'text' => $text];

        return [
            $request('APP-2026-0383', 'Not in LAMP records', 'waiting', [
                $iato('Renegado, Randolph', 'Super Admin', 'Jun 12, 2026 · 9:20 AM', 'Her folder has a DLSL Scholarship award letter dated May 20, 2026, but she isn\'t in LAMP records. Can you check?'),
            ]),
            $request('APP-IS-2026-0101', 'Scholarship type doesn\'t match', 'waiting', [
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 11, 2026 · 1:45 PM', 'We have him as an Academic Scholar, but your record says Athletic Scholar. Which one is correct?'),
            ]),
            $request('APP-2026-0389', 'Scholarship type doesn\'t match', 'replied', [
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 11, 2026 · 3:10 PM', 'Our record says CHED Scholar, but LAMP shows DLSL Scholar. Which one is correct?'),
                $lamp('Jun 11, 2026 · 4:02 PM', 'She moved from CHED to the DLSL Scholarship this semester. We\'ll send a correction to your Review Queue.'),
            ]),
        ];
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
            // Numbers on the sidebar
            'navCounts' => [
                // Amber: requests from IATO that are waiting for LAMP's answer
                'requests' => collect(self::iatoRequests())->where('status', 'waiting')->count(),
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
