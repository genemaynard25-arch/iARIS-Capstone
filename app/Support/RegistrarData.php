<?php

namespace App\Support;

use App\Http\Controllers\IpaceRecordController;
use Illuminate\Support\Carbon;

/**
 * The Registrar's pages (RegistrarController): the records of their units, in one shape.
 *
 * Two kinds of Registrar, by the user's assigned_level ("scope"):
 *   College Registrar ('college'): College, Graduate Programs, College of Law, iPACE and ETEEAP.
 *     College, Graduate Programs, College of Law and ETEEAP come from the applicants table
 *     (ApplicantRecords). iPACE is still placeholder data (IpaceRecordController::sample()),
 *     because the applicants table has no level for it yet.
 *   IS Registrar ('is'): the Integrated School only, from the applicants table, by level.
 *
 * Each row: id, name, unit, dept, program, status, applied, paid, url (opens the record). No
 * amount: that's the cashier's record, not the Registrar's.
 */
class RegistrarData
{
    // Unit => [where the rows come from, records page]
    public const IS_UNITS = ['Integrated School' => ['is', '/records/is']];
    public const UNITS = [
        'College' => ['college', '/records/college'],
        'Graduate Programs' => ['graduate_school', '/records/graduate'],
        'College of Law' => ['law', '/records/law'],
        'iPACE' => ['ipace', '/records/ipace'],
        'ETEEAP' => ['eteeap', '/records/eteeap'],
    ];

    // The Integrated School's levels, in order (the "departments" of the IS Registrar)
    public const IS_LEVELS = ['Preschool', 'Grade School', 'Junior High', 'Senior High'];

    /** 'is' for the IS Registrar, 'college' for the College Registrar (and anyone else previewing). */
    public static function scope(): string
    {
        return auth()->user()?->assigned_level === 'is' ? 'is' : 'college';
    }

    /** The units of a scope: [unit => [source, records page]]. */
    public static function units(?string $scope = null): array
    {
        return ($scope ?? self::scope()) === 'is' ? self::IS_UNITS : self::UNITS;
    }

    /** Preserve every record in the registrar's assigned units, regardless of result or payment. */
    public static function forRecords(array $records, string $scope): array
    {
        if (auth()->user()?->role === 'registrar') {
            $allowed = self::scope() === 'is' ? ['is'] : ['college', 'graduate', 'law', 'ipace', 'eteeap'];
            abort_unless(in_array($scope, $allowed), 403);
        }

        return $records;
    }

    private static function recordExamStatus(array $record, bool $preview): ?string
    {
        return $record['exam_status'] ?? ($preview && in_array($record['status'], ['Passed', 'Paid', 'Enrolled']) ? 'passed' : null);
    }

    private static function hasReservation(array $record): bool
    {
        // No 'amount' field (cashier-only, out of scope here too) - the paid date already says
        // whether the reservation fee came in.
        return ($record['paid'] ?? null) !== null || in_array($record['status'], ['Paid', 'Enrolled']);
    }

    /** Every applicant in the Registrar's units. */
    public static function rows(?string $scope = null): array
    {
        $rows = [];
        foreach (self::units($scope) as $unit => [$source, $url]) {
            $list = match ($source) {
                'ipace' => IpaceRecordController::sample(),
                default => ApplicantRecords::forLevel($source),
            };
            foreach ($list as $a) {
                $preview = in_array($source, ['ipace']);
                // Only the unconnected sample divisions infer a sample result from their sample status.
                $examStatus = self::recordExamStatus($a, $preview);
                $passed = in_array($examStatus, ['passed', 'qualified_other_degree', 'reconsidered']);
                $reserved = self::hasReservation($a);
                $programOrGrade = in_array($source, ['college', 'is']) ? $a['program'] : ($a['program_label'] ?? $a['program']);
                if ($source === 'is') {
                    $placement = array_filter([$a['level'] ?? null, $a['program'] ?? null], fn ($value) => $value && $value !== '—');
                    $programOrGrade = implode(' · ', array_unique($placement)) ?: '—';
                }
                $rows[] = [
                    'id' => $a['id'],
                    'name' => trim("{$a['last']}, {$a['first']}" . (($a['mi'] ?? '') ? " {$a['mi']}." : '')),
                    'unit' => $unit,
                    // College: the college (CITE…); IS: the level (Junior High…); the others: the program code (MBA, JD, BSIT…)
                    'dept' => $a['college'] ?? $a['sub_level'] ?? $a['group'] ?? '—',
                    // College / IS: the program or grade as imported; the others: the program's full name
                    'program' => $programOrGrade,
                    'status' => $a['status'],
                    'exam_status' => $examStatus,
                    'exam_result' => $examStatus ? ApplicantRecords::testLabel($examStatus) : 'Not recorded',
                    'exam_passed' => $passed,
                    'reservation_paid' => $reserved,
                    'ready_for_enrollment' => $passed && $reserved,
                    'is_preview' => $preview,
                    'applied' => $a['applied'],
                    'applied_label' => $a['applied'] ? Carbon::parse($a['applied'])->format('M j, Y') : '—',
                    // No OR/amount: that's the cashier's record, not the Registrar's.
                    'paid' => $a['paid'],
                    'paid_label' => $a['paid'] ? Carbon::parse($a['paid'])->format('M j, Y') : '—',
                    'url' => url($url) . '?open=' . urlencode($a['id']),
                ];
            }
        }

        return $rows;
    }

    /**
     * Counts per group: [group => [Pending, For Exam, Passed, Paid, total, url]].
     * Groups are the units (College Registrar) or, with only one unit, its levels (IS Registrar).
     * (Not Qualified is only in the total.)
     */
    public static function countsByGroup(array $rows, ?string $scope = null): array
    {
        $units = self::units($scope);
        $byLevel = count($units) === 1;
        $groups = $byLevel ? self::IS_LEVELS : array_keys($units);
        $counts = [];
        foreach ($groups as $group) {
            $mine = collect($rows)->where($byLevel ? 'dept' : 'unit', $group);
            $counts[$group] = [
                'url' => url($byLevel ? reset($units)[1] : $units[$group][1]),
                'Pending' => $mine->where('status', 'Pending')->count(),
                'For Exam' => $mine->where('status', 'For Exam')->count(),
                'Passed' => $mine->where('status', 'Passed')->count(),
                'Paid' => $mine->where('status', 'Paid')->count(),
                'total' => $mine->count(),
            ];
        }

        return $counts;
    }
}
