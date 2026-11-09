<?php

namespace App\Support;

use App\Models\Applicant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Real applicants from the database (the `applicants` table, filled by Import Data),
 * turned into the same shape the Records pages and the Applicants pipeline already use
 * for their placeholder people. So the views work the same with either.
 *
 * The table has no email, phone, address, birth date or exam schedule yet: those come back
 * as null and the pages show "—". It also has no college column, so the college is worked
 * out from the program name (see COLLEGES).
 */
class ApplicantRecords
{
    // admission_test_status -> label, in the order they should appear in an edit dropdown.
    // Kept in sync with testLabel() below.
    public const EXAM_STATUSES = [
        'not_taken' => 'Not Taken',
        'took_test' => 'Taken, Awaiting Result',
        'waitlisted' => 'Waitlisted',
        'passed' => 'Passed',
        'qualified_other_degree' => 'Qualified for Another Program',
        'reconsidered' => 'Reconsidered',
        'failed' => 'Failed',
    ];

    // Program name contains ... => college. Checked in order; anything else is "Other".
    const COLLEGES = [
        'Nursing' => 'CON',
        'Criminology' => 'CCJE',
        'Tourism' => 'CIHTM', 'Hospitality' => 'CIHTM', 'Culinary' => 'CIHTM',
        'Accountancy' => 'CBEAM', 'Business' => 'CBEAM', 'Management Accounting' => 'CBEAM', 'Entrepreneur' => 'CBEAM', 'Marketing' => 'CBEAM',
        'Computer' => 'CITE', 'Information' => 'CITE', 'Engineering' => 'CITE',
        'Psychology' => 'CEAS', 'Communication' => 'CEAS', 'Education' => 'CEAS', 'Political' => 'CEAS', 'Philosophy' => 'CEAS', 'Biology' => 'CEAS', 'Arts' => 'CEAS',
    ];

    // Short codes for graduate programs (anything else gets its initials, e.g. "Master of Arts in Teaching" -> MAT)
    const GRADUATE_CODES = [
        'Master of Business Administration' => 'MBA',
        'Master of Education' => 'MEd',
        'Master of Information Technology' => 'MIT',
        'Master of Public Administration' => 'MPA',
        'Doctor of Philosophy' => 'PhD',
    ];

    // sub_level in the table => the Integrated School sub-level the pages use
    const IS_SUB_LEVELS = ['kindergarten' => 'Preschool', 'grade_school' => 'Grade School', 'jhs' => 'Junior High', 'shs' => 'Senior High'];

    /** The newest school year in the table, e.g. "2025–2026" (or the given default if nothing is imported yet). */
    public static function academicYear(string $default = '2025–2026'): string
    {
        $year = Applicant::max('school_year');

        return $year ? str_replace('-', '–', $year) : $default;
    }

    /**
     * Applicants of one level ('college', 'is', 'graduate_school' or 'eteeap'), newest first,
     * from the newest school year, in the placeholder shape.
     */
    public static function forLevel(string $level): array
    {
        $paid = self::paidFees('reservation_fee');
        $paidExam = self::paidFees('application_fee');
        $schoolYear = Applicant::max('school_year');

        return Applicant::where('level', $level)
            ->whereNull('archived_at')
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->latest()
            ->get()
            ->map(fn (Applicant $a) => self::shape($a, $paid[$a->id] ?? null, isset($paidExam[$a->id])))
            ->all();
    }

    /** Applicant id => [amount, paid_at] for a paid fee ('reservation_fee' or 'application_fee'/exam fee). */
    public static function paidFees(string $feeType): array
    {
        return DB::table('payments')
            ->where('fee_type', $feeType)
            ->where('status', 'paid')
            ->get(['applicant_id', 'amount', 'paid_at'])
            ->keyBy('applicant_id')
            ->map(fn ($p) => [$p->amount, $p->paid_at])
            ->all();
    }

