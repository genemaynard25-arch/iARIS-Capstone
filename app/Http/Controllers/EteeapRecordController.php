<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;

class EteeapRecordController extends Controller
{
    /**
     * ETEEAP Records page (Expanded Tertiary Education Equivalency and Accreditation Program).
     *
     * Same view as the other Records pages (applicants.index), with 'scope' => 'eteeap'.
     * The applicants come from the database (Import Data) via ApplicantRecords:
     *   group     program code (BSIT, BSBA, ...)
     *   program   portfolio / assessment status, worked out from the test status
     * Age, education and work history aren't in the applicants table yet, so they show "—".
     */
    public function index()
    {
        return view('applicants.index', [
            'scope' => 'eteeap',
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => now()->format('F Y'),
            'applicants' => \App\Support\RegistrarData::forRecords(ApplicantRecords::forLevel('eteeap'), 'eteeap'),
            'groupOrder' => [],
        ]);
    }
}
