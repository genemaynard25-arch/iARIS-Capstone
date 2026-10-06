<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScholarController extends Controller
{
    /**
     * Scholar Records page.
     *
     * Placeholder data: some of the made-up people from ApplicantController::sample(),
     * plus a scholarship for each. 'lamp' is what the LAMP Office has on file for that
     * person (null = they're not in LAMP's list at all).
     *
     * The cross-check is worked out here, the same way the backend would do it later:
     *   LAMP type same as IATO type -> Match
     *   LAMP type different         -> Discrepancy
     *   not in LAMP's list          -> Not Found
     *
     * sample() is public and static so the LAMP Office pages can show the same scholars.
     */

    // Each scholarship type belongs to one of the three groups on the stat cards
    public const TYPES = [
        'DLSL Scholar' => 'DLSL',
        'Academic Scholar' => 'Merit',
        'Athletic Scholar' => 'Merit',
        'CHED Scholar' => 'Government',
        'DOST Scholar' => 'Government',
        'Other Government' => 'Government',
    ];

    public function index()
    {
        return view('scholars.index', [
            'academicYear' => '2025–2026',
            'scholars' => self::sample(),

            // Bootstrap colours (and icons) for each kind of badge
            'typeTones' => ['DLSL' => 'primary', 'Merit' => 'info', 'Government' => 'warning'],
            'scholarshipTones' => ['Active' => 'primary', 'Probationary' => 'warning', 'Cancelled' => 'danger'],
            'lampBadges' => [
                'Match' => ['primary', 'bi-check-circle-fill'],
                'Discrepancy' => ['danger', 'bi-exclamation-circle-fill'],
                'Not Found' => ['secondary', 'bi-question-circle-fill'],
            ],
            'statusTones' => ['Pending' => 'danger', 'For Exam' => 'warning', 'Paid' => 'info', 'Enrolled' => 'primary'],
            'types' => array_keys(self::TYPES),
        ]);
    }

    public static function sample(): Collection
    {
        // App number => [scholarship type, scholarship status, LAMP Office's record]
        $scholarships = [
            'APP-2026-0381' => ['DLSL Scholar', 'Active', 'DLSL Scholar'],
            'APP-2026-0385' => ['Academic Scholar', 'Active', null],
            'APP-2026-0382' => ['DOST Scholar', 'Active', 'DOST Scholar'],
            'APP-2026-0387' => ['Athletic Scholar', 'Probationary', 'Athletic Scholar'],
            'APP-2026-0389' => ['CHED Scholar', 'Active', 'DLSL Scholar'],
            'APP-2026-0383' => ['DLSL Scholar', 'Cancelled', null],
            'APP-2026-0384' => ['Academic Scholar', 'Active', 'Academic Scholar'],
            'APP-2026-0391' => ['Other Government', 'Active', 'Other Government'],
            'APP-2026-0390' => ['DOST Scholar', 'Active', 'DOST Scholar'],
            'APP-IS-2026-0101' => ['Academic Scholar', 'Active', 'Athletic Scholar'],
            'APP-IS-2026-0105' => ['DLSL Scholar', 'Probationary', 'DLSL Scholar'],
        ];

        $date = fn ($value, $format) => $value ? Carbon::parse($value)->format($format) : null;

        return collect(ApplicantController::sample())
            ->filter(fn ($a) => isset($scholarships[$a['id']]))
            ->map(function ($a) use ($scholarships, $date) {
                [$type, $scholarshipStatus, $lampRecord] = $scholarships[$a['id']];

                return [
                    'id' => $a['id'],
                    'name' => "{$a['last']}, {$a['first']} {$a['mi']}.",
                    'initials' => $a['first'][0] . $a['last'][0],
                    'unit' => $a['unit'] === 'college' ? $a['college'] : "Integrated School · {$a['level']}",
                    'program' => $a['program'],
                    'type' => $type,
                    'type_group' => self::TYPES[$type],
                    'scholarship_status' => $scholarshipStatus,
                    'lamp_record' => $lampRecord ?? 'Not in LAMP records',
                    'lamp' => match (true) {
                        $lampRecord === null => 'Not Found',
                        $lampRecord === $type => 'Match',
                        default => 'Discrepancy',
                    },
                    'status' => $a['status'],
                    'applied' => $a['applied'],
                    'applied_label' => $date($a['applied'], 'M j, Y'),
                    'updated_label' => $date($a['updated'], 'M j, g:i A'),
                    'dob_label' => $date($a['dob'], 'F j, Y'),
                    'gender' => $a['gender'],
                    'email' => $a['email'],
                    'contact' => $a['contact'],
                    'address' => $a['address'],
                    'exam' => $a['exam'],
                    'paid_label' => $date($a['paid'], 'M j, Y'),
                    'amount_label' => $a['amount'] ? '₱' . number_format($a['amount'], 2) : null,
                ];
            })
            ->values();
    }
}
