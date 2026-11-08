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
        // 'title' (optional): how the dean's title reads when it isn't the code ("Dean, College of Nursing")
        'CON' => ['name' => 'College of Nursing', 'title' => 'College of Nursing', 'programs' => [
            'BSN' => ['name' => 'BS Nursing', 'applicants' => [123, 129, 135, 142], 'enrolled' => [36, 37, 39, 39],
                'funnel' => [142, 105, 89, 44, 39], 'status' => [40, 58, 44], 'paidLastYear' => 43],
        ]],
    ];

    // How a dean is greeted ("Good morning, Engr. Villafuerte"). Names as in the mockups.
    private const HONORIFICS = [
        'Cherrie Villafuerte' => 'Engr.', 'Jasmin De Silva' => 'Dr.', 'Gina Dimaano' => 'Dr.',
        'Evangeline Necio' => 'Ms.', 'Aristotle Balba' => 'Dr.', 'Margie Ebreo' => 'Dr.',
    ];

    /**
     * The signed-in dean's college: ['code' => 'CITE', 'name' => …, 'programs' => […]].
     * TODO: read users.assigned_college once the backend adds it. Until then the college code is kept
     * in users.department (see the seeder). Anyone without one (e.g. the Program Chair) is CITE.
     */
    public static function college(): array
    {
        $user = auth()->user();
        $code = strtoupper((string) ($user?->assigned_college ?? $user?->department ?? ''));
        $code = isset(self::COLLEGES[$code]) ? $code : 'CITE';

        return ['code' => $code] + self::COLLEGES[$code];
    }

    /** Program names, for Settings (Briefing and Display). */
    public static function programNames(): array
    {
        return array_column(self::college()['programs'], 'name');
    }

    // What the pages share: the programs in scope, the years, and who is looking.
    // A dean sees every program in their college; a program chair only their own (BS Computer Science for now).
    private function common(): array
    {
        $isChair = auth()->user()->isProgramChair();
        $college = self::college();

        return [
            'isChair' => $isChair,
            // Short name of what you see: "CITE", or a program chair's program
            'label' => $isChair ? 'BS Computer Science' : $college['code'],
            'college' => ['code' => $college['code'], 'name' => $college['name']],
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
    public function applicants(Request $request)
    {
        $common = $this->common();

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
                array_filter(self::applicantsIn($common['programs']), fn ($a) => $a['paid']))),
            // Reports you generated before (kept for 90 days)
            'history' => [['template' => 'programs', 'period' => 'AY 2025–26', 'at' => 'Jun 5, 2026 · 10:12 AM']],
        ]);
    }

    // Messages with IATO: the same page as the Executive Office's
    public function messages()
    {
        return view('executive.messages', ['threads' => self::threads(), 'base' => url('/college')]);
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
        $name = $isChair ? 'BS Computer Science' : self::college()['code'];

        return [
            ['title' => "{$name} Applicants Summary — AY 2025–26", 'kind' => 'programs', 'by' => 'Renegado, Randolph', 'role' => 'Super Admin',
                'at' => 'Jun 12, 2026 · 8:00 AM', 'status' => 'new',
                'message' => "Here is the latest applicant summary for {$name}. Paid reservations are up this week. Let us know if you need a breakdown by program."],
            ['title' => "{$name} Paid Reservations — as of May 30", 'kind' => 'paid', 'by' => 'Cruz, Jasmine', 'role' => 'IATO Staff',
                'at' => 'May 30, 2026 · 3:15 PM', 'status' => 'read',
                'message' => 'Attached is the list of paid reservations as of May 30.'],
            ['title' => "{$name} Enrollment Trends — AY 2024–25", 'kind' => 'trend', 'by' => 'Renegado, Randolph', 'role' => 'Super Admin',
                'at' => 'Jun 14, 2025 · 8:30 AM', 'status' => 'read',
                'message' => 'Year-end enrollment trends for your college.'],
        ];
    }

    /**
     * Applicants in the given programs, newest first. Placeholder records (every college); later a query on the
     * applicants table (e.g. Applicant::where('college', 'CITE')->whereIn('program', ...)).
     * paid: date of the reservation payment, or null if not paid yet.
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
        ];

        return array_values(array_filter($all, fn ($a) => isset($scope[$a['program']])));
    }
}
