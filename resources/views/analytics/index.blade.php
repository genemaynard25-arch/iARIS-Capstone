@extends('layouts.app')

@section('title', 'iARIS — Analytics & Reports')
@section('page-title', 'Analytics & Reports')
@section('body-class', 'iaris-workspace analytics-page')
@section('page-breadcrumb-root', 'Main')
@section('page-breadcrumb', 'Analytics & Reports')
@push('styles')
    <link href="{{ asset('css/app-shell.css') }}" rel="stylesheet">
    <link href="{{ asset('css/analytics.css') }}" rel="stylesheet">
@endpush
@section('sidebar-brand')
    @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
@endsection
@section('app-banner')
    @include('partials.institution-bar')
@endsection
@section('page-subtitle', 'Explore admissions data, read the AI insights, and turn any view into an official report')

@php
    // Which tabs this user gets (see AnalyticsController::index) and the report data for the Reports tab
    $hasAnalytics = in_array('overview', $tabs);
    $hasReports = in_array('reports', $tabs);
    $reportCount = collect($reportData['sections'])->sum(fn ($section) => count($section['reports']));
    // "Send to" choices in the two report builders
    $recipients = collect($reportData['sections'])->pluck('reports')->flatten(1)->pluck('recipient')->unique()->values();
    $tabList = [
        'overview' => ['Overview', 'bi-bar-chart'],
        'insights' => ['AI Insights', 'bi-stars'],
        'reports' => ['Reports', 'bi-file-earmark-text'],
    ];
    $locationPreview = ($charts['locations']['labels'] ?? []) === ['No location data available'];
    if ($locationPreview) {
        $charts['locations'] = ['labels' => ['Lipa City', 'Batangas City', 'CALABARZON', 'Others'], 'values' => [1377, 895, 723, 447]];
    }
@endphp

@section('page-actions')
    @if ($hasAnalytics && $hasReports)
        <button type="button" class="ar-generate-view btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#viewReportModal"><i class="bi bi-file-earmark-arrow-up me-1" aria-hidden="true"></i> Generate from this view</button>
    @endif
@endsection

