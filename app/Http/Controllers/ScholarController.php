<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LampScholarRecord;
use App\Support\ApplicantRecords;
use App\Support\LampRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScholarController extends Controller
{
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

    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolYears = Applicant::whereNull('archived_at')->distinct()->orderByDesc('school_year')->pluck('school_year')->all();
        $schoolYear = $request->input('year', $schoolYears[0] ?? now()->year.'-'.(now()->year + 1));
        abort_unless(is_string($schoolYear) && (in_array($schoolYear, $schoolYears, true) || $schoolYears === []), 422, 'Choose an available school year.');

        return view('scholars.index', [
            'academicYear' => ApplicantRecords::academicYear(),
            // Roles with the edit permission can change applicant records.
            'canEdit' => $user->canAccess('edit'),
            'scholars' => self::sample($schoolYear),
            'schoolYears' => $schoolYears,
            'selectedSchoolYear' => $schoolYear,

            // Conversations with the LAMP Office about applicant records.
            'lampThreads' => LampRequests::byScholar(),
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
                'Awaiting IATO' => ['info', 'bi-hourglass-split'],
                'Not Scholar' => ['secondary', 'bi-x-circle'],
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

        return $parts ? $last.', '.implode(' ', $parts) : $last;
    }

    public static function sample(?string $schoolYear = null): Collection
    {
        $date = fn ($value, $format) => $value ? Carbon::parse($value)->format($format) : null;

        $statusLabels = [
            'pooling' => 'Pending',
            'not_submitted' => 'Pending',
            'submitted' => 'For Exam',
            'completed' => 'Enrolled',
        ];

        $applicants = Applicant::where('applying_for_scholarship', true)
            ->whereNull('archived_at')
            ->where('school_year', $schoolYear ?? Applicant::max('school_year') ?? now()->year.'-'.(now()->year + 1))->get();
        $checks = LampScholarRecord::whereIn('applicant_id', $applicants->pluck('id'))->get()->keyBy('applicant_id');

        return $applicants->map(function (Applicant $a) use ($date, $statusLabels, $checks) {
            [$first, $mi] = ApplicantRecords::nameParts($a);
            $check = $checks->get($a->id);
            $decision = in_array($check?->decision, ['yes', 'no'], true) ? $check->decision : null;
            $confirmed = $decision && $check->iato_status === 'confirmed';
            $unit = match ($a->level) {
                'is' => 'Integrated School · '.(ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? ucfirst($a->sub_level ?? '')),
                'college' => $a->college ?? $a->program_or_track,
                'graduate_school' => 'Graduate Programs',
                'eteeap' => 'ETEEAP',
                default => $a->program_or_track,
            };

            return [
                'id' => $a->reference_number,
                'name' => trim("{$a->last_name}, {$first}".($mi ? ' '.$mi.'.' : '')),
                'last' => $a->last_name, 'first' => $first, 'mi' => $mi,
                'initials' => mb_substr($a->first_name, 0, 1).mb_substr($a->last_name, 0, 1),
                'unit' => $unit,
                'program' => $a->program_or_track,
                'type' => $a->scholarship_type,
                'type_group' => self::TYPES[$a->scholarship_type] ?? null,
                'scholarship_status' => $a->archived_at ? 'Cancelled' : 'Active',
                'lamp_record' => $decision ? ($decision === 'yes' ? 'Scholar' : 'Not Scholar').($confirmed ? ' — IATO confirmed' : ' — awaiting IATO confirmation') : 'No saved LAMP decision',
                'lamp' => ! $decision ? 'Pending Check' : (! $confirmed ? 'Awaiting IATO' : ($decision === 'yes' ? 'Match' : 'Not Scholar')),
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
