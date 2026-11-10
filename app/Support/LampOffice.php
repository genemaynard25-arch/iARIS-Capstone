<?php

namespace App\Support;

use App\Http\Controllers\ScholarController;
use App\Models\Applicant;

/**
 * What the LAMP Office sees. IATO only shares two things about an applicant: whether they answered
 * Yes to the scholarship question, and their entrance exam result. The scholarship type and documents
 * stay with LAMP. LAMP checks each applicant who passed:
 *   wait    passed (Pass or Reconsidered), LAMP hasn't checked yet
 *   yes     LAMP confirmed a scholar          (set by LAMP, in the browser for now)
 *   no      LAMP says not a scholar           (set by LAMP, in the browser for now)
 *   notyet  exam not taken yet, or waitlisted: not eligible yet
 *   fail    did not pass
 * The applicants are real (the applicants table). The checks, the reports IATO sent and the
 * conversations are placeholders, front end only.
 * TODO (backend): save LAMP's checks in lamp_scholar_records, and the reports and messages in tables.
 */
class LampOffice
{
    public const UNITS = [
        'college' => 'College',
        'is' => 'Integrated School',
        'graduate_school' => 'Graduate Programs',
        'law' => 'College of Law',
        'ipace' => 'iPACE',
        'eteeap' => 'ETEEAP',
    ];

    // admission_test_status => the result LAMP sees
    private const RESULTS = [
        'passed' => 'Pass', 'qualified_other_degree' => 'Pass', 'reconsidered' => 'Reconsidered',
        'waitlisted' => 'Waitlisted', 'failed' => 'Fail', 'not_taken' => 'Not yet taken', 'took_test' => 'Not yet taken',
    ];

    /** Applicants who answered Yes to the scholarship question this school year. */
    public static function applicants(): array
    {
        $applicants = self::query()->latest()->get();
        $checks = \App\Models\LampScholarRecord::whereIn('applicant_id', $applicants->pluck('id'))->get()->keyBy('applicant_id');

        return $applicants->map(function (Applicant $a) use ($checks) {
            $check = $checks->get($a->id);
            $result = self::RESULTS[$a->admission_test_status] ?? 'Not yet taken';
            $program = $a->program_or_track ?: '—';

            return [
                'id' => $a->reference_number,
                'applicant_id' => $a->id,
                'iato_status' => $check?->decision ? $check->iato_status : null,
                'at' => $check?->checked_at?->format('M j, Y · g:i A') ?? '',
                'name' => "{$a->last_name}, {$a->first_name}",
                'sex' => strtoupper(substr((string) $a->gender, 0, 1)),
                'pwd' => (bool) $a->pwd,
                'unit' => self::UNITS[$a->level] ?? ucfirst((string) $a->level),
                'program' => match ($a->level) {
                    'college' => ($a->college ?: ApplicantRecords::collegeFor($program)) . ' · ' . $program,
                    'is' => (ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? 'Integrated School') . ($a->year_level ? ' · ' . $a->year_level : ''),
                    default => $program,
                },
                'result' => $result,
                'lamp' => match ($result) {
                    'Pass', 'Reconsidered' => $check?->decision ?: 'wait',
                    'Fail' => 'fail',
                    default => 'notyet',
                },
                'applied' => $a->created_at?->format('M j, Y') ?? '—',
            ];
        })->all();
    }

    /** Scholarship applicants in each of the last four school years: [label, count]. Years with nothing imported show 0. */
    public static function trend(): array
    {
        $start = (int) substr(Applicant::max('school_year') ?? '2025-2026', 0, 4);

        return array_map(function ($i) use ($start) {
            $from = $start - $i;
            $count = Applicant::where('applying_for_scholarship', true)->whereNull('archived_at')
                ->where('school_year', $from . '-' . ($from + 1))->count();

            return [$from . '–' . substr((string) ($from + 1), 2), $count];
        }, [3, 2, 1, 0]);
    }

    /** When the newest scholarship applicant was added or changed, e.g. "Jun 12, 2026 · 8:00 AM" (or null). */
    public static function lastUpdate(): ?string
    {
        $latest = self::query()->max('updated_at');

        return $latest ? \Illuminate\Support\Carbon::parse($latest)->format('M j, Y · g:i A') : null;
    }

