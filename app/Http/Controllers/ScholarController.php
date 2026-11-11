<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\LampScholarRecord;
use App\Support\ApplicantRecords;
use App\Support\ApplicantSearch;
use App\Support\LampRequests;
use Illuminate\Database\Eloquent\Builder;
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
        $filters = $request->validate([
            'year' => 'nullable|string|max:9', 'search' => 'nullable|string|max:150', 'unit' => 'nullable|string|max:150',
            'exam' => 'nullable|in:'.implode(',', array_keys(self::EXAM_LABELS)), 'status' => 'nullable|in:All,Pending,For Exam,Paid,Enrolled',
            'scholarship' => 'nullable|in:Active,Probationary,Cancelled', 'lamp' => 'nullable|in:review,Pending Check,Awaiting IATO,Match,Not Scholar,Discrepancy,Not Found',
            'sort' => 'nullable|in:newest,az,review', 'page' => 'nullable|integer|min:1', 'export' => 'nullable|in:csv', 'open' => 'nullable|string|max:30',
        ]);
        $schoolYears = Applicant::whereNull('archived_at')->distinct()->orderByDesc('school_year')->pluck('school_year')->all();
        $linked = ! empty($filters['open']) ? Applicant::where('reference_number', $filters['open'])->whereNull('archived_at')->where('applying_for_scholarship', true)->first() : null;
        $schoolYear = $filters['year'] ?? $linked?->school_year ?? $schoolYears[0] ?? now()->year.'-'.(now()->year + 1);
        abort_unless(is_string($schoolYear) && (in_array($schoolYear, $schoolYears, true) || $schoolYears === []), 422, 'Choose an available school year.');

        $base = Applicant::where('school_year', $schoolYear)->whereNull('archived_at')->where('applying_for_scholarship', true);
        $unitOptions = (clone $base)->select(['level', 'sub_level', 'college', 'program_or_track'])->distinct()->get()->map(fn ($a) => self::unit($a))->filter()->unique()->sort()->values()->all();
        $decided = fn () => LampScholarRecord::whereNotNull('applicant_id')->whereIn('decision', ['yes', 'no'])->select('applicant_id');
        $scholarTotals = [
            'total' => (clone $base)->count(),
            'passed' => (clone $base)->whereIn('admission_test_status', ['passed', 'qualified_other_degree', 'reconsidered'])->count(),
            'confirmed' => (clone $base)->whereIn('id', LampScholarRecord::where('decision', 'yes')->where('iato_status', 'confirmed')->select('applicant_id'))->count(),
            'waiting' => (clone $base)->whereIn('admission_test_status', ['passed', 'qualified_other_degree', 'reconsidered'])->whereNotIn('id', $decided())->count(),
        ];
        $query = clone $base;
        $this->filterScholars($query, $filters);
        ApplicantSearch::apply($query, trim($filters['search'] ?? ''), ($filters['sort'] ?? '') === 'az');
        if (($filters['sort'] ?? '') === 'review') {
            $query->orderByRaw("CASE WHEN id IN (SELECT applicant_id FROM lamp_scholar_records WHERE decision = 'yes' AND iato_status = 'confirmed') THEN 1 ELSE 0 END");
        }
        $query->latest()->orderByDesc('id');
        if (($filters['export'] ?? '') === 'csv') {
            return response()->streamDownload(function () use ($query, $schoolYear) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['App No.', 'Name', 'Unit', 'Program / Grade', 'Exam result', 'LAMP check', 'Date applied'], escape: '');
                foreach ($query->lazy(500)->chunk(500) as $models) {
                    foreach (self::sample($schoolYear, collect($models->all())) as $row) {
                        $values = [$row['id'], $row['name'], $row['unit'], $row['program'], $row['exam_label'], $row['lamp'], $row['applied_label']];
                        fputcsv($stream, array_map(fn ($v) => preg_match('/^[\s]*[=+@-]/', (string) $v) ? "'".$v : $v, $values), escape: '');
                    }
                }
                fclose($stream);
            }, 'scholar-records-'.$schoolYear.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }
        $paginator = $query->paginate(10);
        if ($paginator->currentPage() > $paginator->lastPage()) {
            $paginator = $query->paginate(10, ['*'], 'page', $paginator->lastPage());
        }
        $scholars = self::sample($schoolYear, $paginator->getCollection());
        $pagination = ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage(), 'total' => $paginator->total(), 'from' => $paginator->firstItem(), 'to' => $paginator->lastItem()];
        $openScholar = $linked && $linked->school_year === $schoolYear ? self::sample($schoolYear, collect([$linked]))->first() : null;

        $data = [
            'academicYear' => ApplicantRecords::academicYear(),
            // Roles with the edit permission can change applicant records.
            'canEdit' => $user->canAccess('edit'),
            'scholars' => $scholars,
            'pagination' => $pagination, 'filters' => $filters, 'unitOptions' => $unitOptions, 'scholarTotals' => $scholarTotals, 'openScholar' => $openScholar,
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
        ];
        if ($request->expectsJson()) {
            return response()->json(['scholars' => $scholars, 'pagination' => $pagination, 'html' => view('scholars.partials.rows', $data)->render()]);
        }

        return view('scholars.index', $data);
    }

    private static function unit(Applicant $a): ?string
    {
        return match ($a->level) {
            'is' => 'Integrated School · '.(ApplicantRecords::IS_SUB_LEVELS[$a->sub_level] ?? ucfirst($a->sub_level ?? '')),
            'college' => $a->college ?? $a->program_or_track, 'graduate_school' => 'Graduate Programs', 'eteeap' => 'ETEEAP',
            default => $a->program_or_track,
        };
    }

    private function filterScholars(Builder $query, array $filters): void
    {
        if (! empty($filters['unit'])) {
            $unit = $filters['unit'];
            $sub = array_search(str_replace('Integrated School · ', '', $unit), ApplicantRecords::IS_SUB_LEVELS, true);
            $query->where(function ($q) use ($unit, $sub) {
                $q->where(fn ($college) => $college->where('level', 'college')->where(fn ($c) => $c->where('college', $unit)->orWhere(fn ($p) => $p->whereNull('college')->where('program_or_track', $unit))));
                if ($sub !== false && str_starts_with($unit, 'Integrated School · ')) {
                    $q->orWhere(fn ($is) => $is->where('level', 'is')->where('sub_level', $sub));
                }
                if ($unit === 'Graduate Programs') {
                    $q->orWhere('level', 'graduate_school');
                }
                if ($unit === 'ETEEAP') {
                    $q->orWhere('level', 'eteeap');
                }
                $q->orWhere(fn ($other) => $other->whereNotIn('level', ['college', 'is', 'graduate_school', 'eteeap'])->where('program_or_track', $unit));
            });
        }
        if (! empty($filters['exam'])) {
            $query->where('admission_test_status', $filters['exam']);
        }
        if (! empty($filters['status']) && $filters['status'] !== 'All') {
            $query->whereIn('application_status', match ($filters['status']) {
                'Pending' => ['pooling', 'not_submitted'], 'For Exam' => ['submitted'], 'Enrolled' => ['completed'], default => []
            });
        }
        if (! empty($filters['scholarship']) && $filters['scholarship'] !== 'Active') {
            $query->whereRaw('1 = 0');
        }
        if (! empty($filters['lamp'])) {
            $lamp = $filters['lamp'];
            $checks = LampScholarRecord::whereNotNull('applicant_id')->whereIn('decision', ['yes', 'no']);
            if ($lamp === 'Pending Check') {
                $query->whereNotIn('id', $checks->select('applicant_id'));
            } elseif ($lamp === 'Awaiting IATO') {
                $query->whereIn('id', $checks->where(fn ($q) => $q->where('iato_status', '!=', 'confirmed')->orWhereNull('iato_status'))->select('applicant_id'));
            } elseif (in_array($lamp, ['Match', 'Not Scholar'], true)) {
                $query->whereIn('id', $checks->where('decision', $lamp === 'Match' ? 'yes' : 'no')->where('iato_status', 'confirmed')->select('applicant_id'));
            } else {
                $query->whereRaw('1 = 0');
            }
        }
    }

    // "Jasmine Cruz" -> "Cruz, Jasmine" (same as the "Signed in as" card)
    private static function lastFirst(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $last = array_pop($parts);

        return $parts ? $last.', '.implode(' ', $parts) : $last;
    }

    public static function sample(?string $schoolYear = null, ?Collection $applicants = null): Collection
    {
        $date = fn ($value, $format) => $value ? Carbon::parse($value)->format($format) : null;

        $statusLabels = [
            'pooling' => 'Pending',
            'not_submitted' => 'Pending',
            'submitted' => 'For Exam',
            'completed' => 'Enrolled',
        ];

        $applicants ??= Applicant::where('applying_for_scholarship', true)
            ->whereNull('archived_at')
            ->where('school_year', $schoolYear ?? Applicant::max('school_year') ?? now()->year.'-'.(now()->year + 1))->get();
        $checks = LampScholarRecord::whereIn('applicant_id', $applicants->pluck('id'))->get()->keyBy('applicant_id');

        return $applicants->map(function (Applicant $a) use ($date, $statusLabels, $checks) {
            [$first, $mi] = ApplicantRecords::nameParts($a);
            $check = $checks->get($a->id);
            $decision = in_array($check?->decision, ['yes', 'no'], true) ? $check->decision : null;
            $confirmed = $decision && $check->iato_status === 'confirmed';
            $unit = self::unit($a);

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
