<?php

namespace App\Support;

use App\Models\Applicant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admission numbers computed live from the applicants table (and payments for
 * reservation/exam fees), instead of a separately-uploaded admission_stats file.
 * This is the single source for Analytics, the dashboard, and Reports — the
 * portal export is the only import in the system; everything countable here
 * (gender, Yes/No fields, pass/fail, paid status) comes from that one file.
 *
 * "Current" is the newest school year in the table; "previous" is the one
 * before it (null, and every *_previous number 0, if there isn't one yet —
 * that just means only one year's worth of applicants has been imported).
 */
class AdmissionStats
{
    // level -> display label, in report order
    public const LEVEL_LABELS = [
        'is' => 'Integrated School',
        'college' => 'College',
        'graduate_school' => 'Graduate Programs',
        'eteeap' => 'ETEEAP',
        'law' => 'College of Law',
        'ipace' => 'iPACE',
    ];

    /** All school years present in the table, newest first. */
    public static function schoolYears(): array
    {
        return Applicant::query()->distinct()->orderByDesc('school_year')->pluck('school_year')->all();
    }

    public static function currentSchoolYear(): ?string
    {
        return self::schoolYears()[0] ?? null;
    }

    public static function previousSchoolYear(): ?string
    {
        return self::schoolYears()[1] ?? null;
    }

    /** How many applicants (by level) have admission_test_status != 'not_taken'. */
    protected static function tookTestStatuses(): array
    {
        return ['took_test', 'passed', 'failed', 'qualified_other_degree', 'reconsidered', 'waitlisted'];
    }

    /** The statuses that count as "passed" (see ApplicantRecords::status()). */
    protected static function passedStatuses(): array
    {
        return ['passed', 'qualified_other_degree', 'reconsidered'];
    }

    protected static function countFor(string $schoolYear, ?string $level = null, ?array $testStatusIn = null, string|array|null $applicationStatus = null): int
    {
        return Applicant::query()
            ->where('school_year', $schoolYear)
            ->whereNull('archived_at')
            ->when($level, fn ($q) => $q->where('level', $level))
            ->when($testStatusIn, fn ($q) => $q->whereIn('admission_test_status', $testStatusIn))
            ->when($applicationStatus, fn ($q) => is_array($applicationStatus)
                ? $q->whereIn('application_status', $applicationStatus)
                : $q->where('application_status', $applicationStatus))
            ->count();
    }

    protected static function reservedFor(string $schoolYear, ?string $level = null): int
    {
        return DB::table('payments')
            ->join('applicants', 'applicants.id', '=', 'payments.applicant_id')
            ->where('payments.fee_type', 'reservation_fee')
            ->where('payments.status', 'paid')
            ->where('applicants.school_year', $schoolYear)
            ->whereNull('applicants.archived_at')
            ->when($level, fn ($q) => $q->where('applicants.level', $level))
            ->count();
    }

    /**
     * Overall numbers across every level, for the Analytics Overview cards/funnel
     * and the dashboard's stat cards.
     */
    public static function summary(): array
    {
        $current = self::currentSchoolYear();
        $previous = self::previousSchoolYear();

        $overallCurrent = $current ? self::countFor($current) : 0;
        $overallPrevious = $previous ? self::countFor($previous) : 0;
        $submittedCurrent = $current ? self::countFor($current, applicationStatus: ['submitted', 'completed']) : 0;
        $tookTestCurrent = $current ? self::countFor($current, testStatusIn: self::tookTestStatuses()) : 0;
        $tookTestPrevious = $previous ? self::countFor($previous, testStatusIn: self::tookTestStatuses()) : 0;
        $passedCurrent = $current ? self::countFor($current, testStatusIn: self::passedStatuses()) : 0;
        $passedPrevious = $previous ? self::countFor($previous, testStatusIn: self::passedStatuses()) : 0;
        $reservedCurrent = $current ? self::reservedFor($current) : 0;
        $reservedPrevious = $previous ? self::reservedFor($previous) : 0;
        $enrolledCurrent = $current ? self::countFor($current, applicationStatus: 'completed') : 0;

        return [
            'currentSchoolYear' => $current,
            'previousSchoolYear' => $previous,
            'overallCurrent' => $overallCurrent,
            'overallPrevious' => $overallPrevious,
            'submittedCurrent' => $submittedCurrent,
            'enrolledCurrent' => $enrolledCurrent,
            'passRateCurrent' => $tookTestCurrent > 0 ? ($passedCurrent / $tookTestCurrent) * 100 : 0,
            'passRatePrevious' => $tookTestPrevious > 0 ? ($passedPrevious / $tookTestPrevious) * 100 : 0,
            'conversionRateCurrent' => $overallCurrent > 0 ? ($reservedCurrent / $overallCurrent) * 100 : 0,
            'conversionRatePrevious' => $overallPrevious > 0 ? ($reservedPrevious / $overallPrevious) * 100 : 0,
            'funnelLabels' => ['Submitted', 'Took Exam', 'Passed Exam', 'Reserved', 'Enrolled'],
            'funnelValues' => [
                max($submittedCurrent, 1),
                $tookTestCurrent,
                $passedCurrent,
                $reservedCurrent,
                $enrolledCurrent,
            ],
        ];
    }

    /**
     * Automatic counts of the portal's Yes/No and gender fields for the current school
     * year — the whole point being nobody has to filter or tally these by hand.
     */
    public static function yesNoBreakdown(): array
    {
        $current = self::currentSchoolYear();
        if (! $current) {
            return ['male' => 0, 'female' => 0, 'scholars' => 0, 'pwd' => 0, 'ssen' => 0, 'noaIssued' => 0, 'paidExam' => 0];
        }

        // A fresh query builder each call - where() mutates the builder in place, so
        // reusing one instance across these counts would stack every condition together.
        $base = fn () => Applicant::query()->where('school_year', $current)->whereNull('archived_at');

        // gender is an encrypted column (FR-09) - non-deterministic ciphertext, so a raw
        // WHERE on it can never match. Fetch and count in PHP instead, where the model
        // cast has already decrypted it.
        $genders = $base()->get('gender')->countBy('gender');

        return [
            'male' => $genders->get('male', 0),
            'female' => $genders->get('female', 0),
            'scholars' => $base()->where('applying_for_scholarship', true)->count(),
            'pwd' => $base()->where('pwd', true)->count(),
            'ssen' => $base()->where('ssen', true)->count(),
            'noaIssued' => $base()->where('noa_issued', true)->count(),
            'paidExam' => DB::table('payments')
                ->join('applicants', 'applicants.id', '=', 'payments.applicant_id')
                ->where('payments.fee_type', 'application_fee')
                ->where('payments.status', 'paid')
                ->where('applicants.school_year', $current)
                ->whereNull('applicants.archived_at')
                ->count(),
        ];
    }

    /**
     * One row per level that actually has imported applicants, current vs previous
     * school year — the replacement for admission_stats' per-level_program rows.
     *
     * @return Collection<int, array{level_program: string, level: string, current: int, previous: int, pct_change: ?float, enrolled: int, reserved: int}>
     */
    public static function byLevel(): Collection
    {
        $current = self::currentSchoolYear();
        $previous = self::previousSchoolYear();
        $levelsPresent = Applicant::query()->whereNull('archived_at')->distinct()->pluck('level');

        return $levelsPresent->map(function (string $level) use ($current, $previous) {
            $currentCount = $current ? self::countFor($current, $level) : 0;
            $previousCount = $previous ? self::countFor($previous, $level) : 0;
            $pct = $previousCount > 0 ? (($currentCount - $previousCount) / $previousCount) * 100 : null;

            return [
                'level' => $level,
                'level_program' => self::LEVEL_LABELS[$level] ?? ucfirst($level),
                'current' => $currentCount,
                'previous' => $previousCount,
                'pct_change' => $pct,
                'enrolled' => $current ? self::countFor($current, $level, applicationStatus: 'completed') : 0,
                'reserved' => $current ? self::reservedFor($current, $level) : 0,
            ];
        })->sortByDesc('current')->values();
    }
}
