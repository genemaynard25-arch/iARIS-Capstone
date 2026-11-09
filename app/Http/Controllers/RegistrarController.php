<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;
use App\Support\RegistrarData;

class RegistrarController extends Controller
{
    /**
     * Registrar pages (College Registrar and IS Registrar): Dashboard, Ready for Enrollment, Reports, and Messages.
     * Records are view only (the Records pages, with "Ask IATO about this record" in the drawer).
     *
     * Real data where the database has it (College, Graduate Programs, ETEEAP); College of Law and
     * iPACE are placeholders (see App\Support\RegistrarData). Messages and generated reports are
     * front end only for now.
     * TODO (RBAC): limit these routes to users whose role is 'registrar' (plus IATO admins).
     */
    public function dashboard()
    {
        $rows = RegistrarData::rows();

        return view('registrar.dashboard', $this->shared() + [
            'counts' => RegistrarData::countsByGroup($rows),
            'summary' => [
                'applicants' => count($rows),
                'passed' => collect($rows)->where('exam_passed', true)->count(),
                'notReserved' => collect($rows)->where('exam_passed', true)->where('reservation_paid', false)->count(),
                'reserved' => collect($rows)->where('ready_for_enrollment', true)->count(),
            ],
            'messages' => MessagesController::threads(),
            'latestPaid' => collect($rows)->where('status', 'Paid')->sortByDesc('paid')->take(5)->values()->all(),
        ]);
    }

    public function enrollment()
    {
        return view('registrar.enrollment', $this->shared() + [
            'rows' => collect(RegistrarData::rows())->where('ready_for_enrollment', true)->values()->all(),
        ]);
    }

    public function reports()
    {
        return view('registrar.reports', $this->shared() + [
            'rows' => RegistrarData::rows(),
            // Reports generated earlier (placeholders until reports are saved)
            'history' => [
                ['Paid reservations list', count(RegistrarData::units()) > 1 ? 'All units' : array_key_first(RegistrarData::units()), 'Last 7 days', 'Excel (CSV)', now()->subDay()->format('M j, Y') . ' · 4:30 PM'],
                ['Applicants by status', array_key_first(RegistrarData::units()), 'AY ' . ApplicantRecords::academicYear() . ' (to date)', 'PDF', now()->subDays(7)->format('M j, Y') . ' · 10:12 AM'],
            ],
        ]);
    }

    // What every Registrar page (and the Registrar sidebar) needs
    private function shared(): array
    {
        $user = auth()->user();
        $me = [
            'name' => self::lastFirst($user->name),
            'role' => RegistrarData::scope() === 'is' ? 'IS Registrar' : 'College Registrar',
        ];

        return [
            'academicYear' => ApplicantRecords::academicYear(),
            'me' => $me,
            'units' => array_keys(RegistrarData::units()),
            // IS Registrar: one unit, so pages break it down by level instead
            'levels' => RegistrarData::scope() === 'is' ? RegistrarData::IS_LEVELS : [],
        ];
    }

    // "Remian Santos" -> "Santos, Remian"
    public static function lastFirst(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $last = array_pop($parts);

        return $parts ? $last . ', ' . implode(' ', $parts) : $last;
    }
}
