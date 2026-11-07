<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;

class CollegeRecordController extends Controller
{
    /**
     * College Records page: the college applicants from the database (Import Data),
     * via ApplicantRecords. Same view as the other Records pages, with 'scope' => 'college'.
     * The table has no year level yet, so that column shows "—".
     */
    public function index()
    {
        $students = collect(ApplicantRecords::forLevel('college'))
            ->map(fn ($a) => $a + ['year' => '—'])
            ->all();

        return view('applicants.index', [
            'scope' => 'college',
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => now()->format('F Y'),
            'applicants' => $students,
        ]);
    }
}
