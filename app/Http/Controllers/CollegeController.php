<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CollegeController extends Controller
{
    /**
     * The college's programs. Placeholder data, same idea as the Chancellor dashboard.
     * Later these numbers would come from the admission_stats and applicants tables.
     *
     * applicants / enrolled: one number per academic year in ChancellorController::YEARS (the last is this year)
     * funnel: this AY — Applied, Took exam, Passed, Paid, Enrolled
     * status: applicants still in process this AY — Pending, For Exam, Paid
     * paidLastYear: paid reservations last AY (for the "vs last AY" change)
     */
    private const PROGRAMS = [
        'BSCS' => ['name' => 'BS Computer Science', 'applicants' => [150, 168, 184, 214], 'enrolled' => [41, 49, 54, 58],
            'funnel' => [214, 164, 139, 72, 58], 'status' => [41, 101, 72], 'paidLastYear' => 66],
        'BSIT' => ['name' => 'BS Information Technology', 'applicants' => [121, 139, 152, 168], 'enrolled' => [31, 37, 41, 46],
            'funnel' => [168, 129, 109, 54, 46], 'status' => [32, 82, 54], 'paidLastYear' => 49],
        'BSIS' => ['name' => 'BS Information Systems', 'applicants' => [62, 66, 70, 74], 'enrolled' => [14, 16, 17, 18],
            'funnel' => [74, 57, 47, 24, 18], 'status' => [14, 36, 24], 'paidLastYear' => 22],
        'BSCpE' => ['name' => 'BS Computer Engineering', 'applicants' => [69, 58, 62, 56], 'enrolled' => [18, 15, 14, 12],
            'funnel' => [56, 42, 36, 18, 12], 'status' => [9, 29, 18], 'paidLastYear' => 15],
    ];

    /**
     * Which programs this user sees: [isChair, selected program code or null, programs in scope, scope name].
     *
     * A dean sees every program in their college and can narrow it to one (?program=BSCS).
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

    // College dashboard: read-only overview of the programs in scope
    public function dashboard(Request $request)
    {
        [$isChair, $selected, $scope, $scopeName] = $this->scope($request);

        // Adds up one field across the programs in scope: per year (applicants, enrolled), per stage (funnel, status), or one number
        $sum = function (string $field) use ($scope) {
            $first = reset($scope)[$field];
            if (! is_array($first)) {
                return array_sum(array_column($scope, $field));
            }
            return array_map(fn ($i) => array_sum(array_map(fn ($p) => $p[$field][$i], $scope)), array_keys($first));
        };

        $applicants = $sum('applicants');
        $enrolled = $sum('enrolled');
        $funnel = array_combine(['Applied', 'Took exam', 'Passed', 'Paid', 'Enrolled'], $sum('funnel'));
        $status = array_combine(['Pending', 'For Exam', 'Paid'], $sum('status'));

        return view('college.dashboard', [
            'isChair' => $isChair,
            'heading' => $isChair ? 'BS Computer Science' : 'CITE Dashboard',
            'description' => $isChair
                ? 'Program overview for BS Computer Science · College of Information Technology and Engineering'
                : 'Admissions overview for the College of Information Technology and Engineering',
            'programs' => self::PROGRAMS,
            'selected' => $selected,
            'scopeName' => $scopeName,

            'years' => ChancellorController::YEARS,
            'applicants' => $applicants,
            'enrolled' => $enrolled,
            'paid' => ['previous' => $sum('paidLastYear'), 'current' => $funnel['Paid']],
            'funnel' => $funnel,
            'status' => $status,
            'takeaways' => $this->takeaways($scope, $applicants, $enrolled, $funnel),

            // The 6 newest applications in scope (all of them are on the Applicants page)
            'recent' => array_slice(self::applicantsIn($scope), 0, 6),

            // Reports the IATO sent to this office
            'reports' => [
                ['title' => ($isChair ? 'BS Computer Science' : 'CITE') . ' Applicants Summary — AY 2025–26', 'sent' => 'Jun 12, 2026', 'new' => true],
                ['title' => 'Enrolled per Program — AY 2024–25', 'sent' => 'Jun 14, 2025', 'new' => false],
                ['title' => ($isChair ? 'BS Computer Science' : 'CITE') . ' Admissions Summary — AY 2023–24', 'sent' => 'Jun 10, 2024', 'new' => false],
            ],
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

    /**
     * Applicants in the given programs, newest first. Placeholder records; later a query on the
     * applicants table (e.g. Applicant::where('college', 'CITE')->whereIn('program', ...)).
     * paid: date of the reservation payment, or null if not paid yet.
     */
    private static function applicantsIn(array $scope): array
    {
        $all = [
            ['id' => 'APP-2026-0501', 'name' => 'Cruz, Angelo Miguel R.', 'program' => 'BSIS', 'status' => 'Pending', 'applied' => '2026-05-25', 'updated' => 'Jun 2, 8:10 AM', 'dob' => 'March 14, 2006', 'gender' => 'Male', 'email' => 'a.cruz@dlsl.edu.ph', 'phone' => '0917 100 1000', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0502', 'name' => 'Dizon, Katrina Joy P.', 'program' => 'BSCpE', 'status' => 'For Exam', 'applied' => '2026-05-24', 'updated' => 'Jun 3, 9:17 AM', 'dob' => 'August 2, 2005', 'gender' => 'Female', 'email' => 'k.dizon@dlsl.edu.ph', 'phone' => '0918 137 1911', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 16, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0503', 'name' => 'Ramos, Joshua Benedict T.', 'program' => 'BSIT', 'status' => 'For Exam', 'applied' => '2026-05-23', 'updated' => 'Jun 4, 10:24 AM', 'dob' => 'January 19, 2006', 'gender' => 'Male', 'email' => 'j.ramos@dlsl.edu.ph', 'phone' => '0919 174 2822', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 17, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0385', 'name' => 'Sangalang, Pia Amherstia V.', 'program' => 'BSCS', 'status' => 'Pending', 'applied' => '2026-05-22', 'updated' => 'Jun 5, 11:31 AM', 'dob' => 'April 18, 2005', 'gender' => 'Female', 'email' => 'p.sangalang@dlsl.edu.ph', 'phone' => '0921 567 8901', 'address' => 'Tanauan City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0505', 'name' => 'Bautista, Marian Grace S.', 'program' => 'BSCS', 'status' => 'For Exam', 'applied' => '2026-05-21', 'updated' => 'Jun 6, 8:38 AM', 'dob' => 'November 3, 2005', 'gender' => 'Female', 'email' => 'm.bautista@dlsl.edu.ph', 'phone' => '0917 248 4644', 'address' => 'San Pablo, Laguna', 'exam' => 'Jun 19, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0506', 'name' => 'Mercado, Sofia Isabel L.', 'program' => 'BSIT', 'status' => 'Pending', 'applied' => '2026-05-20', 'updated' => 'Jun 7, 9:45 AM', 'dob' => 'June 27, 2006', 'gender' => 'Female', 'email' => 's.mercado@dlsl.edu.ph', 'phone' => '0918 285 5555', 'address' => 'Lipa City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0507', 'name' => 'Lim, Kevin Andrei D.', 'program' => 'BSCS', 'status' => 'Paid', 'applied' => '2026-05-19', 'updated' => 'Jun 2, 10:52 AM', 'dob' => 'February 9, 2006', 'gender' => 'Male', 'email' => 'k.lim@dlsl.edu.ph', 'phone' => '0919 322 6466', 'address' => 'Santo Tomas, Batangas', 'exam' => 'Jun 11, 2026 · 8:00 AM', 'paid' => 'Jun 2, 2026'],
            ['id' => 'APP-2026-0508', 'name' => 'Aquino, Matthew Jose B.', 'program' => 'BSCS', 'status' => 'Pending', 'applied' => '2026-05-18', 'updated' => 'Jun 3, 11:59 AM', 'dob' => 'September 12, 2005', 'gender' => 'Male', 'email' => 'm.aquino@dlsl.edu.ph', 'phone' => '0920 359 7377', 'address' => 'Calamba, Laguna', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0509', 'name' => 'Castillo, Bea Francesca M.', 'program' => 'BSIS', 'status' => 'For Exam', 'applied' => '2026-05-17', 'updated' => 'Jun 4, 8:16 AM', 'dob' => 'May 30, 2006', 'gender' => 'Female', 'email' => 'b.castillo@dlsl.edu.ph', 'phone' => '0917 396 8288', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 10:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0382', 'name' => 'Malibiran, Gene Maynard G.', 'program' => 'BSIT', 'status' => 'Paid', 'applied' => '2026-05-16', 'updated' => 'Jun 5, 9:23 AM', 'dob' => 'July 22, 2004', 'gender' => 'Male', 'email' => 'g.malibiran@dlsl.edu.ph', 'phone' => '0918 234 5678', 'address' => 'Batangas City, Batangas', 'exam' => 'Jun 18, 2026 · 9:00 AM', 'paid' => 'Jun 5, 2026'],
            ['id' => 'APP-2026-0511', 'name' => 'Navarro, Carlo Emmanuel T.', 'program' => 'BSCpE', 'status' => 'Paid', 'applied' => '2026-05-15', 'updated' => 'Jun 6, 10:30 AM', 'dob' => 'December 5, 2005', 'gender' => 'Male', 'email' => 'c.navarro@dlsl.edu.ph', 'phone' => '0919 470 1110', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 8, 2026 · 9:00 AM', 'paid' => 'Jun 1, 2026'],
            ['id' => 'APP-2026-0381', 'name' => 'Go, Raphael Lorenzo L.', 'program' => 'BSCS', 'status' => 'Paid', 'applied' => '2026-05-14', 'updated' => 'Jun 7, 11:37 AM', 'dob' => 'March 3, 2005', 'gender' => 'Male', 'email' => 'r.go@dlsl.edu.ph', 'phone' => '0917 123 4567', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 8:00 AM', 'paid' => 'Jun 3, 2026'],
            ['id' => 'APP-2026-0513', 'name' => 'Santiago, Hannah Mae R.', 'program' => 'BSIT', 'status' => 'Paid', 'applied' => '2026-05-13', 'updated' => 'Jun 2, 8:44 AM', 'dob' => 'October 21, 2005', 'gender' => 'Female', 'email' => 'h.santiago@dlsl.edu.ph', 'phone' => '0917 544 2932', 'address' => 'Tanauan City, Batangas', 'exam' => 'Jun 10, 2026 · 8:00 AM', 'paid' => 'Jun 3, 2026'],
            ['id' => 'APP-2026-0514', 'name' => 'Pascual, Gabriel Luis C.', 'program' => 'BSCS', 'status' => 'For Exam', 'applied' => '2026-05-12', 'updated' => 'Jun 3, 9:51 AM', 'dob' => 'January 30, 2006', 'gender' => 'Male', 'email' => 'g.pascual@dlsl.edu.ph', 'phone' => '0918 581 3843', 'address' => 'San Jose, Batangas', 'exam' => 'Jun 20, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0515', 'name' => 'De Guzman, Trisha Anne V.', 'program' => 'BSIS', 'status' => 'Paid', 'applied' => '2026-05-11', 'updated' => 'Jun 4, 10:58 AM', 'dob' => 'April 8, 2005', 'gender' => 'Female', 'email' => 't.deguzman@dlsl.edu.ph', 'phone' => '0919 618 4754', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 5, 2026 · 10:00 AM', 'paid' => 'Jun 5, 2026'],
            ['id' => 'APP-2026-0516', 'name' => 'Flores, Miguel Antonio D.', 'program' => 'BSCpE', 'status' => 'Pending', 'applied' => '2026-05-10', 'updated' => 'Jun 5, 11:15 AM', 'dob' => 'August 17, 2005', 'gender' => 'Male', 'email' => 'm.flores@dlsl.edu.ph', 'phone' => '0920 655 5665', 'address' => 'Batangas City, Batangas', 'exam' => null, 'paid' => null],
            ['id' => 'APP-2026-0517', 'name' => 'Reyes, Jasmine Faith A.', 'program' => 'BSIT', 'status' => 'For Exam', 'applied' => '2026-05-09', 'updated' => 'Jun 6, 8:22 AM', 'dob' => 'February 25, 2006', 'gender' => 'Female', 'email' => 'j.reyes@dlsl.edu.ph', 'phone' => '0917 692 6576', 'address' => 'Lipa City, Batangas', 'exam' => 'Jun 15, 2026 · 9:00 AM', 'paid' => null],
            ['id' => 'APP-2026-0518', 'name' => 'Tolentino, Rafael Dominic S.', 'program' => 'BSCS', 'status' => 'Paid', 'applied' => '2026-05-08', 'updated' => 'Jun 7, 9:29 AM', 'dob' => 'June 14, 2005', 'gender' => 'Male', 'email' => 'r.tolentino@dlsl.edu.ph', 'phone' => '0918 729 7487', 'address' => 'Calamba, Laguna', 'exam' => 'Jun 8, 2026 · 10:00 AM', 'paid' => 'Jun 3, 2026'],
        ];

        return array_values(array_filter($all, fn ($a) => isset($scope[$a['program']])));
    }

    /**
     * "Key Takeaways": three short, descriptive notes worked out from the numbers above
     * (not forecasts). 'tone' is a Bootstrap colour name.
     */
    private function takeaways(array $scope, array $applicants, array $enrolled, array $funnel): array
    {
        $change = fn ($old, $new) => ($new - $old) / $old * 100;
        $takeaways = [];

        if (count($scope) > 1) {
            // Fastest-growing program, and any that shrank
            $growth = array_map(fn ($p) => $change($p['applicants'][2], $p['applicants'][3]), $scope);
            arsort($growth);
            $top = array_key_first($growth);
            $falling = array_filter($growth, fn ($g) => $g < 0);
            $body = 'Applicants are up ' . number_format($growth[$top], 1) . '% vs last AY';
            if ($falling) {
                $body .= ', while ' . implode(' and ', array_map(fn ($code) => $scope[$code]['name'] . ' fell ' . number_format(abs($falling[$code]), 1) . '%', array_keys($falling)));
            }
            $takeaways[] = ['icon' => 'bi-graph-up-arrow', 'tone' => 'primary', 'title' => $scope[$top]['name'] . ' is growing fastest', 'body' => $body . '.'];
        } else {
            $program = reset($scope);
            $growth = $change($applicants[2], $applicants[3]);
            $overall = $change($applicants[0], $applicants[3]);
            $takeaways[] = [
                'icon' => $growth >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow',
                'tone' => $growth >= 0 ? 'primary' : 'danger',
                'title' => $program['name'] . ' applicants ' . ($growth >= 0 ? 'rose' : 'fell') . ' ' . number_format(abs($growth), 1) . '%',
                'body' => number_format($applicants[2]) . ' last AY to ' . number_format($applicants[3]) . ' now, and ' . ($overall >= 0 ? '+' : '−') . number_format(abs($overall), 1) . '% over four academic years.',
            ];
        }

        // Applied-to-enrolled conversion, last AY vs this AY
        $before = $enrolled[2] / $applicants[2] * 100;
        $now = $enrolled[3] / $applicants[3] * 100;
        $takeaways[] = ['icon' => 'bi-funnel', 'tone' => 'warning', 'title' => 'Conversion ' . ($now >= $before ? 'improved' : 'slipped'),
            'body' => 'Applied-to-enrolled moved from ' . number_format($before, 1) . '% to ' . number_format($now, 1) . '%.'];

        // The funnel step where the smallest share of applicants continue (up to Paid)
        $stages = array_keys($funnel);
        $counts = array_values($funnel);
        $worst = 1;
        for ($i = 2; $i < 4; $i++) {
            if ($counts[$i] / $counts[$i - 1] < $counts[$worst] / $counts[$worst - 1]) {
                $worst = $i;
            }
        }
        $takeaways[] = ['icon' => 'bi-exclamation-circle', 'tone' => 'info', 'title' => 'Biggest drop: ' . $stages[$worst - 1] . ' to ' . $stages[$worst],
            'body' => 'Only ' . number_format($counts[$worst] / $counts[$worst - 1] * 100, 1) . '% continue at this step (' . number_format($counts[$worst - 1] - $counts[$worst]) . ' applicants).'];

        return $takeaways;
    }
}
