<?php

namespace App\Http\Controllers;

use App\Support\LampOffice;

/**
 * LAMP Office pages: Dashboard, Scholars and Reports (Analytics & Reports). Messages is MessagesController.
 * IATO shares only whether an applicant answered Yes to the scholarship question and their exam result;
 * LAMP checks each applicant who passed. See App\Support\LampOffice for the data.
 * TODO (RBAC): limit these routes to users whose role is 'lamp' (plus IATO admins).
 */
class LampController extends Controller
{
    public function dashboard()
    {
        return view('lamp.dashboard', $this->shared() + [
            'reports' => LampOffice::reports(),
            'lastUpdate' => LampOffice::lastUpdate(),
        ]);
    }

    public function scholars()
    {
        return view('lamp.scholars', $this->shared());
    }

    public function reports()
    {
        return view('lamp.reports', $this->shared() + [
            'reports' => LampOffice::reports(),
            'readReports' => LampOffice::READ_REPORTS,
            'trend' => LampOffice::trend(),
        ]);
    }

    // What every page needs: the applicants who answered Yes to the scholarship question
    private function shared(): array
    {
        return [
            'academicYear' => \App\Support\ApplicantRecords::academicYear(),
            'applicants' => LampOffice::applicants(),
        ];
    }
}
