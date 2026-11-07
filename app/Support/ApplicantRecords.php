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
    const IS_SUB_LEVELS = ['kindergarten' => 'Preschool', 'jhs' => 'Junior High', 'shs' => 'Senior High'];

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
        $paid = self::paidReservations();
        $schoolYear = Applicant::max('school_year');

        return Applicant::where('level', $level)
            ->whereNull('archived_at')
            ->when($schoolYear, fn ($q) => $q->where('school_year', $schoolYear))
            ->latest()
            ->get()
            ->map(fn (Applicant $a) => self::shape($a, $paid[$a->id] ?? null))
            ->all();
    }

    /** Applicant id => [amount, paid_at] for paid reservation fees (empty until payments are recorded). */
    private static function paidReservations(): array
    {
        return DB::table('payments')
            ->where('fee_type', 'reservation_fee')
            ->where('status', 'paid')
            ->get(['applicant_id', 'amount', 'paid_at'])
            ->keyBy('applicant_id')
            ->map(fn ($p) => [$p->amount, $p->paid_at])
            ->all();
    }

    private static function shape(Applicant $a, ?array $payment): array
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
            'scholar' => $a->applicant_type === 'scholar',
            'feeder_school' => $a->feeder_school,
            'applied' => $a->created_at?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'updated' => ($a->updated_at ?? $a->created_at ?? now())->format('Y-m-d H:i'),
            'dob' => null,
            'gender' => ucfirst($a->gender),
            'email' => null,
            'contact' => null,
            'address' => null,
            'exam' => null,
            'paid' => $payment && $payment[1] ? Carbon::parse($payment[1])->format('Y-m-d') : null,
            'amount' => $payment[0] ?? null,
        ];

        return match ($a->level) {
            'college' => $row + ['unit' => 'college', 'college' => self::collegeFor($program)],
            'is' => $row + [
                'unit' => 'is',
                'sub_level' => self::IS_SUB_LEVELS[$a->sub_level] ?? 'Grade School',
                'level' => '—', // no grade in the table yet
            ],
            'graduate_school' => $row + [
                'unit' => 'graduate',
                'unit_label' => 'Graduate Programs',
                'group' => self::graduateCode($program),
                'program_label' => $program,
                'program' => str_contains($program, 'Doctor') ? 'Doctoral' : "Master's",
                'degree' => '—',
            ],
            default => $row + [
                'unit' => $a->level,
                'unit_label' => strtoupper($a->level),
                'group' => self::initials($program),
                'program_label' => $program,
            ],
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

    private static function testLabel(?string $status): string
    {
        return [
            'not_taken' => 'Not taken yet', 'took_test' => 'Taken, waiting for result', 'passed' => 'Passed',
            'failed' => 'Not qualified', 'qualified_other_degree' => 'Qualified for another program', 'reconsidered' => 'Reconsidered',
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

    // "Master of Arts in Teaching" -> "MAT" (small words skipped)
    private static function initials(string $text): string
    {
        $words = array_filter(preg_split('/\s+/', $text), fn ($w) => ! in_array(strtolower($w), ['of', 'in', 'and', 'the', '—']));

        return strtoupper(implode('', array_map(fn ($w) => $w[0], $words))) ?: '—';
    }
}
