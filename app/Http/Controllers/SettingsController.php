<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    /**
     * Settings: one page for every role; each role sees only the sections that apply.
     *
     * What is real already:
     *  - Profile and "Your access" read the signed-in user.
     *  - Change password posts to Fortify (PUT /user/password).
     *  - Two-factor shows whether the user's 2FA is on, and links to /two-factor.
     *  - Recent sign-ins come from the sessions table.
     * Notification, report, AI and display preferences are saved in the browser for
     * now (there are no columns for them yet). The view says so.
     */
    public function index()
    {
        $user = auth()->user();

        // Every role gets these; the Executive Office also gets report and display preferences
        // (The LAMP Office and Registrars have no AI button, so no AI section either.)
        $sections = $user->isLamp() || $user->role === 'registrar'
            ? ['profile', 'security', 'notifications']
            : ['profile', 'security', 'notifications', 'ai'];
        // (Vice Presidents and Vice Chancellors are view only, so no Report Preferences.)
        // Deans and Program Chairs have the same Briefing, Reports and Analytics, so the same preferences.
        if ($user->isExecutive() || $user->isDeanOrChair()) {
            array_splice($sections, 3, 0, $user->isVice() ? ['briefing'] : ['briefing', 'reports']);
            $sections[] = 'display';
        }

        // The Super Admin also gets the system settings (front end only for now: nothing stores them yet)
        $isSuperAdmin = $user->role === 'admin' && ! $user->isShsPrincipal();
        if ($isSuperAdmin) {
            array_push($sections, 'year', 'fees', 'review', 'retention', 'backups');
        }

        // Role key => [label, title, office, modules]. Same role keys as Access Control.
        $roles = [
            'admin' => ['IATO Admin', 'IATO Admin', 'Institutional Admissions and Testing Office', 'All modules'],
            'iato_staff' => ['IATO Staff', 'IATO Staff', 'Institutional Admissions and Testing Office', 'Dashboard, Applicants, Analytics & Reports, all records, Import Data, My Submissions, Audit Logs (view)'],
            'dean' => ['Dean', 'Dean', '—', 'Briefing, Applicants (view only), Analytics, Reports, AI Assistant'],
            'program_chair' => ['Program Chair, BSCS', 'Program Chair, BSCS', 'BS Computer Science · CITE', 'Briefing, Applicants (view only), Analytics, Reports, AI Assistant'],
            'principal' => ['SHS Principal', 'Principal', 'Senior High School', 'Dashboard, Applicants'],
            'registrar' => ['Registrar', 'Registrar', 'Registrar\'s Office', 'Dashboard, Ready for Enrollment, Reports, Questions to IATO, records (view only)'],
            'lamp' => ['LAMP Office', 'LAMP Officer', 'Lasallian Assistance Management Program', 'Dashboard, Scholars, Reports'],
            'president' => ['President', 'President', 'Office of the President', 'Briefing, Analytics, Reports, Messages'],
            'vice_president' => ['Vice President', 'Vice President', 'Office of the President', 'Briefing, Analytics, Reports, Messages'],
            'chancellor' => ['Chancellor', 'Chancellor', 'Office of the Chancellor', 'Briefing, Analytics, Reports, Messages'],
            'vice_chancellor' => ['Vice Chancellor', 'Vice Chancellor', 'Office of the Chancellor', 'Briefing, Analytics, Reports, Messages'],
        ];
        // The seeded SHS principal has role 'admin', so their SHS assignment decides
        $roleKey = $user->isShsPrincipal() ? 'principal' : $user->role;
        [$roleLabel, $title, $office, $modules] = $roles[$roleKey] ?? [ucwords(str_replace('_', ' ', $user->role ?? 'User')), '—', '—', '—'];
        // Deans: "Dean, CBEAM" and their college's full name
        if ($user->role === 'dean') {
            $college = CollegeController::college();
            $roleLabel = $title = 'Dean, ' . ($college['title'] ?? $college['code']);
            $office = $college['name'];
        }
        // Registrars: "College Registrar" or "IS Registrar"
        if ($user->role === 'registrar') {
            $roleLabel = $title = $user->roleInfo()[0];
        }
        // Vice Presidents and Vice Chancellors: "Vice President for Finance", "Office of the VP for Finance"
        if ($user->isVice() && $user->department) {
            $roleLabel = $title = $user->executiveTitle();
            $office = 'Office of the ' . ($user->role === 'vice_chancellor' ? 'VC' : 'VP') . " for {$user->department}";
        }

        $scopes = ['college' => 'College', 'is' => 'Integrated School'];
        $scope = $user->isExecutive() || ($user->isAdmin() && ! $user->assigned_level)
            ? 'All units, institution-wide'
            : trim(($scopes[$user->assigned_level] ?? 'All units') . ($user->assigned_sub_level === 'shs' ? ' · Senior High' : ''));

        // For the Academic Year section: the newest imported year and the one after it
        $currentYear = \App\Support\ApplicantRecords::academicYear();
        $startYear = (int) substr($currentYear, 0, 4);

        return view('settings.index', [
            'sections' => $sections,
            'academicYears' => [$currentYear, ($startYear + 1) . '–' . ($startYear + 2)],
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'title' => $title,
                'office' => $office,
                // First and last name, e.g. "Teodosia Merlin Suarez" -> TS (a suffix like FSC is skipped)
                'initials' => $user->initials(),
            ],
            'access' => [
                'Role' => $roleLabel,
                'Access level' => match (true) {
                    $user->isLamp() => 'Confirm, flag, and correct scholars',
                    $user->isVice() => 'View only',
                    $user->isExecutive() || $user->isDeanOrChair() => 'View · can generate reports',
                    $user->role === 'registrar' => 'View only; can ask IATO about a record',
                    $user->role === 'iato_staff' && ! $user->isAdmin() => 'Edit, through the Review Queue',
                    $user->access_type === 'editor' => 'Can view and edit',
                    default => 'Read-only',
                },
                'Data scope' => match (true) {
                    $user->isLamp() => 'Scholarships only',
                    $user->role === 'registrar' && $user->assigned_level !== 'is' => 'College, Graduate Programs, College of Law, iPACE, ETEEAP',
                    $user->role === 'iato_staff' => 'All units',
                    $user->role === 'dean' => CollegeController::college()['code'] . ' only',
                    $user->isProgramChair() => 'BS Computer Science only',
                    default => $scope,
                },
                'Modules' => $modules,
                'Account status' => 'Active',
                'Member since' => $user->created_at?->format('M j, Y') ?? '—',
            ],
            'twoFactorOn' => $user->two_factor_confirmed_at !== null,
            'twoFactorSince' => $user->two_factor_confirmed_at ? Carbon::parse($user->two_factor_confirmed_at)->format('M j, Y') : null,
            'signIns' => $this->signIns($user->id),
            'notificationGroups' => $this->notificationGroups($user),

            // For the Executive Office's report and display preferences
            'years' => ExecutiveController::YEARS,
            // (for a Dean or Program Chair: their programs, and the words change to "program")
            'units' => $user->isDeanOrChair()
                ? ($user->isProgramChair() ? ['BS Computer Science'] : CollegeController::programNames())
                : ['College', 'Integrated School', 'Graduate Programs', 'College of Law', 'iPACE', 'ETEEAP'],
            'unitWord' => $user->isDeanOrChair() ? 'program' : 'unit',
            // "Send a copy to": the IATO and the rest of the Executive Office (not you)
            'recipients' => $user->isDeanOrChair() ? [
                ['initials' => 'RR', 'name' => 'IATO · Renegado, Randolph'],
                ['initials' => 'NL', 'name' => 'Dr. Nerissa Lucasia (AVC, Colleges)'],
                ['initials' => 'TP', 'name' => 'Dr. Tessie Palma (VC for Academics)'],
            ] : ($user->isExecutive()
                ? collect(ExecutiveController::executives())
                    ->reject(fn ($person) => str_contains($person['name'], $user->name))
                    ->prepend(['initials' => 'RR', 'name' => 'IATO · Renegado, Randolph'])
                    ->values()->all()
                : []),
        ]);
    }

    /**
     * Email notification switches: heading => [[key, label, description, on by default], …].
     * A '' heading means no heading. "Security alerts" is added by the view (always on).
     */
    private function notificationGroups($user): array
    {
        if ($user->role === 'iato_staff') {
            return [
                'My submissions' => [
                    ['sentBack', 'Submission sent back', 'The Super Admin returned an edit or import to you with a reason. Recommended.', true],
                    ['approved', 'Submission approved', 'Your edit or import is now visible to other users.', false],
                ],
                'Data' => [
                    ['importErrors', 'Import finished with errors', 'A batch import completed but some rows were skipped.', true],
                    ['lampFlag', 'LAMP Office flagged a scholar discrepancy', 'LAMP confirmed, flagged, or edited a scholar record.', true],
                ],
                'Reports' => [
                    ['reply', 'Reply on a report', 'The Chancellor, a Dean, LAMP, or another office replied to a report you sent.', true],
                    ['weekly', 'Weekly admissions summary', 'Every Monday: applicants, paid reservations, and pending items for the week.', false],
                ],
            ];
        }
        if ($user->isExecutive() || $user->isDeanOrChair()) {
            return [
                'Reports' => [
                    ['newReport', 'New report from IATO', 'Get an email when IATO sends a report to ' . ($user->isExecutive() ? 'the Executive Office.' : 'your office.'), true],
                    ['reply', 'Reply from IATO', 'Get an email when IATO answers one of your messages.', true],
                ],
                'Briefing' => [
                    ['attention', 'New "Needs your attention" item', 'Email me when the Briefing flags something new, such as a drop in conversion or a unit growing fast.', true],
                    ['weekly', 'Weekly enrollment summary', 'A short email every Monday with applicants, paid reservations, and enrollment compared with last year.', false],
                ],
            ];
        }
        if ($user->isLamp()) {
            return [
                'Requests from IATO' => [
                    ['request', 'New request from IATO', 'IATO found a scholar whose record doesn\'t match yours and asked you to check. Recommended.', true],
                    ['reply', 'IATO replied', 'IATO answered a request or a flag you sent.', true],
                ],
                'Your corrections' => [
                    ['approved', 'Correction approved', 'The Super Admin approved a scholar correction you sent. It is now visible to everyone.', false],
                    ['returned', 'Correction sent back', 'The Super Admin returned a correction with a reason.', true],
                ],
                'Reports' => [
                    ['newReport', 'New report from IATO', 'IATO sent you a scholarship report.', true],
                    ['weekly', 'Weekly scholar summary', 'Every Monday: new scholars, matches, and open discrepancies.', false],
                ],
            ];
        }

        return [
            '' => [
                ['newReport', 'New report from IATO', 'Get an email when IATO sends a report to your office.', true],
                ['reply', 'Reply from IATO', 'Get an email when IATO answers one of your messages on a report.', true],
                ['weekly', 'Weekly enrollment summary', 'A short email every Monday with applicants, paid reservations, and enrollment compared with last year.', false],
            ],
        ];
    }

    /**
     * The user's sign-ins, newest first, from the sessions table (SESSION_DRIVER=database).
     * Each one is a browser that is (or was) signed in.
     */
    private function signIns(int $userId): array
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'when' => Carbon::createFromTimestamp($s->last_activity)->timezone(config('app.timezone'))->format('M j, Y · g:i A'),
                'device' => $this->device($s->user_agent ?? ''),
                'ip' => $s->ip_address ?? '—',
                'current' => $s->id === session()->getId(),
            ])
            ->all();
    }

    // "Chrome on Windows" from a browser's user-agent text
    private function device(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };
        $system = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'an unknown device',
        };

        return "{$browser} on {$system}";
    }
}
