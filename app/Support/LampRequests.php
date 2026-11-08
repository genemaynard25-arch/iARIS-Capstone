<?php

namespace App\Support;

class LampRequests
{
    /**
     * Conversations between IATO and the LAMP Office about scholars whose records don't match.
     * IATO starts one with "Ask LAMP to check" (Scholarships page); LAMP answers under
     * "Requests from IATO" (LAMP Scholars page). Both pages read this same list.
     *   waiting  IATO asked, LAMP hasn't answered yet (the amber number on the sidebar)
     *   replied  LAMP answered, IATO hasn't closed it yet
     *   resolved closed (by LAMP fixing its record, a correction, or IATO marking it resolved)
     * Placeholder data, front end only. Later: a table of requests and their messages.
     */
    public static function sample(): array
    {
        $scholars = \App\Http\Controllers\ScholarController::sample()->keyBy('id');
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

    /** Conversations waiting for LAMP's answer (the amber number on the LAMP sidebar). */
    public static function waitingCount(): int
    {
        return collect(self::sample())->where('status', 'waiting')->count();
    }

    /** The same list keyed by applicant number, for the IATO Scholarships page. */
    public static function byScholar(): array
    {
        return collect(self::sample())->keyBy('id')->all();
    }
}
