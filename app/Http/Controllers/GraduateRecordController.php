<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;

class GraduateRecordController extends Controller
{
    // Program code => [full name, level]. Sets the order of the program filter.
    const PROGRAMS = [
        'MBA' => ['Master of Business Administration', "Master's"],
        'MEd' => ['Master of Education', "Master's"],
        'MIT' => ['Master of Information Technology', "Master's"],
        'MPA' => ['Master of Public Administration', "Master's"],
        'PhD' => ['Doctor of Philosophy in Education', 'Doctoral'],
    ];

    /**
     * Graduate Programs Records page (master's and doctoral).
     *
     * Same view as the other Records pages (applicants.index), with 'scope' => 'graduate'.
     * The applicants come from the database (Import Data) via ApplicantRecords:
     *   group          program code shown in the table and filter (MBA, MEd, ...)
     *   program_label  full program name for the drawer
     *   program        level: Master's or Doctoral (the table's second column)
     * PROGRAMS above only sets the order of the program filter.
     */
    public function index()
    {
        return view('applicants.index', [
            'scope' => 'graduate',
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => now()->format('F Y'),
            // Graduate School applicants from the database (Import Data); see ApplicantRecords
            'applicants' => \App\Support\RegistrarData::forRecords(ApplicantRecords::forLevel('graduate_school'), 'graduate'),
            'groupOrder' => array_keys(self::PROGRAMS),
        ]);
    }
}
