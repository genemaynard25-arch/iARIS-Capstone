<?php

namespace App\Support;

use App\Http\Controllers\ScholarController;
use App\Models\Applicant;
use App\Models\LampScholarRecord;
use App\Models\ReviewQueueItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What the LAMP Office sees. IATO only shares two things about an applicant: whether they answered
 * Yes to the scholarship question, and their entrance exam result. The scholarship type and documents
 * stay with LAMP. LAMP checks each applicant who passed:
 *   wait    passed (Pass or Reconsidered), LAMP hasn't checked yet
 *   yes     approved scholarship decision
 *   no      approved non-scholar decision
 *   notyet  exam not taken yet, or waitlisted: not eligible yet
 *   fail    did not pass
 * Applicants, approved decisions, published reports and conversations use persisted records.
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
        $checks = LampScholarRecord::whereIn('applicant_id', $applicants->pluck('id'))->get()->keyBy('applicant_id');

        $pending = ReviewQueueItem::whereIn('applicant_id', $applicants->pluck('id'))->where('type', 'lamp')->where('status', 'waiting')->pluck('rq_number', 'applicant_id');

        return $applicants->map(function (Applicant $a) use ($checks, $pending) {
            $check = $checks->get($a->id);
            $result = self::RESULTS[$a->admission_test_status] ?? 'Not yet taken';
            $program = $a->program_or_track ?: '—';

            return [
                'id' => $a->reference_number,
                'applicant_id' => $a->id,
                'pending_review' => $pending->get($a->id),
                'iato_status' => $check?->decision ? $check->iato_status : null,
                'at' => $check?->checked_at?->format('M j, Y · g:i A') ?? '',
                'name' => "{$a->last_name}, {$a->first_name}",
                'sex' => strtoupper(substr((string) $a->gender, 0, 1)),
                'pwd' => (bool) $a->pwd,
                'unit' => self::UNITS[$a->level] ?? ucfirst((string) $a->level),
                'program' => match ($a->level) {
                    'college' => ($a->college ?: ApplicantRecords::collegeFor($program)).' · '.$program,
                    'is' => (ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? 'Integrated School').($a->year_level ? ' · '.$a->year_level : ''),
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
                ->where('school_year', $from.'-'.($from + 1))->count();

            return [$from.'–'.substr((string) ($from + 1), 2), $count];
        }, [3, 2, 1, 0]);
    }

    /** When the newest scholarship applicant was added or changed, e.g. "Jun 12, 2026 · 8:00 AM" (or null). */
    public static function lastUpdate(): ?string
    {
        $latest = self::query()->max('updated_at');

        return $latest ? Carbon::parse($latest)->format('M j, Y · g:i A') : null;
    }

    private static function query()
    {
        $schoolYear = Applicant::max('school_year');

        return Applicant::where('applying_for_scholarship', true)->whereNull('archived_at')
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear));
    }

    /**
     * Reports IATO sent to the LAMP Office, newest first. 'tone' is the Bootstrap colour of its type.
     */
    public static function reports(): array
    {
        return DB::table('lamp_reports')->whereNotNull('published_at')->orderByDesc('published_at')->get()->map(fn ($r) => [
            'id' => (string) $r->id, 'title' => $r->title,
            'date' => Carbon::parse($r->published_at)->format('M j, Y · g:i A'),
            'type' => 'Applicant List', 'tone' => 'info', 'note' => 'Scholarship applicant snapshot for AY '.$r->school_year,
            'by' => $r->generated_by_name, 'school_year' => $r->school_year,
        ])->all();
    }

    public static function readReports(): array
    {
        return DB::table('lamp_report_reads')->where('user_id', auth()->id())->pluck('lamp_report_id')->map(fn ($id) => (string) $id)->all();
    }

    public static function unreadReports(): int
    {
        return DB::table('lamp_reports')->whereNotNull('published_at')
            ->whereNotIn('id', self::readReports())->count();
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
        $threads = [];

        foreach (LampRequests::sample() as $r) {
            $threads[] = [
                'id' => $r['id'],
                'topic' => $r['reason'],
                'subject' => 'Scholar check: '.$r['name'],
                'status' => ['waiting' => 'answered', 'replied' => 'waiting'][$r['status']] ?? 'resolved',
                'check' => ['id' => $r['id'], 'name' => $r['name'], 'unit' => $r['program'], 'iato' => $r['iato'], 'lamp' => $r['lamp'], 'url' => url('/lamp/scholars')],
                'messages' => array_map(fn ($m) => $m['from'] === 'lamp' ? ['from' => 'me', 'at' => $m['at'], 'text' => $m['text']] : $m, $r['messages']),
            ];
        }

        return $threads;
    }
}
