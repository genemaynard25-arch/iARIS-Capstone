<?php

namespace App\Http\Controllers;

use App\Support\ApplicantRecords;

class IsRecordController extends Controller
{
    /**
     * Integrated School Records page.
     *
     * Same view as College Records (applicants.index), with 'scope' => 'is'.
     * Students are grouped by sub-level, the same four choices as the DLSL
     * application form: Preschool, Grade School, Junior High, Senior High.
     * Only Senior High has strands, so everyone else shows "—" there.
     *
     * The students come from the applicants table; its sub_level column gives the sub-level
     * (subLevelFor() is kept for rows that only have a grade, like the placeholder people).
     */
    public function index()
    {
        $subLevels = ['Preschool', 'Grade School', 'Junior High', 'Senior High'];
        // An Assistant Principal only sees their own levels
        $mine = match (auth()->user()->assistantLevel()) {
            'Grade School' => ['Preschool', 'Grade School'],
            'Junior High' => ['Junior High'],
            'Senior High' => ['Senior High'],
            default => null,
        };
        if ($mine) {
            $subLevels = $mine;
        }

        // Integrated School applicants from the database (Import Data); see ApplicantRecords
        $students = collect(ApplicantRecords::forLevel('is'))
            ->map(function ($a) {
                $subLevel = $a['sub_level'] ?? self::subLevelFor($a['level']);

                return array_merge($a, [
                    'sub_level' => $subLevel,
                    'program' => $subLevel === 'Senior High' ? $a['program'] : '—',
                ]);
            })
            // Role-based filtering: the SHS principal only sees Senior High students
            ->when(auth()->user()->isShsPrincipal(), fn ($rows) => $rows->where('sub_level', 'Senior High'))
            ->when($mine, fn ($rows) => $rows->whereIn('sub_level', $mine))
            ->values()
            ->all();

        return view('applicants.index', [
            'scope' => 'is',
            'academicYear' => ApplicantRecords::academicYear(),
            'periodLabel' => now()->format('F Y'),
            'applicants' => \App\Support\RegistrarData::forRecords($students, 'is'),
            'groupOrder' => $subLevels,
        ]);
    }

    // "Grade 11" -> Senior High, "Grade 7" -> Junior High, "Grade 3" -> Grade School, anything else -> Preschool
    public static function subLevelFor(string $level): string
    {
        $grade = (int) preg_replace('/\D/', '', $level);

        return match (true) {
            $grade >= 11 => 'Senior High',
            $grade >= 7 => 'Junior High',
            $grade >= 1 => 'Grade School',
            default => 'Preschool',
        };
    }
}
