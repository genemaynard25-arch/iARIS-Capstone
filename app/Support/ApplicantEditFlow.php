<?php

namespace App\Support;

use App\Models\Applicant;
use App\Models\ReviewQueueItem;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The actual field-level work behind the Applicants pipeline's add/edit/remove/restore/exam-status
 * actions - shared by ApplicantController (applies immediately for admins) and
 * ReviewQueueController::approve() (applies a staged IATO Staff submission once approved).
 * Keeping this here, not in either controller, means both call the exact same code.
 */
class ApplicantEditFlow
{
    public static function moduleFor(string $level): string
    {
        return match ($level) {
            'college' => 'College Records',
            'is' => 'Integrated School',
            'graduate_school' => 'Graduate Programs',
            'eteeap' => 'ETEEAP',
            'law' => 'College of Law',
            default => 'Applicants',
        };
    }

    public static function subLevelFromGroup(string $group): ?string
    {
        return match ($group) {
            'Preschool' => 'kindergarten',
            'Grade School' => 'grade_school',
            'Junior High' => 'jhs',
            'Senior High' => 'shs',
            default => null,
        };
    }

    public static function normalizeContact(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $compact = preg_replace('/[\s()\-]+/u', '', trim($value));
        if (preg_match('/\A09[0-9]{9}\z/', $compact)) {
            return '+63'.substr($compact, 1);
        }

        return $compact;
    }

    public static function contactRules(): array
    {
        return ['nullable', 'string', 'max:13', 'regex:/\A\+639[0-9]{9}\z/'];
    }

    /** Additional fields collected by the manual form; optional for older API clients. */
    public static function detailRules(?string $examResult = null): array
    {
        return [
            'year_level' => 'sometimes|nullable|string|max:30',
            'exam_date' => 'nullable|required_with:exam_time|date_format:Y-m-d',
            'exam_time' => 'nullable|required_with:exam_date|date_format:H:i',
            'exam_result' => 'sometimes|required|in:'.implode(',', array_keys(ApplicantRecords::EXAM_STATUSES)),
            'applying_for_scholarship' => 'sometimes|boolean',
            'exam_fee_paid' => 'sometimes|boolean',
            'reservation_fee_paid' => ['sometimes', 'boolean', function ($attribute, $value, $fail) use ($examResult) {
                if ($value && ! in_array($examResult, ['passed', 'reconsidered'], true)) {
                    $fail('Reservation payment can only be confirmed for Passed or Reconsidered applicants.');
                }
            }],
            'pwd' => 'sometimes|boolean',
            'ssen' => 'sometimes|boolean',
            'feeder_school' => 'sometimes|nullable|string|max:150',
            'school_address' => 'sometimes|nullable|string|max:255',
            'school_classification' => 'sometimes|nullable|string|max:30',
            'nationality' => 'sometimes|nullable|string|max:50',
            'permit_no' => 'sometimes|nullable|string|max:50',
            'gwa' => 'sometimes|nullable|numeric|min:0|max:99.99',
            'student_type' => 'sometimes|nullable|string|max:50',
            'learning_modalities' => 'sometimes|nullable|string|max:50',
        ];
    }

    private const DETAIL_FIELDS = [
        'year_level', 'applying_for_scholarship', 'pwd', 'ssen',
        'feeder_school', 'school_address', 'school_classification', 'nationality',
        'permit_no', 'gwa', 'student_type', 'learning_modalities',
    ];

