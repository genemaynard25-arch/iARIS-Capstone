<?php

namespace App\Support;

use App\Http\Controllers\IpaceRecordController;
use App\Http\Controllers\LawRecordController;
use Illuminate\Support\Carbon;

/**
 * The Registrar's pages (RegistrarController): the records of their units, in one shape.
 *
 * College Registrar: College, Graduate Programs, College of Law, iPACE and ETEEAP.
 *   College, Graduate Programs and ETEEAP come from the applicants table (ApplicantRecords).
 *   College of Law and iPACE are still placeholder data (their controllers' sample()),
 *   because the applicants table has no level for them yet.
 *
 * Each row: id, name, unit, dept, program, status, applied, paid, amount, url (opens the record).
 */
class RegistrarData
{
    // Unit => [where the rows come from, records page]
    public const UNITS = [
        'College' => ['college', '/records/college'],
        'Graduate Programs' => ['graduate_school', '/records/graduate'],
        'College of Law' => ['law', '/records/law'],
        'iPACE' => ['ipace', '/records/ipace'],
        'ETEEAP' => ['eteeap', '/records/eteeap'],
    ];

    /** Every applicant in the Registrar's units. */
    public static function rows(): array
    {
        $rows = [];
        foreach (self::UNITS as $unit => [$source, $url]) {
            $list = match ($source) {
                'law' => LawRecordController::sample(),
                'ipace' => IpaceRecordController::sample(),
                default => ApplicantRecords::forLevel($source),
            };
            foreach ($list as $a) {
                $rows[] = [
                    'id' => $a['id'],
                    'name' => trim("{$a['last']}, {$a['first']}" . (($a['mi'] ?? '') ? " {$a['mi']}." : '')),
                    'unit' => $unit,
                    // College: the college (CITE…); the others: the program code (MBA, JD, BSIT…)
                    'dept' => $a['college'] ?? $a['group'] ?? '—',
                    'program' => $source === 'college' ? $a['program'] : ($a['program_label'] ?? $a['program']),
                    'status' => $a['status'],
                    'applied' => $a['applied'],
                    'applied_label' => $a['applied'] ? Carbon::parse($a['applied'])->format('M j, Y') : '—',
                    'paid' => $a['paid'],
                    'paid_label' => $a['paid'] ? Carbon::parse($a['paid'])->format('M j, Y') : '—',
                    'amount' => $a['amount'] ? '₱' . number_format($a['amount'], 2) : '—',
                    'url' => url($url) . '?open=' . urlencode($a['id']),
                ];
            }
        }

        return $rows;
    }

    /**
     * Counts per unit: [unit => [Pending, For Exam, Passed, Paid, total]].
     * (Not Qualified is only in the total.)
     */
    public static function countsByUnit(array $rows): array
    {
        $counts = [];
        foreach (array_keys(self::UNITS) as $unit) {
            $mine = collect($rows)->where('unit', $unit);
            $counts[$unit] = [
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
