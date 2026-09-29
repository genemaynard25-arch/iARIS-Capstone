<?php

namespace App\Http\Controllers;

class AccessControlController extends Controller
{
    /**
     * Access Control page (admin only once RBAC is in place).
     *
     * Placeholder data so the UI can be built first, same idea as the other
     * pages. Names are made up. Later the matrix would come from a
     * role_permissions table, the temporary admin from a temp_admins table,
     * and the log from an audit log table.
     *
     * Role keys match the 'role' column on users (admin, iato_staff, dean, ...).
     * For each module, 'on' lists the roles that have access. IATO Admin always
     * has access, so it isn't listed. 'adminOnly' modules can't be given to
     * anyone else, so their switches are locked off.
     */
    public function index()
    {
        $roles = [
            ['key' => 'admin', 'name' => 'IATO Admin', 'icon' => 'bi-star-fill', 'tone' => 'primary'],
            ['key' => 'iato_staff', 'name' => 'IATO Staff', 'icon' => 'bi-person-badge', 'tone' => 'primary'],
            ['key' => 'dean', 'name' => 'Dean / Chair', 'icon' => 'bi-person-workspace', 'tone' => 'info'],
            ['key' => 'shs_principal', 'name' => 'SHS Principal', 'icon' => 'bi-building', 'tone' => 'secondary'],
            ['key' => 'registrar', 'name' => 'Registrar', 'icon' => 'bi-person-vcard', 'tone' => 'warning'],
            ['key' => 'lamp', 'name' => 'LAMP Office', 'icon' => 'bi-award', 'tone' => 'secondary'],
            ['key' => 'chancellor', 'name' => 'Chancellor', 'icon' => 'bi-bank', 'tone' => 'danger'],
        ];

        $modules = [
            'Main' => [
                ['key' => 'dashboard', 'name' => 'Dashboard', 'description' => 'Overview stats and charts',
                    'on' => ['iato_staff', 'dean', 'shs_principal', 'registrar', 'lamp', 'chancellor']],
                ['key' => 'applicants', 'name' => 'Applicants', 'description' => 'View and search applicant records',
                    'on' => ['iato_staff', 'dean', 'shs_principal', 'registrar']],
                ['key' => 'reports', 'name' => 'Reports', 'description' => 'Generate and download reports',
                    'on' => ['iato_staff', 'dean', 'shs_principal', 'registrar', 'lamp', 'chancellor']],
                ['key' => 'analytics', 'name' => 'Analytics', 'description' => 'View charts and AI insights',
                    'on' => ['iato_staff', 'dean', 'shs_principal', 'chancellor']],
            ],
            'Records' => [
                ['key' => 'college', 'name' => 'College Records', 'description' => 'View college applicant records',
                    'on' => ['iato_staff', 'dean', 'registrar']],
                ['key' => 'is', 'name' => 'Integrated School Records', 'description' => 'View IS applicant records',
                    'on' => ['iato_staff', 'shs_principal', 'registrar']],
                ['key' => 'scholars', 'name' => 'Scholars Records', 'description' => 'View scholarship applicant data',
                    'on' => ['iato_staff', 'lamp']],
                ['key' => 'import', 'name' => 'Import Data', 'description' => 'Upload Excel/CSV records',
                    'on' => ['iato_staff']],
            ],
            'System' => [
                ['key' => 'edit', 'name' => 'Edit & Review Records', 'description' => 'Edit applicant data before publishing',
                    'on' => ['iato_staff']],
                ['key' => 'access', 'name' => 'Access Control', 'description' => 'Manage role permissions',
                    'on' => [], 'adminOnly' => true],
                ['key' => 'accounts', 'name' => 'User Accounts', 'description' => 'Manage user accounts and roles',
                    'on' => [], 'adminOnly' => true],
                ['key' => 'audit', 'name' => 'Audit Logs', 'description' => 'View system activity history',
                    'on' => ['iato_staff']],
            ],
        ];

        return view('access-control.index', [
            'roles' => $roles,
            'modules' => $modules,

            'permanentAdmin' => ['name' => 'Renegado, Randolph'],

            // Only one temporary admin can be active at a time, so this is one record or null
            'tempAdmin' => [
                'name' => 'Cruz, Jasmine',
                'initials' => 'JC',
                'role' => 'IATO Staff',
                'email' => 'j.cruz@dlsl.edu.ph',
                'grantedBy' => 'Renegado, Randolph',
                'grantedOn' => 'Jun 10, 2026',
                'expires' => 'Jun 14, 2026 · 11:59 PM',
            ],

            // Staff who can be picked in the "Assign Temporary Admin" pop-up
            'staffOptions' => [
                ['name' => 'Cruz, Jasmine', 'role' => 'IATO Staff', 'email' => 'j.cruz@dlsl.edu.ph'],
                ['name' => 'Santos, Marco', 'role' => 'IATO Staff', 'email' => 'm.santos@dlsl.edu.ph'],
                ['name' => 'Bautista, Clarisse', 'role' => 'IATO Staff', 'email' => 'c.bautista@dlsl.edu.ph'],
            ],

            // Change types: label => Bootstrap colour for its badge
            'changeTypes' => [
                'Granted' => 'primary',
                'Revoked' => 'danger',
                'Modified' => 'warning',
                'Temporary' => 'info',
            ],

            'log' => [
                ['action' => 'Temporary admin access assigned', 'meta' => 'Cruz, Jasmine granted admin-level permissions until Jun 14, 2026',
                    'affected' => 'IATO Staff', 'module' => 'All Modules', 'type' => 'Temporary', 'by' => 'R. Renegado', 'at' => 'Jun 10, 2026 · 9:00 AM'],
                ['action' => 'Analytics access granted', 'meta' => 'Dean / Program Chair role can now view Analytics',
                    'affected' => 'Dean / Chair', 'module' => 'Analytics', 'type' => 'Granted', 'by' => 'R. Renegado', 'at' => 'Jun 8, 2026 · 10:22 AM'],
                ['action' => 'Applicants access revoked', 'meta' => 'LAMP Office can no longer view the Applicants module',
                    'affected' => 'LAMP Office', 'module' => 'Applicants', 'type' => 'Revoked', 'by' => 'R. Renegado', 'at' => 'Jun 5, 2026 · 2:14 PM'],
                ['action' => 'Temporary admin period extended', 'meta' => 'Santos, Marco extended from May 30 to Jun 2, 2026',
                    'affected' => 'IATO Staff', 'module' => 'All Modules', 'type' => 'Modified', 'by' => 'R. Renegado', 'at' => 'Jun 4, 2026 · 8:30 AM'],
                ['action' => 'IS Records access granted', 'meta' => 'Registrar role can now view Integrated School records',
                    'affected' => 'Registrar', 'module' => 'IS Records', 'type' => 'Granted', 'by' => 'R. Renegado', 'at' => 'Jun 3, 2026 · 11:05 AM'],
                ['action' => 'Import Data access revoked', 'meta' => 'Dean / Chair role can no longer import data files',
                    'affected' => 'Dean / Chair', 'module' => 'Import Data', 'type' => 'Revoked', 'by' => 'R. Renegado', 'at' => 'May 28, 2026 · 3:40 PM'],
            ],
        ]);
    }
}
