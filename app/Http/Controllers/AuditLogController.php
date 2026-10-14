<?php

namespace App\Http\Controllers;

class AuditLogController extends Controller
{
    /**
     * Audit Logs page (admin only).
     *
     * Placeholder data so the UI can be built first, same idea as the other
     * pages. Later 'logs' would come from an audit_logs table that the backend
     * fills whenever someone signs in, edits, deletes, exports, and so on.
     *
     * 'at' is built from now() so the "Today" stats and the time filter always
     * have something to show. 'details' marks bold words with **double stars**;
     * the page turns those into <b> safely. 'diff' lists the fields that changed.
     */
    public function index()
    {
        $ago = fn ($minutes) => now()->subMinutes($minutes)->toIso8601String();

        // Who did it: shown as initials + name + role in the table
        $people = [
            'rr' => ['name' => 'Renegado, Randolph', 'role' => 'IATO Admin', 'tone' => 'primary', 'ip' => '192.168.1.10'],
            'jc' => ['name' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'tone' => 'info', 'ip' => '192.168.1.22'],
            'cv' => ['name' => 'Villafuerte, Cherrie', 'role' => 'Dean', 'tone' => 'warning', 'ip' => '192.168.1.31'],
            'lf' => ['name' => 'Fernandez, Liza', 'role' => 'LAMP Office', 'tone' => 'secondary', 'ip' => '192.168.1.45'],
            'rt' => ['name' => 'Torres, Ramon', 'role' => 'SHS Principal', 'tone' => 'secondary', 'ip' => '192.168.1.52'],
            'ga' => ['name' => 'Atienza, Gil', 'role' => 'Chancellor', 'tone' => 'danger', 'ip' => '192.168.2.11'],
            'eb' => ['name' => 'Bautista, Elena', 'role' => 'Registrar', 'tone' => 'warning', 'ip' => '192.168.1.38'],
        ];

        $log = fn ($id, $who, $action, $module, $minutes, $title, $details, $description, $diff = []) => [
            'id' => $id, 'user' => $who, 'action' => $action, 'module' => $module, 'at' => $ago($minutes),
            'ip' => $people[$who]['ip'], 'title' => $title, 'details' => $details, 'description' => $description, 'diff' => $diff,
        ];

        return view('audit-logs.index', [
            'people' => $people,

            // Action type => label, icon, Bootstrap colour, and which "Action:" pill it belongs to
            'actions' => [
                'login' => ['label' => 'Login', 'icon' => 'bi-box-arrow-in-right', 'tone' => 'primary', 'group' => 'session'],
                'logout' => ['label' => 'Logout', 'icon' => 'bi-box-arrow-right', 'tone' => 'secondary', 'group' => 'session'],
                'create' => ['label' => 'Create', 'icon' => 'bi-plus-lg', 'tone' => 'info', 'group' => 'create'],
                'edit' => ['label' => 'Edit', 'icon' => 'bi-pencil', 'tone' => 'warning', 'group' => 'edit'],
                'delete' => ['label' => 'Delete', 'icon' => 'bi-trash', 'tone' => 'danger', 'group' => 'delete'],
                'export' => ['label' => 'Export', 'icon' => 'bi-download', 'tone' => 'dark', 'group' => 'export'],
                'import' => ['label' => 'Import', 'icon' => 'bi-cloud-arrow-up', 'tone' => 'info', 'group' => 'import'],
                'permission' => ['label' => 'Permission Change', 'icon' => 'bi-shield-lock', 'tone' => 'danger', 'group' => 'permission'],
                'report' => ['label' => 'Report Generated', 'icon' => 'bi-file-earmark-text', 'tone' => 'primary', 'group' => 'report'],
            ],

            // The "Action:" pills along the top of the table
            'actionGroups' => [
                'session' => 'Login / Logout', 'create' => 'Create', 'edit' => 'Edit', 'delete' => 'Delete',
                'export' => 'Export', 'import' => 'Import', 'permission' => 'Permission', 'report' => 'Report',
            ],

            'modules' => [
                'Applicants', 'College Records', 'Integrated School', 'Scholars', 'Graduate Programs', 'College of Law',
                'iPACE', 'ETEEAP', 'Reports', 'Analytics', 'Access Control', 'User Accounts', 'Import Data', 'System',
            ],

            // Newest first
            'logs' => [
                $log(1268, 'rr', 'permission', 'Access Control', 12, 'Permission Change',
                    'Updated **Dean** role — enabled **Analytics** module access',
                    'Updated Dean role permissions — enabled Analytics module access.',
                    [['field' => 'Analytics Access', 'before' => 'Disabled', 'after' => 'Enabled']]),
                $log(1267, 'jc', 'edit', 'College Records', 30, 'Record Edited',
                    'Edited record of **Go, Raphael Lorenzo L.** — status changed to **Paid**',
                    'Edited applicant record of Go, Raphael Lorenzo L. (APP-2026-0381).',
                    [['field' => 'Status', 'before' => 'For Exam', 'after' => 'Paid'], ['field' => 'Payment Date', 'before' => '—', 'after' => 'Jun 3, 2026'], ['field' => 'Payment Amount', 'before' => '—', 'after' => '₱2,000.00']]),
                $log(1266, 'lf', 'edit', 'Scholars', 46, 'Scholar Record Edited',
                    'Updated scholarship status of **Santos, Juan Paolo M.** — flagged discrepancy sent to IATO',
                    'Updated scholarship status of Santos, Juan Paolo M. (APP-2026-0387) and flagged the discrepancy to the IATO Admin.',
                    [['field' => 'LAMP Status', 'before' => 'Pending Check', 'after' => 'Discrepancy'], ['field' => 'Flagged to IATO', 'before' => 'No', 'after' => 'Yes']]),
                $log(1265, 'jc', 'export', 'Applicants', 70, 'Applicant List Exported',
                    'Exported **College applicants** list as **XLSX** — 318 rows',
                    'Exported the filtered College applicants list (Status: For Exam) as an Excel file.'),
                $log(1264, 'rr', 'report', 'Reports', 150, 'Report Generated',
                    'Generated **Scholar Cross-Check Report — June 2026** · Sent to LAMP Office',
                    'Generated the Scholar Cross-Check Report — June 2026. Report sent to the LAMP Office (Fernandez, Liza).'),
                $log(1263, 'rt', 'login', 'System', 165, 'User Login',
                    'Logged in successfully', 'Successful login from the SHS Principal\'s office computer.'),
                $log(1262, 'rr', 'delete', 'Applicants', 190, 'Applicant Deleted',
                    'Deleted duplicate applicant record **Reyes, Joshua D.** (APP-2026-0390)',
                    'Removed a duplicate of APP-2026-0382 created by a double import.',
                    [['field' => 'Record', 'before' => 'APP-2026-0390', 'after' => 'Deleted']]),
                $log(1261, 'rr', 'login', 'System', 200, 'User Login',
                    'Logged in successfully · MFA verified', 'Successful login. MFA verification passed via Google Authenticator.'),
                $log(1260, 'jc', 'import', 'Import Data', 60 * 24 + 30, 'Data Import',
                    'Imported **applicants_batch_june2026.xlsx** — **142 records** added',
                    'Imported applicants_batch_june2026.xlsx — 142 records added to the system. 0 errors.'),
                $log(1259, 'ga', 'export', 'Analytics', 60 * 24 + 110, 'Analytics Export',
                    'Exported **Enrollment Trends Report AY 2025–2026** as PDF',
                    'Exported the Enrollment Trends Report AY 2025–2026 as PDF from the Analytics module.'),
                $log(1258, 'rr', 'delete', 'User Accounts', 60 * 24 + 135, 'User Account Deleted',
                    'Deleted user account of **Bautista, Elena** (Registrar) — account permanently removed',
                    'Permanently deleted the user account of Bautista, Elena (Registrar). Reason: staff reassignment.',
                    [['field' => 'Account Status', 'before' => 'Pending', 'after' => 'Deleted'], ['field' => 'Access Level', 'before' => 'Registrar', 'after' => 'None']]),
                $log(1257, 'jc', 'create', 'Applicants', 60 * 24 + 240, 'Applicant Created',
                    'Added new applicant **Mendoza, Clarissa Joy T.** — College of Law · JD',
                    'New applicant record created for Mendoza, Clarissa Joy T. (APP-2026-L006) — College of Law · Juris Doctor.'),
                $log(1256, 'eb', 'logout', 'System', 60 * 24 + 300, 'User Logout',
                    'Session ended — logged out normally', 'Normal session end. User logged out with the Log Out button.'),
                $log(1255, 'cv', 'report', 'Reports', 60 * 48 + 20, 'Report Generated',
                    'Generated **Enrolled vs Target — CITE** for the Dean\'s office',
                    'Generated the Enrolled vs Target per Level/Program report filtered to CITE programs.'),
                $log(1254, 'cv', 'login', 'System', 60 * 48 + 25, 'User Login',
                    'Logged in successfully', 'Successful login. MFA not set up for this account.'),
                $log(1253, 'rr', 'permission', 'Access Control', 60 * 72, 'Permission Change',
                    'Updated **LAMP Office** role — disabled **Export** on Scholars',
                    'Updated LAMP Office role permissions — removed export rights on the Scholars module.',
                    [['field' => 'Scholars · Export', 'before' => 'Enabled', 'after' => 'Disabled']]),
                $log(1252, 'jc', 'edit', 'Integrated School', 60 * 72 + 45, 'Record Edited',
                    'Edited record of **Lim, Andrea Mae C.** — exam schedule moved to **Jun 20**',
                    'Edited applicant record of Lim, Andrea Mae C. (APP-IS-2026-0104).',
                    [['field' => 'Exam Schedule', 'before' => 'Jun 13, 2026 · 8:00 AM', 'after' => 'Jun 20, 2026 · 8:00 AM']]),
                $log(1251, 'rr', 'create', 'User Accounts', 60 * 24 * 5, 'User Account Created',
                    'Created account for **Torres, Ramon** (SHS Principal)',
                    'New account created for Torres, Ramon with the SHS Principal role. Invitation e-mail sent.'),
                $log(1250, 'lf', 'export', 'Scholars', 60 * 24 * 6, 'Scholar List Exported',
                    'Exported **Scholars cross-check** list as **XLSX**',
                    'Exported the Scholars cross-check list (all types) as an Excel file.'),
                $log(1249, 'jc', 'import', 'Import Data', 60 * 24 * 12, 'Data Import',
                    'Imported **admission_stats_ay2025.csv** — **36 rows** added',
                    'Imported admission statistics for AY 2025–2026 — 36 rows added. 0 errors.'),
                $log(1248, 'ga', 'login', 'System', 60 * 24 * 20, 'User Login',
                    'Logged in successfully · MFA verified', 'Successful login. MFA verification passed via Microsoft Authenticator.'),
                $log(1247, 'rr', 'delete', 'College Records', 60 * 24 * 40, 'Record Deleted',
                    'Deleted withdrawn applicant **Garcia, Mark Anthony** (APP-2026-0384)',
                    'Removed an applicant who withdrew before the entrance exam.',
                    [['field' => 'Record', 'before' => 'APP-2026-0384', 'after' => 'Deleted']]),
            ],
        ]);
    }
}