    private static function shape(Applicant $a, ?array $payment, bool $examPaid = false): array
    {
        $program = $a->program_or_track ?: '—';
        $row = [
            'id' => $a->reference_number,
            'last' => $a->last_name,
            'first' => $a->first_name,
            'mi' => '',
            'program' => $program,
            'status' => self::status($a, $payment !== null),
            'test' => self::testLabel($a->admission_test_status),
            'exam_status' => $a->admission_test_status,
            'scholar' => $a->applying_for_scholarship,
            'scholarship' => $a->applying_for_scholarship ? 'Yes' : 'No',
            'exam_paid' => $examPaid,
            'feeder_school' => $a->feeder_school,
            'applied' => $a->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'updated' => ($a->updated_at ?? $a->created_at ?? now())->format('Y-m-d H:i'),
            'dob' => null,
            'dob' => $a->date_of_birth?->format('Y-m-d'),
            'gender' => ucfirst($a->gender),
            'email' => $a->email,
            'contact' => $a->contact_number,
            'address' => $a->address,
            'exam' => $a->exam_scheduled_at ? $a->exam_scheduled_at->format('M j, Y') . ' · ' . $a->exam_scheduled_at->format('g:i A') : null,
            'paid' => $payment && $payment[1] ? Carbon::parse($payment[1])->format('Y-m-d') : null,
            'amount' => $payment[0] ?? null,
        ];

        // array_merge: the level's own values replace the shared ones (e.g. 'program' on Graduate and ETEEAP)
        return match ($a->level) {
            'college' => array_merge($row, [
                'unit' => 'college',
                'college' => $a->college ?: self::collegeFor($program),
                'year' => $a->year_level ?: '—',
            ]),
            'is' => array_merge($row, [
                'unit' => 'is',
                'sub_level' => self::IS_SUB_LEVELS[$a->sub_level] ?? 'Grade School',
                'level' => $a->year_level ?: '—',
            ]),
            'graduate_school' => array_merge($row, [
                'unit' => 'graduate',
                'unit_label' => 'Graduate Programs',
                'group' => self::graduateCode($program),
                'program_label' => $program,
                'program' => str_contains($program, 'Doctor') ? 'Doctoral' : "Master's",
                // Not in the portal export - there's no "what degree did you already
                // have" column to read this from, so this always shows "-" for real data.
                'degree' => '—',
            ]),
            'eteeap' => array_merge($row, [
                'unit' => 'eteeap',
                'unit_label' => 'ETEEAP',
                'group' => self::initials($program),
                'program_label' => $program,
                // The second column on ETEEAP Records: how far the portfolio / competency assessment is
                'program' => self::portfolioStatus($a->admission_test_status),
                'age' => $a->age ? (string) $a->age : '—',
                // Not in the portal export - no columns for these
                'education' => '—', 'experience' => '—', 'employer' => '—',
            ]),
            default => array_merge($row, [
                'unit' => $a->level,
                'unit_label' => strtoupper($a->level),
                'group' => self::initials($program),
                'program_label' => $program,
            ]),
        };
    }

    /**
     * One status for the pages, from the table's three columns:
     * a paid reservation -> Paid; test passed -> Passed; failed -> Not Qualified;
     * test taken or application in -> For Exam; still pooling / not submitted -> Pending.
     */
    public static function status(Applicant $a, bool $paid = false): string
    {
        return match (true) {
            $paid => 'Paid',
            in_array($a->admission_test_status, ['passed', 'qualified_other_degree', 'reconsidered']) => 'Passed',
            $a->admission_test_status === 'failed' => 'Not Qualified',
            $a->admission_test_status === 'took_test',
            in_array($a->application_status, ['submitted', 'completed']) => 'For Exam',
            default => 'Pending',
        };
    }

    public static function testLabel(?string $status): string
    {
        return [
            'not_taken' => 'Not taken yet', 'took_test' => 'Taken, waiting for result', 'passed' => 'Passed',
            'failed' => 'Not qualified', 'qualified_other_degree' => 'Qualified for another program',
            'reconsidered' => 'Reconsidered', 'waitlisted' => 'Waitlisted',
        ][$status] ?? '—';
    }

    public static function collegeFor(string $program): string
    {
        foreach (self::COLLEGES as $word => $college) {
            if (stripos($program, $word) !== false) {
                return $college;
            }
        }

        return 'Other';
    }

    private static function graduateCode(string $program): string
    {
        foreach (self::GRADUATE_CODES as $name => $code) {
            if (stripos($program, $name) !== false) {
                return $code;
            }
        }

        return self::initials($program);
    }

    // ETEEAP portfolio / competency assessment, from the test status column
    private static function portfolioStatus(?string $status): string
    {
        return match ($status) {
            'took_test' => 'Submitted',
            'passed', 'qualified_other_degree', 'reconsidered' => 'Cleared',
            'failed' => 'Not Cleared',
            default => 'Pending',
        };
    }

    // "Master of Arts in Teaching" -> "MAT", "BS Information Technology" -> "BSIT" (small words skipped, "BS" kept whole)
    private static function initials(string $text): string
    {
        $words = array_filter(preg_split('/\s+/', $text), fn ($w) => ! in_array(strtolower($w), ['of', 'in', 'and', 'the', '&', '—']));

        return strtoupper(implode('', array_map(fn ($w) => strtoupper($w) === 'BS' ? 'BS' : $w[0], $words))) ?: '—';
    }
}
