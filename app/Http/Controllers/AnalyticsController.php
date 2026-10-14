<?php

namespace App\Http\Controllers;

use App\Models\AdmissionStat;

class AnalyticsController extends Controller
{
    public function index()
    {
        $rows = AdmissionStat::all();

        $submittedCurrent = (int) $rows->sum('submitted_current');
        $submittedPrevious = (int) $rows->sum('submitted_previous');
        $overallCurrent = (int) $rows->sum('overall_total_current');
        $overallPrevious = (int) $rows->sum('overall_total_previous');
        $tookTestCurrent = (int) $rows->sum('took_test_current');
        $tookTestPrevious = (int) $rows->sum('took_test_previous');
        $passedCurrent = (int) $rows->sum('passed_current');
        $passedPrevious = (int) $rows->sum('passed_previous');
        $reservedCurrent = (int) $rows->sum('total_reserved_current');
        $reservedPrevious = (int) $rows->sum('total_reserved_previous');
        $enrolledCurrent = (int) $rows->sum('officially_enrolled');

        $passRateCurrent = $tookTestCurrent > 0 ? ($passedCurrent / $tookTestCurrent) * 100 : 0;
        $passRatePrevious = $tookTestPrevious > 0 ? ($passedCurrent / $tookTestPrevious) * 100 : 0;
        $conversionRateCurrent = $overallCurrent > 0 ? ($reservedCurrent / $overallCurrent) * 100 : 0;
        $conversionRatePrevious = $overallPrevious > 0 ? ($reservedPrevious / $overallPrevious) * 100 : 0;

        $stats = [
            [
                'label' => 'Total applicants',
                'value' => number_format($overallCurrent),
                'change' => $this->pctChange($overallCurrent, $overallPrevious),
                'good' => $overallCurrent >= $overallPrevious,
                'tone' => 'primary',
            ],
            [
                'label' => 'Exam pass rate',
                'value' => number_format($passRateCurrent, 1) . '%',
                'change' => $this->ptsChange($passRateCurrent, $passRatePrevious),
                'good' => $passRateCurrent >= $passRatePrevious,
                'tone' => 'info',
            ],
            [
                'label' => 'Conversion rate',
                'value' => number_format($conversionRateCurrent, 1) . '%',
                'change' => $this->ptsChange($conversionRateCurrent, $conversionRatePrevious),
                'good' => $conversionRateCurrent >= $conversionRatePrevious,
                'tone' => 'warning',
            ],
            [
                // No prior-year "enrolled" column exists in the source file, so no trend is shown for this one.
                'label' => 'Total enrolled',
                'value' => number_format($enrolledCurrent),
                'change' => 'No prior-year data',
                'good' => true,
                'tone' => 'primary',
            ],
        ];

        $charts = [
            // Repurposed from "monthly trend" (no such data exists) to a straight current-vs-previous comparison.
            'trends' => [
                'labels' => ['Previous SY', 'Current SY'],
                'values' => [$overallPrevious, $overallCurrent],
            ],
            'programs' => $rows->isEmpty()
                ? ['labels' => ['No data imported yet'], 'values' => [0]]
                : [
                    'labels' => $rows->sortByDesc('overall_total_current')->take(5)->pluck('level_program')->all(),
                    'values' => $rows->sortByDesc('overall_total_current')->take(5)->pluck('overall_total_current')->all(),
                ],
            'funnel' => [
                'labels' => ['Submitted', 'Took Exam', 'Passed Exam', 'Reserved', 'Enrolled'],
                'values' => [
                    max($submittedCurrent, 1), // guards against divide-by-zero in the funnel partial
                    $tookTestCurrent,
                    $passedCurrent,
                    $reservedCurrent,
                    $enrolledCurrent,
                ],
            ],
            // No location data exists anywhere in the source file — left as an explicit placeholder.
            'locations' => [
                'labels' => ['No location data available'],
                'values' => [1],
            ],
        ];

        $comparison = $rows->mapWithKeys(fn (AdmissionStat $row) => [
            $row->level_program => [
                'Previous SY' => (int) ($row->overall_total_previous ?? 0),
                'Current SY' => (int) ($row->overall_total_current ?? 0),
            ],
        ])->all();

        if (empty($comparison)) {
            $comparison = ['No data imported yet' => ['Previous SY' => 0, 'Current SY' => 0]];
        }

        $projection = [
            'year' => 'Next School Year (projected)',
            'items' => $rows->map(function (AdmissionStat $row) {
                $current = (int) ($row->overall_total_current ?? 0);
                $projected = (int) ($row->projection_mid ?? 0);
                $change = $current > 0 ? (($projected - $current) / $current) * 100 : 0;

                return [
                    'dept' => $row->level_program,
                    'current' => $current,
                    'projected' => $projected,
                    'change' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
                    'up' => $change >= 0,
                ];
            })->values()->all(),
        ];

        return view('analytics.index', [
            'filters' => [
                'ranges' => ['This Week', 'This Month', 'This Semester', 'This AY'],
                'departments' => $rows->pluck('level_program')->unique()->values()->all(),
                'years' => ['Current SY', 'Previous SY'],
            ],
            'stats' => $stats,
            'charts' => $charts,
            'comparison' => $comparison,
            'insights' => $this->buildInsights($rows, $charts['funnel']),
            'projection' => $projection,
            'lastAnalyzed' => now()->format('M j, Y · g:i A'),
        ]);
    }

