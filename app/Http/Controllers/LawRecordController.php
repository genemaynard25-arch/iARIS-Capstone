<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;

class LawRecordController extends Controller
{
    /**
     * College of Law Records page: the Law applicants from the database (Import Data),
     * via ApplicantRecords. Same view as the other Records pages, with 'scope' => 'law'.
     * Law applicants don't fit the College data, so they have their own list with a
     * few extra keys:
     *   group          program code shown in the table and filter (JD, LLM, JSD)
     *   program_label  full program name for the drawer
     *   program        undergraduate / prior degree (the table's second column) - not
     *                  in the portal export, so this always shows "—"
     *   bar            bar exam result - also not in the portal export, so "—"
     * Status uses the usual names; the view shows "For Exam" as "For Exam / Interview".
     */
    public function index()
    {
        return view('applicants.index', [
            'scope' => 'law',
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => now()->format('F Y'),
            'applicants' => \App\Support\RegistrarData::forRecords(ApplicantRecords::forLevel('law'), 'law'),
            'groupOrder' => ['JD', 'LLM', 'JSD'],
        ]);
    }
}
