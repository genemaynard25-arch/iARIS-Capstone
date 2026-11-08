<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LampRequests;
use App\Support\RegistrarQuestions;

/**
 * IATO's Messages page (Super Admin and IATO Staff): every conversation with the other offices in one list.
 * Three kinds of conversation:
 *   msg   a message with anyone (deans, leadership, TOIC…), started by either side
 *   reg   a Registrar's question about one record (their "Questions to IATO" page)
 *   lamp  a scholar IATO asked LAMP to check (the Scholarships page's "Ask LAMP to check")
 * Each has a state from IATO's side:
 *   iato   needs IATO's reply (the number on the sidebar)
 *   other  IATO answered or asked, waiting on them
 *   done   resolved
 * The people come from the users table. The conversations are placeholders, front end only.
 * TODO (backend): read and write the message_threads / messages tables.
 */
class IatoMessagesController extends Controller
{
    // Sender groups, in the order of the "From" filter and the "To" list
    public const GROUPS = [
        'toic' => 'TOIC',
        'principal' => 'Principals',
        'director' => 'iPACE Director',
        'dean' => 'Deans',
        'chair' => 'Program Chairs',
        'lead' => 'Leadership',
        'reg' => 'Registrars',
        'lamp' => 'LAMP Office',
    ];

    public function index()
    {
        $user = auth()->user();
        // The SHS Principal has an admin account but their own Messages page
        if ($user->isShsPrincipal()) {
            return redirect('/shs/messages');
        }
        abort_unless($user->isAdmin() || $user->role === 'iato_staff', 403);

        return view('messages.index', [
            'conversations' => self::conversations(),
            'people' => self::people(),
            'groups' => self::GROUPS,
        ]);
    }

    /** Conversations waiting for IATO's reply (the number on the sidebar). */
    public static function needsReply(): int
    {
        return collect(self::conversations())->where('state', 'iato')->count();
    }

    /** Which group a user belongs to, or null for IATO's own accounts. */
    private static function group(User $u): ?string
    {
        if ($u->isShsPrincipal()) {
            return 'principal';
        }

        return match ($u->role) {
            'toic' => 'toic',
            'principal' => 'principal',
            'director' => 'director',
            'dean' => 'dean',
            'program_chair' => 'chair',
            'president', 'chancellor', 'vice_president', 'vice_chancellor' => 'lead',
            'registrar' => 'reg',
            'lamp' => 'lamp',
            default => null,   // admin, iato_staff
        };
    }

    /** "Dean · CITE", "VP for Finance", "College Registrar": the role, and the unit when it says something. */
    private static function label(User $u): string
    {
        [$role, $unit] = $u->roleInfo();

        return in_array($unit, ['Executive Office', "Registrar's Office", 'LAMP'], true) ? $role : "{$role} · {$unit}";
    }

    /** Everyone IATO can write to, from the users table: [id, name, role, initials, group]. */
    public static function people(): array
    {
        $order = array_flip(array_keys(self::GROUPS));

        return User::all()
            ->map(fn (User $u) => ['id' => $u->id, 'email' => $u->email, 'name' => $u->displayName(), 'role' => self::label($u), 'initials' => $u->initials(), 'group' => self::group($u)])
            ->filter(fn ($p) => $p['group'] !== null)
            ->sortBy(fn ($p) => sprintf('%02d %s', $order[$p['group']], $p['name']))
            ->values()
            ->all();
    }

