<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;
use App\Support\RegistrarData;

class CollegeRecordController extends Controller
{
    /**
     * College Records page: the college applicants from the database (Import Data),
     * via ApplicantRecords. Same view as the other Records pages, with 'scope' => 'college'.
     * The table has no year level yet, so that column shows "—".
     */
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'program_chair') {
            return $user->isGradChair() ? app(GraduateRecordController::class)->index() : app(CollegeController::class)->applicants(request());
        }

        $students = collect(ApplicantRecords::forLevel('college'))
            ->map(fn ($a) => $a + ['year' => '—'])
            ->when($user->role === 'dean', fn ($rows) => $rows
                ->filter(fn ($row) => CollegeController::matchesCollege($row['college'], CollegeController::college()['code']))->values())
            ->all();

        return view('applicants.index', [
            'scope' => 'college',
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => now()->format('F Y'),
            'applicants' => RegistrarData::forRecords($students, 'college'),
            'groupOrder' => $user->role === 'dean' ? [CollegeController::college()['code']] : ['CITE', 'CBEAM', 'CEAS', 'CIHTM', 'CON', 'CCJE'],
        ]);
    }
}