    private static function query()
    {
        $schoolYear = Applicant::max('school_year');

        return Applicant::where('applying_for_scholarship', true)->whereNull('archived_at')
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear));
    }

    /**
     * Reports IATO sent to the LAMP Office, newest first. 'tone' is the Bootstrap colour of its type.
     * Placeholder data: nothing stores sent reports yet.
     */
    public static function reports(): array
    {
        return [
            ['id' => 'r1', 'title' => 'Scholarship Applicants and Exam Results — June 2026', 'date' => 'Jun 12, 2026 · 8:00 AM', 'type' => 'Exam Results', 'tone' => 'primary',
                'note' => 'Scholarship applicants whose entrance exam results came in this month. The ones who passed are waiting for your check in Scholars.'],
            ['id' => 'r2', 'title' => 'LAMP Checks Summary — May 2026', 'date' => 'Jun 1, 2026 · 3:00 PM', 'type' => 'LAMP Checks', 'tone' => 'warning',
                'note' => 'Every scholarship applicant you checked in May, and whether IATO has confirmed each change.'],
            ['id' => 'r3', 'title' => 'Scholarship Applicants and Exam Results — May 2026', 'date' => 'May 30, 2026 · 10:00 AM', 'type' => 'Exam Results', 'tone' => 'primary',
                'note' => 'Scholarship applicants whose entrance exam results came in during May.'],
            ['id' => 'r4', 'title' => 'Scholarship Applicants — AY 2025–2026', 'date' => 'May 15, 2026 · 9:00 AM', 'type' => 'Applicant List', 'tone' => 'info',
                'note' => 'All scholarship applicants for AY 2025–2026, with exam result and LAMP check.'],
            ['id' => 'r5', 'title' => 'LAMP Checks Summary — April 2026', 'date' => 'Apr 30, 2026 · 2:00 PM', 'type' => 'LAMP Checks', 'tone' => 'warning',
                'note' => 'Every scholarship applicant you checked in April, and whether IATO confirmed each change.'],
        ];
    }

    /** Reports already opened (the page keeps the real list in the browser). */
    public const READ_REPORTS = ['r3', 'r4', 'r5'];

    /** New reports: the red number on the sidebar (the pages update it as reports are opened). */
    public static function unreadReports(): int
    {
        return collect(self::reports())->reject(fn ($r) => in_array($r['id'], self::READ_REPORTS))->count();
    }

    /** What the Scholars page lets LAMP say the right type is (Messages → "Correct the IATO record"). */
    public static function correctTypes(): array
    {
        return [...array_keys(ScholarController::TYPES), 'Not a scholar'];
    }

    /**
     * LAMP's conversations with IATO, in the shape of the shared Messages page. Two kinds:
     * ordinary messages, and scholar checks IATO started ("Ask LAMP to check"). For a check:
     *   waiting (IATO asked)   -> 'answered' on the page: it needs LAMP's answer
     *   replied (LAMP replied) -> 'waiting': waiting for IATO
     */
    public static function threads(): array
    {
        $threads = [
            ['id' => 'MSG-0113', 'topic' => 'Request a report', 'subject' => 'Scholar list with the new CCJE applicants', 'status' => 'waiting', 'messages' => [
                ['from' => 'me', 'at' => 'Jun 12, 2026 · 10:40 AM', 'text' => 'Can IATO send an updated list of scholarship applicants that includes CCJE? We need it for the LAMP committee on Monday.'],
            ]],
        ];

        foreach (LampRequests::sample() as $r) {
            $threads[] = [
                'id' => $r['id'],
                'topic' => $r['reason'],
                'subject' => 'Scholar check: ' . $r['name'],
                'status' => ['waiting' => 'answered', 'replied' => 'waiting'][$r['status']] ?? 'resolved',
                'check' => ['id' => $r['id'], 'name' => $r['name'], 'unit' => $r['program'], 'iato' => $r['iato'], 'lamp' => $r['lamp'], 'url' => url('/lamp/scholars')],
                'messages' => array_map(fn ($m) => $m['from'] === 'lamp' ? ['from' => 'me', 'at' => $m['at'], 'text' => $m['text']] : $m, $r['messages']),
            ];
        }

        return $threads;
    }
}
