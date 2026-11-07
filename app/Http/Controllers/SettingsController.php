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

        // Every role gets these; the President / Chancellor office also gets report and display preferences
        $sections = ['profile', 'security', 'notifications', 'ai'];
        if ($user->isChancellor()) {
            array_splice($sections, 3, 0, ['reports']);
            $sections[] = 'display';
        }

        // Role key => [label, title, office, modules]. Same role keys as Access Control.
        $roles = [
            'admin' => ['IATO Admin', 'IATO Admin', 'Institutional Admissions and Testing Office', 'All modules'],
            'iato_staff' => ['IATO Staff', 'IATO Staff', 'Institutional Admissions and Testing Office', 'Dashboard, Applicants, Records, Reports, Analytics, Import'],
            'dean' => ['Dean', 'Dean', 'College of Information Technology and Engineering', 'Dashboard, Applicants, Analytics, Reports'],
            'program_chair' => ['Program Chair', 'Program Chair', 'BS Computer Science · CITE', 'Dashboard, Applicants, Analytics, Reports'],
            'principal' => ['SHS Principal', 'Principal', 'Senior High School', 'Dashboard, Applicants'],
            'registrar' => ['Registrar', 'Registrar', 'Registrar\'s Office', 'Dashboard, Applicants, Reports'],
            'lamp' => ['LAMP Office', 'LAMP Officer', 'Lasallian Mission Office', 'Dashboard, Scholars, Reports'],
            'chancellor' => ['President / Chancellor', 'Chancellor', 'Office of the Chancellor', 'Dashboard, Analytics, Reports'],
        ];
        // The seeded SHS principal has role 'admin', so their SHS assignment decides
        $roleKey = $user->isShsPrincipal() ? 'principal' : $user->role;
        [$roleLabel, $title, $office, $modules] = $roles[$roleKey] ?? [ucwords(str_replace('_', ' ', $user->role ?? 'User')), '—', '—', '—'];

        $scopes = ['college' => 'College', 'is' => 'Integrated School'];
        $scope = $user->isChancellor() || ($user->isAdmin() && ! $user->assigned_level)
            ? 'All units, institution-wide'
            : trim(($scopes[$user->assigned_level] ?? 'All units') . ($user->assigned_sub_level === 'shs' ? ' · Senior High' : ''));

        return view('settings.index', [
            'sections' => $sections,
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'title' => $title,
                'office' => $office,
                // First and last name, e.g. "Merlin Teodosia C. Suarez" -> MS
                'initials' => mb_strtoupper(mb_substr(Str::before($user->name, ' '), 0, 1) . mb_substr(Str::afterLast($user->name, ' '), 0, 1)),
            ],
            'access' => [
                'Role' => $roleLabel,
                'Access level' => $user->access_type === 'editor' ? 'Can view and edit' : 'Read-only',
                'Data scope' => $scope,
                'Modules' => $modules,
                'Account status' => 'Active',
                'Member since' => $user->created_at?->format('M j, Y') ?? '—',
            ],
            'twoFactorOn' => $user->two_factor_confirmed_at !== null,
            'twoFactorSince' => $user->two_factor_confirmed_at ? Carbon::parse($user->two_factor_confirmed_at)->format('M j, Y') : null,
            'signIns' => $this->signIns($user->id),

            // For the President / Chancellor's report and display preferences
            'years' => ChancellorController::YEARS,
            'units' => ['College', 'Integrated School', 'Graduate Programs', 'College of Law', 'iPACE', 'ETEEAP'],
            'recipients' => $user->isChancellor() ? [
                ['initials' => 'RR', 'name' => 'IATO · Renegado, Randolph'],
                ['initials' => 'EF', 'name' => 'Br. Edmundo L. Fernandez FSC'],
                ['initials' => 'TP', 'name' => 'Dr. Tessie Palma'],
                ['initials' => 'SM', 'name' => 'Dr. Sheila Maloles'],
                ['initials' => 'WA', 'name' => 'Mr. Wenifredo Angeles'],
            ] : [],
        ]);
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