@section('content')
    @if ($hasAnalytics)
    {{-- Filters: one row above everything they scope --}}
    <div class="ar-filter-card card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <div class="small fw-bold text-uppercase text-body-secondary mb-2">Date range</div>
                    <div class="d-flex flex-wrap gap-2" role="radiogroup" aria-label="Date range">
                        @foreach ($filters['ranges'] as $range)
                            <input type="radio" class="btn-check" name="range" id="range{{ $loop->index }}" value="{{ $range }}" {{ $range === 'This Semester' ? 'checked' : '' }}>
                            <label class="btn btn-sm btn-outline-primary fw-semibold" for="range{{ $loop->index }}">{{ $range }}</label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label for="filterDepartment" class="small fw-bold text-uppercase text-body-secondary mb-2 d-block">Department</label>
                    <select class="form-select" id="filterDepartment">
                        <option>All Departments</option>
                        @foreach ($filters['departments'] as $department)
                            <option>{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filterYear" class="small fw-bold text-uppercase text-body-secondary mb-2 d-block">Academic year</label>
                    <select class="form-select" id="filterYear">
                        @foreach ($filters['years'] as $year)
                            <option>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="ar-filter-note"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Preview filters update report scope only. Figures below continue to show the current imported school-year data.</div>
        </div>
    </div>
    @endif

    {{-- Tabs, with what the filters are showing and "Generate from this view" on the right --}}
    <div class="ar-tab-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <ul class="ar-tabs nav nav-pills gap-1" role="tablist">
            @foreach ($tabs as $key)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link fw-semibold {{ $key === $tab ? 'active' : '' }}" id="tab-{{ $key }}" data-bs-toggle="pill" data-bs-target="#pane-{{ $key }}" data-tab="{{ $key }}" role="tab" aria-controls="pane-{{ $key }}" aria-selected="{{ $key === $tab ? 'true' : 'false' }}">
                        <i class="bi {{ $tabList[$key][1] }} me-1"></i> {{ $tabList[$key][0] }}
                        @if ($key === 'reports')
                            <span class="badge rounded-pill bg-body-tertiary text-body-secondary border ms-1">{{ $reportCount }}</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>
        @if ($hasAnalytics)
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="ar-scope-chip badge rounded-pill text-wrap text-start">
                    <i class="bi bi-funnel me-1"></i> Showing: <span class="text-body" id="scopeText">—</span>
                </span>
            </div>
        @endif
    </div>

    <div class="tab-content">
    @if ($hasAnalytics)
    {{-- ===== Overview tab ===== --}}
    <div class="tab-pane fade {{ $tab === 'overview' ? 'show active' : '' }}" id="pane-overview" role="tabpanel" aria-labelledby="tab-overview" tabindex="0">

    {{-- Stat tiles --}}
    <div class="ar-stat-grid row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="ar-stat-card card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold mb-1">{{ $stat['value'] }}</div>
                        @if (in_array($stat['change'], ['N/A', 'No prior-year data']))
                            <div class="small text-body-secondary">{{ $stat['change'] }}</div>
                        @else
                            <div class="small fw-semibold {{ $stat['good'] ? 'text-primary' : 'text-danger' }}">
                                <i class="bi {{ str_starts_with($stat['change'], '+') ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}" aria-hidden="true"></i>
                                {{ $stat['change'] }} <span class="text-body-secondary fw-normal">vs last AY</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @include('analytics.partials.field-counts')

    {{-- Charts --}}
    <div class="ar-chart-grid row g-4 mb-4">
        <div class="col-12 col-xl-6">
           @include('analytics.partials.chart-card', ['id' => 'trends', 'title' => 'Applicants: current vs previous SY', 'subtitle' => 'Total applicants summed across all levels/programs', 'types' => ['bar' => 'Bar chart', 'line' => 'Line chart']])
        </div>
        <div class="col-12 col-xl-6">
            @include('analytics.partials.chart-card', ['id' => 'programs', 'title' => 'Top programs by applicants', 'subtitle' => 'Most applied-to programs this AY'])
        </div>

        <div class="col-12 col-xl-6">
            @include('analytics.partials.chart-card', ['id' => 'funnel', 'title' => 'Conversion funnel', 'subtitle' => 'Applicant pipeline from application to enrollment', 'body' => 'analytics.partials.funnel'])
        </div>
        <div class="col-12 col-xl-6">
            @include('analytics.partials.chart-card', ['id' => 'locations', 'title' => 'Applicants by location', 'subtitle' => $locationPreview ? 'Mockup preview · location aggregation is not connected yet' : 'Where applicants come from', 'types' => ['donut' => 'Donut chart', 'bar' => 'Bar chart'], 'body' => 'analytics.partials.locations'])
        </div>
    </div>

    {{-- School year comparison builder --}}
    <div class="ar-comparison-card card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-1">School year comparison</h2>
            <p class="small text-body-secondary mb-3">Pick a department and 2–3 academic years, then generate the chart.</p>

            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label for="compareDepartment" class="small fw-bold text-uppercase text-body-secondary mb-2 d-block">Department</label>
                    <select class="form-select" id="compareDepartment">
                        <option value="">All Departments</option>
                        @foreach (array_keys($comparison) as $department)
                            <option>{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="small fw-bold text-uppercase text-body-secondary mb-2">Academic years (max 3)</div>
                    <div class="d-flex flex-wrap gap-2" id="yearChips"></div>
                </div>
                <div>
                    <label for="yearPicker" class="small fw-bold text-uppercase text-body-secondary mb-2 d-block">Add year</label>
                    <div class="input-group">
                        <select class="form-select" id="yearPicker">
                            @foreach (array_keys(reset($comparison)) as $year)
                                <option>{{ $year }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="addYear"><i class="bi bi-plus-lg"></i> Add</button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary fw-semibold" id="generateComparison"><i class="bi bi-bar-chart me-1"></i> Generate comparison</button>
            </div>
            <div class="small text-danger mt-2 d-none" id="compareMessage" role="alert"></div>

            <div class="border-top mt-4 pt-4 d-none" id="comparisonResult">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <div class="fw-bold" id="comparisonTitle"></div>
                        <div class="small text-body-secondary" id="comparisonSubtitle"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @if ($hasReports)
                            <button type="button" class="btn btn-sm btn-light border" data-add-report="School year comparison" aria-pressed="false"><i class="bi bi-plus-lg me-1"></i> Add to report</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-light border" data-table-toggle="comparison" aria-pressed="false"><i class="bi bi-table me-1"></i> Table</button>
                        <button type="button" class="btn btn-sm btn-light border" data-export="comparison"><i class="bi bi-download me-1"></i> CSV</button>
                    </div>
                </div>
                <div data-chart-area="comparison">
                    <div class="chart-box chart-box-lg"><canvas id="chart-comparison" role="img" aria-label="School year comparison"></canvas></div>
                </div>
                <div class="table-responsive d-none" data-table-view="comparison"></div>
            </div>
        </div>
    </div>

    </div>{{-- /Overview tab --}}

    {{-- ===== AI Insights tab ===== --}}
    <div class="tab-pane fade {{ $tab === 'insights' ? 'show active' : '' }}" id="pane-insights" role="tabpanel" aria-labelledby="tab-insights" tabindex="0">

    {{-- AI insights --}}
    <div class="ar-insights-header card border-0 rounded-4 bg-iaris text-white mb-3">
        <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-circle rounded-3 bg-white bg-opacity-10 border border-white border-opacity-25 fs-5 d-flex align-items-center justify-content-center">
                    <i class="bi bi-cpu"></i>
                </div>
                <div>
                    <h2 class="fs-5 fw-bold mb-1">AI-generated insights</h2>
                    <p class="small text-white-50 mb-0">Patterns, trends, and projections from historical admissions data</p>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 fw-semibold">
                    <i class="bi bi-circle-fill text-iaris-pale me-1" style="font-size: 0.5rem;"></i> Last analyzed: {{ $lastAnalyzed }}
                </span>
               <form method="POST" action="{{ url('/analytics/reanalyze') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-repeat me-1"></i> Re-analyze</button>
                </form>
                @if (session('insightsError'))
                    <span class="badge bg-danger ms-2">{{ session('insightsError') }}</span>
                @endif
                <button type="button" class="btn btn-sm btn-light fw-semibold" id="exportInsights"><i class="bi bi-download me-1" aria-hidden="true"></i> Export insights</button>
            </div>
        </div>
    </div>
    <div class="small text-body-secondary text-end mb-3">Data source: current imported admission data</div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
        @foreach ($insights as $insight)
            <div class="col">
                <div class="ar-insight-card card border-0 border-start border-4 border-{{ $insight['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body d-flex flex-column p-4">
                        <span class="badge bg-{{ $insight['tone'] }}-subtle text-{{ $insight['tone'] }}-emphasis text-uppercase tracking-wide align-self-start mb-3">
                            <i class="bi {{ $insight['icon'] }} me-1"></i> {{ $insight['type'] }}
                        </span>
                        <h3 class="fs-6 fw-bold mb-2">{{ $insight['title'] }}</h3>
                        <p class="small text-body-secondary flex-grow-1">{{ $insight['body'] }}</p>
                        <div class="rounded-3 bg-body-tertiary px-3 py-2 mb-3">
                            <div class="d-flex align-items-baseline justify-content-between gap-2">
                                <div class="fs-4 fw-bold">{{ $insight['stat'] }}</div>
                                <div class="small fw-bold text-{{ $insight['tone'] }}-emphasis text-nowrap">{{ $insight['trend'] }}</div>
                            </div>
                            <div class="small text-body-secondary lh-sm">{{ $insight['statLabel'] }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($insight['tags'] as $tag)
                                <span class="badge bg-body-tertiary text-body-secondary border fw-semibold">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Projection --}}
    <div class="ar-projection-card card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-1">Projected enrollment — {{ $projection['year'] }}</h2>
            <p class="small text-body-secondary mb-3">Based on imported school-year comparisons · For planning purposes only</p>
            <div class="row row-cols-2 row-cols-lg-4 g-3">
                @foreach ($projection['items'] as $item)
                    <div class="col">
                        <div class="rounded-3 bg-body-tertiary border-top border-3 border-{{ $item['up'] ? 'primary' : 'danger' }} p-3 h-100">
                            <div class="small fw-bold text-uppercase text-body-secondary mb-1">{{ $item['dept'] }}</div>
                            <div class="small text-body-secondary mb-1">Current AY: {{ number_format($item['current']) }} enrolled</div>
                            <div class="fs-4 fw-bold">{{ number_format($item['projected']) }}</div>
                            <div class="small fw-bold {{ $item['up'] ? 'text-primary' : 'text-danger' }}">
                                <i class="bi {{ $item['up'] ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i> {{ $item['change'] }} projected
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="alert alert-light border d-flex gap-2 small text-body-secondary mb-0">
        <i class="bi bi-info-circle mt-1"></i>
        <div>
            <strong class="text-body">About these insights:</strong> AI-generated insights and projections are based on historical admissions data stored in iARIS. Projections are statistical estimates only and do not account for external factors such as policy changes, demographic shifts, or program modifications.
            <strong class="text-body">All decisions remain with the IATO Admin and institutional leadership.</strong> This tool assists, and does not replace, human judgment.
        </div>
    </div>
    </div>{{-- /AI Insights tab --}}
    @endif

    @if ($hasReports)
    {{-- ===== Reports tab ===== --}}
    <div class="tab-pane fade {{ $tab === 'reports' ? 'show active' : '' }}" id="pane-reports" role="tabpanel" aria-labelledby="tab-reports" tabindex="0">
        @include('analytics.partials.reports-tab', $reportData)
    </div>
    @endif
    </div>{{-- /tab-content --}}

    @if ($hasAnalytics && $hasReports)
        @include('analytics.partials.report-builders')
    @endif
@endsection

@section('scripts')
    <script>
        // ---- Tabs: keep the open tab in the address (?tab=...) so reloading or sharing the link opens it ----
        document.querySelectorAll('[data-tab]').forEach(btn => btn.addEventListener('shown.bs.tab', () => {
            const url = new URL(location.href);
            url.searchParams.set('tab', btn.dataset.tab);
            history.replaceState(null, '', url);
            if (window.Chart) Object.values(Chart.instances).forEach(chart => chart.resize());
        }));
    </script>

    @if ($hasAnalytics)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const CHARTS = @json($charts);
        const COMPARISON = @json($comparison);
        const INSIGHTS = @json($insights);
        const LOCATION_PREVIEW = @json($locationPreview);

        // Chart colours (checked with the dataviz palette validator).
        const COLORS = {
            single: '#00674F',                                        // one-series charts
            locations: ['#00674F', '#4DAB8E', '#A8D5C5', '#D4EDE5'],  // 3 distinct hues + grey for "Others"
            years: ['#7FC4AB', '#2E8A6E', '#00503D'],                 // ordered: oldest (light) → newest (dark)
            grid: '#E7ECEA',
            text: 'rgba(26, 43, 38, 0.6)',
        };

        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = COLORS.text;
        Chart.defaults.plugins.legend.display = false;
        Chart.defaults.plugins.tooltip.backgroundColor = '#1A2B26';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.bodyFont = { weight: 'bold' };
        Chart.defaults.plugins.tooltip.displayColors = false; // one-series charts don't need a colour key in the tooltip
        Chart.defaults.plugins.tooltip.callbacks.label = ctx =>
            ` ${(typeof ctx.parsed === 'object' ? (ctx.chart.options.indexAxis === 'y' ? ctx.parsed.x : ctx.parsed.y) : ctx.parsed).toLocaleString()}${ctx.dataset.label ? ' · ' + ctx.dataset.label : ''}`;

        const format = n => Number(n).toLocaleString();

        // Shared chart pieces
        const axis = (showGrid) => ({ grid: { display: showGrid, color: COLORS.grid, drawTicks: false }, border: { display: false }, ticks: { padding: 8 } });
        const bar = (label, data, color) => ({ label, data, backgroundColor: color, hoverBackgroundColor: color + 'CC', borderRadius: 4, borderSkipped: 'start', maxBarThickness: 24 });

        // Vertical hairline that follows the hovered point on line charts
        const crosshair = {
            id: 'crosshair',
            afterDatasetsDraw(chart) {
                const active = chart.tooltip?.getActiveElements();
                if (chart.config.type !== 'line' || !active?.length) return;
                const { ctx, chartArea } = chart;
                ctx.save();
                ctx.strokeStyle = COLORS.text;
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(active[0].element.x, chartArea.top);
                ctx.lineTo(active[0].element.x, chartArea.bottom);
                ctx.stroke();
                ctx.restore();
            },
        };

        const instances = {};
        function draw(id, config) {
            instances[id]?.destroy();
            instances[id] = new Chart(document.getElementById(`chart-${id}`), { ...config, plugins: [crosshair] });
        }

        // ---- Applicant trends (bar or line) ----
        function drawTrends(type) {
            const labels = Object.keys(COMPARISON);
            const years = Object.keys(Object.values(COMPARISON)[0]);
            const colors = ['#A8D5C5', '#00674F', '#00503D'];
            const datasets = years.map((year, index) => type === 'line'
                ? { label: year, data: labels.map(label => COMPARISON[label][year]), borderColor: colors[index], borderWidth: 2, tension: .15, pointRadius: 4 }
                : bar(year, labels.map(label => COMPARISON[label][year]), colors[index]));
            draw('trends', {
                type,
                data: { labels, datasets },
                options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10 } }, tooltip: { displayColors: true } },
                    scales: { x: axis(false), y: { ...axis(true), beginAtZero: true } } },
            });
        }

        // ---- Top programs (horizontal bars, one colour) ----
        function drawPrograms() {
            const { labels, values } = CHARTS.programs;
            draw('programs', {
                type: 'bar',
                data: { labels, datasets: [bar('', values, COLORS.single)] },
                options: { indexAxis: 'y', maintainAspectRatio: false,
                    scales: { x: { ...axis(true), beginAtZero: true }, y: axis(false) } },
            });
        }

        // ---- Locations (donut with its own legend, or one-colour bars) ----
        function drawLocations(type) {
            const { labels, values } = CHARTS.locations;
            const total = values.reduce((a, b) => a + b, 0);
            document.getElementById('locationsDonutExtras').classList.toggle('d-none', type !== 'donut');
            document.getElementById('locationsBox').classList.toggle('chart-box-donut', type === 'donut');
            if (type === 'donut') {
                document.getElementById('locationsTotal').textContent = format(total);
                const legend = document.getElementById('locationsLegend');
                legend.replaceChildren(...labels.map((label, i) => {
                    const li = document.createElement('li');
                    li.className = 'd-flex align-items-center gap-2 small';
                    li.innerHTML = '<span class="rounded-1 flex-shrink-0" style="width:10px;height:10px"></span><span class="flex-grow-1"></span><span class="fw-bold text-body-secondary"></span>';
                    li.children[0].style.background = COLORS.locations[i];
                    li.children[1].textContent = label;
                    li.children[2].textContent = (total > 0 ? Math.round(values[i] / total * 100) : 0) + '%';
                    return li;
                }));
            }
            draw('locations', type === 'donut'
                ? { type: 'doughnut',
                    data: { labels, datasets: [{ data: values, backgroundColor: COLORS.locations, borderColor: '#fff', borderWidth: 2, hoverOffset: 4 }] },
                    options: { maintainAspectRatio: false, cutout: '70%' } }
                : { type: 'bar',
                    data: { labels, datasets: [bar('', values, COLORS.single)] },
                    options: { indexAxis: 'y', maintainAspectRatio: false, scales: { x: { ...axis(true), beginAtZero: true }, y: axis(false) } } });
        }

        // ---- School year comparison ----
        let selectedYears = ['Previous SY', 'Current SY'];
        let comparisonTable = null;

        function renderYearChips() {
            const wrap = document.getElementById('yearChips');
            wrap.replaceChildren(...selectedYears.map(year => {
                const chip = document.createElement('span');
                chip.className = 'badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle d-inline-flex align-items-center gap-1 px-3 py-2 fs-6 fw-semibold';
                chip.append(year);
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'btn-close ms-1';
                remove.style.fontSize = '0.6rem';
                remove.setAttribute('aria-label', `Remove ${year}`);
                remove.addEventListener('click', () => { selectedYears = selectedYears.filter(y => y !== year); renderYearChips(); });
                chip.appendChild(remove);
                return chip;
            }));
        }

        function compareMessage(text) {
            const el = document.getElementById('compareMessage');
            el.textContent = text;
            el.classList.toggle('d-none', !text);
        }

        document.getElementById('addYear').addEventListener('click', () => {
            const year = document.getElementById('yearPicker').value;
            if (selectedYears.includes(year)) return compareMessage(`${year} is already added.`);
            if (selectedYears.length >= 3) return compareMessage('You can compare up to 3 academic years. Remove one first.');
            selectedYears.push(year);
            compareMessage('');
            renderYearChips();
        });

        document.getElementById('generateComparison').addEventListener('click', () => {
            if (selectedYears.length < 2) return compareMessage('Add at least 2 academic years to compare.');
            compareMessage('');
            const availableYears = Object.keys(Object.values(COMPARISON)[0]);
            const years = [...selectedYears].sort((a, b) => availableYears.indexOf(a) - availableYears.indexOf(b));
            const yearColors = years.length === 2 ? [COLORS.years[0], COLORS.years[2]] : COLORS.years;
            const department = document.getElementById('compareDepartment').value;

            let labels, datasets;
            if (department) {
                // One department: one bar per year, one colour
                labels = years;
                datasets = [bar(department, years.map(y => COMPARISON[department][y]), COLORS.single)];
                comparisonTable = { head: ['Academic year', department], rows: years.map(y => [y, COMPARISON[department][y]]) };
            } else {
                // All departments: a group of bars per department, one bar per year
                labels = Object.keys(COMPARISON);
                datasets = years.map((y, i) => bar(y, labels.map(d => COMPARISON[d][y]), yearColors[i]));
                comparisonTable = { head: ['Department', ...years], rows: labels.map(d => [d, ...years.map(y => COMPARISON[d][y])]) };
            }

            document.getElementById('comparisonTitle').textContent = `Comparison: ${department || 'All Departments'}`;
            document.getElementById('comparisonSubtitle').textContent = years.join(' · ');
            document.getElementById('comparisonResult').classList.remove('d-none');
            draw('comparison', {
                type: 'bar',
                data: { labels, datasets },
                options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: !department, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, useBorderRadius: true, borderRadius: 2 } },
                        tooltip: { displayColors: !department, boxPadding: 4 },
                    },
                    scales: { x: axis(false), y: { ...axis(true), beginAtZero: true } } },
            });
            refreshTable('comparison');
            document.getElementById('comparisonResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        // ---- Table view + CSV export (every chart) ----
        function tableData(id) {
            if (id === 'comparison') return comparisonTable;
            if (id === 'trends') {
                const years = Object.keys(Object.values(COMPARISON)[0]);
                return { head: ['Division / program', ...years], rows: Object.entries(COMPARISON).map(([label, values]) => [label, ...years.map(year => values[year])]) };
            }
            const { labels, values } = CHARTS[id];
            const heads = { trends: ['School year', 'Applicants'], programs: ['Program', 'Applicants'], funnel: ['Stage', 'Applicants'], locations: LOCATION_PREVIEW ? ['Location (mockup preview)', 'Sample applicants'] : ['Location', 'Applicants'] };
            return { head: heads[id], rows: labels.map((l, i) => [l, values[i]]) };
        }

        function refreshTable(id) {
            const data = tableData(id);
            const wrap = document.querySelector(`[data-table-view="${id}"]`);
            if (!data || !wrap) return;
            const table = document.createElement('table');
            table.className = 'table table-sm align-middle small mb-0';
            const headRow = table.createTHead().insertRow();
            data.head.forEach((h, i) => {
                const th = document.createElement('th');
                th.className = 'text-body-secondary text-uppercase' + (i ? ' text-end' : '');
                th.textContent = h;
                headRow.appendChild(th);
            });
            const body = table.createTBody();
            data.rows.forEach(r => {
                const tr = body.insertRow();
                r.forEach((cell, i) => {
                    const td = tr.insertCell();
                    td.textContent = typeof cell === 'number' ? format(cell) : cell;
                    if (i) td.className = 'text-end font-monospace';
                });
            });
            wrap.replaceChildren(table);
        }

        document.querySelectorAll('[data-table-toggle]').forEach(btn => btn.addEventListener('click', () => {
            const id = btn.dataset.tableToggle;
            const showTable = btn.getAttribute('aria-pressed') !== 'true';
            refreshTable(id);
            document.querySelector(`[data-table-view="${id}"]`).classList.toggle('d-none', !showTable);
            document.querySelector(`[data-chart-area="${id}"]`).classList.toggle('d-none', showTable);
            btn.setAttribute('aria-pressed', showTable);
            btn.classList.toggle('active', showTable);
            btn.innerHTML = showTable ? '<i class="bi bi-bar-chart me-1"></i> Chart' : '<i class="bi bi-table me-1"></i> Table';
        }));

        document.querySelectorAll('[data-export]').forEach(btn => btn.addEventListener('click', () => {
            const data = tableData(btn.dataset.export);
            if (!data) return;
            const suffix = btn.dataset.export === 'locations' && LOCATION_PREVIEW ? '-preview' : '';
            downloadCsv(`iaris-${btn.dataset.export}${suffix}.csv`, [data.head, ...data.rows]);
        }));

        // Chart type switches
        document.querySelectorAll('[data-chart-type]').forEach(select => select.addEventListener('change', () => {
            ({ trends: drawTrends, locations: drawLocations })[select.dataset.chartType](select.value);
        }));

        drawTrends('bar');
        drawPrograms();
        drawLocations('donut');
        renderYearChips();

        // Field counts use the values rendered from imported records.
        document.querySelectorAll('[data-field-view]').forEach(button => button.addEventListener('click', () => {
            const table = button.dataset.fieldView === 'table';
            document.getElementById('fieldCountCards').classList.toggle('d-none', table);
            document.getElementById('fieldCountTable').classList.toggle('d-none', !table);
            document.querySelectorAll('[data-field-view]').forEach(choice => {
                const selected = choice === button;
                choice.classList.toggle('active', selected);
                choice.setAttribute('aria-pressed', String(selected));
            });
        }));
        function downloadCsv(filename, rows) {
            const quote = value => {
                const text = String(value ?? '');
                const safe = typeof value === 'string' && /^[=+@\-\t\r]/.test(text) ? `'${text}` : text;
                return `"${safe.replace(/"/g, '""')}"`;
            };
            const csv = rows.map(row => row.map(quote).join(',')).join('\r\n');
            const url = URL.createObjectURL(new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' }));
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            link.click();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        }
        document.getElementById('exportFieldCounts').addEventListener('click', () => {
            const rows = [...document.querySelectorAll('[data-field-name]')].map(field => [field.dataset.fieldName, field.dataset.fieldValue]);
            downloadCsv('iaris-applicant-field-counts.csv', [['Field', 'Current school year'], ...rows]);
        });
        document.getElementById('exportInsights').addEventListener('click', () => {
            downloadCsv('iaris-insights.csv', [['Type', 'Title', 'Insight', 'Statistic'], ...INSIGHTS.map(insight => [insight.type, insight.title, insight.body, insight.stat])]);
        });

        // ---- "Showing: ..." — what the filters at the top are set to ----
        function currentView() {
            return {
                range: document.querySelector('input[name="range"]:checked').value,
                department: document.getElementById('filterDepartment').value,
                year: document.getElementById('filterYear').value,
            };
        }
        function updateScope() {
            const v = currentView();
            document.getElementById('scopeText').textContent = `${v.range} · ${v.department} · ${v.year}`;
        }
        document.querySelectorAll('input[name="range"], #filterDepartment, #filterYear').forEach(el => el.addEventListener('change', updateScope));
        updateScope();
    </script>
    @endif

    @if ($hasReports)
        @include('analytics.partials.reports-script')
    @endif
@endsection
