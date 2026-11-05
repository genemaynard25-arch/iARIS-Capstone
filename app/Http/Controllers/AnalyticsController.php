<?php

namespace App\Http\Controllers;

use App\Models\AdmissionStat;
use App\Models\CachedInsight;
use App\Services\AiAssistant;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index()
    {
        $summary = $this->computeSummary();
        $rows = $summary['rows'];

        $stats = [
            [
                'label' => 'Total applicants',
                'value' => number_format($summary['overallCurrent']),
                'change' => $this->pctChange($summary['overallCurrent'], $summary['overallPrevious']),
                'good' => $summary['overallCurrent'] >= $summary['overallPrevious'],
                'tone' => 'primary',
            ],
            [
                'label' => 'Exam pass rate',
                'value' => number_format($summary['passRateCurrent'], 1) . '%',
                'change' => $this->ptsChange($summary['passRateCurrent'], $summary['passRatePrevious']),
                'good' => $summary['passRateCurrent'] >= $summary['passRatePrevious'],
                'tone' => 'info',
            ],
            [
                'label' => 'Conversion rate',
                'value' => number_format($summary['conversionRateCurrent'], 1) . '%',
                'change' => $this->ptsChange($summary['conversionRateCurrent'], $summary['conversionRatePrevious']),
                'good' => $summary['conversionRateCurrent'] >= $summary['conversionRatePrevious'],
                'tone' => 'warning',
            ],
            [
                'label' => 'Total enrolled',
                'value' => number_format($summary['enrolledCurrent']),
                'change' => 'No prior-year data',
                'good' => true,
                'tone' => 'primary',
            ],
        ];

        $charts = [
            'trends' => [
                'labels' => ['Previous SY', 'Current SY'],
                'values' => [$summary['overallPrevious'], $summary['overallCurrent']],
            ],
            'programs' => $rows->isEmpty()
                ? ['labels' => ['No data imported yet'], 'values' => [0]]
                : [
                    'labels' => $rows->sortByDesc('overall_total_current')->take(5)->pluck('level_program')->all(),
                    'values' => $rows->sortByDesc('overall_total_current')->take(5)->pluck('overall_total_current')->all(),
                ],
            'funnel' => [
                'labels' => $summary['funnelLabels'],
                'values' => $summary['funnelValues'],
            ],
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

        $cached = CachedInsight::where('key', 'analytics')->first();
        $insights = $cached['payload'] ?? [[
            'type' => 'Not analyzed yet', 'icon' => 'bi-stars', 'tone' => 'info',
            'title' => 'Click "Re-analyze" to generate real AI insights',
            'body' => 'This will send your current admission numbers to the AI assistant and have it write insights from them.',
            'stat' => '—', 'statLabel' => '', 'trend' => '', 'tags' => [],
        ]];

        return view('analytics.index', [
            'filters' => [
                'ranges' => ['This Week', 'This Month', 'This Semester', 'This AY'],
                'departments' => $rows->pluck('level_program')->unique()->values()->all(),
                'years' => ['Current SY', 'Previous SY'],
            ],
            'stats' => $stats,
            'charts' => $charts,
            'comparison' => $comparison,
            'insights' => $insights,
            'projection' => $projection,
            'lastAnalyzed' => $cached?->generated_at?->format('M j, Y \a\t g:i A') ?? 'Never',
        ]);
    }

    public function reanalyze(Request $request, AiAssistant $ai)
    {
        $summary = $this->computeSummary();

        $growers = $summary['rows']->sortByDesc('overall_pct_change')->take(3)
            ->map(fn (AdmissionStat $r) => "{$r->level_program}: " . number_format($r->overall_pct_change ?? 0, 1) . '%')
            ->implode('; ');
        $decliners = $summary['rows']->sortBy('overall_pct_change')->take(3)
            ->map(fn (AdmissionStat $r) => "{$r->level_program}: " . number_format($r->overall_pct_change ?? 0, 1) . '%')
            ->implode('; ');

        $prompt = 'You are analyzing real school admissions data for the IATO office at De La Salle Lipa. '
            . "Here are the real current numbers:\n"
            . "- Total applicants: {$summary['overallCurrent']} (previous SY: {$summary['overallPrevious']})\n"
            . '- Exam pass rate: ' . number_format($summary['passRateCurrent'], 1) . '% (previous SY: ' . number_format($summary['passRatePrevious'], 1) . "%)\n"
            . '- Conversion rate (applied to reserved): ' . number_format($summary['conversionRateCurrent'], 1) . "%\n"
            . "- Total enrolled: {$summary['enrolledCurrent']}\n"
            . "- Biggest growers in applications vs previous SY (level/program: % change): {$growers}\n"
            . "- Biggest decliners in applications vs previous SY (level/program: % change): {$decliners}\n"
            . '- Funnel stages (' . implode(' -> ', $summary['funnelLabels']) . '): ' . implode(' -> ', $summary['funnelValues']) . "\n\n"
            . 'Write 3 short insight cards about this data for a school admissions dashboard. '
            . 'Respond with ONLY a JSON array, no other text, no markdown, no code fences. '
            . 'Each array item must be an object with exactly these keys: '
            . '"type" (a short 1-3 word label), "icon" (a Bootstrap Icons class name like "bi-graph-up-arrow"), '
            . '"tone" (one of: primary, danger, warning, info), "title" (a short headline), '
            . '"body" (1-2 sentences), "stat" (a short number/percent string), "statLabel" (what the stat measures), '
            . '"trend" (1-2 words), "tags" (an array of 1-3 short strings). '
            . 'Use only the real numbers given above — never invent numbers.';

            $raw = trim($ai->askQuestion($prompt));
            preg_match('/\[[\s\S]*\]/', $raw, $matches);
            $parsed = isset($matches[0]) ? json_decode($matches[0], true) : null;

        $valid = is_array($parsed) && count($parsed) > 0 && collect($parsed)->every(
            fn ($item) => is_array($item) && isset($item['title'], $item['body'])
        );

        if (! $valid) {
            return back()->with('insightsError', 'The AI did not return valid insights. Please try again.');
        }

        CachedInsight::updateOrCreate(
            ['key' => 'analytics'],
            ['payload' => $parsed, 'generated_at' => now()]
        );

        return redirect('/analytics');
    }

    protected function computeSummary(): array
    {
        $rows = AdmissionStat::all();

        $submittedCurrent = (int) $rows->sum('submitted_current');
        $overallCurrent = (int) $rows->sum('overall_total_current');
        $overallPrevious = (int) $rows->sum('overall_total_previous');
        $tookTestCurrent = (int) $rows->sum('took_test_current');
        $tookTestPrevious = (int) $rows->sum('took_test_previous');
        $passedCurrent = (int) $rows->sum('passed_current');
        $passedPrevious = (int) $rows->sum('passed_previous');
        $reservedCurrent = (int) $rows->sum('total_reserved_current');
        $reservedPrevious = (int) $rows->sum('total_reserved_previous');
        $enrolledCurrent = (int) $rows->sum('officially_enrolled');

        return [
            'rows' => $rows,
            'overallCurrent' => $overallCurrent,
            'overallPrevious' => $overallPrevious,
            'enrolledCurrent' => $enrolledCurrent,
            'passRateCurrent' => $tookTestCurrent > 0 ? ($passedCurrent / $tookTestCurrent) * 100 : 0,
            'passRatePrevious' => $tookTestPrevious > 0 ? ($passedPrevious / $tookTestPrevious) * 100 : 0,
            'conversionRateCurrent' => $overallCurrent > 0 ? ($reservedCurrent / $overallCurrent) * 100 : 0,
            'conversionRatePrevious' => $overallPrevious > 0 ? ($reservedPrevious / $overallPrevious) * 100 : 0,
            'funnelLabels' => ['Submitted', 'Took Exam', 'Passed Exam', 'Reserved', 'Enrolled'],
            'funnelValues' => [
                max($submittedCurrent, 1),
                $tookTestCurrent,
                $passedCurrent,
                $reservedCurrent,
                $enrolledCurrent,
            ],
        ];
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
}