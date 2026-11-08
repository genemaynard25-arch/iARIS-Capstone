<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollegeController extends Controller
{
    /**
     * CITE's programs (names and totals as in the dean mockups). Placeholder data, same idea as the Executive pages.
     * Later these numbers would come from the admission_stats and applicants tables.
     *
     * applicants / enrolled: one number per academic year in ExecutiveController::YEARS (the last is this year)
     * funnel: this AY — Applied, Took exam, Passed, Paid, Enrolled
     * status: applicants this AY by status — Pending, For Exam, Paid (they add up to this year's applicants)
     * paidLastYear: paid reservations last AY (for the "vs last AY" change)
     */
    private const PROGRAMS = [
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
    ];

    // The college these pages are for. TODO: from the user's assigned_college once the backend adds it.
    public const COLLEGE = ['code' => 'CITE', 'name' => 'College of Information Technology and Engineering'];

    // How a dean is greeted ("Good morning, Engr. Villafuerte"). Names as in the mockups.
    private const HONORIFICS = ['Cherrie Villafuerte' => 'Engr.'];

    /** Program names, for Settings (Briefing and Display). */
    public static function programNames(): array
    {
        return array_column(self::PROGRAMS, 'name');
    }

    /**
     * Which programs this user sees: [isChair, selected program code or null, programs in scope, scope name].
     *
     * A dean sees every program in their college and can narrow the Applicants page to one (?program=BSCS).
     * A program chair is locked to their own program; ?program= is ignored for them.
     * TODO: there is no column yet for a user's college or program, so every dean is
     * CITE and every program chair is BS Computer Science for now.
     */
    private function scope(Request $request): array
    {
        $isChair = auth()->user()->isProgramChair();
        $selected = $isChair ? 'BSCS' : (array_key_exists($request->query('program'), self::PROGRAMS) ? $request->query('program') : null);
        $scope = $selected ? [$selected => self::PROGRAMS[$selected]] : self::PROGRAMS;

        return [$isChair, $selected, $scope, $selected ? self::PROGRAMS[$selected]['name'] : 'All CITE programs'];
    }

    // What the pages share: the programs in scope, the years, and who is looking
    private function common(): array
    {
        $isChair = auth()->user()->isProgramChair();

        return [
            'isChair' => $isChair,
            // Short name of what you see: "CITE", or a program chair's program
            'label' => $isChair ? 'BS Computer Science' : self::COLLEGE['code'],
            'college' => self::COLLEGE,
            'programs' => $isChair ? ['BSCS' => self::PROGRAMS['BSCS']] : self::PROGRAMS,
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
                ? self::HONORIFICS[$user->name] . ' ' . \Illuminate\Support\Str::afterLast($user->name, ' ')
                : \Illuminate\Support\Str::before($user->name, ' '),
            // The 3 newest reports the IATO sent (all of them are on the Reports page)
            'reports' => array_slice(self::received($common['isChair']), 0, 3),
        ]);
    }

    // College Applicants: read-only list of the applicants in scope, with a profile drawer
    public function applicants(Request $request)
    {
        [$isChair, $selected, $scope, $scopeName] = $this->scope($request);
        $status = array_combine(['Pending', 'For Exam', 'Paid'], array_map(
            fn ($i) => array_sum(array_map(fn ($p) => $p['status'][$i], $scope)), [0, 1, 2]));

        return view('college.applicants', [
            'isChair' => $isChair,
            'heading' => $isChair ? 'Applicants · BS Computer Science' : 'Applicants',
            'description' => ($isChair ? 'BS Computer Science' : 'CITE') . ' applicants for AY 2025–2026',
            'programs' => self::PROGRAMS,
            'selected' => $selected,
            'scopeName' => $scopeName,
            // Status totals for the whole program(s); the table below is a sample of the newest applicants
            'status' => $status,
            'rows' => self::applicantsIn($scope),
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
            'paidRows' => array_values(array_map(fn ($a) => [$a['id'], $a['name'], self::PROGRAMS[$a['program']]['name'], $a['paid']],
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

    // Your conversations with IATO, newest first (as in the mockup)
    private static function threads(): array
    {
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
        $name = $isChair ? 'BS Computer Science' : self::COLLEGE['code'];

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
     * Applicants in the given programs, newest first. Placeholder records; later a query on the
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
        ];

        return array_values(array_filter($all, fn ($a) => isset($scope[$a['program']])));
    }
}
