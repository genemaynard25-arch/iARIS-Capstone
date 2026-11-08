@extends('layouts.app')

@section('title', 'iARIS — Executive Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'Institution-wide admissions and enrollment analysis, with AI-generated insights')

@php
    // Next AY estimate: the average growth of the last two years, made 20% smaller
    // (so one good year doesn't make the estimate too hopeful). Worked out here so it
    // always matches the numbers above it.
    $estimates = collect($units)->map(function ($unit, $name) {
        [$a, $b, $c] = array_slice($unit['applicants'], -3);
        $growth = (($b - $a) / $a + ($c - $b) / $b) / 2 * 0.8;

        return ['name' => $name, 'current' => $c, 'estimate' => (int) round($c * (1 + $growth)), 'confidence' => $unit['confidence']];
    })->values();
    $currentTotal = $estimates->sum('current');
    $estimateTotal = $estimates->sum('estimate');
    $nextYear = 'AY 2026–27';
    $confidenceTones = ['High' => 'primary', 'Medium' => 'warning', 'Low' => 'danger'];
@endphp

@section('content')
    {{-- Same id as the dashboard, so "Export PDF" prints this page the same way (iaris.css section 9) --}}
    <div id="executiveReport">
        {{-- Toolbar: read-only notice, academic year, export --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2">
                <i class="bi bi-eye me-1"></i> Read-only view
            </span>
            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                <i class="bi bi-calendar3 me-1"></i> AY {{ $academicYear }}
            </span>
            <button type="button" class="btn btn-primary fw-semibold ms-sm-auto no-print" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
        </div>

        {{-- Unit filter: changes the stat cards, trend chart and funnel --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body d-flex flex-wrap align-items-center gap-2 px-4 py-3" id="unitPills">
                <span class="small fw-bold text-uppercase tracking-wide text-body-secondary me-1">Unit</span>
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-primary" data-unit="" aria-pressed="true">All Units</button>
                @foreach ($units as $name => $unit)
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-outline-secondary" data-unit="{{ $name }}" aria-pressed="false">{{ $name }}</button>
                @endforeach
            </div>
        </div>

        {{-- Four tabs, as in the mockup. "Export PDF" prints all of them (iaris.css section 9). --}}
        <ul class="nav nav-pills gap-2 mb-4 no-print" role="tablist" id="analyticsTabs">
            <li class="nav-item" role="presentation"><button class="nav-link active fw-semibold" data-bs-toggle="pill" data-bs-target="#tabOverview" type="button" role="tab" data-hash="overview"><i class="bi bi-graph-up me-1"></i> Overview <span class="small fw-normal opacity-75 d-none d-md-inline">· trend, funnel, units</span></button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabCompare" type="button" role="tab" data-hash="compare"><i class="bi bi-calendar-range me-1"></i> Compare years</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabInsights" type="button" role="tab" data-hash="insights"><i class="bi bi-stars me-1"></i> AI insights <span class="badge rounded-pill bg-body-secondary text-body-secondary ms-1">{{ count($insights) }}</span></button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabEstimate" type="button" role="tab" data-hash="estimate"><i class="bi bi-magic me-1"></i> Next AY estimate</button></li>
        </ul>
        <div class="tab-content">
        <div class="tab-pane fade show active" id="tabOverview" role="tabpanel">
        {{-- Stat cards (numbers filled in by the script) --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
            @foreach ([['applicants', 'Total Applicants', 'primary'], ['paid', 'Paid Reservations', 'info'], ['enrolled', 'Enrolled', 'dark'], ['conversion', 'Applied → Enrolled', 'warning']] as [$key, $label, $tone])
                <div class="col">
                    <div class="card border-0 border-top border-3 border-{{ $tone }} shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                            <div class="fs-2 fw-bold" data-stat="{{ $key }}"></div>
                            <div class="small fw-bold" data-delta="{{ $key }}"></div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4 mb-4">
            {{-- Applicants vs enrolled over the years --}}
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Applicants vs Enrolled</h2>
                                <div class="small text-body-secondary">Showing: <b class="text-primary" data-scope>All Units</b> · {{ count($years) }} academic years</div>
                            </div>
                            <div class="d-flex gap-2 no-print">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Chart type">
                                    <button type="button" class="btn btn-primary" data-type="line" aria-pressed="true"><i class="bi bi-graph-up me-1"></i>Line</button>
                                    <button type="button" class="btn btn-outline-secondary" data-type="bar" aria-pressed="false"><i class="bi bi-bar-chart me-1"></i>Bar</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border" data-export="trendChart"><i class="bi bi-download me-1"></i>Export</button>
                            </div>
                        </div>
                        @include('executive.partials.legend', ['items' => ['Applicants' => '#00674F', 'Enrolled' => '#2558D9']])
                        <div class="chart-box flex-grow-1">
                            <canvas id="trendChart" role="img" aria-label="Applicants and enrolled per academic year"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Conversion funnel for this AY --}}
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Conversion Funnel</h2>
                                <div class="small text-body-secondary">Showing: <b class="text-primary" data-scope>All Units</b> · {{ end($years) }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border no-print" data-export="funnelChart"><i class="bi bi-download me-1"></i>Export</button>
                        </div>
                        <div class="chart-box flex-grow-1">
                            <canvas id="funnelChart" role="img" aria-label="Applicants at each stage, from applied to enrolled"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- Applicants per unit, last AY vs this AY --}}
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Applicants by Unit</h2>
                                <div class="small text-body-secondary">{{ $years[count($years) - 1] }} vs {{ $years[count($years) - 2] }} · selected unit is highlighted</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border no-print" data-export="unitChart"><i class="bi bi-download me-1"></i>Export</button>
                        </div>
                        @include('executive.partials.legend', ['items' => [$years[count($years) - 2] => '#7FC4AB', $years[count($years) - 1] => '#00674F']])
                        <div class="chart-box">
                            <canvas id="unitChart" role="img" aria-label="Applicants per unit, last academic year and this academic year"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- College applicants per college --}}
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">College Applicants by College</h2>
                                <div class="small text-body-secondary">{{ number_format(array_sum($colleges)) }} applicants · {{ end($years) }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border no-print" data-export="collegeChart"><i class="bi bi-download me-1"></i>Export</button>
                        </div>
                        <div class="chart-box">
                            <canvas id="collegeChart" role="img" aria-label="College applicants per college"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>
        <div class="tab-pane fade" id="tabCompare" role="tabpanel">
        {{-- Compare up to 3 academic years --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-0">Academic Year Comparison</h2>
                <div class="small text-body-secondary mb-3">Pick up to 3 academic years and a measure, then generate</div>

                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
                    <span class="small fw-bold text-uppercase tracking-wide text-body-secondary me-1">Years</span>
                    <div class="d-flex flex-wrap gap-2" id="yearPicks">
                        @foreach ($years as $i => $year)
                            {{-- The last two years start picked --}}
                            <input type="checkbox" class="btn-check" id="year{{ $i }}" value="{{ $i }}" autocomplete="off" @checked($i >= count($years) - 2)>
                            <label class="btn btn-sm btn-outline-primary fw-semibold" for="year{{ $i }}">{{ $year }}</label>
                        @endforeach
                    </div>
                    <select class="form-select form-select-sm w-auto" id="measure" aria-label="Measure">
                        <option value="applicants">Applicants</option>
                        <option value="enrolled">Enrolled</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-primary fw-semibold" id="generate"><i class="bi bi-lightning-charge me-1"></i>Generate</button>
                    <span class="small text-danger-emphasis" id="pickNote" role="status"></span>
                </div>

                <div class="d-flex flex-wrap gap-3 small text-body-secondary mb-2" id="compareLegend"></div>
                <div class="chart-box">
                    <canvas id="compareChart" role="img" aria-label="Chosen measure per unit for the chosen academic years"></canvas>
                </div>
            </div>
        </div>

        </div>
        <div class="tab-pane fade" id="tabInsights" role="tabpanel">
        {{-- AI insights (data-ai-content: hidden when "Show AI-written takeaways" is off in Settings) --}}
        <div data-ai-content>
        <div class="d-flex align-items-center gap-3 mt-2 mb-3">
            <span class="badge bg-info-subtle text-info-emphasis px-3 py-2"><i class="bi bi-stars me-1"></i> AI INSIGHTS</span>
            <div>
                <h2 class="fs-5 fw-bold mb-0">What the last {{ count($years) }} years show</h2>
                <div class="small text-body-secondary">Automatically analyzed from {{ $years[0] }} to {{ end($years) }} admissions data</div>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xxl-3 g-3 mb-4">
            @foreach ($insights as $insight)
                <div class="col">
                    <div class="card border-0 border-start border-4 border-{{ $insight['tone'] }} shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rounded-2 bg-{{ $insight['tone'] }}-subtle text-{{ $insight['tone'] }}-emphasis d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                    <i class="bi {{ $insight['icon'] }} small"></i>
                                </span>
                                <span class="small fw-bold text-uppercase tracking-wide text-body-secondary">{{ $insight['type'] }}</span>
                            </div>
                            <div class="fw-bold mb-2">{{ $insight['title'] }}</div>
                            <div class="fs-2 fw-bold lh-1 text-{{ $insight['tone'] }}-emphasis">{{ $insight['stat'] }}</div>
                            <div class="small fw-semibold text-body-secondary mt-1 mb-2">{{ $insight['statLabel'] }}</div>
                            <div class="small">{{ $insight['body'] }}</div>
                            <div class="d-flex flex-wrap gap-1 mt-3">
                                @foreach ($insight['tags'] as $tag)
                                    <span class="badge bg-body-secondary text-body-secondary">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        </div>

        </div>
        <div class="tab-pane fade" id="tabEstimate" role="tabpanel">
        {{-- Next AY estimate --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4 pb-2 d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h2 class="fs-6 fw-bold mb-0">Next AY Estimate · {{ $nextYear }} Applicants</h2>
                    <div class="small text-body-secondary">Average of the last two years' growth, reduced by 20%</div>
                </div>
                <span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-stars me-1"></i> ESTIMATE</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-nowrap">
                            <th class="ps-4 text-body-secondary fw-bold">Unit</th>
                            <th class="text-body-secondary fw-bold text-end">{{ str_replace('AY ', '', end($years)) }}</th>
                            <th class="text-body-secondary fw-bold text-end">Estimated {{ str_replace('AY ', '', $nextYear) }}</th>
                            <th class="text-body-secondary fw-bold">Est. change</th>
                            <th class="pe-4 text-body-secondary fw-bold">Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estimates as $row)
                            @php
                                $estimateChange = round(($row['estimate'] - $row['current']) / $row['current'] * 100, 1);
                            @endphp
                            <tr>
                                <td class="ps-4 fw-bold">{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['current']) }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['estimate']) }}</td>
                                <td><span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-graph-up-arrow me-1"></i>+{{ $estimateChange }}%</span></td>
                                <td class="pe-4"><span class="badge bg-{{ $confidenceTones[$row['confidence']] }}-subtle text-{{ $confidenceTones[$row['confidence']] }}-emphasis">{{ $row['confidence'] }}</span></td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <td class="ps-4">All units</td>
                            <td class="text-end">{{ number_format($currentTotal) }}</td>
                            <td class="text-end">{{ number_format($estimateTotal) }}</td>
                            <td><span class="badge bg-primary-subtle text-primary-emphasis"><i class="bi bi-graph-up-arrow me-1"></i>+{{ round(($estimateTotal - $currentTotal) / $currentTotal * 100, 1) }}%</span></td>
                            <td class="pe-4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-4 pt-3">
                <div class="small text-body-secondary bg-body-tertiary rounded-3 p-2 d-flex gap-2">
                    <i class="bi bi-info-circle text-warning-emphasis"></i>
                    <span>These insights and estimates are generated automatically from historical iARIS data. They describe past patterns and are not guarantees. All decisions remain with university leadership.</span>
                </div>
            </div>
        </div>
        </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const YEARS = @json($years);
        const UNITS = @json($units);
        const COLLEGES = @json($colleges);
        const NAMES = Object.keys(UNITS);
        const LAST = YEARS.length - 1;

        // Colours: applicants green, enrolled blue; last AY light green, this AY dark green.
        // Compared years go light -> dark green (older -> newer), so the order reads at a glance.
        const GREEN = '#00674F', BLUE = '#2558D9', LIGHT_GREEN = '#7FC4AB';
        const YEAR_GREENS = ['#7FC4AB', '#2E8A6E', '#003D2E'];
        const GRID = '#E2ECEA';

        let unit = '';          // '' = All Units
        let trendType = 'line';
        const charts = {};

        const fmt = n => Math.round(n).toLocaleString('en-US');
        const sign = n => (n >= 0 ? '+' : '−') + Math.abs(n).toFixed(1);
        const sum = (key, i) => NAMES.reduce((total, name) => total + (i === undefined ? UNITS[name][key] : UNITS[name][key][i]), 0);

        // The numbers for one unit, or every unit added up
        function data() {
            if (unit) return UNITS[unit];
            const series = key => YEARS.map((_, i) => sum(key, i));
            return { applicants: series('applicants'), enrolled: series('enrolled'),
                took: sum('took'), passed: sum('passed'), paid: sum('paid'), paidPrevious: sum('paidPrevious') };
        }

        // Shared chart look: thin grid, numbers with commas, white background (for PNG export)
        const whiteBackground = {
            id: 'whiteBackground',
            beforeDraw: chart => { const c = chart.ctx; c.save(); c.fillStyle = '#fff'; c.fillRect(0, 0, chart.width, chart.height); c.restore(); },
        };
        function options(extra = {}) {
            const horizontal = extra.indexAxis === 'y';
            const valueAxis = { beginAtZero: true, grid: { color: GRID }, ticks: { callback: v => fmt(v) } };
            const labelAxis = { grid: { display: false } };
            return {
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => `${c.dataset.label}: ${fmt(c.parsed[horizontal ? 'x' : 'y'])}` } } },
                scales: horizontal ? { x: valueAxis, y: labelAxis } : { x: labelAxis, y: valueAxis },
                ...extra,
            };
        }
        function make(id, config) {
            charts[id]?.destroy();
            charts[id] = new Chart(document.getElementById(id), { plugins: [whiteBackground], ...config });
        }

        // ---- Stat cards ----
        function renderStats() {
            const d = data();
            const change = (a, b) => (b - a) / a * 100;
            const conversion = i => d.enrolled[i] / d.applicants[i] * 100;
            const cards = {
                applicants: [fmt(d.applicants[LAST]), change(d.applicants[LAST - 1], d.applicants[LAST]), '%'],
                paid: [fmt(d.paid), change(d.paidPrevious, d.paid), '%'],
                enrolled: [fmt(d.enrolled[LAST]), change(d.enrolled[LAST - 1], d.enrolled[LAST]), '%'],
                conversion: [conversion(LAST).toFixed(1) + '%', conversion(LAST) - conversion(LAST - 1), ' pts'],
            };
            Object.entries(cards).forEach(([key, [value, delta, unitText]]) => {
                document.querySelector(`[data-stat="${key}"]`).textContent = value;
                const el = document.querySelector(`[data-delta="${key}"]`);
                el.className = `small fw-bold ${delta >= 0 ? 'text-primary' : 'text-warning-emphasis'}`;
                el.innerHTML = `<i class="bi ${delta >= 0 ? 'bi-arrow-up' : 'bi-arrow-down'}"></i> `;
                el.append(`${sign(delta)}${unitText} vs last AY`);
            });
        }

        // ---- Charts ----
        function renderTrend() {
            const d = data();
            const line = trendType === 'line';
            make('trendChart', {
                type: trendType,
                data: {
                    labels: YEARS,
                    datasets: [['Applicants', d.applicants, GREEN], ['Enrolled', d.enrolled, BLUE]].map(([label, values, color]) => ({
                        label, data: values, borderColor: color, backgroundColor: color,
                        borderWidth: line ? 3 : 0, borderRadius: 4, pointRadius: 5, pointBackgroundColor: '#fff', pointBorderWidth: 3, tension: 0.3,
                    })),
                },
                options: options(),
            });
        }

        function renderFunnel() {
            const d = data();
            const stages = [['Applied', d.applicants[LAST]], ['Took exam', d.took], ['Passed', d.passed], ['Paid', d.paid], ['Enrolled', d.enrolled[LAST]]];
            const applied = stages[0][1];
            make('funnelChart', {
                type: 'bar',
                // Two-line labels: the stage, then its count and % of applicants (so they show in the PDF too)
                data: { labels: stages.map(([name, count]) => [name, `${fmt(count)} · ${(count / applied * 100).toFixed(1)}%`]),
                    datasets: [{ label: 'Applicants', data: stages.map(s => s[1]), backgroundColor: GREEN, borderRadius: 4, barPercentage: 0.7 }] },
                options: options({
                    indexAxis: 'y',
                    plugins: { legend: { display: false }, tooltip: { callbacks: { title: items => items[0].label.split(',')[0], label: c => `${fmt(c.parsed.x)} · ${(c.parsed.x / applied * 100).toFixed(1)}% of applicants` } } },
                }),
            });
        }

        function renderUnits() {
            // Dim the units that aren't selected
            const fade = (color, i) => !unit || NAMES[i] === unit ? color : color + '4D';
            make('unitChart', {
                type: 'bar',
                data: {
                    labels: NAMES.map(n => UNITS[n].short),
                    datasets: [[YEARS[LAST - 1], LAST - 1, LIGHT_GREEN], [YEARS[LAST], LAST, GREEN]].map(([label, i, color]) => ({
                        label, data: NAMES.map(n => UNITS[n].applicants[i]),
                        backgroundColor: NAMES.map((_, k) => fade(color, k)), borderRadius: 4,
                    })),
                },
                options: options(),
            });
        }

        function renderColleges() {
            make('collegeChart', {
                type: 'bar',
                data: { labels: Object.keys(COLLEGES), datasets: [{ label: 'Applicants', data: Object.values(COLLEGES), backgroundColor: GREEN, borderRadius: 4, barPercentage: 0.7 }] },
                options: options({ indexAxis: 'y' }),
            });
        }

        function renderCompare() {
            const picked = [...document.querySelectorAll('#yearPicks input:checked')].map(i => Number(i.value)).sort();
            const measure = document.getElementById('measure').value;
            // Newest picked year always gets the darkest green
            const colors = YEAR_GREENS.slice(YEAR_GREENS.length - picked.length);
            make('compareChart', {
                type: 'bar',
                data: {
                    labels: NAMES.map(n => UNITS[n].short),
                    datasets: picked.map((y, j) => ({ label: YEARS[y], data: NAMES.map(n => UNITS[n][measure][y]), backgroundColor: colors[j], borderRadius: 4 })),
                },
                options: options(),
            });
            const legend = document.getElementById('compareLegend');
            legend.replaceChildren(...picked.map((y, j) => {
                const span = document.createElement('span');
                span.className = 'd-flex align-items-center gap-1';
                span.innerHTML = `<span class="rounded-1" style="width: 10px; height: 10px; background: ${colors[j]};"></span>`;
                span.append(YEARS[y]);
                return span;
            }));
            const label = document.createElement('span');
            label.className = 'ms-auto';
            label.innerHTML = 'Measure: <b class="text-body"></b>';
            label.querySelector('b').textContent = measure === 'applicants' ? 'Applicants' : 'Enrolled';
            legend.appendChild(label);
        }

        function renderFiltered() {
            renderStats();
            renderTrend();
            renderFunnel();
            renderUnits();
            document.querySelectorAll('[data-scope]').forEach(el => el.textContent = unit || 'All Units');
        }

        // ---- Controls ----
        // Pill groups: the clicked one turns green, the rest outlined
        function choose(buttons, active) {
            buttons.forEach(b => {
                b.classList.toggle('btn-primary', b === active);
                b.classList.toggle('btn-outline-secondary', b !== active);
                b.setAttribute('aria-pressed', b === active);
            });
        }

        const unitButtons = document.querySelectorAll('#unitPills [data-unit]');
        unitButtons.forEach(b => b.addEventListener('click', () => { unit = b.dataset.unit; choose(unitButtons, b); renderFiltered(); }));

        const typeButtons = document.querySelectorAll('[data-type]');
        typeButtons.forEach(b => b.addEventListener('click', () => { trendType = b.dataset.type; choose(typeButtons, b); renderTrend(); }));

        // Export a chart as a PNG file
        document.querySelectorAll('[data-export]').forEach(b => b.addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = charts[b.dataset.export].toBase64Image('image/png', 1);
            link.download = `iaris-${b.dataset.export.replace('Chart', '')}-chart.png`;
            link.click();
        }));

        // Years: at least 1, at most 3
        const pickNote = document.getElementById('pickNote');
        document.querySelectorAll('#yearPicks input').forEach(box => box.addEventListener('change', () => {
            const count = document.querySelectorAll('#yearPicks input:checked').length;
            pickNote.textContent = '';
            if (count > 3) { box.checked = false; pickNote.textContent = 'You can compare up to 3 years.'; }
            if (count < 1) { box.checked = true; pickNote.textContent = 'Keep at least one year selected.'; }
        }));
        document.getElementById('generate').addEventListener('click', () => { pickNote.textContent = ''; renderCompare(); });

        // Start with the unit and chart type chosen in Settings → Display
        const display = window.IARIS_SETTINGS?.display || {};
        if (display.unit in UNITS) { unit = display.unit; choose(unitButtons, document.querySelector(`#unitPills [data-unit="${unit}"]`)); }
        if (display.chart === 'bar') { trendType = 'bar'; choose(typeButtons, document.querySelector('[data-type="bar"]')); }

        renderFiltered();
        renderColleges();
        renderCompare();

        // Tabs follow the address (#overview, #compare, #insights, #estimate); #unit=College picks that unit
        document.querySelectorAll('#analyticsTabs [data-hash]').forEach(b => b.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + b.dataset.hash)));
        (() => {
            const hash = decodeURIComponent(location.hash.slice(1));
            if (hash.startsWith('unit=')) document.querySelector(`#unitPills [data-unit="${CSS.escape(hash.slice(5))}"]`)?.click();
            const tab = document.querySelector(`#analyticsTabs [data-hash="${hash}"]`);
            if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
        })();
    </script>
@endsection
