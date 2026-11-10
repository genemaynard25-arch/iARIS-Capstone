<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\CachedInsight;
use App\Services\AiAssistant;
use App\Support\AddressClassifier;
use App\Support\AdmissionStats;
use App\Support\AnalyticsFilterData;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    // Analytics & Reports page. Overview and AI Insights need the 'analytics' permission, the Reports tab needs 'reports'.
    public function index(Request $request)
    {
        $user = $request->user();
        $tabs = array_keys(array_filter([
            'overview' => $user->canAccess('analytics'),
            'insights' => $user->canAccess('analytics'),
            'reports' => $user->canAccess('reports'),
        ]));
        abort_if(empty($tabs), 403, 'Your role does not have access to this module.');
        $tab = in_array($request->query('tab'), $tabs) ? $request->query('tab') : $tabs[0];

        $summary = AdmissionStats::summary();
        $rows = AdmissionStats::byLevel();

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
                'value' => number_format($summary['passRateCurrent'], 1).'%',
                'change' => $this->ptsChange($summary['passRateCurrent'], $summary['passRatePrevious']),
                'good' => $summary['passRateCurrent'] >= $summary['passRatePrevious'],
                'tone' => 'info',
            ],
            [
                'label' => 'Conversion rate',
                'value' => number_format($summary['conversionRateCurrent'], 1).'%',
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
            // byLevel() is already sorted by current count, descending
            'programs' => $rows->isEmpty()
                ? ['labels' => ['No data imported yet'], 'values' => [0]]
                : [
                    'labels' => $rows->take(5)->pluck('level_program')->all(),
                    'values' => $rows->take(5)->pluck('current')->all(),
                ],
            'funnel' => [
                'labels' => $summary['funnelLabels'],
                'values' => $summary['funnelValues'],
            ],
            'locations' => $this->locationChart(),
        ];

        $comparison = $rows->mapWithKeys(fn (array $row) => [
            $row['level_program'] => [
                'Previous SY' => $row['previous'],
                'Current SY' => $row['current'],
            ],
        ])->all();

        if (empty($comparison)) {
            $comparison = ['No data imported yet' => ['Previous SY' => 0, 'Current SY' => 0]];
        }

        // Auto-projected from each level's own year-over-year growth (current + the same
        // change again) — nobody sets a target number, this is computed, not imported.
        $projection = [
            'year' => 'Next School Year (auto-projected from growth)',
            'items' => $rows->map(function (array $row) {
                $current = $row['current'];
                $projected = $row['previous'] > 0 ? max(0, $current + ($current - $row['previous'])) : $current;
                $change = $current > 0 ? (($projected - $current) / $current) * 100 : 0;

                return [
                    'dept' => $row['level_program'],
                    'current' => $current,
                    'projected' => $projected,
                    'change' => ($change >= 0 ? '+' : '').number_format($change, 1).'%',
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
            // Overview/Insights are safe for the AI widget to read (aggregate totals only).
            // Reports now has real per-applicant rows (names, scores) in its "Admissions
            // Reports" section, so that tab must NOT be readable by the widget — same leak
            // this session already fixed once for the Applicants pipeline.
            'aggregateOnlyPage' => $tab !== 'reports',
            'tabs' => $tabs,
            'tab' => $tab,
            'reportData' => (new ReportController)->data('Current School Year', $request->query('year')),
            'filters' => [
                'ranges' => ['This Week', 'This Month', 'This Semester', 'This AY'],
                'departments' => $rows->pluck('level_program')->unique()->values()->all(),
                'years' => ['Current SY', 'Previous SY'],
            ],
            'filterData' => $user->canAccess('analytics') ? AnalyticsFilterData::make() : null,
            'stats' => $stats,
            'charts' => $charts,
            'comparison' => $comparison,
            'insights' => $insights,
            'projection' => $projection,
            // Automatic Yes/No and gender counts - no manual filtering or tallying needed
            'yesNoBreakdown' => AdmissionStats::yesNoBreakdown(),
            'lastAnalyzed' => $cached?->generated_at?->format('M j, Y \a\t g:i A') ?? 'Never',
        ]);
    }

    public function reanalyze(Request $request, AiAssistant $ai)
    {
        $summary = AdmissionStats::summary();
        $rows = AdmissionStats::byLevel()->filter(fn (array $r) => $r['pct_change'] !== null);

        $growers = $rows->sortByDesc('pct_change')->take(3)
            ->map(fn (array $r) => "{$r['level_program']}: ".number_format($r['pct_change'], 1).'%')
            ->implode('; ') ?: 'No prior-year data to compare';
        $decliners = $rows->sortBy('pct_change')->take(3)
            ->map(fn (array $r) => "{$r['level_program']}: ".number_format($r['pct_change'], 1).'%')
            ->implode('; ') ?: 'No prior-year data to compare';

        $prompt = 'You are analyzing real school admissions data for the IATO office at De La Salle Lipa. '
            ."Here are the real current numbers:\n"
            ."- Total applicants: {$summary['overallCurrent']} (previous SY: {$summary['overallPrevious']})\n"
            .'- Exam pass rate: '.number_format($summary['passRateCurrent'], 1).'% (previous SY: '.number_format($summary['passRatePrevious'], 1)."%)\n"
            .'- Conversion rate (applied to reserved): '.number_format($summary['conversionRateCurrent'], 1)."%\n"
            ."- Total enrolled: {$summary['enrolledCurrent']}\n"
            ."- Biggest growers in applications vs previous SY (level/program: % change): {$growers}\n"
            ."- Biggest decliners in applications vs previous SY (level/program: % change): {$decliners}\n"
            .'- Funnel stages ('.implode(' -> ', $summary['funnelLabels']).'): '.implode(' -> ', $summary['funnelValues'])."\n\n"
            .'Write 3 short insight cards about this data for a school admissions dashboard. '
            .'Respond with ONLY a JSON array, no other text, no markdown, no code fences. '
            .'Each array item must be an object with exactly these keys: '
            .'"type" (a short 1-3 word label), "icon" (a Bootstrap Icons class name like "bi-graph-up-arrow"), '
            .'"tone" (one of: primary, danger, warning, info), "title" (a short headline), '
            .'"body" (1-2 sentences), "stat" (a short number/percent string), "statLabel" (what the stat measures), '
            .'"trend" (1-2 words), "tags" (an array of 1-3 short strings). '
            .'Use only the real numbers given above — never invent numbers.';

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

        return redirect('/analytics?tab=insights');
    }

    protected function pctChange(int $current, int $previous): string
    {
        if ($previous === 0) {
            return 'N/A';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change >= 0 ? '+' : '').number_format($change, 1).'%';
    }

    protected function ptsChange(float $current, float $previous): string
    {
        $change = $current - $previous;

        return ($change >= 0 ? '+' : '').number_format($change, 1).' pts';
    }

    /** Current school year's applicants, bucketed by address (App\Support\AddressClassifier). */
    protected function locationChart(): array
    {
        $schoolYear = AdmissionStats::currentSchoolYear();
        $counts = $schoolYear
            ? Applicant::where('school_year', $schoolYear)->whereNull('archived_at')
                ->get(['address'])->countBy(fn ($a) => AddressClassifier::classify($a->address))
            : collect();

        if ($counts->isEmpty()) {
            return ['labels' => ['No location data available'], 'values' => [1]];
        }

        $groups = collect(AddressClassifier::GROUPS)->filter(fn ($g) => $counts->get($g, 0) > 0);

        return ['labels' => $groups->values()->all(), 'values' => $groups->map(fn ($g) => $counts->get($g, 0))->values()->all()];
    }
}
