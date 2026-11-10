<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollegeController extends Controller
{
    /**
     * The colleges and their programs (CITE's names and totals as in the dean mockups; the other
     * colleges' programs from their Settings mockups, with placeholder numbers). Same idea as the Executive pages.
     * Later these numbers would come from the admission_stats and applicants tables.
     *
     * applicants / enrolled: one number per academic year in ExecutiveController::YEARS (the last is this year)
     * funnel: this AY — Applied, Took exam, Passed, Paid, Enrolled
     * status: applicants this AY by status — Pending, For Exam, Paid (they add up to this year's applicants)
     * paidLastYear: paid reservations last AY (for the "vs last AY" change)
     */
    private const COLLEGES = [
        'CITE' => ['name' => 'College of Information Technology and Engineering', 'programs' => [
            'BSCS' => ['name' => 'BS Computer Science', 'applicants' => [82, 88, 94, 100], 'enrolled' => [20, 23, 25, 26],
                'funnel' => [100, 74, 63, 30, 26], 'status' => [28, 42, 30], 'paidLastYear' => 29],
            'BSIT' => ['name' => 'BS Information Technology', 'applicants' => [85, 95, 103, 114], 'enrolled' => [21, 24, 28, 30],
                'funnel' => [114, 84, 71, 34, 30], 'status' => [32, 48, 34], 'paidLastYear' => 31],
            'BSCpE' => ['name' => 'BS Computer Engineering', 'applicants' => [49, 51, 53, 56], 'enrolled' => [12, 13, 14, 14],
                'funnel' => [56, 41, 35, 16, 14], 'status' => [16, 24, 16], 'paidLastYear' => 16],
            'BSEE' => ['name' => 'BS Electrical Engineering', 'applicants' => [36, 38, 40, 42], 'enrolled' => [8, 9, 10, 11],
                'funnel' => [42, 31, 26, 12, 11], 'status' => [12, 18, 12], 'paidLastYear' => 11],
            'BSECE' => ['name' => 'BS Electronics Engineering', 'applicants' => [31, 33, 34, 35], 'enrolled' => [7, 8, 9, 9],
                'funnel' => [35, 26, 22, 10, 9], 'status' => [10, 15, 10], 'paidLastYear' => 10],
            'BSIE' => ['name' => 'BS Industrial Engineering', 'applicants' => [40, 43, 46, 50], 'enrolled' => [10, 11, 13, 13],
                'funnel' => [50, 37, 31, 15, 13], 'status' => [14, 21, 15], 'paidLastYear' => 14],
            'BSEMC' => ['name' => 'BS Entertainment & Multimedia Computing', 'applicants' => [29, 33, 38, 43], 'enrolled' => [7, 9, 10, 11],
                'funnel' => [43, 32, 27, 13, 11], 'status' => [12, 18, 13], 'paidLastYear' => 12],
            'BSArch' => ['name' => 'BS Architecture', 'applicants' => [67, 69, 70, 72], 'enrolled' => [16, 17, 18, 19],
                'funnel' => [72, 54, 46, 21, 19], 'status' => [20, 31, 21], 'paidLastYear' => 21],
        ]],
        'CBEAM' => ['name' => 'College of Business, Economics, Accountancy, and Management', 'programs' => [
            'BSA' => ['name' => 'BS Accountancy', 'applicants' => [106, 112, 119, 126], 'enrolled' => [32, 32, 34, 35],
                'funnel' => [126, 93, 79, 40, 35], 'status' => [34, 52, 40], 'paidLastYear' => 38],
            'BSBA' => ['name' => 'BS Business Administration', 'applicants' => [149, 155, 162, 168], 'enrolled' => [44, 44, 46, 46],
                'funnel' => [168, 124, 105, 52, 46], 'status' => [46, 70, 52], 'paidLastYear' => 51],
            'BSEntrep' => ['name' => 'BS Entrepreneurship', 'applicants' => [36, 40, 43, 47], 'enrolled' => [9, 10, 11, 11],
                'funnel' => [47, 35, 30, 13, 11], 'status' => [14, 20, 13], 'paidLastYear' => 12],
            'BSLM' => ['name' => 'BS Legal Management', 'applicants' => [57, 58, 59, 60], 'enrolled' => [15, 15, 15, 15],
                'funnel' => [60, 44, 37, 17, 15], 'status' => [18, 25, 17], 'paidLastYear' => 17],
        ]],
        'CEAS' => ['name' => 'College of Education, Arts and Sciences', 'programs' => [
            'BSEd' => ['name' => 'Bachelor of Secondary Education', 'applicants' => [75, 74, 72, 71], 'enrolled' => [19, 18, 18, 17],
                'funnel' => [71, 53, 45, 19, 17], 'status' => [22, 30, 19], 'paidLastYear' => 19],
            'BAMMA' => ['name' => 'BA Multimedia Arts', 'applicants' => [46, 50, 54, 58], 'enrolled' => [14, 14, 16, 16],
                'funnel' => [58, 43, 37, 18, 16], 'status' => [16, 24, 18], 'paidLastYear' => 17],
            'BAComm' => ['name' => 'BA Communication', 'applicants' => [46, 47, 49, 50], 'enrolled' => [13, 13, 13, 13],
                'funnel' => [50, 37, 31, 15, 13], 'status' => [14, 21, 15], 'paidLastYear' => 15],
            'BSPsych' => ['name' => 'BS Psychology', 'applicants' => [87, 93, 100, 107], 'enrolled' => [25, 26, 28, 29],
                'funnel' => [107, 79, 67, 33, 29], 'status' => [30, 44, 33], 'paidLastYear' => 31],
            'BSBio' => ['name' => 'BS Biology', 'applicants' => [54, 57, 60, 63], 'enrolled' => [16, 16, 17, 17],
                'funnel' => [63, 47, 40, 19, 17], 'status' => [18, 26, 19], 'paidLastYear' => 18],
        ]],
        'CIHTM' => ['name' => 'College of International Hospitality and Tourism Management', 'programs' => [
            'BSHM' => ['name' => 'BS Hospitality Management', 'applicants' => [116, 122, 128, 134], 'enrolled' => [33, 34, 36, 36],
                'funnel' => [134, 99, 84, 41, 36], 'status' => [38, 55, 41], 'paidLastYear' => 40],
            'BSTM' => ['name' => 'BS Tourism Management', 'applicants' => [64, 69, 74, 80], 'enrolled' => [16, 17, 18, 19],
                'funnel' => [80, 59, 50, 22, 19], 'status' => [24, 34, 22], 'paidLastYear' => 21],
            'BSCA' => ['name' => 'BS Culinary Arts', 'applicants' => [38, 39, 40, 41], 'enrolled' => [11, 11, 11, 11],
                'funnel' => [41, 30, 26, 12, 11], 'status' => [12, 17, 12], 'paidLastYear' => 12],
        ]],
        'CCJE' => ['name' => 'College of Criminal Justice Education', 'programs' => [
            'BSCrim' => ['name' => 'BS Criminology', 'applicants' => [133, 141, 149, 158], 'enrolled' => [38, 39, 41, 42],
                'funnel' => [158, 117, 99, 48, 42], 'status' => [44, 66, 48], 'paidLastYear' => 46],
            'BSFS' => ['name' => 'BS Forensic Science', 'applicants' => [24, 27, 30, 33], 'enrolled' => [6, 7, 8, 8],
                'funnel' => [33, 24, 20, 9, 8], 'status' => [10, 14, 9], 'paidLastYear' => 8],
        ]],
        // Optional keys: 'title' — how the title reads when it isn't the code ("Dean, College of Nursing");
        // 'label' — the short name on the pages when it isn't the code; 'office' — Settings' office line;
        // 'word' — what the groups are called ('program' by default, 'level' for the Integrated School).
        'CON' => ['name' => 'College of Nursing', 'title' => 'College of Nursing', 'programs' => [
            'BSN' => ['name' => 'BS Nursing', 'applicants' => [123, 129, 135, 142], 'enrolled' => [36, 37, 39, 39],
                'funnel' => [142, 105, 89, 44, 39], 'status' => [40, 58, 44], 'paidLastYear' => 43],
        ]],
        // The College of Law (Account Management stores it as the department "College of Law"). Its applicants
        // show their degree and whether they passed the Bar.
        'LAW' => ['name' => 'College of Law', 'title' => 'College of Law', 'label' => 'College of Law', 'middle' => 'For Exam / Interview', 'programs' => [
            'JD' => ['name' => 'Juris Doctor', 'applicants' => [118, 129, 138, 152], 'enrolled' => [30, 33, 35, 38],
                'funnel' => [152, 124, 98, 46, 38], 'status' => [34, 72, 46], 'paidLastYear' => 43],
            'LLM' => ['name' => 'Master of Laws', 'applicants' => [17, 19, 21, 24], 'enrolled' => [5, 6, 6, 7],
                'funnel' => [24, 20, 16, 8, 7], 'status' => [5, 11, 8], 'paidLastYear' => 7],
            'JSD' => ['name' => 'Doctor of Juridical Science', 'applicants' => [7, 8, 9, 10], 'enrolled' => [1, 1, 2, 2],
                'funnel' => [10, 8, 6, 2, 2], 'status' => [3, 5, 2], 'paidLastYear' => 2],
        ]],
        // The Integrated School, for its Principal: the same pages by level instead of program (totals as in the mockup)
        'IS' => ['name' => 'Integrated School', 'title' => 'Integrated School', 'label' => 'Integrated School', 'subtitle' => 'Kindergarten to Grade 12',
            'office' => 'Integrated School · Kindergarten to Grade 12', 'word' => 'level', 'programs' => [
            'K' => ['name' => 'Kindergarten', 'applicants' => [51, 53, 55, 58], 'enrolled' => [12, 12, 13, 14],
                'funnel' => [58, 46, 39, 18, 14], 'status' => [10, 30, 18], 'paidLastYear' => 17],
            'GS' => ['name' => 'Grade School', 'applicants' => [194, 199, 204, 214], 'enrolled' => [49, 52, 54, 55],
                'funnel' => [214, 173, 146, 74, 55], 'status' => [32, 108, 74], 'paidLastYear' => 72],
            'JHS' => ['name' => 'Junior High School', 'applicants' => [205, 214, 221, 236], 'enrolled' => [52, 56, 58, 61],
                'funnel' => [236, 190, 160, 82, 61], 'status' => [36, 118, 82], 'paidLastYear' => 78],
            'SHS' => ['name' => 'Senior High School', 'applicants' => [190, 202, 214, 234], 'enrolled' => [47, 52, 55, 60],
                'funnel' => [234, 189, 160, 80, 60], 'status' => [34, 120, 80], 'paidLastYear' => 74],
        ]],
        // The Graduate Programs (under CBEAM), for their Program Chair. 'level': Master's or Doctoral.
        // 'middle': what "For Exam" is called here.
        'GRAD' => ['name' => 'Graduate Programs', 'title' => 'Graduate Programs', 'label' => 'Graduate Programs', 'subtitle' => 'Graduate Programs · under CBEAM',
            'office' => 'Graduate Programs · under CBEAM', 'scope' => 'the Graduate Programs', 'middle' => 'For Exam / Interview', 'programs' => [
            'MBA' => ['name' => 'Master of Business Administration', 'level' => "Master's", 'applicants' => [74, 81, 88, 96], 'enrolled' => [17, 19, 21, 22],
                'funnel' => [96, 77, 63, 33, 22], 'status' => [25, 38, 33], 'paidLastYear' => 30],
            'MEd' => ['name' => 'Master of Education', 'level' => "Master's", 'applicants' => [61, 64, 68, 72], 'enrolled' => [14, 15, 16, 17],
                'funnel' => [72, 58, 47, 25, 17], 'status' => [19, 28, 25], 'paidLastYear' => 24],
            'MIT' => ['name' => 'Master of Information Technology', 'level' => "Master's", 'applicants' => [39, 43, 48, 54], 'enrolled' => [9, 11, 12, 13],
                'funnel' => [54, 43, 35, 19, 13], 'status' => [14, 21, 19], 'paidLastYear' => 17],
            'MPA' => ['name' => 'Master of Public Administration', 'level' => "Master's", 'applicants' => [33, 35, 36, 38], 'enrolled' => [8, 8, 8, 9],
                'funnel' => [38, 31, 25, 13, 9], 'status' => [10, 15, 13], 'paidLastYear' => 13],
            'PhD' => ['name' => 'PhD in Education', 'level' => 'Doctoral', 'applicants' => [19, 21, 22, 24], 'enrolled' => [4, 5, 5, 5],
                'funnel' => [24, 19, 16, 8, 5], 'status' => [6, 10, 8], 'paidLastYear' => 7],
        ]],
        // iPACE and ETEEAP, for the iPACE Director (who heads both). Two units: each program has a 'unit',
        // and the same program can be in both (so the codes start with I- or E-). Unit totals as in the mockup.
        // 'profiles': the Learner profile on the Briefing, per unit: [title, [[group, [[label, count], …]], …], note]
        'IPACE' => ['name' => 'Institute for Professional Advancement and Continuing Education', 'title' => 'iPACE', 'label' => 'iPACE + ETEEAP',
            'subtitle' => 'Institute for Professional Advancement and Continuing Education · with ETEEAP', 'scope' => 'iPACE and ETEEAP', 'programs' => [
            'I-BSIT' => ['unit' => 'iPACE', 'name' => 'BS Information Technology', 'applicants' => [46, 62, 82, 104], 'enrolled' => [12, 16, 21, 26],
                'funnel' => [104, 69, 61, 30, 26], 'status' => [29, 45, 30], 'paidLastYear' => 23],
            'I-BSCS' => ['unit' => 'iPACE', 'name' => 'BS Computer Science', 'applicants' => [28, 37, 47, 58], 'enrolled' => [7, 10, 12, 15],
                'funnel' => [58, 38, 33, 17, 15], 'status' => [16, 25, 17], 'paidLastYear' => 14],
            'I-BSBA' => ['unit' => 'iPACE', 'name' => 'BS Business Administration', 'applicants' => [33, 44, 57, 72], 'enrolled' => [9, 11, 15, 18],
                'funnel' => [72, 48, 42, 21, 18], 'status' => [20, 31, 21], 'paidLastYear' => 17],
            'I-BSN' => ['unit' => 'iPACE', 'name' => 'BS Nursing', 'applicants' => [26, 32, 39, 46], 'enrolled' => [7, 9, 11, 12],
                'funnel' => [46, 31, 27, 14, 12], 'status' => [12, 20, 14], 'paidLastYear' => 12],
            'I-BEED' => ['unit' => 'iPACE', 'name' => 'Bachelor of Elementary Education', 'applicants' => [17, 21, 27, 32], 'enrolled' => [5, 6, 7, 9],
                'funnel' => [32, 21, 19, 10, 9], 'status' => [9, 13, 10], 'paidLastYear' => 8],
            'E-BSIT' => ['unit' => 'ETEEAP', 'name' => 'BS Information Technology', 'applicants' => [16, 20, 24, 30], 'enrolled' => [5, 6, 6, 7],
                'funnel' => [30, 19, 16, 8, 7], 'status' => [9, 13, 8], 'paidLastYear' => 6],
            'E-BSBA' => ['unit' => 'ETEEAP', 'name' => 'BS Business Administration', 'applicants' => [13, 16, 20, 24], 'enrolled' => [3, 5, 5, 5],
                'funnel' => [24, 16, 13, 6, 5], 'status' => [7, 11, 6], 'paidLastYear' => 5],
            'E-BEED' => ['unit' => 'ETEEAP', 'name' => 'Bachelor of Elementary Education', 'applicants' => [9, 11, 14, 16], 'enrolled' => [2, 3, 4, 4],
                'funnel' => [16, 10, 8, 4, 4], 'status' => [5, 7, 4], 'paidLastYear' => 3],
            'E-BSN' => ['unit' => 'ETEEAP', 'name' => 'BS Nursing', 'applicants' => [9, 10, 12, 14], 'enrolled' => [3, 3, 3, 4],
                'funnel' => [14, 9, 8, 4, 4], 'status' => [4, 6, 4], 'paidLastYear' => 3],
            'E-BSHRM' => ['unit' => 'ETEEAP', 'name' => 'BS Hotel & Restaurant Management', 'applicants' => [5, 6, 8, 10], 'enrolled' => [1, 1, 2, 2],
                'funnel' => [10, 7, 6, 2, 2], 'status' => [3, 5, 2], 'paidLastYear' => 2],
        ], 'profiles' => [
            'iPACE' => ['Learner profile', [
                ['Delivery mode', [['Online', 198], ['Blended', 114]]],
                ['Working students', [['Working', 236], ['Not working', 76]]],
            ], 'Most iPACE applicants study while working, so most choose Online classes.'],
            'ETEEAP' => ['Applicant profile', [
                ['Portfolio and competency assessment', [['Not yet submitted', 31], ['Submitted, being assessed', 38], ['Assessment cleared', 25]]],
                ['Work experience', [['5–9 years', 38], ['10–14 years', 34], ['15 years or more', 22]]],
                ['Highest educational attainment', [['High school graduate', 19], ['Some college', 47], ['Vocational / technical', 28]]],
            ], 'Average work experience is 11.4 years. ETEEAP requires at least 5 years.'],
        ]],
    ];

    // How a dean is greeted ("Good morning, Engr. Villafuerte"). Names as in the mockups.
    private const HONORIFICS = [
        'Cherrie Villafuerte' => 'Engr.', 'Jasmin De Silva' => 'Dr.', 'Gina Dimaano' => 'Dr.',
        'Evangeline Necio' => 'Ms.', 'Aristotle Balba' => 'Dr.', 'Margie Ebreo' => 'Dr.',
        'Kristine Anne Dimaculangan' => 'Ms.', 'Haidee Angeles' => 'Dr.', 'Erickson Martinez' => 'Dr.', 'Ma. Isabel Romero' => 'Atty.',
    ];

    /**
     * The signed-in dean's college: ['code' => 'CITE', 'name' => …, 'programs' => […]]. The IS Principal gets 'IS'.
     * Reads users.assigned_college, falling back to users.department for older rows that predate
     * that column. Anyone without either (e.g. the Program Chair) is CITE.
     */
    public static function college(): array
    {
        $user = auth()->user();
        $code = $user?->isPrincipal() ? 'IS' : ($user?->isDirector() ? 'IPACE' : ($user?->isGradChair() ? 'GRAD' : null));
        $code ??= strtoupper((string) ($user?->assigned_college ?? $user?->department ?? ''));
        // A college can also be stored by its full name ("College of Law")
        $code = isset(self::COLLEGES[$code]) ? $code
            : (collect(self::COLLEGES)->search(fn ($c) => strtoupper($c['name']) === $code) ?: 'CITE');

        return ['code' => $code] + self::COLLEGES[$code];
    }

    /** Where these pages live for the signed-in user: /principal for the IS Principal, /college otherwise. */
    public static function base(): string
    {
        $user = auth()->user();

        return url($user?->isPrincipal() ? '/principal' : ($user?->isDirector() ? '/director' : '/college'));
    }

    // The applicants.level value a college code's records actually live under.
    private static function levelFor(string $collegeCode): string
    {
        return match ($collegeCode) {
            'IS' => 'is',
            'GRAD' => 'graduate_school',
            'IPACE' => 'ipace',
            'LAW' => 'law',
            default => 'college',
        };
    }

    /**
     * Real per-program numbers from the applicants table, in the same shape the hardcoded
     * COLLEGES programs used to have, matched by each applicant's program_or_track against
     * the program's name. There is only one real school year of data (no import history), so
     * all 4 yearly slots repeat the current total instead of 0 (which would divide by zero in
     * the Analytics page's % change math) or a fabricated prior year.
     */
    private static function realPrograms(string $collegeCode, array $programs): array
    {
        $level = self::levelFor($collegeCode);
        $applicants = \App\Models\Applicant::where('level', $level)->get();

        $result = [];
        foreach ($programs as $code => $p) {
            $matched = $applicants->where('program_or_track', $p['name']);
            $submitted = $matched->count();
            $tookExam = $matched->where('admission_test_status', '!=', 'not_taken')->count();
            $passed = $matched->where('admission_test_status', 'passed')->count();
            $completed = $matched->where('application_status', 'completed')->count();
            $pending = $matched->whereIn('application_status', ['pooling', 'not_submitted'])->count();
            $forExam = $matched->where('application_status', 'submitted')->count();

            // array_merge, not $p + [...]: array union keeps the LEFT side's value on key
            // collision, which would silently keep the old hardcoded numbers instead of these.
            $result[$code] = array_merge($p, [
                'applicants' => [$submitted, $submitted, $submitted, $submitted],
                'enrolled' => [$completed, $completed, $completed, $completed],
                'funnel' => [$submitted, $tookExam, $passed, $completed, $completed],
                'status' => [$pending, $forExam, $completed],
                'paidLastYear' => $completed,
            ]);
        }

        return $result;
    }

    /**
     * Real applicant rows for the given programs/level, matched the same way as realPrograms().
     * email/phone/address/dob/exam/paid are null: the regular import doesn't fill those in
     * (only the Add/Edit Applicant form does), so they're honestly blank, not fabricated.
     */
    private static function realApplicantsIn(array $scope, string $collegeCode): array
    {
        $level = self::levelFor($collegeCode);
        $byName = collect($scope)->mapWithKeys(fn ($p, $code) => [$p['name'] => $code]);
        $statusLabel = fn ($s) => match ($s) {
            'submitted' => 'For Exam',
            'completed' => 'Paid',
            default => 'Pending',
        };

        return \App\Models\Applicant::where('level', $level)
            ->get()
            ->filter(fn ($a) => $byName->has($a->program_or_track))
            ->map(fn ($a) => [
                'id' => $a->reference_number,
                'name' => "{$a->last_name}, {$a->first_name}",
                'program' => $byName[$a->program_or_track],
                'status' => $statusLabel($a->application_status),
                'applied' => optional($a->created_at)->toDateString(),
                'updated' => optional($a->updated_at)->format('M j, g:i A'),
                'dob' => optional($a->date_of_birth)->format('F j, Y'),
                'gender' => $a->gender === 'male' ? 'Male' : 'Female',
                'email' => $a->email,
                'phone' => $a->contact_number,
                'address' => $a->address,
                'exam' => null,
                'paid' => null,
            ])
            ->sortByDesc('applied')
            ->values()
            ->all();
    }

    /** Program names, for Settings (Briefing and Display). */
    public static function programNames(): array
    {
        // iPACE and ETEEAP share some programs: list each name once
        return array_values(array_unique(array_column(self::college()['programs'], 'name')));
    }

    // What the pages share: the programs in scope, the years, and who is looking.
    // A dean sees every program in their college; a program chair only their own (BS Computer Science for now).
    private function common(): array
    {
        $isChair = auth()->user()->isProgramChair();
        $college = self::college();

        return [
            'isChair' => $isChair,
            // Short name of what you see: "CITE", "Integrated School", or a program chair's program
            'label' => $isChair ? 'BS Computer Science' : ($college['label'] ?? $college['code']),
            'college' => ['code' => $college['code'], 'name' => $college['name'], 'subtitle' => $college['subtitle'] ?? $college['name']],
            // What the groups are called ("program" or "level") and what you see ("your college", "your school", "iPACE and ETEEAP")
            'word' => $college['word'] ?? 'program',
            'whole' => $college['scope'] ?? ($college['code'] === 'IS' ? 'your school' : 'your college'),
            // What "For Exam" is called ("For Exam / Interview" in the Graduate Programs)
            'middle' => $college['middle'] ?? 'For Exam',
            // The Learner profile on the Briefing (iPACE Director only)
            'profiles' => $college['profiles'] ?? [],
            // The address of these pages: /college for deans and program chairs, /principal for the IS Principal
            'base' => self::base(),
            'programs' => $isChair
                ? self::realPrograms('CITE', ['BSCS' => self::COLLEGES['CITE']['programs']['BSCS']])
                : self::realPrograms($college['code'], $college['programs']),
            'years' => ExecutiveController::YEARS,
        ];
    }

    // Briefing: this week's summary, tiles, "Needs your attention", programs at a glance, reports, Ask iARIS
    public function dashboard()
    {
        $user = auth()->user();
        $common = $this->common();

        return view('college.dashboard', $common + [
            // "Engr. Villafuerte", or the first name when there's no honorific
            'greetName' => isset(self::HONORIFICS[$user->name])
                ? self::HONORIFICS[$user->name] . ' ' . \Illuminate\Support\Str::before($user->displayName(), ',')
                : \Illuminate\Support\Str::before($user->name, ' '),
            // The 3 newest reports the IATO sent (all of them are on the Reports page)
            'reports' => array_slice(self::received($common['isChair']), 0, 3),
        ]);
    }

    // Applicants: the applicants in scope, view only. ?program=BSCS picks a program first (the Briefing's "See applicants").
    // The iPACE Director gets their own page: iPACE or ETEEAP (?unit=eteeap), each with its own columns.
    public function applicants(Request $request)
    {
        $common = $this->common();
        if (auth()->user()->isDirector()) {
            $unit = $request->query('unit') === 'eteeap' ? 'ETEEAP' : 'iPACE';
            $programs = array_filter($common['programs'], fn ($p) => $p['unit'] === $unit);

            return view('college.applicants-director', $common + [
                'unit' => $unit,
                'unitPrograms' => $programs,
                // Applicants in each unit (for the switch at the top)
                'unitTotals' => array_map(fn ($u) => array_sum(array_map(fn ($p) => array_sum($p['status']), array_filter($common['programs'], fn ($p) => $p['unit'] === $u))), ['iPACE' => 'iPACE', 'ETEEAP' => 'ETEEAP']),
                'selected' => isset($programs[$request->query('program')]) ? $request->query('program') : '',
                'rows' => self::directorApplicants()[$unit],
            ]);
        }

        return view('college.applicants', $common + [
            'selected' => isset($common['programs'][$request->query('program')]) ? $request->query('program') : '',
            'rows' => self::realApplicantsIn($common['programs'], $common['college']['code']),
        ]);
    }

    // Analytics: Overview (trend and funnel), Programs, Compare years, AI insights. Worked out by the page from $programs.
    public function analytics()
    {
        return view('college.analytics', $this->common());
    }

    // Reports: what IATO sent, four reports you can generate, and the ones you generated
    public function reports()
    {
        $common = $this->common();

        return view('college.reports', $common + [
            'received' => self::received($common['isChair']),
            // For the "Paid reservations" report: applicants in scope who paid. Real rows never have
            // a 'paid' value (payments aren't tracked yet), so this is honestly empty outside Director.
            'paidRows' => array_values(array_map(fn ($a) => [$a['id'], $a['name'], $common['programs'][$a['program']]['name'], $a['paid']],
                array_filter(auth()->user()->isDirector() ? array_merge(...array_values(self::directorApplicants())) : self::realApplicantsIn($common['programs'], $common['college']['code']), fn ($a) => $a['paid']))),
            // Reports you generated before (kept for 90 days)
            'history' => [['template' => 'programs', 'period' => 'AY 2025–26', 'at' => 'Jun 5, 2026 · 10:12 AM']],
        ]);
    }

    // Messages with IATO: the same page as the Executive Office's
    public function messages()
    {
        return view('executive.messages', ['threads' => self::threads(), 'base' => self::base()]);
    }

    /** Conversations IATO answered (the number on the sidebar's Messages link). */
    public static function answeredMessages(): int
    {
        return collect(self::threads())->where('status', 'answered')->count();
    }

    // Your conversations with IATO, newest first (CITE's is the one in the mockup; the other deans have none yet)
    private static function threads(): array
    {
        if (self::college()['code'] !== 'CITE' || auth()->user()->isProgramChair()) {
            return [];
        }

        return [
            ['id' => 'MSG-0112', 'topic' => 'Question about the data', 'subject' => 'BS Architecture applicants look low', 'status' => 'waiting', 'messages' => [
                ['from' => 'me', 'at' => 'Jun 12, 2026 · 9:14 AM', 'text' => 'BS Architecture shows 72 applicants, but our department head counted more walk-in applications last week. Are the walk-ins already encoded?'],
            ]],
        ];
    }

    /**
     * Reports the IATO sent to this office, newest first.
     * status: new or read. 'kind' picks the table you get when you download it (see the Reports page).
     */
    public static function received(bool $isChair): array
    {
        $college = self::college();
        $name = $isChair ? 'BS Computer Science' : ($college['label'] ?? $college['code']);

        return [
            ['title' => "{$name} Applicants Summary — AY 2025–26", 'kind' => 'programs', 'by' => 'Renegado, Randolph', 'role' => 'Super Admin',
                'at' => 'Jun 12, 2026 · 8:00 AM', 'status' => 'new',
                'message' => "Here is the latest applicant summary for {$name}. Paid reservations are up this week. Let us know if you need a breakdown by program."],
            ['title' => "{$name} Paid Reservations — as of May 30", 'kind' => 'paid', 'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff',
                'at' => 'May 30, 2026 · 3:15 PM', 'status' => 'read',
                'message' => 'Attached is the list of paid reservations as of May 30.'],
            ['title' => "{$name} Enrollment Trends — AY 2024–25", 'kind' => 'trend', 'by' => 'Renegado, Randolph', 'role' => 'Super Admin',
                'at' => 'Jun 14, 2025 · 8:30 AM', 'status' => 'read',
                'message' => 'Year-end enrollment trends for ' . ($college['scope'] ?? ($college['code'] === 'IS' ? 'your school' : 'your college')) . '.'],
        ];
    }

    /**
     * The iPACE Director's sample applicants (as in the mockups), per unit. 'program' is a code in COLLEGES['IPACE'].
     * iPACE: delivery mode, year level, working student, employer. ETEEAP: age, education, work history, portfolio.
     */
    private static function directorApplicants(): array
    {
        $paid = fn ($on, $amount) => ['paid' => $on, 'amount' => $amount];

        return [
            'iPACE' => [
                ['id' => 'APP-2026-I005', 'name' => 'Villanueva, Rodel Santos C.', 'program' => 'I-BEED', 'mode' => 'Online', 'year' => '2nd Year', 'working' => 'Yes', 'employer' => 'DepEd Lipa · Teacher Aide',
                    'dob' => 'June 20, 1998', 'gender' => 'Male', 'email' => 'rs.villanueva@dlsl.edu.ph', 'phone' => '0921 500 5555', 'address' => 'Lipa City, Batangas',
                    'status' => 'For Exam', 'applied' => '2026-05-11', 'updated' => 'Jun 4, 11:00 AM', 'exam' => 'Jun 18, 2026 · 9:00 AM'] + $paid(null, null),
                ['id' => 'APP-2026-I004', 'name' => 'Torres, Angelica Mae D.', 'program' => 'I-BSN', 'mode' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'Adventist Medical Center · Nursing Aide',
                    'dob' => 'October 5, 2002', 'gender' => 'Female', 'email' => 'am.torres@dlsl.edu.ph', 'phone' => '0920 600 4444', 'address' => 'Santo Tomas, Batangas',
                    'status' => 'Pending', 'applied' => '2026-05-16', 'updated' => 'May 16, 3:00 PM', 'exam' => null] + $paid(null, null),
                ['id' => 'APP-2026-I002', 'name' => 'Garcia, Joanna Marie T.', 'program' => 'I-BSBA', 'mode' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'SM Lipa · Sales Associate',
                    'dob' => 'July 30, 2001', 'gender' => 'Female', 'email' => 'jm.garcia@dlsl.edu.ph', 'phone' => '0918 900 2222', 'address' => 'Batangas City, Batangas',
                    'status' => 'For Exam', 'applied' => '2026-05-09', 'updated' => 'Jun 3, 2:00 PM', 'exam' => 'Jun 15, 2026 · 10:00 AM'] + $paid(null, null),
                ['id' => 'APP-2026-I001', 'name' => 'Lim, Kenneth Andrew P.', 'program' => 'I-BSIT', 'mode' => 'Online', 'year' => '2nd Year', 'working' => 'Yes', 'employer' => 'BDO Unibank · IT Associate',
                    'dob' => 'April 12, 1999', 'gender' => 'Male', 'email' => 'ka.lim@dlsl.edu.ph', 'phone' => '0917 800 1111', 'address' => 'Lipa City, Batangas',
                    'status' => 'Paid', 'applied' => '2026-05-05', 'updated' => 'Jun 1, 9:00 AM', 'exam' => 'Jun 10, 2026 · 9:00 AM'] + $paid('Jun 1, 2026', '₱2,000.00'),
                ['id' => 'APP-2026-I003', 'name' => 'Ramos, Aldrich Jose B.', 'program' => 'I-BSCS', 'mode' => 'Online', 'year' => '3rd Year', 'working' => 'No', 'employer' => null,
                    'dob' => 'February 14, 2000', 'gender' => 'Male', 'email' => 'aj.ramos@dlsl.edu.ph', 'phone' => '0919 700 3333', 'address' => 'Tanauan City, Batangas',
                    'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => 'May 28, 10:00 AM', 'exam' => 'Jun 8, 2026 · 8:00 AM'] + $paid('May 28, 2026', '₱2,000.00'),
            ],
            // assess: pending (portfolio not yet submitted), submitted (being assessed) or cleared. work: [title, company, period, years]
            'ETEEAP' => [
                ['id' => 'APP-2026-E005', 'name' => 'Villanueva, Dario Cortez M.', 'program' => 'E-BSHRM', 'age' => 39, 'dob' => 'June 17, 1987', 'gender' => 'Male',
                    'email' => 'dc.villanueva@dlsl.edu.ph', 'phone' => '0921 555 6666', 'address' => 'Lipa City, Batangas', 'education' => '2 Years College (BS HRM — DLSL, 2006, incomplete)',
                    'workYears' => 16, 'employer' => 'Dusit Thani Manila · Food & Beverage Supervisor', 'assess' => 'submitted', 'portfolio' => 'Submitted — Jun 4, 2026',
                    'work' => [['F&B Supervisor', 'Dusit Thani Manila', '2015 – Present', '9 years'], ['Head Waiter', 'Crimson Hotel Filinvest City', '2009 – 2015', '6 years']],
                    'status' => 'For Assessment', 'applied' => '2026-05-10', 'updated' => 'Jun 4, 11:00 AM'] + $paid(null, null),
                ['id' => 'APP-2026-E004', 'name' => 'Santos, Loreta Manalo A.', 'program' => 'E-BSN', 'age' => 35, 'dob' => 'September 4, 1991', 'gender' => 'Female',
                    'email' => 'lm.santos@dlsl.edu.ph', 'phone' => '0920 444 5555', 'address' => 'Tanauan City, Batangas', 'education' => 'High School Graduate — Tanauan City National High School, 2008',
                    'workYears' => 10, 'employer' => 'Adventist Medical Center · Senior Nursing Aide', 'assess' => 'pending', 'portfolio' => 'Not yet submitted',
                    'work' => [['Senior Nursing Aide', 'Adventist Medical Center', '2016 – Present', '8 years'], ['Caregiver', 'Mabini Medical Center', '2014 – 2016', '2 years']],
                    'status' => 'Pending', 'applied' => '2026-05-14', 'updated' => 'May 14, 3:30 PM'] + $paid(null, null),
                ['id' => 'APP-2026-E002', 'name' => 'Dela Cruz, Maricel Santos B.', 'program' => 'E-BEED', 'age' => 42, 'dob' => 'July 8, 1984', 'gender' => 'Female',
                    'email' => 'ms.delacruz@dlsl.edu.ph', 'phone' => '0918 222 3333', 'address' => 'Batangas City, Batangas', 'education' => 'High School Graduate — Batangas National High School, 2001',
                    'workYears' => 18, 'employer' => 'DepEd Batangas · Paraprofessional Teacher', 'assess' => 'submitted', 'portfolio' => 'Submitted — Jun 3, 2026',
                    'work' => [['Paraprofessional Teacher', 'DepEd Batangas', '2006 – Present', '18 years'], ['Daycare Teacher', "City Gov't of Batangas", '2003 – 2006', '3 years']],
                    'status' => 'For Assessment', 'applied' => '2026-05-07', 'updated' => 'Jun 3, 2:00 PM'] + $paid(null, null),
                ['id' => 'APP-2026-E003', 'name' => 'Reyes, Ernesto Datu C.', 'program' => 'E-BSBA', 'age' => 45, 'dob' => 'January 20, 1981', 'gender' => 'Male',
                    'email' => 'ed.reyes@dlsl.edu.ph', 'phone' => '0919 333 4444', 'address' => 'San Jose, Batangas', 'education' => '3 Years College (BS Commerce — Lyceum of the Philippines, 1999, incomplete)',
                    'workYears' => 22, 'employer' => 'Jollibee Foods Corporation · Area Manager', 'assess' => 'cleared', 'portfolio' => 'Submitted — May 30, 2026',
                    'work' => [['Area Manager', 'Jollibee Foods Corporation', '2010 – Present', '14 years'], ['Store Manager', "McDonald's Philippines", '2003 – 2010', '7 years']],
                    'status' => 'Paid', 'applied' => '2026-05-05', 'updated' => 'Jun 1, 10:00 AM'] + $paid('Jun 1, 2026', '₱2,500.00'),
                ['id' => 'APP-2026-E001', 'name' => 'Pascual, Roberto Miguel A.', 'program' => 'E-BSIT', 'age' => 38, 'dob' => 'March 12, 1988', 'gender' => 'Male',
                    'email' => 'rm.pascual@dlsl.edu.ph', 'phone' => '0917 111 2222', 'address' => 'Lipa City, Batangas', 'education' => '2 Years College (BS Computer Engineering — DLSL, 2007, incomplete)',
                    'workYears' => 14, 'employer' => 'Philippine Long Distance Telephone Co. · IT Specialist', 'assess' => 'cleared', 'portfolio' => 'Submitted — Jun 1, 2026',
                    'work' => [['IT Specialist', 'PLDT', '2012 – Present', '12 years'], ['IT Support Technician', 'Globe Telecom', '2009 – 2012', '3 years']],
                    'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => 'Jun 2, 9:00 AM'] + $paid('Jun 2, 2026', '₱2,500.00'),
            ],
        ];
    }

}
