<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    /**
     * Applicants: the master pipeline of every division (Integrated School, College, Graduate Programs,
     * College of Law, iPACE, ETEEAP).
     *
     * IS, College, Graduate Programs and ETEEAP come from the database (Import Data, via ApplicantRecords).
     * The applicants table has no Law or iPACE level yet, so those two still use their Records pages'
     * placeholder people.
     */
    public function index()
    {
        $user = auth()->user();

        return view('applicants.pipeline', [
            'academicYear' => ApplicantRecords::academicYear(),
            'divisions' => self::DIVISIONS,
            'applicants' => self::pipeline(),
            // Exam result editing is only wired up for the divisions backed by real
            // database records — College of Law and iPACE still use placeholder data.
            'canEditExam' => [
                'Integrated School' => $user->canEdit('is'),
                'College' => $user->canEdit('college'),
                'Graduate Programs' => $user->canEdit('graduate_school'),
                'ETEEAP' => $user->canEdit('eteeap'),
            ],
            'examStatuses' => ApplicantRecords::EXAM_STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $scopeToLevel = ['college' => 'college', 'is' => 'is'];
        $level = $scopeToLevel[$request->input('scope')] ?? null;

        if (! $level || ! $request->user()->canEdit($level)) {
            abort(403, 'You do not have permission to add applicants here.');
        }

        $validated = $request->validate([
            'last' => 'required|string|max:100',
            'first' => 'required|string|max:100',
            'mi' => 'nullable|string|max:5',
            'gender' => 'required|in:Male,Female',
            'email' => 'nullable|email|max:150',
            'contact' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'group' => 'required|string|max:100',
            'program' => 'required|string|max:150',
            'status' => 'required|in:Pending,For Exam,Passed,Not Qualified,Paid',
            'applied' => 'required|date',
        ]);

        $referenceNumber = 'APP-' . now()->format('Y') . '-' . str_pad((string) (\App\Models\Applicant::max('id') + 1), 4, '0', STR_PAD_LEFT);

        $applicant = \App\Models\Applicant::create([
            'reference_number' => $referenceNumber,
            'school_year' => \App\Models\Applicant::max('school_year') ?? (now()->format('Y') . '-' . (now()->year + 1)),
            'level' => $level,
            'sub_level' => $level === 'is' ? $this->subLevelFromGroup($validated['group']) : null,
            'last_name' => $validated['last'],
            'first_name' => $validated['first'],
            'gender' => strtolower($validated['gender']),
            'program_or_track' => $validated['program'],
            'college' => $level === 'college' ? $validated['group'] : null,
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        \Illuminate\Support\Facades\DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $validated['applied']]);

        $this->applyStatus($applicant, $validated['status']);

        $module = $level === 'college' ? 'College Records' : 'Integrated School';
        \App\Models\AuditLog::record(
            'create',
            $module,
            'Applicant Created',
            "Added new applicant **{$validated['last']}, {$validated['first']}** — {$validated['group']} · {$validated['program']}",
        );

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, \App\Models\Applicant $applicant)
    {
        if (! $request->user()->canEdit($applicant->level)) {
            abort(403, 'You do not have permission to edit this record.');
        }

        $validated = $request->validate([
            'last' => 'required|string|max:100',
            'first' => 'required|string|max:100',
            'mi' => 'nullable|string|max:5',
            'gender' => 'required|in:Male,Female',
            'email' => 'nullable|email|max:150',
            'contact' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'group' => 'required|string|max:100',
            'program' => 'required|string|max:150',
            'status' => 'required|in:Pending,For Exam,Passed,Not Qualified,Paid',
            'applied' => 'required|date',
        ]);

        $beforeStatus = ApplicantRecords::status($applicant);
        $name = "{$applicant->last_name}, {$applicant->first_name}";

        $applicant->update([
            'last_name' => $validated['last'],
            'first_name' => $validated['first'],
            'gender' => strtolower($validated['gender']),
            'program_or_track' => $validated['program'],
            'college' => $applicant->level === 'college' ? $validated['group'] : $applicant->college,
            'sub_level' => $applicant->level === 'is' ? $this->subLevelFromGroup($validated['group']) : $applicant->sub_level,
            'email' => $validated['email'] ?? null,
            'contact_number' => $validated['contact'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        \Illuminate\Support\Facades\DB::table('applicants')->where('id', $applicant->id)->update(['created_at' => $validated['applied']]);

        $this->applyStatus($applicant, $validated['status']);

        $module = $applicant->level === 'college' ? 'College Records' : 'Integrated School';
        \App\Models\AuditLog::record(
            'edit',
            $module,
            'Record Edited',
            "Edited record of **{$name}** — status changed to **{$validated['status']}**",
            null,
            $beforeStatus !== $validated['status'] ? [['field' => 'Status', 'before' => $beforeStatus, 'after' => $validated['status']]] : null,
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, \App\Models\Applicant $applicant)
    {
        if (! $request->user()->canEdit($applicant->level)) {
            abort(403, 'You do not have permission to remove this record.');
        }

        $name = "{$applicant->last_name}, {$applicant->first_name}";
        $refNumber = $applicant->reference_number;
        $module = $applicant->level === 'college' ? 'College Records' : 'Integrated School';

        $applicant->update(['archived_at' => now()]);

        \App\Models\AuditLog::record('delete', $module, 'Applicant Removed', "Removed applicant **{$name}** ({$refNumber})");

        return response()->json(['ok' => true]);
    }

    public function restore(Request $request, \App\Models\Applicant $applicant)
    {
        if (! $request->user()->canEdit($applicant->level)) {
            abort(403);
        }

        $module = $applicant->level === 'college' ? 'College Records' : 'Integrated School';

        $applicant->update(['archived_at' => null]);

        \App\Models\AuditLog::record('edit', $module, 'Applicant Restored', "Restored applicant **{$applicant->last_name}, {$applicant->first_name}** ({$applicant->reference_number})");

        return response()->json(['ok' => true]);
    }

    /**
     * Update just the entrance exam result (Pass / Fail / Waitlisted / Reconsidered / etc.)
     * from the Applicants pipeline drawer, without going through the full Add/Edit form.
     * A passing result (Passed, Qualified for Another Program, Reconsidered) issues the
     * Notice of Admission automatically — a failing or still-pending result revokes it.
     */
    public function updateExamStatus(Request $request, \App\Models\Applicant $applicant)
    {
        if (! $request->user()->canEdit($applicant->level)) {
            abort(403, 'You do not have permission to edit this record.');
        }

        $validated = $request->validate([
            'admission_test_status' => 'required|in:' . implode(',', array_keys(\App\Support\ApplicantRecords::EXAM_STATUSES)),
        ]);

        $before = $applicant->admission_test_status;
        $passing = in_array($validated['admission_test_status'], ['passed', 'qualified_other_degree', 'reconsidered']);

        $applicant->update([
            'admission_test_status' => $validated['admission_test_status'],
            'noa_issued' => $passing,
        ]);

        $module = [
            'is' => 'Integrated School', 'college' => 'College Records',
            'graduate_school' => 'Graduate Programs', 'eteeap' => 'ETEEAP',
        ][$applicant->level] ?? 'Applicants';

        \App\Models\AuditLog::record(
            'edit',
            $module,
            'Exam Result Updated',
            "Updated exam result for **{$applicant->last_name}, {$applicant->first_name}** ({$applicant->reference_number})",
            null,
            [['field' => 'Exam Result', 'before' => \App\Support\ApplicantRecords::EXAM_STATUSES[$before] ?? $before, 'after' => \App\Support\ApplicantRecords::EXAM_STATUSES[$validated['admission_test_status']]]],
        );

        $reservationPaid = \Illuminate\Support\Facades\DB::table('payments')
            ->where('applicant_id', $applicant->id)
            ->where('fee_type', 'reservation_fee')
            ->where('status', 'paid')
            ->exists();

        return response()->json([
            'ok' => true,
            'exam_status' => $applicant->admission_test_status,
            'exam_label' => \App\Support\ApplicantRecords::EXAM_STATUSES[$applicant->admission_test_status],
            'noa_issued' => $applicant->noa_issued,
            'status' => \App\Support\ApplicantRecords::status($applicant->fresh(), $reservationPaid),
        ]);
    }

    protected function applyStatus(\App\Models\Applicant $applicant, string $status): void
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
            \Illuminate\Support\Facades\DB::table('payments')->updateOrInsert(
                ['applicant_id' => $applicant->id, 'fee_type' => 'reservation_fee'],
                ['status' => 'paid', 'paid_at' => now(), 'amount' => 2000]
            );
        } else {
            \Illuminate\Support\Facades\DB::table('payments')
                ->where('applicant_id', $applicant->id)
                ->where('fee_type', 'reservation_fee')
                ->delete();
        }
    }

    protected function subLevelFromGroup(string $group): ?string
    {
        return match ($group) {
            'Preschool' => 'kindergarten',
            'Grade School' => 'grade_school',
            'Junior High' => 'jhs',
            'Senior High' => 'shs',
            default => null,
        };
    }

    /**
     * The divisions, in sidebar order.
     * group: 'ad' = Academic Divisions, 'al' = Alternative Learning (the two filters on the page).
     * dept: what the Department column means for that division. colors: [text, background] for its tag.
     * records: its Records page (null = not built yet).
     */
    const DIVISIONS = [
        'Integrated School' => ['group' => 'ad', 'icon' => 'bi-building', 'dept' => 'Level', 'colors' => ['#00674F', '#E6F5EF'], 'records' => '/records/is'],
        'College' => ['group' => 'ad', 'icon' => 'bi-mortarboard', 'dept' => 'College', 'colors' => ['#2558D9', '#EAF1FF'], 'records' => '/records/college'],
        'Graduate Programs' => ['group' => 'ad', 'icon' => 'bi-journal-bookmark', 'dept' => 'Program', 'colors' => ['#7C3AED', '#F3EAFF'], 'records' => '/records/graduate'],
        'College of Law' => ['group' => 'ad', 'icon' => 'bi-bank2', 'dept' => 'Program', 'colors' => ['#8A1538', '#FBE9EF'], 'records' => '/records/law'],
        'iPACE' => ['group' => 'al', 'icon' => 'bi-laptop', 'dept' => 'Program', 'colors' => ['#B96000', '#FFF3E6'], 'records' => '/records/ipace'],
        'ETEEAP' => ['group' => 'al', 'icon' => 'bi-briefcase', 'dept' => 'Program', 'colors' => ['#0E7490', '#E3F5F9'], 'records' => '/records/eteeap'],
    ];

    /**
     * Every applicant from every division, in one shape:
     * id, name, division, dept (short code shown in the table), dept_label (full name),
     * program, scholarship ('' = none), status, applied (Y-m-d) and the drawer details.
     */
    public static function pipeline(): array
    {
        $row = fn (array $a, string $division, string $dept, string $deptLabel, string $program) => [
            'id' => $a['id'],
            'name' => trim("{$a['last']}, {$a['first']} " . ($a['mi'] ? "{$a['mi']}." : '')),
            'division' => $division,
            'dept' => $dept,
            'dept_label' => $deptLabel,
            'program' => $program,
            'scholarship' => ! empty($a['scholar']) ? 'Scholar' : '',
            'exam_paid' => ! empty($a['exam_paid']),
            'status' => $a['status'],
            // Raw admission_test_status, for the editable Exam Result control (null on
            // the still-placeholder College of Law / iPACE rows — editing is hidden then)
            'exam_status' => $a['exam_status'] ?? null,
            'applied' => $a['applied'],
            'dob' => $a['dob'],
            'gender' => $a['gender'],
            'email' => $a['email'],
            'contact' => $a['contact'],
            'address' => $a['address'],
            'exam' => $a['exam'],
            'paid' => $a['paid'],
            'amount' => $a['amount'],
        ];

        $isNames = ['Preschool' => 'Preschool', 'Grade School' => 'GS', 'Junior High' => 'JHS', 'Senior High' => 'SHS'];
        $all = [];

        foreach (ApplicantRecords::forLevel('is') as $a) {
            $all[] = $row($a, 'Integrated School', $isNames[$a['sub_level']], $a['sub_level'], $a['program']);
        }
        foreach (ApplicantRecords::forLevel('college') as $a) {
            $all[] = $row($a, 'College', $a['college'], $a['college'], $a['program']);
        }
        foreach (ApplicantRecords::forLevel('graduate_school') as $a) {
            $all[] = $row($a, 'Graduate Programs', $a['group'], $a['program_label'], $a['program_label']);
        }
        foreach (ApplicantRecords::forLevel('eteeap') as $a) {
            $all[] = $row($a, 'ETEEAP', $a['group'], $a['program_label'], $a['program_label']);
        }

        foreach (LawRecordController::sample() as $a) {
            $all[] = $row($a, 'College of Law', $a['group'], $a['program_label'], $a['program_label']);
        }
        foreach (IpaceRecordController::sample() as $a) {
            $all[] = $row($a, 'iPACE', $a['group'], $a['program_label'], "{$a['program_label']} · {$a['program']}");
        }

        return $all;
    }

    /**
     * The placeholder applicants. Public and static so other pages
     * (like College Records) can reuse the same made-up people.
     */
    public static function sample(): array
    {
        return [
            ['id' => 'APP-2026-0381', 'unit' => 'college', 'last' => 'Santos', 'first' => 'Maria Isabel', 'mi' => 'L', 'college' => 'CITE', 'program' => 'BS Computer Science', 'status' => 'Enrolled', 'applied' => '2026-05-14', 'updated' => '2026-06-08 09:14', 'dob' => '2005-03-03', 'gender' => 'Female', 'email' => 'm.santos@example.com', 'contact' => '0917 000 0381', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 8:00 AM', 'paid' => '2026-06-03', 'amount' => 2000],
            ['id' => 'APP-2026-0382', 'unit' => 'college', 'last' => 'Reyes', 'first' => 'Joshua', 'mi' => 'D', 'college' => 'CITE', 'program' => 'BS Information Technology', 'status' => 'Paid', 'applied' => '2026-05-16', 'updated' => '2026-06-08 10:02', 'dob' => '2004-07-22', 'gender' => 'Male', 'email' => 'j.reyes@example.com', 'contact' => '0917 000 0382', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => '2026-06-05', 'amount' => 2000],
            ['id' => 'APP-2026-0383', 'unit' => 'college', 'last' => 'Dela Cruz', 'first' => 'Ana Patricia', 'mi' => 'M', 'college' => 'CBEAM', 'program' => 'BS Accountancy', 'status' => 'For Exam', 'applied' => '2026-05-18', 'updated' => '2026-06-07 15:45', 'dob' => '2005-01-10', 'gender' => 'Female', 'email' => 'a.delacruz@example.com', 'contact' => '0917 000 0383', 'address' => 'San Jose, Batangas', 'exam' => 'Jun 20, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0384', 'unit' => 'college', 'last' => 'Garcia', 'first' => 'Mark Anthony', 'mi' => 'R', 'college' => 'CIHTM', 'program' => 'BS Tourism Management', 'status' => 'Enrolled', 'applied' => '2026-05-20', 'updated' => '2026-06-07 13:20', 'dob' => '2004-09-05', 'gender' => 'Male', 'email' => 'm.garcia@example.com', 'contact' => '0917 000 0384', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 12, 2026 · 10:00 AM', 'paid' => '2026-06-01', 'amount' => 2000],
            ['id' => 'APP-2026-0385', 'unit' => 'college', 'last' => 'Villareal', 'first' => 'Pia', 'mi' => 'V', 'college' => 'CITE', 'program' => 'BS Computer Science', 'status' => 'Pending', 'applied' => '2026-05-22', 'updated' => '2026-06-06 14:30', 'dob' => '2005-04-18', 'gender' => 'Female', 'email' => 'p.villareal@example.com', 'contact' => '0917 000 0385', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0386', 'unit' => 'college', 'last' => 'Mendoza', 'first' => 'Carlo', 'mi' => 'B', 'college' => 'CEAS', 'program' => 'BS Psychology', 'status' => 'For Exam', 'applied' => '2026-05-23', 'updated' => '2026-06-06 11:05', 'dob' => '2005-11-30', 'gender' => 'Male', 'email' => 'c.mendoza@example.com', 'contact' => '0917 000 0386', 'address' => 'Malvar, Batangas', 'exam' => 'Jun 21, 2026 · 1:00 PM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0387', 'unit' => 'college', 'last' => 'Aquino', 'first' => 'Bea', 'mi' => 'S', 'college' => 'College of Nursing', 'program' => 'BS Nursing', 'status' => 'Paid', 'applied' => '2026-05-24', 'updated' => '2026-06-05 16:40', 'dob' => '2005-06-14', 'gender' => 'Female', 'email' => 'b.aquino@example.com', 'contact' => '0917 000 0387', 'address' => 'Rosario, Batangas', 'exam' => 'Jun 14, 2026 · 8:00 AM', 'paid' => '2026-06-04', 'amount' => 2000],
            ['id' => 'APP-2026-0388', 'unit' => 'college', 'last' => 'Ramos', 'first' => 'Kevin', 'mi' => 'T', 'college' => 'CBEAM', 'program' => 'BS Business Administration', 'status' => 'Pending', 'applied' => '2026-05-25', 'updated' => '2026-06-05 09:10', 'dob' => '2004-12-02', 'gender' => 'Male', 'email' => 'k.ramos@example.com', 'contact' => '0917 000 0388', 'address' => 'Padre Garcia, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0389', 'unit' => 'college', 'last' => 'Torres', 'first' => 'Nicole', 'mi' => 'A', 'college' => 'CEAS', 'program' => 'AB Communication', 'status' => 'Enrolled', 'applied' => '2026-05-26', 'updated' => '2026-06-04 14:55', 'dob' => '2005-02-27', 'gender' => 'Female', 'email' => 'n.torres@example.com', 'contact' => '0917 000 0389', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 9:00 AM', 'paid' => '2026-05-30', 'amount' => 2000],
            ['id' => 'APP-2026-0390', 'unit' => 'college', 'last' => 'Castillo', 'first' => 'Paolo', 'mi' => 'J', 'college' => 'CITE', 'program' => 'BS Computer Engineering', 'status' => 'For Exam', 'applied' => '2026-05-27', 'updated' => '2026-06-04 10:20', 'dob' => '2005-08-09', 'gender' => 'Male', 'email' => 'p.castillo@example.com', 'contact' => '0917 000 0390', 'address' => 'Cuenca, Batangas', 'exam' => 'Jun 22, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-2026-0391', 'unit' => 'college', 'last' => 'Navarro', 'first' => 'Janine', 'mi' => 'C', 'college' => 'CIHTM', 'program' => 'BS Hospitality Management', 'status' => 'Paid', 'applied' => '2026-05-28', 'updated' => '2026-06-03 15:15', 'dob' => '2005-05-19', 'gender' => 'Female', 'email' => 'j.navarro@example.com', 'contact' => '0917 000 0391', 'address' => 'Ibaan, Batangas', 'exam' => 'Jun 13, 2026 · 1:00 PM', 'paid' => '2026-06-02', 'amount' => 2000],
            ['id' => 'APP-2026-0392', 'unit' => 'college', 'last' => 'Bautista', 'first' => 'Luis', 'mi' => 'E', 'college' => 'College of Law', 'program' => 'Juris Doctor', 'status' => 'Pending', 'applied' => '2026-05-29', 'updated' => '2026-06-03 08:45', 'dob' => '2001-10-11', 'gender' => 'Male', 'email' => 'l.bautista@example.com', 'contact' => '0917 000 0392', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0101', 'unit' => 'is', 'last' => 'Lim', 'first' => 'Ethan', 'mi' => 'G', 'level' => 'Grade 11', 'program' => 'STEM', 'status' => 'Enrolled', 'applied' => '2026-05-10', 'updated' => '2026-06-08 08:30', 'dob' => '2010-03-03', 'gender' => 'Male', 'email' => 'parent.lim@example.com', 'contact' => '0917 000 0101', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 8:00 AM', 'paid' => '2026-06-01', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0102', 'unit' => 'is', 'last' => 'Soriano', 'first' => 'Andrea', 'mi' => 'P', 'level' => 'Grade 11', 'program' => 'ABM', 'status' => 'Paid', 'applied' => '2026-05-12', 'updated' => '2026-06-08 09:00', 'dob' => '2010-04-18', 'gender' => 'Female', 'email' => 'parent.soriano@example.com', 'contact' => '0917 000 0102', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 14, 2026 · 9:00 AM', 'paid' => '2026-06-03', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0103', 'unit' => 'is', 'last' => 'Pascual', 'first' => 'Miguel', 'mi' => 'F', 'level' => 'Grade 7', 'program' => 'Junior High School', 'status' => 'For Exam', 'applied' => '2026-05-13', 'updated' => '2026-06-07 10:10', 'dob' => '2014-01-21', 'gender' => 'Male', 'email' => 'parent.pascual@example.com', 'contact' => '0917 000 0103', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 19, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0104', 'unit' => 'is', 'last' => 'Del Rosario', 'first' => 'Sofia', 'mi' => 'H', 'level' => 'Kindergarten', 'program' => 'Kindergarten', 'status' => 'Pending', 'applied' => '2026-05-15', 'updated' => '2026-06-06 13:25', 'dob' => '2021-07-08', 'gender' => 'Female', 'email' => 'parent.delrosario@example.com', 'contact' => '0917 000 0104', 'address' => 'Mataas na Kahoy, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0105', 'unit' => 'is', 'last' => 'Francisco', 'first' => 'Gabriel', 'mi' => 'N', 'level' => 'Grade 11', 'program' => 'HUMSS', 'status' => 'Enrolled', 'applied' => '2026-05-17', 'updated' => '2026-06-05 11:50', 'dob' => '2010-09-12', 'gender' => 'Male', 'email' => 'parent.francisco@example.com', 'contact' => '0917 000 0105', 'address' => 'San Juan, Batangas', 'exam' => 'Jun 11, 2026 · 8:00 AM', 'paid' => '2026-06-02', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0106', 'unit' => 'is', 'last' => 'Morales', 'first' => 'Claire', 'mi' => 'D', 'level' => 'Grade 7', 'program' => 'Junior High School', 'status' => 'Paid', 'applied' => '2026-05-19', 'updated' => '2026-06-04 15:35', 'dob' => '2014-05-30', 'gender' => 'Female', 'email' => 'parent.morales@example.com', 'contact' => '0917 000 0106', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => '2026-06-04', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0107', 'unit' => 'is', 'last' => 'Evangelista', 'first' => 'Lucas', 'mi' => 'R', 'level' => 'Grade 1', 'program' => 'Grade School', 'status' => 'Enrolled', 'applied' => '2026-05-11', 'updated' => '2026-06-06 10:40', 'dob' => '2019-02-14', 'gender' => 'Male', 'email' => 'parent.evangelista@example.com', 'contact' => '0917 000 0107', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 9, 2026 · 9:00 AM', 'paid' => '2026-05-30', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0108', 'unit' => 'is', 'last' => 'Manalo', 'first' => 'Isabela', 'mi' => 'C', 'level' => 'Grade 4', 'program' => 'Grade School', 'status' => 'For Exam', 'applied' => '2026-05-20', 'updated' => '2026-06-05 14:15', 'dob' => '2016-08-03', 'gender' => 'Female', 'email' => 'parent.manalo@example.com', 'contact' => '0917 000 0108', 'address' => 'Rosario, Batangas', 'exam' => 'Jun 20, 2026 · 9:00 AM', 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0109', 'unit' => 'is', 'last' => 'Ocampo', 'first' => 'Nathan', 'mi' => 'L', 'level' => 'Nursery', 'program' => 'Preschool', 'status' => 'Paid', 'applied' => '2026-05-21', 'updated' => '2026-06-04 09:05', 'dob' => '2022-01-27', 'gender' => 'Male', 'email' => 'parent.ocampo@example.com', 'contact' => '0917 000 0109', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 13, 2026 · 9:00 AM', 'paid' => '2026-06-03', 'amount' => 1500],
            ['id' => 'APP-IS-2026-0110', 'unit' => 'is', 'last' => 'Salazar', 'first' => 'Bianca', 'mi' => 'T', 'level' => 'Grade 9', 'program' => 'Junior High School', 'status' => 'Pending', 'applied' => '2026-05-24', 'updated' => '2026-06-03 16:20', 'dob' => '2012-06-11', 'gender' => 'Female', 'email' => 'parent.salazar@example.com', 'contact' => '0917 000 0110', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null, 'amount' => null],
            ['id' => 'APP-IS-2026-0111', 'unit' => 'is', 'last' => 'Dimaculangan', 'first' => 'Rafael', 'mi' => 'S', 'level' => 'Grade 12', 'program' => 'STEM', 'status' => 'For Exam', 'applied' => '2026-05-26', 'updated' => '2026-06-02 11:30', 'dob' => '2009-10-05', 'gender' => 'Male', 'email' => 'parent.dimaculangan@example.com', 'contact' => '0917 000 0111', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 23, 2026 · 8:00 AM', 'paid' => null, 'amount' => null],
        ];
    }
}