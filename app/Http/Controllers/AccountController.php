<?php

namespace App\Http\Controllers;

class AccountController extends Controller
{
    /**
     * User Accounts page (admin only once RBAC is in place).
     *
     * Placeholder data so the UI can be built first, same idea as the other
     * pages. Names and emails are made up. Later 'users' would come from the
     * users table: role from the 'role' column, mfa from two_factor_confirmed_at,
     * lastLogin from a last_login_at column the backend would add.
     *
     * lastLogin is built from now() so the "Active today / Last 7 days" labels
     * always look right when you open the page. null means never logged in.
     */
    public function index()
    {
        $ago = fn ($hours) => now()->subHours($hours)->toIso8601String();
        $recent = fn ($days) => now()->subDays($days)->toDateString();

        return view('accounts.index', [
            // Same role keys as the 'role' column and the Access Control page
            'roles' => [
                'admin' => ['name' => 'IATO Admin', 'tone' => 'primary'],
                'iato_staff' => ['name' => 'IATO Staff', 'tone' => 'primary'],
                'dean' => ['name' => 'Dean / Chair', 'tone' => 'info'],
                'shs_principal' => ['name' => 'SHS Principal', 'tone' => 'secondary'],
                'registrar' => ['name' => 'Registrar', 'tone' => 'warning'],
                'lamp' => ['name' => 'LAMP Office', 'tone' => 'secondary'],
                'chancellor' => ['name' => 'Chancellor', 'tone' => 'danger'],
            ],

            // Status => Bootstrap colour for its badge
            'statuses' => [
                'Active' => 'primary',
                'Pending' => 'warning',
                'Suspended' => 'danger',
                'Inactive' => 'secondary',
            ],

            'departments' => [
                'IATO', 'CITE', 'CEAS', 'CIHTM', 'CBEAM', 'CON', 'College of Law', 'Graduate Programs',
                'Integrated School', 'College Registrar', 'IS Registrar', 'Lasallian Mission Office', 'Office of the President',
            ],

            'users' => [
                ['id' => 1, 'last' => 'Renegado', 'first' => 'Randolph', 'email' => 'r.renegado@dlsl.edu.ph', 'role' => 'admin', 'department' => 'IATO',
                    'status' => 'Active', 'mfa' => true, 'lastLogin' => $ago(2), 'joined' => '2024-01-10', 'permanent' => true],
                ['id' => 2, 'last' => 'Cruz', 'first' => 'Jasmine', 'email' => 'j.cruz@dlsl.edu.ph', 'role' => 'iato_staff', 'department' => 'IATO',
                    'status' => 'Active', 'mfa' => true, 'lastLogin' => $ago(1), 'joined' => '2024-02-03'],
                ['id' => 3, 'last' => 'Santos', 'first' => 'Marco', 'email' => 'm.santos@dlsl.edu.ph', 'role' => 'iato_staff', 'department' => 'IATO',
                    'status' => 'Active', 'mfa' => true, 'lastLogin' => $ago(50), 'joined' => '2024-08-19'],
                ['id' => 4, 'last' => 'Bautista', 'first' => 'Clarisse', 'email' => 'c.bautista@dlsl.edu.ph', 'role' => 'iato_staff', 'department' => 'IATO',
                    'status' => 'Pending', 'mfa' => false, 'lastLogin' => null, 'joined' => $recent(3)],
                ['id' => 5, 'last' => 'Villafuerte', 'first' => 'Cherrie', 'email' => 'c.villafuerte@dlsl.edu.ph', 'role' => 'dean', 'department' => 'CITE',
                    'status' => 'Active', 'mfa' => false, 'lastLogin' => $ago(75), 'joined' => '2025-06-01'],
                ['id' => 6, 'last' => 'Mendoza', 'first' => 'Paolo', 'email' => 'p.mendoza@dlsl.edu.ph', 'role' => 'dean', 'department' => 'CBEAM',
                    'status' => 'Active', 'mfa' => true, 'lastLogin' => $ago(5), 'joined' => '2025-06-01'],
                ['id' => 7, 'last' => 'Aquino', 'first' => 'Teresa', 'email' => 't.aquino@dlsl.edu.ph', 'role' => 'dean', 'department' => 'CIHTM',
                    'status' => 'Inactive', 'mfa' => true, 'lastLogin' => $ago(24 * 75), 'joined' => '2024-11-12'],
                ['id' => 8, 'last' => 'Torres', 'first' => 'Ramon', 'email' => 'r.torres@dlsl.edu.ph', 'role' => 'shs_principal', 'department' => 'Integrated School',
                    'status' => 'Active', 'mfa' => true, 'lastLogin' => $ago(3), 'joined' => '2025-06-01'],
                ['id' => 9, 'last' => 'Bautista', 'first' => 'Elena', 'email' => 'e.bautista@dlsl.edu.ph', 'role' => 'registrar', 'department' => 'College Registrar',
                    'status' => 'Pending', 'mfa' => false, 'lastLogin' => null, 'joined' => $recent(2)],
                ['id' => 10, 'last' => 'Castillo', 'first' => 'Noel', 'email' => 'n.castillo@dlsl.edu.ph', 'role' => 'registrar', 'department' => 'IS Registrar',
                    'status' => 'Active', 'mfa' => true, 'lastLogin' => $ago(24 * 12), 'joined' => '2025-02-14'],
                ['id' => 11, 'last' => 'Fernandez', 'first' => 'Liza', 'email' => 'l.fernandez@dlsl.edu.ph', 'role' => 'lamp', 'department' => 'Lasallian Mission Office',
                    'status' => 'Active', 'mfa' => false, 'lastLogin' => $ago(24 * 20), 'joined' => '2025-04-20'],
                ['id' => 12, 'last' => 'Atienza', 'first' => 'Gil', 'email' => 'g.atienza@dlsl.edu.ph', 'role' => 'chancellor', 'department' => 'Office of the President',
                    'status' => 'Suspended', 'mfa' => true, 'lastLogin' => $ago(24 * 40), 'joined' => '2025-03-15'],
                ['id' => 13, 'last' => 'Ramos', 'first' => 'Katrina', 'email' => 'k.ramos@dlsl.edu.ph', 'role' => 'dean', 'department' => 'CEAS',
                    'status' => 'Active', 'mfa' => false, 'lastLogin' => $ago(24 * 6), 'joined' => '2025-07-08'],
                ['id' => 14, 'last' => 'Navarro', 'first' => 'Jerome', 'email' => 'j.navarro@dlsl.edu.ph', 'role' => 'dean', 'department' => 'CON',
                    'status' => 'Pending', 'mfa' => false, 'lastLogin' => null, 'joined' => $recent(1)],
            ],
        ]);
    }
}
