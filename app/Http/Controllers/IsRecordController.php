<?php

namespace App\Http\Controllers;

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
     * The sample data has no sub-level yet, so it's worked out from the grade.
     * Later it comes from the applicants table's sub_level column.
     */
    public function index()
    {
        $subLevels = ['Preschool', 'Grade School', 'Junior High', 'Senior High'];

        $students = collect(ApplicantController::sample())
            ->where('unit', 'is')
            ->map(function ($a) {
                $subLevel = $this->subLevelFor($a['level']);

                return array_merge($a, [
                    'sub_level' => $subLevel,
                    'program' => $subLevel === 'Senior High' ? $a['program'] : '—',
                ]);
            })
            // Role-based filtering: the SHS principal only sees Senior High students
            ->when(auth()->user()->isShsPrincipal(), fn ($rows) => $rows->where('sub_level', 'Senior High'))
            ->values()
            ->all();

        return view('applicants.index', [
            'scope' => 'is',
            'academicYear' => '2025–2026',
            'periodLabel' => 'June 2026',
            'applicants' => $students,
            'groupOrder' => $subLevels,
        ]);
    }

    // "Grade 11" -> Senior High, "Grade 7" -> Junior High, "Grade 3" -> Grade School, anything else -> Preschool
    private function subLevelFor(string $level): string
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
