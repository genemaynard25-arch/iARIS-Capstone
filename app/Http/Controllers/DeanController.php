<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeanController extends Controller
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
     * Dean / Program Chair dashboard: read-only, scoped to the user's college or program.
     *
     * A dean sees every program in their college and can narrow it to one (?program=BSCS).
     * A program chair is locked to their own program; ?program= is ignored for them.
     * TODO: there is no column yet for a user's college or program, so every dean is
     * CITE and every program chair is BS Computer Science for now.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $isChair = $user->isProgramChair();

        // Which programs the numbers add up
        $myProgram = $isChair ? 'BSCS' : null;
        $selected = $myProgram ?? (array_key_exists($request->query('program'), self::PROGRAMS) ? $request->query('program') : null);
        $scope = $selected ? [$selected => self::PROGRAMS[$selected]] : self::PROGRAMS;
        $scopeName = $selected ? self::PROGRAMS[$selected]['name'] : 'All CITE programs';

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

        return view('dean.dashboard', [
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

            // Newest first. Only the programs in scope.
            'recent' => array_slice(array_values(array_filter([
                ['name' => 'Cruz, Angelo Miguel R.', 'program' => 'BSIS', 'status' => 'Pending', 'date' => 'May 25, 2026'],
                ['name' => 'Dizon, Katrina Joy P.', 'program' => 'BSCpE', 'status' => 'For Exam', 'date' => 'May 24, 2026'],
                ['name' => 'Ramos, Joshua Benedict T.', 'program' => 'BSIT', 'status' => 'For Exam', 'date' => 'May 23, 2026'],
                ['name' => 'Sangalang, Pia Amherstia V.', 'program' => 'BSCS', 'status' => 'Pending', 'date' => 'May 22, 2026'],
                ['name' => 'Bautista, Marian Grace S.', 'program' => 'BSCS', 'status' => 'For Exam', 'date' => 'May 21, 2026'],
                ['name' => 'Lim, Kevin Andrei D.', 'program' => 'BSCS', 'status' => 'Paid', 'date' => 'May 19, 2026'],
                ['name' => 'Malibiran, Gene Maynard G.', 'program' => 'BSIT', 'status' => 'Paid', 'date' => 'May 16, 2026'],
                ['name' => 'Go, Raphael Lorenzo L.', 'program' => 'BSCS', 'status' => 'Paid', 'date' => 'May 14, 2026'],
            ], fn ($a) => isset($scope[$a['program']]))), 0, 6),

            // Reports the IATO sent to this office
            'reports' => [
                ['title' => ($isChair ? 'BS Computer Science' : 'CITE') . ' Applicants Summary — AY 2025–26', 'sent' => 'Jun 12, 2026', 'new' => true],
                ['title' => 'Enrolled per Program — AY 2024–25', 'sent' => 'Jun 14, 2025', 'new' => false],
                ['title' => ($isChair ? 'BS Computer Science' : 'CITE') . ' Admissions Summary — AY 2023–24', 'sent' => 'Jun 10, 2024', 'new' => false],
            ],
        ]);
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
