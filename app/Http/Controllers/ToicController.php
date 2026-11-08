<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * TOIC (system support): how iARIS runs — uptime, backups, security, costs — never applicant data.
 * Pages at /toic. The Super Admin can open them too (to preview).
 *
 * From the database: accounts per role, who has MFA, who is signed in now, audit log entries.
 * Everything else (uptime, backups, service checks, failed sign-ins, budget) is sample data
 * from the mockups until the backend measures it.
 */
class ToicController extends Controller
{
    // Role labels as TOIC sees them, in the mockup's order: role key => [label, data scope]
    private const ROLES = [
        'admin' => ['Super Admin', 'Institution-wide'],
        'iato_staff' => ['IATO Staff', 'Institution-wide'],
        'dean' => ['Dean', 'Own college only'],
        'program_chair' => ['Program Chair, Graduate Programs', 'Graduate Programs only'],
        'principal' => ['Integrated School Principal', 'Integrated School'],
        'shs_principal' => ['SHS Principal', 'Senior High School'],
        'registrar' => ['Registrar', 'Assigned unit (College or IS)'],
        'lamp' => ['LAMP Office', 'Scholarships'],
        'president' => ['President', 'Institution-wide'],
        'chancellor' => ['Chancellor', 'Institution-wide'],
        'vice_chancellor' => ['Vice Chancellor', 'Institution-wide'],
        'vice_president' => ['Vice President', 'Institution-wide'],
        'director' => ['iPACE Director', 'iPACE and ETEEAP'],
        'toic' => ['TOIC (System Support)', 'System data only'],
    ];

    // Only TOIC (and the Super Admin, to preview) can open these pages
    private function guard(): void
    {
        abort_unless(auth()->user()->isToic() || auth()->user()->isAdmin(), 403);
    }

    /**
     * Accounts per role: [label, scope, accounts, with MFA, signed in now].
     * "Signed in now": a session active in the last 30 minutes (the session timeout).
     */
    public static function roles(): array
    {
        $active = DB::table('sessions')->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(30)->timestamp)
            ->distinct()->pluck('user_id')->all();
        $rows = [];
        foreach (User::all() as $user) {
            // The seeded SHS principal is an admin assigned to SHS, so it gets its own row
            $key = $user->isShsPrincipal() ? 'shs_principal' : $user->role;
            $rows[$key] ??= ['accounts' => 0, 'mfa' => 0, 'now' => 0];
            $rows[$key]['accounts']++;
            $rows[$key]['mfa'] += $user->two_factor_confirmed_at ? 1 : 0;
            $rows[$key]['now'] += in_array($user->id, $active) ? 1 : 0;
        }