    protected function pctChange(int $current, int $previous): string
    {
        if ($previous === 0) {
            return 'N/A';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change >= 0 ? '+' : '') . number_format($change, 1) . '%';
    }

    protected function ptsChange(float $current, float $previous): string
    {
        $change = $current - $previous;

        return ($change >= 0 ? '+' : '') . number_format($change, 1) . ' pts';
    }

    /**
     * A few real insights computed from the imported numbers, replacing the
     * fully hand-written placeholder ones. Simple on purpose.
     */
    protected function buildInsights($rows, array $funnel): array
    {
        if ($rows->isEmpty()) {
            return [[
                'type' => 'No data', 'icon' => 'bi-info-circle', 'tone' => 'info',
                'title' => 'No admission data imported yet',
                'body' => 'Import an admission stats file to see real insights here.',
                'stat' => '—', 'statLabel' => '', 'trend' => '', 'tags' => [],
            ]];
        }

        $insights = [];

        $topGrower = $rows->sortByDesc('overall_pct_change')->first();
        if ($topGrower && $topGrower->overall_pct_change !== null) {
            $insights[] = [
                'type' => 'Growth', 'icon' => 'bi-graph-up-arrow', 'tone' => 'primary',
                'title' => "{$topGrower->level_program} grew the most this season",
                'body' => "{$topGrower->level_program} recorded the largest increase in total applications compared to the previous school year.",
                'stat' => ($topGrower->overall_pct_change >= 0 ? '+' : '') . number_format($topGrower->overall_pct_change, 1) . '%',
                'statLabel' => 'Change in overall applications vs previous SY',
                'trend' => $topGrower->overall_pct_change >= 0 ? 'Growing' : 'Declining',
                'tags' => [$topGrower->level_program],
            ];
        }

        $topDecliner = $rows->sortBy('overall_pct_change')->first();
        if ($topDecliner && $topDecliner->overall_pct_change !== null && $topDecliner->level_program !== ($topGrower->level_program ?? null)) {
            $insights[] = [
                'type' => 'Needs attention', 'icon' => 'bi-exclamation-triangle', 'tone' => 'danger',
                'title' => "{$topDecliner->level_program} had the biggest drop",
                'body' => "{$topDecliner->level_program} recorded the largest decrease in total applications compared to the previous school year.",
                'stat' => ($topDecliner->overall_pct_change >= 0 ? '+' : '') . number_format($topDecliner->overall_pct_change, 1) . '%',
                'statLabel' => 'Change in overall applications vs previous SY',
                'trend' => 'Declining',
                'tags' => [$topDecliner->level_program],
            ];
        }

        $labels = $funnel['labels'];
        $values = $funnel['values'];
        $biggestDropIndex = null;
        $biggestDropPct = 0;
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $drop = (1 - ($values[$i] / $values[$i - 1])) * 100;
                if ($drop > $biggestDropPct) {
                    $biggestDropPct = $drop;
                    $biggestDropIndex = $i;
                }
            }
        }
        if ($biggestDropIndex !== null) {
            $insights[] = [
                'type' => 'Conversion drop', 'icon' => 'bi-funnel', 'tone' => 'warning',
                'title' => "Biggest drop-off: {$labels[$biggestDropIndex - 1]} to {$labels[$biggestDropIndex]}",
                'body' => "The largest share of applicants is lost between the \"{$labels[$biggestDropIndex - 1]}\" and \"{$labels[$biggestDropIndex]}\" stages of the funnel.",
                'stat' => number_format($biggestDropPct, 1) . '%',
                'statLabel' => "Drop-off from {$labels[$biggestDropIndex - 1]} to {$labels[$biggestDropIndex]}",
                'trend' => 'High drop-off',
                'tags' => ['Funnel', $labels[$biggestDropIndex]],
            ];
        }

        return $insights ?: [[
            'type' => 'No data', 'icon' => 'bi-info-circle', 'tone' => 'info',
            'title' => 'Not enough data yet for insights',
            'body' => 'Import more admission stats rows to see insights here.',
            'stat' => '—', 'statLabel' => '', 'trend' => '', 'tags' => [],
        ]];
    }
}