    private static function applyDetails(Applicant $applicant, array $validated): void
    {
        $details = array_intersect_key($validated, array_flip(self::DETAIL_FIELDS));
        if (array_key_exists('exam_date', $validated)) {
            $details['exam_scheduled_at'] = empty($validated['exam_date']) ? null
                : $validated['exam_date'].' '.($validated['exam_time'] ?? '00:00');
        }
        if ($details) {
            $applicant->update($details);
        }
        // Exam results and Cashier-confirmed payments are independent. Legacy status requests retain their flow.
        if (array_key_exists('exam_result', $validated)) {
            self::applyExamStatus($applicant, $validated['exam_result']);
        } else {
            self::applyStatus($applicant, $validated['status']);
        }
        foreach (['exam_fee_paid' => 'application_fee', 'reservation_fee_paid' => 'reservation_fee'] as $field => $fee) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }
            $payments = DB::table('payments')->where('applicant_id', $applicant->id)->where('fee_type', $fee);
            $status = $validated[$field] ? 'paid' : 'unpaid';
            if ($payments->exists()) {
                // Keep the existing amount and confirmation timestamp when the status is unchanged.
                $payments->where('status', '!=', $status)->update(['status' => $status, 'paid_at' => $validated[$field] ? now() : null]);
            } elseif ($validated[$field]) {
                DB::table('payments')->insert(['applicant_id' => $applicant->id, 'fee_type' => $fee, 'status' => 'paid', 'amount' => null, 'paid_at' => now()]);
            }
        }
    }

    public static function applyStatus(Applicant $applicant, string $status): void
    {
        $map = [
            'Pending' => ['admission_test_status' => 'not_taken', 'application_status' => 'pooling'],
            'For Exam' => ['admission_test_status' => 'took_test', 'application_status' => 'submitted'],
            'Passed' => ['admission_test_status' => 'passed', 'application_status' => 'submitted'],
            'Not Qualified' => ['admission_test_status' => 'failed', 'application_status' => 'submitted'],
            'Paid' => ['admission_test_status' => 'passed', 'application_status' => 'completed'],
        ];

        $applicant->update($map[$status]);

        if ($status === 'Paid') {
            DB::table('payments')->updateOrInsert(
                ['applicant_id' => $applicant->id, 'fee_type' => 'reservation_fee'],
                ['status' => 'paid', 'paid_at' => now(), 'amount' => 2000]
            );
        } else {
            DB::table('payments')->where('applicant_id', $applicant->id)->where('fee_type', 'reservation_fee')->delete();
        }
    }

    /** [label, before, after] triples for the Review Queue drawer - computed before applying anything. */
    public static function diffForUpdate(Applicant $applicant, array $validated): array
    {
        $diff = [];
        $beforeStatus = ApplicantRecords::status($applicant);
        if ($beforeStatus !== $validated['status']) {
            $diff[] = ['Status', $beforeStatus, $validated['status']];
        }

        $fields = [
            'last' => 'Last Name', 'first' => 'First Name', 'mi' => 'Middle Initial', 'email' => 'Email',
            'contact' => 'Contact No.', 'address' => 'Address', 'program' => 'Program / Track',
        ];
        $before = [
            'last' => $applicant->last_name, 'first' => $applicant->first_name, 'mi' => $applicant->middle_initial, 'email' => $applicant->email,
            'contact' => $applicant->contact_number, 'address' => $applicant->address, 'program' => $applicant->program_or_track,
        ];
        foreach ($fields as $key => $label) {
            $after = $validated[$key] ?? null;
            if ((string) $before[$key] !== (string) $after) {
                $diff[] = [$label, $before[$key] ?? '—', $after ?? '—'];
            }
        }

        $groupBefore = $applicant->level === 'college' ? $applicant->college : (ApplicantRecords::IS_SUB_LEVELS[$applicant->sub_level] ?? $applicant->sub_level);
        if ($groupBefore !== $validated['group']) {
            $diff[] = [$applicant->level === 'college' ? 'College' : 'Group', $groupBefore ?? '—', $validated['group']];
        }

        foreach (self::DETAIL_FIELDS as $field) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }
            $beforeValue = $applicant->$field;
            $afterValue = $validated[$field];
            if ((string) $beforeValue !== (string) $afterValue) {
                $diff[] = [ucwords(str_replace('_', ' ', $field)), $beforeValue ?? '—', $afterValue ?? '—'];
            }
        }
        if (isset($validated['exam_result']) && $applicant->admission_test_status !== $validated['exam_result']) {
            $diff[] = ['Exam Result', $applicant->admission_test_status, $validated['exam_result']];
        }
        if (array_key_exists('exam_date', $validated)) {
            $beforeExam = $applicant->exam_scheduled_at?->format('Y-m-d H:i');
            $afterExam = $validated['exam_date'] ? $validated['exam_date'].' '.($validated['exam_time'] ?? '00:00') : null;
            if ($beforeExam !== $afterExam) {
                $diff[] = ['Entrance Exam Schedule', $beforeExam ?? '—', $afterExam ?? '—'];
            }
        }

        foreach (['exam_fee_paid' => 'application_fee', 'reservation_fee_paid' => 'reservation_fee'] as $field => $fee) {
            if (array_key_exists($field, $validated)) {
                $beforePaid = DB::table('payments')->where('applicant_id', $applicant->id)->where('fee_type', $fee)->where('status', 'paid')->exists();
                if ($beforePaid !== (bool) $validated[$field]) {
                    $diff[] = [$fee === 'application_fee' ? 'Exam Fee Paid' : 'Reservation Fee Paid', $beforePaid ? 'Yes' : 'No', $validated[$field] ? 'Yes' : 'No'];
                }
            }
        }

        return $diff;
    }

    public static function diffForCreate(array $validated): array
    {
        $changes = [
            ['Name', '—', trim("{$validated['last']}, {$validated['first']} ".($validated['mi'] ?? ''))],
            [$validated['group'] === null ? 'Group' : 'College/Group', '—', $validated['group']],
            ['Program / Track', '—', $validated['program']],
            ['Status', '—', $validated['status']],
        ];
        foreach (array_merge(self::DETAIL_FIELDS, ['exam_date', 'exam_time', 'exam_result', 'exam_fee_paid', 'reservation_fee_paid']) as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] !== null && $validated[$field] !== '') {
                $changes[] = [ucwords(str_replace('_', ' ', $field)), '—', $validated[$field]];
            }
        }

        return $changes;
    }

    public static function applyUpdate(Applicant $applicant, array $validated): void
    {
        $applicant->update([
            'last_name' => $validated['last'],
            'first_name' => $validated['first'],
            'middle_initial' => mb_strtoupper(rtrim(trim($validated['mi'] ?? ''), '.')) ?: null,
            'gender' => strtolower($validated['gender']),
            'program_or_track' => $validated['program'],
            'college' => $applicant->level === 'college' ? $validated['group'] : $applicant->college,
            'sub_level' => $applicant->level === 'is' ? self::subLevelFromGroup($validated['group']) : $applicant->sub_level,
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $validated['applied']]);
        self::applyDetails($applicant, $validated);
    }

    /** Detect a repeated application without treating a shared family email as one person. */
    public static function ensureNotDuplicate(array $validated, string $level, bool $includePending = false): void
    {
        $normalize = fn ($value) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value)));
        $year = Applicant::max('school_year') ?? now()->year.'-'.(now()->year + 1);
        $candidates = Applicant::where('school_year', $year)->where('level', $level)->whereNull('archived_at')->get();
        if ($includePending) {
            foreach (ReviewQueueItem::where('type', 'edit')->where('status', 'waiting')->get() as $item) {
                $action = $item->pending_action;
                $payload = $action['payload'] ?? [];
                if (($action['action'] ?? null) !== 'create' || ($payload['level'] ?? null) !== $level) {
                    continue;
                }
                $entry = $payload['validated'];
                $candidates->push(new Applicant([
                    'first_name' => $entry['first'], 'last_name' => $entry['last'],
                    'middle_initial' => $entry['mi'] ?? null, 'email' => $entry['email'] ?? null,
                    'contact_number' => $entry['contact'] ?? null, 'program_or_track' => $entry['program'],
                    'reference_number' => 'pending review '.$item->rq_number,
                ]));
            }
        }
        foreach ($candidates as $existing) {
            [$existingFirst, $existingMi] = ApplicantRecords::nameParts($existing);
            if ($normalize($existingFirst) !== $normalize($validated['first']) || $normalize($existing->last_name) !== $normalize($validated['last'])) {
                continue;
            }
            if ($existingMi && ! empty($validated['mi']) && $normalize(rtrim($existingMi, '.')) !== $normalize(rtrim($validated['mi'], '.'))) {
                continue;
            }
            $sameEmail = ! empty($validated['email']) && $normalize($existing->email) === $normalize($validated['email']);
            $sameContact = ! empty($validated['contact']) && preg_replace('/\D/', '', (string) $existing->contact_number) === preg_replace('/\D/', '', $validated['contact']);
            $sameProgram = $normalize($existing->program_or_track) === $normalize($validated['program']);
            if ($sameEmail || $sameContact || $sameProgram) {
                throw ValidationException::withMessages(['first' => 'An application already exists for this person in '.$year.' ('.$existing->reference_number.'). Open the existing record instead.']);
            }
        }
    }

    public static function applyCreate(array $validated, string $level): Applicant
    {
        self::ensureNotDuplicate($validated, $level);
        $referenceNumber = 'APP-'.now()->format('Y').'-'.strtoupper(bin2hex(random_bytes(6)));

        $applicant = Applicant::create([
            'reference_number' => $referenceNumber,
            'recorded_at' => now(),
            'school_year' => Applicant::max('school_year') ?? (now()->format('Y').'-'.(now()->year + 1)),
            'level' => $level,
            'sub_level' => $level === 'is' ? self::subLevelFromGroup($validated['group']) : null,
            'last_name' => $validated['last'],
            'first_name' => $validated['first'],
            'middle_initial' => mb_strtoupper(rtrim(trim($validated['mi'] ?? ''), '.')) ?: null,
            'gender' => strtolower($validated['gender']),
            'program_or_track' => $validated['program'],
            'college' => $level === 'college' ? $validated['group'] : null,
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $validated['applied']]);
        self::applyDetails($applicant, $validated);

        return $applicant;
    }

    public static function applyDestroy(Applicant $applicant): void
    {
        $applicant->update(['archived_at' => now()]);
    }

    public static function applyRestore(Applicant $applicant): void
    {
        try {
            $applicant->update(['archived_at' => null]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['applicant' => 'An active application with this application number already exists for this school year. Review it before restoring this archived copy.']);
        }
    }

    public static function applyExamStatus(Applicant $applicant, string $newStatus): void
    {
        $applicant->update([
            'admission_test_status' => $newStatus,
            'noa_issued' => in_array($newStatus, ['passed', 'qualified_other_degree', 'reconsidered']),
        ]);
    }
}
