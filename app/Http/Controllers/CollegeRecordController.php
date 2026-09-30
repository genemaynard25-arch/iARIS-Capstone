<?php

namespace App\Http\Controllers;

class CollegeRecordController extends Controller
{
    /**
     * College Records page.
     *
     * Reuses the Applicants view and its placeholder people, but only the college
     * ones. College of Law is left out because it gets its own Records page.
     * The view hides the College / IS tabs and adds a Year Level column when
     * 'scope' is 'college'.
     *
     * 'year' isn't in the sample data yet, so everyone is 1st Year except a
     * couple of made-up transferees. Later this comes from the database.
     */
    public function index()
    {
        $transferees = ['APP-2026-0386' => '2nd Year', 'APP-2026-0391' => '3rd Year'];

        $students = collect(ApplicantController::sample())
            ->where('unit', 'college')
            ->reject(fn ($a) => $a['college'] === 'College of Law')
            ->map(fn ($a) => $a + ['year' => $transferees[$a['id']] ?? '1st Year'])
            ->values()
            ->all();

        return view('applicants.index', [
            'scope' => 'college',
            'academicYear' => '2025–2026',
            'periodLabel' => 'June 2026',
            'applicants' => $students,
        ]);
    }
}