        return collect(self::ROLES)->filter(fn ($r, $key) => isset($rows[$key]))
            ->map(fn ($r, $key) => ['label' => $r[0], 'scope' => $r[1]] + $rows[$key])->values()->all();
    }

    /**
     * Problems reported to TOIC (the mockup's examples). Reports people send with "Report a problem"
     * are added in the browser (partials/issues-store). TODO (backend): an issues table.
     * st: new, investigating, fixed or closed. tl: the timeline; toic = written by TOIC.
     */
    public static function issues(): array
    {
        return [
            ['id' => 'INC-0034', 'title' => 'Large Excel imports are slow or time out', 'svc' => 'Import', 'sev' => 'High', 'st' => 'investigating',
                'src' => 'Reported by Renegado, Randolph · Super Admin', 'page' => 'Import Data', 'at' => 'Jun 12, 2026 · 9:40 AM', 'tl' => [
                    ['who' => 'Renegado, Randolph', 'role' => 'Super Admin', 'at' => 'Jun 12, 2026 · 9:40 AM', 'text' => 'Files over 2,000 rows take more than 2 minutes and sometimes time out. We have a batch on June 15.'],
                    ['who' => 'TOIC Support Team', 'role' => 'TOIC', 'at' => 'Jun 12, 2026 · 10:05 AM', 'text' => 'Acknowledged. Checking the import worker. It looks short on memory.', 'toic' => true],
                ]],
            ['id' => 'INC-0033', 'title' => 'SSL certificate renews in 21 days', 'svc' => 'Web server', 'sev' => 'Normal', 'st' => 'new',
                'src' => 'Automatic system check', 'page' => '—', 'at' => 'Jun 12, 2026 · 6:00 AM', 'tl' => [
                    ['who' => 'System check', 'role' => 'Automatic', 'at' => 'Jun 12, 2026 · 6:00 AM', 'text' => 'Certificate for iaris.dlsl.edu.ph expires Jul 3, 2026. Auto-renewal is on.'],
                ]],
            ['id' => 'INC-0032', 'title' => 'Nightly backup did not finish', 'svc' => 'Backups', 'sev' => 'Critical', 'st' => 'fixed',
                'src' => 'Automatic system check', 'page' => '—', 'at' => 'Jun 8, 2026 · 11:40 PM', 'tl' => [
                    ['who' => 'System check', 'role' => 'Automatic', 'at' => 'Jun 8, 2026 · 11:40 PM', 'text' => 'Backup stopped at 61%: disk full.'],
                    ['who' => 'TOIC Support Team', 'role' => 'TOIC', 'at' => 'Jun 9, 2026 · 12:55 AM', 'text' => 'Removed imported source files past their retention period. Re-ran the backup.', 'toic' => true],
                    ['who' => 'TOIC Support Team', 'role' => 'TOIC', 'at' => 'Jun 9, 2026 · 1:10 AM', 'text' => 'Fixed: backup completed (47.4 MB). Added a disk-space alert at 85%.', 'toic' => true],
                ]],
            ['id' => 'INC-0031', 'title' => 'AI assistant answers time out', 'svc' => 'AI assistant', 'sev' => 'High', 'st' => 'closed',
                'src' => 'Reported by Villafuerte, Cherrie · Dean · CITE', 'page' => 'Briefing', 'at' => 'Jun 3, 2026 · 2:15 PM', 'tl' => [
                    ['who' => 'Villafuerte, Cherrie', 'role' => 'Dean · CITE', 'at' => 'Jun 3, 2026 · 2:15 PM', 'text' => 'Ask iARIS keeps loading and never answers.'],
                    ['who' => 'TOIC Support Team', 'role' => 'TOIC', 'at' => 'Jun 3, 2026 · 3:00 PM', 'text' => 'Fixed: the AI service key had expired. Renewed and tested.', 'toic' => true],
                ]],
        ];
    }

    // System Overview: tiles, needs your attention, signed in now, backups, service checks, maintenance
    public function dashboard()
    {
        $this->guard();
        $roles = self::roles();

        return view('toic.dashboard', [
            'roles' => $roles,
            'accounts' => array_sum(array_column($roles, 'accounts')),
            'signedIn' => array_sum(array_column($roles, 'now')),
            'withoutMfa' => array_sum(array_column($roles, 'accounts')) - array_sum(array_column($roles, 'mfa')),
            // Sample until measured: [label, value, note]
            'tiles' => [
                ['Uptime · last 30 days', '99.96%', '17 min of downtime'],
                ['Average page load', '0.42 s', 'Target under 1 s'],
                ['Storage used', '38.2 GB', 'of 100 GB on the DLSL server'],
            ],
            'backups' => [
                ['Automatic backup', 'Jun 11, 2026 · 11:00 PM', '48.2 MB'],
                ['Manual backup before the June import', 'Jun 11, 2026 · 4:12 PM', '47.6 MB'],
                ['Automatic backup', 'Jun 10, 2026 · 11:00 PM', '47.9 MB'],
                ['Automatic backup', 'Jun 9, 2026 · 11:00 PM', '47.5 MB'],
            ],
            // [service, status, ok?]
            'services' => [
                ['Web server', 'Running', true],
                ['Database', 'Running', true],
                ['Import worker', 'Slow · 2 min per large file', false],
                ['Email for MFA codes', 'Running', true],
                ['AI assistant service', 'Running · 1.1 s average answer', true],
                ['Nightly backup job', 'Last run completed', true],
                ['SSL certificate', 'Renews in 21 days', false],
            ],
        ]);
    }

    // Security: MFA, failed sign-ins, locked accounts, encryption, audit log health
    public function security()
    {
        $this->guard();
        $roles = self::roles();

        return view('toic.security', [
            'roles' => $roles,
            'accounts' => array_sum(array_column($roles, 'accounts')),
            'withMfa' => array_sum(array_column($roles, 'mfa')),
            'auditEntries' => \App\Models\AuditLog::count(),
            // Sample until sign-in failures are recorded: [when, account (partly hidden), reason, result]
            'failed' => [
                ['Jun 12 · 7:42 AM', 'r.s****@dlsl.edu.ph', 'Wrong password', 'Signed in on 2nd try'],
                ['Jun 11 · 9:15 PM', 'unknown@gmail.com', 'Not a DLSL account', 'Blocked'],
                ['Jun 11 · 2:03 PM', 'j.c****@dlsl.edu.ph', 'Wrong MFA code', 'Signed in on 2nd try'],
                ['Jun 10 · 11:48 PM', 'a.b****@dlsl.edu.ph', 'Wrong password × 5', 'Locked for 30 min'],
                ['Jun 9 · 8:20 AM', 'e.n****@dlsl.edu.ph', 'MFA not set up', 'Sent setup link'],
            ],
        ]);
    }

    // Documentation: modules, roles (from the database), data lifecycle, retention
    public function docs()
    {
        $this->guard();
        $roles = self::roles();

        return view('toic.docs', ['roles' => $roles, 'accounts' => array_sum(array_column($roles, 'accounts'))]);
    }

    // Budget & Maintenance: cost estimates, who shoulders each, maintenance plan
    public function budget()
    {
        $this->guard();

        return view('toic.budget', [
            // [item, what it covers, monthly ₱, shouldered by]
            'items' => [
                ['Server space', 'Virtual server on the DLSL server for the web app and database', 4500, 'TOIC'],
                ['Storage and backups', 'Encrypted daily backups, kept 30 to 90 days', 600, 'To be agreed'],
                ['Domain and SSL certificate', 'iaris.dlsl.edu.ph and its security certificate', 150, 'TOIC'],
                ['Email for MFA codes and notices', 'Sign-in codes and system notices', 800, 'To be agreed'],
                ['AI assistant service', 'Plain-language explanations of graphs, billed by use', 1500, 'IATO'],
                ['Maintenance and support hours', 'About 8 hours a month for updates and fixes', 6000, 'To be agreed'],
            ],
        ]);
    }

    // Reports: four system reports (no applicant data), each downloadable
    public function reports()
    {
        $this->guard();

        return view('toic.reports', [
            // [title, icon, description, header, rows]
            'reports' => [
                ['Uptime and performance · May 2026', 'bi-hdd-rack', 'Monthly uptime, page load, and incidents', ['Week', 'Uptime', 'Avg page load', 'Incidents'],
                    [['May 1–7', '100.00%', '0.40 s', '0'], ['May 8–14', '99.90%', '0.45 s', '1 (database restart, 10 min)'], ['May 15–21', '99.98%', '0.41 s', '0'], ['May 22–31', '100.00%', '0.43 s', '0']]],
                ['Security summary · Q2 2026', 'bi-shield-lock', 'MFA adoption, failed sign-ins, locked accounts', ['Measure', 'April', 'May', 'June (to date)'],
                    [['Accounts with MFA', '16 of 21', '18 of 23', '19 of 24'], ['Failed sign-ins', '31', '26', '14'], ['Locked accounts', '2', '1', '1'], ['Blocked non-DLSL sign-ins', '4', '3', '1']]],
                ['Backup report · June 2026', 'bi-floppy', 'Every backup with size and result', ['Backup', 'When', 'Size', 'Result'],
                    [['Automatic', 'Jun 11 · 11:00 PM', '48.2 MB', 'Completed'], ['Manual (before June import)', 'Jun 11 · 4:12 PM', '47.6 MB', 'Completed'], ['Automatic', 'Jun 10 · 11:00 PM', '47.9 MB', 'Completed'], ['Automatic', 'Jun 9 · 11:00 PM', '47.5 MB', 'Completed']]],
                ['Storage growth · AY 2025–2026', 'bi-device-hdd', 'Database and file storage by month', ['Month', 'Database', 'Imported files', 'Backups'],
                    [['March', '29.4 GB', '2.1 GB', '4.6 GB'], ['April', '33.0 GB', '2.6 GB', '5.0 GB'], ['May', '35.8 GB', '2.9 GB', '5.3 GB'], ['June', '38.2 GB', '3.1 GB', '5.6 GB']]],
            ],
        ]);
    }

    // Issues: everything reported to TOIC. TODO: the Issues mockup is still to come; this is a simple list for now.
    public function issuesPage()
    {
        $this->guard();

        return view('toic.issues');
    }

    // Messages with IATO: the same page as the Executive Office's, about support, maintenance and costs
    public function messages()
    {
        $this->guard();

        return view('executive.messages', [
            'threads' => self::threads(),
            'base' => url('/toic'),
            // Instead of "Other ways to get answers"
            'note' => ['TOIC talks with IATO here about support, maintenance, and costs. Applicant data is never shared in Messages with TOIC.', 'System Overview'],
        ]);
    }

    /** Conversations IATO answered or started (the number on the sidebar's Messages link). */
    public static function answeredMessages(): int
    {
        return collect(self::threads())->where('status', 'answered')->count();
    }

    // TOIC's conversations with IATO (as in the mockup). IATO can start one too.
    private static function threads(): array
    {
        $iato = fn ($at, $text) => ['from' => 'iato', 'name' => 'Renegado, Randolph', 'role' => 'Super Admin', 'at' => $at, 'text' => $text];

        return [
            ['id' => 'MSG-0118', 'topic' => 'Something looks wrong', 'subject' => 'Large Excel imports are slow', 'status' => 'answered', 'messages' => [
                $iato('Jun 12, 2026 · 9:40 AM', 'Importing Excel files over 2,000 rows takes more than 2 minutes and sometimes times out. Can TOIC check the server before the June 15 batch?'),
            ]],
            ['id' => 'MSG-0117', 'topic' => 'Other', 'subject' => 'Maintenance on Saturday, Jun 20', 'status' => 'resolved', 'messages' => [
                ['from' => 'me', 'at' => 'Jun 10, 2026 · 3:00 PM', 'text' => 'Heads-up: iARIS will be read-only on Saturday, Jun 20, 10 PM to 12 AM for security updates and database tuning.'],
                $iato('Jun 10, 2026 · 3:25 PM', 'Noted, thank you. We will tell the deans and the Registrars.'),
            ]],
        ];
    }
}