    public static function conversations(): array
    {
        $people = collect(self::people())->keyBy('email');
        $list = [];

        // ---- Messages: sample conversations with people who are in the users table ----
        $iato = fn ($name, $role, $at, $text) => ['from' => 'iato', 'name' => $name, 'role' => $role, 'at' => $at, 'text' => $text];
        $them = fn ($at, $text) => ['from' => 'them', 'at' => $at, 'text' => $text];
        $samples = [
            // [their email, id, topic, subject, status (waiting = for IATO, answered = IATO answered, resolved), messages]
            ['toic@gmail.com', 'MSG-0118', 'Something looks wrong', 'Large Excel imports are slow', 'answered', [
                $iato('Renegado, Randolph', 'Super Admin', 'Jun 12, 2026 · 9:40 AM', 'Importing Excel files over 2,000 rows takes more than 2 minutes and sometimes times out. Can TOIC check the server before the June 15 batch?'),
            ]],
            ['toic@gmail.com', 'MSG-0117', 'Other', 'Maintenance on Saturday, Jun 20', 'resolved', [
                $them('Jun 10, 2026 · 3:00 PM', 'Heads-up: iARIS will be read-only on Saturday, Jun 20, 10 PM to 12 AM for security updates and database tuning.'),
                $iato('Renegado, Randolph', 'Super Admin', 'Jun 10, 2026 · 3:25 PM', 'Noted, thank you. We will tell the deans and the Registrars.'),
            ]],
            ['director@gmail.com', 'MSG-0116', 'Request a report', 'ETEEAP portfolio assessments this month', 'waiting', [
                $them('Jun 12, 2026 · 1:10 PM', 'Can IATO send the list of ETEEAP applicants whose portfolio assessment is scheduled this month? The panel needs it by Friday.'),
            ]],
            ['principal@gmail.com', 'MSG-0115', 'Question about the data', 'Grade 11 STEM exam slots', 'answered', [
                $them('Jun 12, 2026 · 10:05 AM', 'Senior High has 34 applicants still Pending. Are there enough exam slots for Grade 11 STEM next week?'),
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 12, 2026 · 11:20 AM', 'Yes, Ms. Dimaculangan. We opened two more sessions on Jun 18. Pending applicants will be scheduled by Monday.'),
            ]],
            ['registrar@gmail.com', 'MSG-0114', 'Question about the data', 'When are the June exam results posted?', 'answered', [
                $them('Jun 12, 2026 · 8:05 AM', 'When will the June 6 exam results be in iARIS? Some applicants are asking us about enrollment.'),
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 12, 2026 · 9:30 AM', 'They will be posted on Jun 15, Ms. Santos. Passers will show as For Exam → Paid once they pay.'),
            ]],
            ['lamp@gmail.com', 'MSG-0113', 'Request a report', 'Scholar list with the new CCJE applicants', 'waiting', [
                $them('Jun 12, 2026 · 10:40 AM', 'Can IATO send an updated list of scholarship applicants that includes CCJE? We need it for the LAMP committee on Monday.'),
            ]],
            ['dean@gmail.com', 'MSG-0112', 'Question about the data', 'BS Architecture applicants look low', 'waiting', [
                $them('Jun 12, 2026 · 9:14 AM', 'BS Architecture shows 72 applicants, but our department head counted more walk-in applications last week. Are the walk-ins already encoded?'),
            ]],
            ['chair@gmail.com', 'MSG-0111', 'Request a report', 'PhD in Education interview schedule', 'waiting', [
                $them('Jun 11, 2026 · 4:50 PM', 'Can IATO send me the interview schedule of the PhD in Education applicants? I need to assign panel members.'),
            ]],
            ['vc@gmail.com', 'MSG-0110', 'Request a report', 'Include CCJE in the monthly report', 'waiting', [
                $them('Jun 11, 2026 · 1:20 PM', 'Now that CCJE is in iARIS, please include it as its own row in the monthly admissions report to the Academic Council.'),
            ]],
            ['deanccje@gmail.com', 'MSG-0109', 'Access or account', 'Welcome to iARIS, CCJE', 'answered', [
                $iato('Renegado, Randolph', 'Super Admin', 'Jun 10, 2026 · 8:30 AM', 'Good morning, Dr. Balba. CCJE is now in iARIS. Your Briefing shows BS Criminology and BS Forensic Science applicants. Message us here if a number looks wrong.'),
            ]],
            ['deancon@gmail.com', 'MSG-0108', 'Request a report', 'Weekly paid list for BS Nursing', 'answered', [
                $them('Jun 9, 2026 · 10:05 AM', 'Could we get a weekly list of BS Nursing applicants who already paid? We are planning sections.'),
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 9, 2026 · 2:40 PM', 'Yes, Dr. Ebreo. We will send it every Monday under Reports. The first one is already there.'),
            ]],
            ['chancellor@gmail.com', 'MSG-0107', 'Question about the data', 'Why did conversion dip?', 'answered', [
                $them('Jun 8, 2026 · 3:10 PM', 'Conversion is down half a point. Is it one college, or across the board?'),
                $iato('Renegado, Randolph', 'Super Admin', 'Jun 8, 2026 · 4:25 PM', 'Across the board, Dr. Suarez. The biggest gap is exam passers who have not paid yet. Most are waiting for scholarship results from LAMP.'),
            ]],
            ['deanlaw@gmail.com', 'MSG-0106', 'Something looks wrong', 'Duplicate JD applicant', 'resolved', [
                $them('Jun 5, 2026 · 11:00 AM', 'I see the same JD applicant twice in my list. Can you check?'),
                $iato('Cruz, Jasmine', 'IATO Staff', 'Jun 5, 2026 · 1:15 PM', 'Thank you, Atty. Romero. It was a double import. The duplicate was removed (RQ-0398).'),
                $them('Jun 5, 2026 · 1:40 PM', 'Noted, thank you.'),
            ]],
            ['deancbeam@gmail.com', 'MSG-0105', 'Question about the data', 'BS Legal Management pending share', 'resolved', [
                $them('Jun 4, 2026 · 9:30 AM', 'Why are so many BS Legal Management applicants still Pending?'),
                $iato('Renegado, Randolph', 'Super Admin', 'Jun 4, 2026 · 10:10 AM', 'Their exam slots open on Jun 15, Dr. De Silva. They will move to For Exam once scheduled.'),
            ]],
        ];
        foreach ($samples as [$email, $id, $topic, $subject, $status, $messages]) {
            $p = $people[$email] ?? null;
            if (! $p) {
                continue;   // that account isn't in the users table
            }
            $list[] = self::conversation('msg', $id, $p, $subject, $topic, ['waiting' => 'iato', 'answered' => 'other'][$status] ?? 'done', $messages);
        }

        // ---- Registrars' questions about one record (the same ones they see under Questions to IATO) ----
        foreach (['registrar' => 'college', 'isregistrar' => 'is'] as $who => $scope) {
            $p = $people["{$who}@gmail.com"] ?? collect($people)->first(fn ($x) => $x['group'] === 'reg' && str_contains($x['role'], $scope === 'is' ? 'IS' : 'College'));
            if (! $p) {
                continue;
            }
            foreach (RegistrarQuestions::sample(['name' => $p['name'], 'role' => $p['role']], $scope) as $q) {
                $messages = array_map(fn ($m) => $m['from'] === 'reg' ? ['from' => 'them', 'at' => $m['at'], 'text' => $m['text']] : $m, $q['messages']);
                $list[] = self::conversation('reg', $q['id'], $p, "Record question: {$q['name']}", $q['topic'], ['waiting' => 'iato', 'answered' => 'other'][$q['status']] ?? 'done', $messages,
                    ['id' => $q['id'], 'name' => $q['name'], 'unit' => "{$q['unit']} · {$q['program']}", 'url' => $q['url']]);
            }
        }

        // ---- Scholars IATO asked LAMP to check (the same ones LAMP sees under Requests from IATO) ----
        $lamp = collect($people)->firstWhere('group', 'lamp');
        if ($lamp) {
            foreach (LampRequests::sample() as $r) {
                $messages = array_map(fn ($m) => $m['from'] === 'lamp' ? ['from' => 'them', 'at' => $m['at'], 'text' => $m['text']] : $m, $r['messages']);
                // LAMP replied = IATO's turn; waiting = waiting for LAMP
                $list[] = self::conversation('lamp', $r['id'], $lamp, "Scholar check: {$r['name']}", $r['reason'], ['replied' => 'iato', 'waiting' => 'other'][$r['status']] ?? 'done', $messages,
                    ['id' => $r['id'], 'name' => $r['name'], 'unit' => $r['program'], 'url' => url('/records/scholars') . '?open=' . urlencode($r['id']), 'iato' => $r['iato'], 'lamp' => $r['lamp']]);
            }
        }

        // Newest first
        usort($list, fn ($a, $b) => $b['when'] <=> $a['when']);

        return $list;
    }

    /** One conversation in the shape the page uses. Their messages are signed with their name from the users table. */
    private static function conversation(string $kind, string $id, array $person, string $subject, string $topic, string $state, array $messages, ?array $record = null): array
    {
        $messages = array_map(fn ($m) => $m['from'] === 'them' ? $m + ['name' => $person['name'], 'role' => $person['role']] : $m, $messages);
        $last = end($messages);

        return [
            'key' => "{$kind}:{$id}",
            'kind' => $kind,
            'id' => $id,
            'person' => $person['id'],
            'who' => $person['name'],
            'role' => $person['role'],
            'initials' => $person['initials'],
            'group' => $person['group'],
            'subject' => $subject,
            'topic' => $topic,
            'state' => $state,
            'record' => $record,
            'messages' => $messages,
            'when' => strtotime(str_replace(' · ', ' ', $last['at'])) ?: 0,
        ];
    }
}
