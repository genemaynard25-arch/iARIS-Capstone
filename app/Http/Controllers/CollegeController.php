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
        'Kristine Anne Dimaculangan' => 'Ms.', 'Haidee Angeles' => 'Dr.', 'Erickson Martinez' => 'Dr.',
    ];

    /**
     * The signed-in dean's college: ['code' => 'CITE', 'name' => …, 'programs' => […]]. The IS Principal gets 'IS'.
     * TODO: read users.assigned_college once the backend adds it. Until then the college code is kept
     * in users.department (see the seeder). Anyone without one (e.g. the Program Chair) is CITE.
     */
    public static function college(): array
    {
        $user = auth()->user();
        $code = $user?->isPrincipal() ? 'IS' : ($user?->isDirector() ? 'IPACE' : ($user?->isGradChair() ? 'GRAD' : null));
        $code ??= strtoupper((string) ($user?->assigned_college ?? $user?->department ?? ''));
        $code = isset(self::COLLEGES[$code]) ? $code : 'CITE';

        return ['code' => $code] + self::COLLEGES[$code];
    }

    /** Where these pages live for the signed-in user: /principal for the IS Principal, /college otherwise. */
    public static function base(): string
    {
        $user = auth()->user();

        return url($user?->isPrincipal() ? '/principal' : ($user?->isDirector() ? '/director' : '/college'));
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
            'programs' => $isChair ? ['BSCS' => self::COLLEGES['CITE']['programs']['BSCS']] : $college['programs'],
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
            // Sample records; the tiles count every applicant (the programs' status totals)
            'rows' => self::applicantsIn($common['programs']),
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
            // For the "Paid reservations" report: applicants in scope who paid
            'paidRows' => array_values(array_map(fn ($a) => [$a['id'], $a['name'], $common['programs'][$a['program']]['name'], $a['paid']],
                array_filter(auth()->user()->isDirector() ? array_merge(...array_values(self::directorApplicants())) : self::applicantsIn($common['programs']), fn ($a) => $a['paid']))),
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

    /**
     * Applicants in the given programs, newest first. Placeholder records (every college); later a query on the
     * applicants table (e.g. Applicant::where('college', 'CITE')->whereIn('program', ...)).
     * paid: date of the reservation payment, or null if not paid yet. year (optional): "Grade 11 · STEM" (default 1st Year).
     * amount (optional): the reservation fee paid (default ₱2,000.00). undergrad (optional): a graduate applicant's degree.
     */
    private static function applicantsIn(array $scope): array
    {
        $all = [
            ['id' => 'APP-2026-0501', 'name' => 'Cruz, Angelo Miguel R.', 'program' => 'BSArch', 'status' => 'Pending', 'applied' => '2026-05-25', 'updated' => 'Jun 2, 8:10 AM', 'dob' => 'March 14, 2006', 'gender' => 'Male', 'email' => 'a.cruz@dlsl.edu.ph', 'phone' => '0917 100 1000', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0502', 'name' => 'Dizon, Katrina Joy P.', 'program' => 'BSCpE', 'status' => 'For Exam', 'applied' => '2026-05-24', 'updated' => 'Jun 3, 9:17 AM', 'dob' => 'August 2, 2005', 'gender' => 'Female', 'email' => 'k.dizon@dlsl.edu.ph', 'phone' => '0918 137 1911', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0503', 'name' => 'Ramos, Joshua Benedict T.', 'program' => 'BSIT', 'status' => 'For Exam', 'applied' => '2026-05-23', 'updated' => 'Jun 4, 10:24 AM', 'dob' => 'January 19, 2006', 'gender' => 'Male', 'email' => 'j.ramos@dlsl.edu.ph', 'phone' => '0919 174 2822', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 17, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0385', 'name' => 'Sangalang, Pia Amherstia V.', 'program' => 'BSCS', 'status' => 'Pending', 'applied' => '2026-05-22', 'updated' => 'Jun 5, 11:31 AM', 'dob' => 'April 18, 2005', 'gender' => 'Female', 'email' => 'p.sangalang@dlsl.edu.ph', 'phone' => '0921 567 8901', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0505', 'name' => 'Bautista, Marian Grace S.', 'program' => 'BSCS', 'status' => 'For Exam', 'applied' => '2026-05-21', 'updated' => 'Jun 6, 8:38 AM', 'dob' => 'November 3, 2005', 'gender' => 'Female', 'email' => 'm.bautista@dlsl.edu.ph', 'phone' => '0917 248 4644', 'address' => 'San Pablo, Laguna', 'exam' => 'Jun 19, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0506', 'name' => 'Mercado, Sofia Isabel L.', 'program' => 'BSIT', 'status' => 'Pending', 'applied' => '2026-05-20', 'updated' => 'Jun 7, 9:45 AM', 'dob' => 'June 27, 2006', 'gender' => 'Female', 'email' => 's.mercado@dlsl.edu.ph', 'phone' => '0918 285 5555', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0507', 'name' => 'Lim, Kevin Andrei D.', 'program' => 'BSCS', 'status' => 'Paid', 'applied' => '2026-05-19', 'updated' => 'Jun 2, 10:52 AM', 'dob' => 'February 9, 2006', 'gender' => 'Male', 'email' => 'k.lim@dlsl.edu.ph', 'phone' => '0919 322 6466', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 11, 2026 · 8:00 AM', 'paid' => 'Jun 2, 2026'],
            ['id' => 'APP-2026-0508', 'name' => 'Aquino, Matthew Jose B.', 'program' => 'BSCS', 'status' => 'Pending', 'applied' => '2026-05-18', 'updated' => 'Jun 3, 11:59 AM', 'dob' => 'September 12, 2005', 'gender' => 'Male', 'email' => 'm.aquino@dlsl.edu.ph', 'phone' => '0920 359 7377', 'address' => 'Calamba, Laguna', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0509', 'name' => 'Castillo, Bea Francesca M.', 'program' => 'BSEMC', 'status' => 'For Exam', 'applied' => '2026-05-17', 'updated' => 'Jun 4, 8:16 AM', 'dob' => 'May 30, 2006', 'gender' => 'Female', 'email' => 'b.castillo@dlsl.edu.ph', 'phone' => '0917 396 8288', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0382', 'name' => 'Malibiran, Gene Maynard G.', 'program' => 'BSIT', 'status' => 'Paid', 'applied' => '2026-05-16', 'updated' => 'Jun 5, 9:23 AM', 'dob' => 'July 22, 2004', 'gender' => 'Male', 'email' => 'g.malibiran@dlsl.edu.ph', 'phone' => '0918 234 5678', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => 'Jun 5, 2026'],
            ['id' => 'APP-2026-0511', 'name' => 'Navarro, Carlo Emmanuel T.', 'program' => 'BSCpE', 'status' => 'Paid', 'applied' => '2026-05-15', 'updated' => 'Jun 6, 10:30 AM', 'dob' => 'December 5, 2005', 'gender' => 'Male', 'email' => 'c.navarro@dlsl.edu.ph', 'phone' => '0919 470 1110', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 8, 2026 · 9:00 AM', 'paid' => 'Jun 1, 2026'],
            ['id' => 'APP-2026-0381', 'name' => 'Go, Raphael Lorenzo L.', 'program' => 'BSCS', 'status' => 'Paid', 'applied' => '2026-05-14', 'updated' => 'Jun 7, 11:37 AM', 'dob' => 'March 3, 2005', 'gender' => 'Male', 'email' => 'r.go@dlsl.edu.ph', 'phone' => '0917 123 4567', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 8:00 AM', 'paid' => 'Jun 3, 2026'],
            ['id' => 'APP-2026-0513', 'name' => 'Santiago, Hannah Mae R.', 'program' => 'BSIT', 'status' => 'Paid', 'applied' => '2026-05-13', 'updated' => 'Jun 2, 8:44 AM', 'dob' => 'October 21, 2005', 'gender' => 'Female', 'email' => 'h.santiago@dlsl.edu.ph', 'phone' => '0917 544 2932', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 10, 2026 · 8:00 AM', 'paid' => 'Jun 3, 2026'],
            ['id' => 'APP-2026-0514', 'name' => 'Pascual, Gabriel Luis C.', 'program' => 'BSCS', 'status' => 'For Exam', 'applied' => '2026-05-12', 'updated' => 'Jun 3, 9:51 AM', 'dob' => 'January 30, 2006', 'gender' => 'Male', 'email' => 'g.pascual@dlsl.edu.ph', 'phone' => '0918 581 3843', 'address' => 'San Jose, Batangas', 'exam' => 'Jun 20, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0515', 'name' => 'De Guzman, Trisha Anne V.', 'program' => 'BSIE', 'status' => 'Paid', 'applied' => '2026-05-11', 'updated' => 'Jun 4, 10:58 AM', 'dob' => 'April 8, 2005', 'gender' => 'Female', 'email' => 't.deguzman@dlsl.edu.ph', 'phone' => '0919 618 4754', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 5, 2026 · 10:00 AM', 'paid' => 'Jun 5, 2026'],
            ['id' => 'APP-2026-0516', 'name' => 'Flores, Miguel Antonio D.', 'program' => 'BSCpE', 'status' => 'Pending', 'applied' => '2026-05-10', 'updated' => 'Jun 5, 11:15 AM', 'dob' => 'August 17, 2005', 'gender' => 'Male', 'email' => 'm.flores@dlsl.edu.ph', 'phone' => '0920 655 5665', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0517', 'name' => 'Reyes, Jasmine Faith A.', 'program' => 'BSIT', 'status' => 'For Exam', 'applied' => '2026-05-09', 'updated' => 'Jun 6, 8:22 AM', 'dob' => 'February 25, 2006', 'gender' => 'Female', 'email' => 'j.reyes@dlsl.edu.ph', 'phone' => '0917 692 6576', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0518', 'name' => 'Tolentino, Rafael Dominic S.', 'program' => 'BSCS', 'status' => 'Paid', 'applied' => '2026-05-08', 'updated' => 'Jun 7, 9:29 AM', 'dob' => 'June 14, 2005', 'gender' => 'Male', 'email' => 'r.tolentino@dlsl.edu.ph', 'phone' => '0918 729 7487', 'address' => 'Calamba, Laguna', 'exam' => 'Jun 8, 2026 · 10:00 AM', 'paid' => 'Jun 3, 2026'],
            // Other colleges (sample records)
            ['id' => 'APP-2026-0601', 'name' => 'Villanueva, Andrea Nicole P.', 'program' => 'BSA', 'status' => 'Paid', 'applied' => '2026-05-24', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'June 3, 2006', 'gender' => 'Female', 'email' => 'a.villanueva@dlsl.edu.ph', 'phone' => '0917 610 2201', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 9, 2026 · 8:00 AM', 'paid' => 'Jun 4, 2026'],
            ['id' => 'APP-2026-0602', 'name' => 'Mendoza, John Carlo S.', 'program' => 'BSBA', 'status' => 'For Exam', 'applied' => '2026-05-22', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'October 11, 2005', 'gender' => 'Male', 'email' => 'j.mendoza@dlsl.edu.ph', 'phone' => '0918 620 3302', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0603', 'name' => 'Perez, Bianca Louise R.', 'program' => 'BSBA', 'status' => 'Pending', 'applied' => '2026-05-20', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'January 7, 2006', 'gender' => 'Female', 'email' => 'b.perez@dlsl.edu.ph', 'phone' => '0919 630 4403', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0604', 'name' => 'Garcia, Paolo Miguel D.', 'program' => 'BSEntrep', 'status' => 'Paid', 'applied' => '2026-05-18', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'July 19, 2005', 'gender' => 'Male', 'email' => 'p.garcia@dlsl.edu.ph', 'phone' => '0920 640 5504', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 8, 2026 · 9:00 AM', 'paid' => 'Jun 2, 2026'],
            ['id' => 'APP-2026-0605', 'name' => 'Ramirez, Isabelle T.', 'program' => 'BSLM', 'status' => 'For Exam', 'applied' => '2026-05-16', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'April 2, 2006', 'gender' => 'Female', 'email' => 'i.ramirez@dlsl.edu.ph', 'phone' => '0917 650 6605', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 17, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0611', 'name' => 'Fernando, Clarisse Anne M.', 'program' => 'BSPsych', 'status' => 'Paid', 'applied' => '2026-05-23', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'May 27, 2006', 'gender' => 'Female', 'email' => 'c.fernando@dlsl.edu.ph', 'phone' => '0918 711 2211', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 8:00 AM', 'paid' => 'Jun 4, 2026'],
            ['id' => 'APP-2026-0612', 'name' => 'Salazar, Nathan Paul G.', 'program' => 'BAMMA', 'status' => 'For Exam', 'applied' => '2026-05-21', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'September 5, 2005', 'gender' => 'Male', 'email' => 'n.salazar@dlsl.edu.ph', 'phone' => '0919 712 3312', 'address' => 'San Pablo, Laguna', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0613', 'name' => 'Domingo, Ella Marie C.', 'program' => 'BSEd', 'status' => 'Pending', 'applied' => '2026-05-19', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'December 14, 2005', 'gender' => 'Female', 'email' => 'e.domingo@dlsl.edu.ph', 'phone' => '0920 713 4413', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0614', 'name' => 'Torres, Miguel Angelo B.', 'program' => 'BSBio', 'status' => 'Paid', 'applied' => '2026-05-17', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'February 21, 2006', 'gender' => 'Male', 'email' => 'm.torres@dlsl.edu.ph', 'phone' => '0917 714 5514', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 9, 2026 · 9:00 AM', 'paid' => 'Jun 3, 2026'],
            ['id' => 'APP-2026-0615', 'name' => 'Aguilar, Samantha Rose L.', 'program' => 'BAComm', 'status' => 'For Exam', 'applied' => '2026-05-15', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'August 30, 2005', 'gender' => 'Female', 'email' => 's.aguilar@dlsl.edu.ph', 'phone' => '0918 715 6615', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 16, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0621', 'name' => 'Manalo, Kristine Joy A.', 'program' => 'BSHM', 'status' => 'Paid', 'applied' => '2026-05-24', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'March 9, 2006', 'gender' => 'Female', 'email' => 'k.manalo@dlsl.edu.ph', 'phone' => '0919 821 2221', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 10, 2026 · 9:00 AM', 'paid' => 'Jun 5, 2026'],
            ['id' => 'APP-2026-0622', 'name' => 'Robles, Adrian James P.', 'program' => 'BSTM', 'status' => 'Pending', 'applied' => '2026-05-21', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'November 18, 2005', 'gender' => 'Male', 'email' => 'a.robles@dlsl.edu.ph', 'phone' => '0920 822 3322', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0623', 'name' => 'Lopez, Danielle Mae S.', 'program' => 'BSCA', 'status' => 'For Exam', 'applied' => '2026-05-18', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'June 25, 2006', 'gender' => 'Female', 'email' => 'd.lopez@dlsl.edu.ph', 'phone' => '0917 823 4423', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 17, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0624', 'name' => 'Hernandez, Marco Luis R.', 'program' => 'BSHM', 'status' => 'For Exam', 'applied' => '2026-05-15', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'January 29, 2006', 'gender' => 'Male', 'email' => 'm.hernandez@dlsl.edu.ph', 'phone' => '0918 824 5524', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 19, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0631', 'name' => 'Valdez, Christian Mark T.', 'program' => 'BSCrim', 'status' => 'Paid', 'applied' => '2026-05-23', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'April 15, 2005', 'gender' => 'Male', 'email' => 'c.valdez@dlsl.edu.ph', 'phone' => '0919 931 2231', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 8, 2026 · 8:00 AM', 'paid' => 'Jun 3, 2026'],
            ['id' => 'APP-2026-0632', 'name' => 'Castro, Jenny Lyn M.', 'program' => 'BSCrim', 'status' => 'For Exam', 'applied' => '2026-05-20', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'October 3, 2005', 'gender' => 'Female', 'email' => 'j.castro@dlsl.edu.ph', 'phone' => '0920 932 3332', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0633', 'name' => 'Rivera, Joshua Emmanuel D.', 'program' => 'BSFS', 'status' => 'Pending', 'applied' => '2026-05-17', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'July 8, 2006', 'gender' => 'Male', 'email' => 'j.rivera@dlsl.edu.ph', 'phone' => '0917 933 4433', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0634', 'name' => 'Santos, Erika Mae V.', 'program' => 'BSCrim', 'status' => 'Pending', 'applied' => '2026-05-14', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'December 1, 2005', 'gender' => 'Female', 'email' => 'e.santos@dlsl.edu.ph', 'phone' => '0918 934 5534', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0641', 'name' => 'Bautista, Patricia Anne L.', 'program' => 'BSN', 'status' => 'Paid', 'applied' => '2026-05-23', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'March 22, 2006', 'gender' => 'Female', 'email' => 'p.bautista@dlsl.edu.ph', 'phone' => '0917 941 2241', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 9, 2026 · 8:00 AM', 'paid' => 'Jun 4, 2026'],
            ['id' => 'APP-2026-0642', 'name' => 'Mercado, John Paul R.', 'program' => 'BSN', 'status' => 'For Exam', 'applied' => '2026-05-20', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'August 14, 2005', 'gender' => 'Male', 'email' => 'j.mercado@dlsl.edu.ph', 'phone' => '0918 942 3342', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0643', 'name' => 'Ilagan, Sophia Marie C.', 'program' => 'BSN', 'status' => 'Pending', 'applied' => '2026-05-17', 'updated' => 'Jun 6, 9:00 AM', 'dob' => 'November 9, 2005', 'gender' => 'Female', 'email' => 's.ilagan@dlsl.edu.ph', 'phone' => '0919 943 4443', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            // Integrated School (as in the Principal's mockup). 'year' and 'amount' are given here; elsewhere they default.
            ['id' => 'IS-2026-1101', 'name' => 'Santos, Bianca Mae R.', 'program' => 'K', 'year' => 'Kinder', 'status' => 'Pending', 'applied' => '2026-05-26', 'updated' => 'Jun 2, 8:12 AM', 'dob' => 'March 2, 2021', 'gender' => 'Female', 'email' => 'santos.family@gmail.com', 'phone' => '0917 200 1000', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'IS-2026-1102', 'name' => 'Lim, Ethan Gabriel S.', 'program' => 'GS', 'year' => 'Grade 1', 'status' => 'For Exam', 'applied' => '2026-05-25', 'updated' => 'Jun 3, 9:19 AM', 'dob' => 'June 18, 2019', 'gender' => 'Male', 'email' => 'lim.family@gmail.com', 'phone' => '0918 241 1733', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1103', 'name' => 'Reyes, Andrea Nicole P.', 'program' => 'SHS', 'year' => 'Grade 11 · STEM', 'status' => 'Paid', 'applied' => '2026-05-24', 'updated' => 'Jun 4, 10:26 AM', 'dob' => 'October 9, 2009', 'gender' => 'Female', 'email' => 'reyes.family@gmail.com', 'phone' => '0919 282 2466', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 16, 2026 · 10:00 AM', 'paid' => 'Jun 4, 2026', 'amount' => '₱1,500.00'],
            ['id' => 'IS-2026-1104', 'name' => 'Garcia, Miguel Andres T.', 'program' => 'JHS', 'year' => 'Grade 7', 'status' => 'For Exam', 'applied' => '2026-05-23', 'updated' => 'Jun 5, 11:33 AM', 'dob' => 'January 21, 2013', 'gender' => 'Male', 'email' => 'garcia.family@gmail.com', 'phone' => '0920 323 3199', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 17, 2026 · 8:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1105', 'name' => 'Mendoza, Sofia Isabel L.', 'program' => 'SHS', 'year' => 'Grade 11 · ABM', 'status' => 'For Exam', 'applied' => '2026-05-22', 'updated' => 'Jun 6, 8:40 AM', 'dob' => 'May 30, 2009', 'gender' => 'Female', 'email' => 'mendoza.family@gmail.com', 'phone' => '0917 364 3932', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1106', 'name' => 'Navarro, Joaquin Luis D.', 'program' => 'GS', 'year' => 'Grade 4', 'status' => 'Paid', 'applied' => '2026-05-21', 'updated' => 'Jun 7, 9:47 AM', 'dob' => 'August 3, 2016', 'gender' => 'Male', 'email' => 'navarro.family@gmail.com', 'phone' => '0918 405 4665', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 19, 2026 · 10:00 AM', 'paid' => 'Jun 7, 2026', 'amount' => '₱1,500.00'],
            ['id' => 'IS-2026-1107', 'name' => 'Villanueva, Gwyneth Anne C.', 'program' => 'SHS', 'year' => 'Grade 11 · HUMSS', 'status' => 'Pending', 'applied' => '2026-05-20', 'updated' => 'Jun 8, 10:54 AM', 'dob' => 'July 14, 2009', 'gender' => 'Female', 'email' => 'villanueva.family@gmail.com', 'phone' => '0919 446 5398', 'address' => 'San Jose, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'IS-2026-1108', 'name' => 'Castillo, Lorenzo Miguel B.', 'program' => 'JHS', 'year' => 'Grade 8', 'status' => 'Paid', 'applied' => '2026-05-19', 'updated' => 'Jun 2, 11:01 AM', 'dob' => 'November 27, 2012', 'gender' => 'Male', 'email' => 'castillo.family@gmail.com', 'phone' => '0920 487 6131', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 21, 2026 · 9:00 AM', 'paid' => 'Jun 3, 2026', 'amount' => '₱1,500.00'],
            ['id' => 'IS-2026-1109', 'name' => 'Aquino, Chloe Margaret V.', 'program' => 'K', 'year' => 'Kinder', 'status' => 'Paid', 'applied' => '2026-05-18', 'updated' => 'Jun 3, 8:08 AM', 'dob' => 'February 11, 2021', 'gender' => 'Female', 'email' => 'aquino.family@gmail.com', 'phone' => '0917 528 6864', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 22, 2026 · 10:00 AM', 'paid' => 'Jun 4, 2026', 'amount' => '₱1,500.00'],
            ['id' => 'IS-2026-1110', 'name' => 'Bautista, Rafael Jose M.', 'program' => 'SHS', 'year' => 'Grade 11 · STEM', 'status' => 'For Exam', 'applied' => '2026-05-17', 'updated' => 'Jun 4, 9:15 AM', 'dob' => 'December 5, 2009', 'gender' => 'Male', 'email' => 'bautista.family@gmail.com', 'phone' => '0918 569 7597', 'address' => 'Calamba, Laguna', 'exam' => 'Jun 14, 2026 · 8:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1111', 'name' => 'Dela Rosa, Kyla Marie F.', 'program' => 'GS', 'year' => 'Grade 2', 'status' => 'Pending', 'applied' => '2026-05-16', 'updated' => 'Jun 5, 10:22 AM', 'dob' => 'April 19, 2018', 'gender' => 'Female', 'email' => 'delarosa.family@gmail.com', 'phone' => '0919 610 8330', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'IS-2026-1112', 'name' => 'Torres, Nathaniel James R.', 'program' => 'SHS', 'year' => 'Grade 12 · GAS', 'status' => 'Paid', 'applied' => '2026-05-15', 'updated' => 'Jun 6, 11:29 AM', 'dob' => 'September 8, 2008', 'gender' => 'Male', 'email' => 'torres.family@gmail.com', 'phone' => '0920 651 9063', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 16, 2026 · 10:00 AM', 'paid' => 'Jun 7, 2026', 'amount' => '₱1,500.00'],
            ['id' => 'IS-2026-1113', 'name' => 'Flores, Alyssa Jane C.', 'program' => 'JHS', 'year' => 'Grade 7', 'status' => 'Pending', 'applied' => '2026-05-14', 'updated' => 'Jun 7, 8:36 AM', 'dob' => 'March 25, 2013', 'gender' => 'Female', 'email' => 'flores.family@gmail.com', 'phone' => '0917 692 9796', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'IS-2026-1114', 'name' => 'Ramos, Gabriel Vince A.', 'program' => 'SHS', 'year' => 'Grade 11 · TVL', 'status' => 'For Exam', 'applied' => '2026-05-13', 'updated' => 'Jun 8, 9:43 AM', 'dob' => 'June 1, 2009', 'gender' => 'Male', 'email' => 'ramos.family@gmail.com', 'phone' => '0918 733 1529', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1115', 'name' => 'Pascual, Isabella Rose T.', 'program' => 'GS', 'year' => 'Grade 6', 'status' => 'For Exam', 'applied' => '2026-05-12', 'updated' => 'Jun 2, 10:50 AM', 'dob' => 'January 7, 2014', 'gender' => 'Female', 'email' => 'pascual.family@gmail.com', 'phone' => '0919 774 2262', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 19, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1116', 'name' => 'Cruz, Daniel Matthew S.', 'program' => 'SHS', 'year' => 'Grade 11 · STEM', 'status' => 'Paid', 'applied' => '2026-05-11', 'updated' => 'Jun 3, 11:57 AM', 'dob' => 'August 22, 2009', 'gender' => 'Male', 'email' => 'cruz.family@gmail.com', 'phone' => '0920 815 2995', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 20, 2026 · 8:00 AM', 'paid' => 'Jun 5, 2026', 'amount' => '₱1,500.00'],
            ['id' => 'IS-2026-1117', 'name' => 'Ocampo, Leah Patricia M.', 'program' => 'JHS', 'year' => 'Grade 9', 'status' => 'For Exam', 'applied' => '2026-05-10', 'updated' => 'Jun 4, 8:04 AM', 'dob' => 'October 15, 2011', 'gender' => 'Female', 'email' => 'ocampo.family@gmail.com', 'phone' => '0917 856 3728', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 21, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'IS-2026-1118', 'name' => 'Salazar, Marco Antonio G.', 'program' => 'SHS', 'year' => 'Grade 11 · ABM', 'status' => 'Pending', 'applied' => '2026-05-09', 'updated' => 'Jun 5, 9:11 AM', 'dob' => 'April 2, 2009', 'gender' => 'Male', 'email' => 'salazar.family@gmail.com', 'phone' => '0918 897 4461', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            // Graduate Programs (as in the Program Chair's mockup). 'undergrad': the degree they hold.
            ['id' => 'APP-2026-G006', 'name' => 'Villanueva, Rosario Anne B.', 'program' => 'MBA', 'year' => "Master's", 'status' => 'Pending', 'applied' => '2026-05-18', 'updated' => 'May 18, 9:45 AM', 'dob' => 'July 14, 1994', 'gender' => 'Female', 'email' => 'ra.villanueva@dlsl.edu.ph', 'phone' => '0912 400 6666', 'address' => 'Calamba, Laguna', 'undergrad' => 'BS Accountancy — DLSL, 2016', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-G003', 'name' => 'Garcia, Eduardo M.', 'program' => 'MIT', 'year' => "Master's", 'status' => 'Pending', 'applied' => '2026-05-12', 'updated' => 'May 12, 4:15 PM', 'dob' => 'September 11, 1992', 'gender' => 'Male', 'email' => 'e.garcia@dlsl.edu.ph', 'phone' => '0919 700 3333', 'address' => 'Tanauan City, Batangas', 'undergrad' => 'BS Computer Science — DLSL, 2014', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-G002', 'name' => 'Fernandez, Maricel D.', 'program' => 'MEd', 'year' => "Master's", 'status' => 'For Exam', 'applied' => '2026-05-08', 'updated' => 'Jun 3, 2:00 PM', 'dob' => 'March 22, 1988', 'gender' => 'Female', 'email' => 'm.fernandez@dlsl.edu.ph', 'phone' => '0918 900 2222', 'address' => 'Batangas City, Batangas', 'undergrad' => 'Bachelor of Secondary Education — DLSL, 2010', 'exam' => 'Jun 14, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-G005', 'name' => 'Torres, Andrei Benedict V.', 'program' => 'PhD', 'year' => 'Doctoral', 'status' => 'For Exam', 'applied' => '2026-05-06', 'updated' => 'Jun 4, 3:30 PM', 'dob' => 'April 28, 1978', 'gender' => 'Male', 'email' => 'ab.torres@dlsl.edu.ph', 'phone' => '0921 500 5555', 'address' => 'Lipa City, Batangas', 'undergrad' => 'Master of Education — DLSL, 2005', 'exam' => 'Jun 16, 2026 · 1:00 PM', 'paid' => null],
            ['id' => 'APP-2026-G001', 'name' => 'Bautista, Ramon Jose A.', 'program' => 'MBA', 'year' => "Master's", 'status' => 'Paid', 'applied' => '2026-05-05', 'updated' => 'Jun 1, 9:00 AM', 'dob' => 'June 15, 1990', 'gender' => 'Male', 'email' => 'rj.bautista@dlsl.edu.ph', 'phone' => '0917 800 1111', 'address' => 'Lipa City, Batangas', 'undergrad' => 'BS Business Administration — DLSL, 2012', 'exam' => 'Jun 10, 2026 · 9:00 AM', 'paid' => 'Jun 1, 2026', 'amount' => '₱3,000.00'],
            ['id' => 'APP-2026-G004', 'name' => 'Mendoza, Luisa Patricia C.', 'program' => 'MPA', 'year' => "Master's", 'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => 'May 30, 11:00 AM', 'dob' => 'January 5, 1985', 'gender' => 'Female', 'email' => 'lp.mendoza@dlsl.edu.ph', 'phone' => '0920 600 4444', 'address' => 'Santo Tomas, Batangas', 'undergrad' => 'BS Political Science — DLSL, 2007', 'exam' => 'Jun 8, 2026 · 8:00 AM', 'paid' => 'May 30, 2026', 'amount' => '₱3,000.00'],
        ];

        return array_values(array_filter($all, fn ($a) => isset($scope[$a['program']])));
    }
}
