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

    // Each scholarship type belongs to one of the three groups on LAMP's stat cards.
    // Admissions doesn't see scholarship type at all (see sample() below) — this stays
    // here only because LampController/lamp views still read it.
    public const TYPES = [
        'DLSL Scholar' => 'DLSL',
        'Academic Scholar' => 'Merit',
        'Athletic Scholar' => 'Merit',
        'CHED Scholar' => 'Government',
        'DOST Scholar' => 'Government',
        'Other Government' => 'Government',
    ];

    // admission_test_status -> [Bootstrap tone, label] for Admissions' own pass/fail view.
    public const EXAM_LABELS = [
        'not_taken' => ['secondary', 'Not Taken'],
        'took_test' => ['warning', 'Awaiting Result'],
        'waitlisted' => ['warning', 'Waitlisted'],
        'passed' => ['primary', 'Passed'],
        'qualified_other_degree' => ['info', 'Qualified (Other Program)'],
        'reconsidered' => ['info', 'Reconsidered'],
        'failed' => ['danger', 'Failed'],
    ];

    public function index()
    {
        $user = auth()->user();

        return view('scholars.index', [
            'academicYear' => '2025–2026',
            // Add / Edit / Remove (front end only): roles with the 'edit' permission
            'canEdit' => $user->canAccess('edit'),
            'scholars' => self::sample(),

            // Conversations with the LAMP Office about mismatched records (front end only)
            'lampThreads' => \App\Support\LampRequests::byScholar(),
            // Only IATO (admins and staff) can ask LAMP or reply; others can read the conversation
            'canMessageLamp' => $user->isAdmin() || $user->role === 'iato_staff',
            // How your messages are signed, e.g. "Cruz, Jasmine · IATO Staff"
            'me' => ['name' => self::lastFirst($user->name), 'role' => $user->roleInfo()[0]],

            // Bootstrap colours (and icons) for each kind of badge
            'examStatuses' => self::EXAM_LABELS,
            'scholarshipTones' => ['Active' => 'primary', 'Probationary' => 'warning', 'Cancelled' => 'danger'],
            'lampBadges' => [
                'Match' => ['primary', 'bi-check-circle-fill'],
                'Discrepancy' => ['danger', 'bi-exclamation-circle-fill'],
                'Not Found' => ['secondary', 'bi-question-circle-fill'],
                // Every real scholar gets this today — there's no real LAMP dataset to
                // compare against yet (see sample() below), so this was missing and would
                // throw on every row once real data replaced the placeholder people.
                'Pending Check' => ['warning', 'bi-clock-fill'],
            ],
            'statusTones' => ['Pending' => 'danger', 'For Exam' => 'warning', 'Paid' => 'info', 'Enrolled' => 'primary'],
        ]);
    }

    // "Jasmine Cruz" -> "Cruz, Jasmine" (same as the "Signed in as" card)
    private static function lastFirst(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $last = array_pop($parts);

        return $parts ? $last . ', ' . implode(' ', $parts) : $last;
    }

    // Real scholars: applicants who checked "Yes" to Applying for Scholarship on the
    // portal file. Admissions only knows this Yes/No plus pass/fail — the specific
    // scholarship type (Academic, Sports, etc.) is LAMP's own call to make once they
    // process the secondary application stage, so scholarship_type isn't set from
    // import and 'lamp'/'lamp_record' stay "Pending Check" / "Not in LAMP records"
    // for every row: iARIS has no second dataset representing LAMP's own records to
    // actually cross-check against yet.
    public static function sample(): Collection
    {
        $date = fn ($value, $format) => $value ? Carbon::parse($value)->format($format) : null;

        $statusLabels = [
            'pooling' => 'Pending',
            'not_submitted' => 'Pending',
            'submitted' => 'For Exam',
            'completed' => 'Enrolled',
        ];

        return \App\Models\Applicant::where('applying_for_scholarship', true)
            ->get()
            ->map(function (\App\Models\Applicant $a) use ($date, $statusLabels) {
                $unit = match ($a->level) {
                    'is' => 'Integrated School · ' . (\App\Support\ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? ucfirst($a->sub_level ?? '')),
                    'college' => $a->college ?? $a->program_or_track,
                    'graduate_school' => 'Graduate Programs',
                    'eteeap' => 'ETEEAP',
                    default => $a->program_or_track,
                };

                return [
                    'id' => $a->reference_number,
                    'name' => "{$a->last_name}, {$a->first_name}",
                    'initials' => mb_substr($a->first_name, 0, 1) . mb_substr($a->last_name, 0, 1),
                    'unit' => $unit,
                    'program' => $a->program_or_track,
                    'type' => $a->scholarship_type,
                    'type_group' => self::TYPES[$a->scholarship_type] ?? null,
                    'scholarship_status' => $a->archived_at ? 'Cancelled' : 'Active',
                    'lamp_record' => 'Not in LAMP records',
                    'lamp' => 'Pending Check',
                    'status' => $statusLabels[$a->application_status] ?? 'Pending',
                    'applied' => optional($a->created_at)->toDateString(),
                    'applied_label' => $date($a->created_at, 'M j, Y'),
                    'updated_label' => $date($a->updated_at, 'M j, g:i A'),
                    'dob_label' => $date($a->date_of_birth ?? null, 'F j, Y'),
                    'gender' => $a->gender,
                    'email' => $a->email,
                    'contact' => $a->contact_number,
                    'address' => $a->address,
                    'exam' => $a->admission_test_status,
                    'exam_label' => self::EXAM_LABELS[$a->admission_test_status][1] ?? '—',
                    'exam_tone' => self::EXAM_LABELS[$a->admission_test_status][0] ?? 'secondary',
                    'paid_label' => null,
                    'amount_label' => null,
                ];
            })
            ->values();
    }
}
