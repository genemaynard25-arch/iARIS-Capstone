<?php

namespace App\Support;

use App\Models\Applicant;
use Illuminate\Support\Facades\DB;

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
            'last' => 'Last Name', 'first' => 'First Name', 'email' => 'Email',
            'contact' => 'Contact No.', 'address' => 'Address', 'program' => 'Program / Track',
        ];
        $before = [
            'last' => $applicant->last_name, 'first' => $applicant->first_name, 'email' => $applicant->email,
            'contact' => $applicant->contact_number, 'address' => $applicant->address, 'program' => $applicant->program_or_track,
        ];
        foreach ($fields as $key => $label) {
            $after = $validated[$key] ?? null;
            if ((string) $before[$key] !== (string) $after) {
                $diff[] = [$label, $before[$key] ?? '—', $after ?? '—'];
            }
        }

        $groupBefore = $applicant->level === 'college' ? $applicant->college : $applicant->sub_level;
        if ($groupBefore !== $validated['group']) {
            $diff[] = [$applicant->level === 'college' ? 'College' : 'Group', $groupBefore ?? '—', $validated['group']];
        }

        return $diff;
    }

    public static function diffForCreate(array $validated): array
    {
        return [
            ['Name', '—', "{$validated['last']}, {$validated['first']}"],
            [$validated['group'] === null ? 'Group' : 'College/Group', '—', $validated['group']],
            ['Program / Track', '—', $validated['program']],
            ['Status', '—', $validated['status']],
        ];
    }

    public static function applyUpdate(Applicant $applicant, array $validated): void
    {
        $applicant->update([
            'last_name' => $validated['last'],
            'first_name' => $validated['first'],
            'gender' => strtolower($validated['gender']),
            'program_or_track' => $validated['program'],
            'college' => $applicant->level === 'college' ? $validated['group'] : $applicant->college,
            'sub_level' => $applicant->level === 'is' ? self::subLevelFromGroup($validated['group']) : $applicant->sub_level,
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $validated['applied']]);
        self::applyStatus($applicant, $validated['status']);
    }

    public static function applyCreate(array $validated, string $level): Applicant
    {
        $referenceNumber = 'APP-' . now()->format('Y') . '-' . str_pad((string) (Applicant::max('id') + 1), 4, '0', STR_PAD_LEFT);

        $applicant = Applicant::create([
            'reference_number' => $referenceNumber,
            'school_year' => Applicant::max('school_year') ?? (now()->format('Y') . '-' . (now()->year + 1)),
            'level' => $level,
            'sub_level' => $level === 'is' ? self::subLevelFromGroup($validated['group']) : null,
            'last_name' => $validated['last'],
            'first_name' => $validated['first'],
            'gender' => strtolower($validated['gender']),
            'program_or_track' => $validated['program'],
            'college' => $level === 'college' ? $validated['group'] : null,
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $validated['applied']]);
        self::applyStatus($applicant, $validated['status']);

        return $applicant;
    }

    public static function applyDestroy(Applicant $applicant): void
    {
        $applicant->update(['archived_at' => now()]);
    }

    public static function applyRestore(Applicant $applicant): void
    {
        $applicant->update(['archived_at' => null]);
    }

    public static function applyExamStatus(Applicant $applicant, string $newStatus): void
    {
        $applicant->update([
            'admission_test_status' => $newStatus,
            'noa_issued' => in_array($newStatus, ['passed', 'qualified_other_degree', 'reconsidered']),
        ]);
    }
}
