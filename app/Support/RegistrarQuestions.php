<?php

namespace App\Support;

/**
 * "Questions to IATO": a Registrar asks about a record they can't edit (Ask IATO about this record),
 * and IATO answers. Placeholder conversations about real rows from RegistrarData, front end only.
 *   waiting   asked, IATO hasn't answered yet
 *   answered  IATO answered (the amber number on the Registrar's sidebar)
 *   resolved  the Registrar marked it resolved
 * TODO (backend): a table of questions and their messages, and a page for IATO to answer them.
 */
class RegistrarQuestions
{
    // $me: how the Registrar's own messages are signed, e.g. ['name' => 'Santos, Remian', 'role' => 'College Registrar']
    public static function sample(array $me = ['name' => 'Registrar', 'role' => 'College Registrar']): array
    {
        $rows = collect(RegistrarData::rows())->keyBy('id');
        // Two records to talk about: a College applicant from the database (if any) and an iPACE one
        $college = collect(RegistrarData::rows())->firstWhere('unit', 'College');
        $ipace = $rows->get('APP-2026-I002');

        $question = fn ($row, $topic, $status, $messages) => $row ? [
            'id' => $row['id'], 'name' => $row['name'], 'unit' => $row['unit'], 'program' => $row['program'],
            'url' => $row['url'], 'topic' => $topic, 'status' => $status, 'messages' => $messages,
        ] : null;
        $reg = fn ($at, $text) => ['from' => 'reg', 'name' => $me['name'], 'role' => $me['role'], 'at' => $at, 'text' => $text];
        $iato = fn ($name, $role, $at, $text) => ['from' => 'iato', 'name' => $name, 'role' => $role, 'at' => $at, 'text' => $text];

        return array_values(array_filter([
            $question($college, 'Personal details', 'answered', [
                $reg('Jun 10, 2026 · 2:12 PM', "The record has no date of birth or email. Can you add them before enrollment?"),
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 10, 2026 · 3:05 PM', "They weren't in the imported file. I'll get them from the applicant's folder and add them."),
            ]),
            $question($ipace, 'Program or year level', 'waiting', [
                $reg('Jun 12, 2026 · 8:40 AM', 'She applied as 1st Year but her transcript shows 36 units earned. Should she be 2nd Year?'),
            ]),
        ]));
    }

    /** Questions IATO answered (the number on the sidebar). */
    public static function answeredCount(): int
    {
        return collect(self::sample())->where('status', 'answered')->count();
    }
}
