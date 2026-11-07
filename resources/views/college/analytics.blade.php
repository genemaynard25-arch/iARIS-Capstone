@extends('layouts.app')

@section('title', 'iARIS — College Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', $description)

@php
    // % change, e.g. 184 -> 214 = +16.3
    $change = fn ($old, $new) => round(($new - $old) / $old * 100, 1);
    $conversion = array_map(fn ($e, $a) => $e / $a * 100, $enrolled, $applicants);

    $stats = [
        ['label' => 'Total Applicants', 'value' => number_format($applicants[3]), 'delta' => $change($applicants[2], $applicants[3]), 'unit' => '%', 'tone' => 'primary'],
        ['label' => 'Paid Reservations', 'value' => number_format($paid['current']), 'delta' => $change($paid['previous'], $paid['current']), 'unit' => '%', 'tone' => 'info'],
        ['label' => 'Enrolled', 'value' => number_format($enrolled[3]), 'delta' => $change($enrolled[2], $enrolled[3]), 'unit' => '%', 'tone' => 'dark'],
        // Conversion changes in percentage points, not percent
        ['label' => 'Applied → Enrolled', 'value' => number_format($conversion[3], 1) . '%', 'delta' => round($conversion[3] - $conversion[2], 1), 'unit' => ' pts', 'tone' => 'warning'],
    ];

    $current = end($years);
    $previous = $years[count($years) - 2];
    $nextYear = 'AY 2026–27';
    $statusColors = ['Pending' => '#B91C1C', 'For Exam' => '#B96000', 'Paid' => '#2558D9'];
    $confidenceTones = ['High' => 'primary', 'Medium' => 'warning', 'Low' => 'danger'];
    $estimateCurrent = array_sum(array_column($estimates, 'current'));
    $estimateTotal = array_sum(array_column($estimates, 'estimate'));
@endphp

@section('content')
    {{-- id="collegeReport": "Export PDF" prints this whole page (iaris.css section 9) --}}
    <div id="collegeReport">
        @include('college.partials.program-bar', ['route' => 'college.analytics', 'export' => true])

        {{-- Stat cards --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
            @foreach ($stats as $stat)
                <div class="col">
                    <div class="card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                            <div class="fs-2 fw-bold">{{ $stat['value'] }}</div>
                            <div class="small fw-bold {{ $stat['delta'] >= 0 ? 'text-primary' : 'text-warning-emphasis' }}">
                                <i class="bi {{ $stat['delta'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                                {{ $stat['delta'] >= 0 ? '+' : '−' }}{{ abs($stat['delta']) }}{{ $stat['unit'] }} vs last AY
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4 mb-4">
            {{-- Applicants vs enrolled over the years, as a line or bar chart --}}
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Applicants vs Enrolled</h2>
                                <div class="small text-body-secondary">Showing: <b class="text-primary">{{ $scopeName }}</b> · {{ count($years) }} academic years</div>
                            </div>
                            <div class="d-flex gap-2 no-print">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Chart type">
                                    <button type="button" class="btn btn-primary" data-type="line" aria-pressed="true"><i class="bi bi-graph-up me-1"></i>Line</button>
                                    <button type="button" class="btn btn-outline-secondary" data-type="bar" aria-pressed="false"><i class="bi bi-bar-chart me-1"></i>Bar</button>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border" data-export="trendChart"><i class="bi bi-download me-1"></i>Export</button>
                            </div>
                        </div>
                        @include('chancellor.partials.legend', ['items' => ['Applicants' => '#00674F', 'Enrolled' => '#2558D9']])
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
                                <div class="small text-body-secondary">Showing: <b class="text-primary">{{ $scopeName }}</b> · {{ $current }}</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border text-nowrap flex-shrink-0 no-print" data-export="funnelChart"><i class="bi bi-download me-1"></i>Export</button>
                        </div>
                        <div class="chart-box flex-grow-1">
                            <canvas id="funnelChart" role="img" aria-label="Applicants at each stage, from applied to enrolled"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- Dean: applicants per program (this AY vs last AY). Program chair: their applicants by status. --}}
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        @if ($isChair)
                            <h2 class="fs-6 fw-bold mb-0">Applicants by Status</h2>
                            <div class="small text-body-secondary mb-3">{{ $scopeName }} · {{ $current }}</div>
                            <div class="d-flex flex-wrap align-items-center gap-4 flex-grow-1">
                                <div class="chart-box chart-box-donut flex-shrink-0 w-100">
                                    <canvas id="statusChart" role="img" aria-label="Applicants by status"></canvas>
                                </div>
                                <div class="flex-grow-1" style="min-width: 170px;">
                                    @foreach ($status as $label => $count)
                                        <div class="d-flex align-items-center gap-2 small mb-2">
                                            <span class="rounded-1" style="width: 11px; height: 11px; background: {{ $statusColors[$label] }};"></span>
                                            {{ $label }}
                                            <span class="ms-auto fw-bold">{{ number_format($count) }}</span>
                                            <span class="text-body-secondary fw-semibold" style="width: 3.2rem; text-align: right;">{{ number_format($count / array_sum($status) * 100, 1) }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div>
                                    <h2 class="fs-6 fw-bold mb-0">Applicants by Program</h2>
                                    <div class="small text-body-secondary">{{ $current }} vs {{ $previous }}{{ $selected ? ' · selected program is highlighted' : '' }}</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light border text-nowrap flex-shrink-0 no-print" data-export="programChart"><i class="bi bi-download me-1"></i>Export</button>
                            </div>
                            @include('chancellor.partials.legend', ['items' => [$previous => '#7FC4AB', $current => '#00674F']])
                            <div class="chart-box flex-grow-1">
                                <canvas id="programChart" role="img" aria-label="Applicants per program, last academic year and this academic year"></canvas>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Applied-to-enrolled conversion per year --}}
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Applied → Enrolled by Year</h2>
                                <div class="small text-body-secondary">Conversion rate · <b class="text-primary">{{ $scopeName }}</b></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light border text-nowrap flex-shrink-0 no-print" data-export="conversionChart"><i class="bi bi-download me-1"></i>Export</button>
                        </div>
                        <div class="chart-box flex-grow-1">
                            <canvas id="conversionChart" role="img" aria-label="Applied-to-enrolled conversion per academic year"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                    <canvas id="compareChart" role="img" aria-label="Chosen measure per program for the chosen academic years"></canvas>
                </div>
            </div>
        </div>

        {{-- AI insights (data-ai-content: hidden when "Show AI-written takeaways" is off in Settings) --}}
        <div data-ai-content>
            <div class="d-flex align-items-center gap-3 mt-2 mb-3">
                <span class="badge bg-info-subtle text-info-emphasis px-3 py-2"><i class="bi bi-stars me-1"></i> AI INSIGHTS</span>
                <div>
                    <h2 class="fs-5 fw-bold mb-0">What the last {{ count($years) }} years show</h2>
                    <div class="small text-body-secondary">Automatically analyzed for <b class="text-primary">{{ $scopeName }}</b>, {{ $years[0] }} to {{ $current }}</div>
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
                            <th class="ps-4 text-body-secondary fw-bold">Program</th>
                            <th class="text-body-secondary fw-bold text-end">{{ str_replace('AY ', '', $current) }}</th>
                            <th class="text-body-secondary fw-bold text-end">Estimated {{ str_replace('AY ', '', $nextYear) }}</th>
                            <th class="text-body-secondary fw-bold">Est. change</th>
                            <th class="pe-4 text-body-secondary fw-bold">Confidence</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($estimates as $row)
                            @php
                                $estimateChange = $change($row['current'], $row['estimate']);
                            @endphp
                            <tr>
                                <td class="ps-4 fw-bold">{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['current']) }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['estimate']) }}</td>
                                <td>
                                    <span class="badge {{ $estimateChange >= 0 ? 'bg-primary-subtle text-primary-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                        <i class="bi {{ $estimateChange >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} me-1"></i>{{ $estimateChange >= 0 ? '+' : '−' }}{{ abs($estimateChange) }}%
                                    </span>
                                </td>
                                <td class="pe-4"><span class="badge bg-{{ $confidenceTones[$row['confidence']] }}-subtle text-{{ $confidenceTones[$row['confidence']] }}-emphasis">{{ $row['confidence'] }}</span></td>
                            </tr>
                        @endforeach
                        {{-- Total row only when there is more than one program --}}
                        @if (count($estimates) > 1)
                            @php
                                $totalChange = $change($estimateCurrent, $estimateTotal);
                            @endphp
                            <tr class="table-light fw-bold">
                                <td class="ps-4">All CITE programs</td>
                                <td class="text-end">{{ number_format($estimateCurrent) }}</td>
                                <td class="text-end">{{ number_format($estimateTotal) }}</td>
                                <td>
                                    <span class="badge {{ $totalChange >= 0 ? 'bg-primary-subtle text-primary-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                        <i class="bi {{ $totalChange >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} me-1"></i>{{ $totalChange >= 0 ? '+' : '−' }}{{ abs($totalChange) }}%
                                    </span>
                                </td>
                                <td class="pe-4"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="p-4 pt-3">
                <div class="small text-body-secondary bg-body-tertiary rounded-3 p-2 d-flex gap-2">
                    <i class="bi bi-info-circle text-warning-emphasis"></i>
                    <span>These insights and estimates are generated automatically from historical iARIS data. They describe past patterns and are not guarantees. All decisions remain with you and university leadership.</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const YEARS = @json($years);
        const APPLICANTS = @json($applicants);
        const ENROLLED = @json($enrolled);
        const FUNNEL = @json($funnel);
        const PROGRAMS = @json($programs);           // every CITE program (for the dean's per-program chart)
        const CHART_PROGRAMS = @json($chartPrograms); // the programs in the year comparison
        const SELECTED = @json($selected);
        const LAST = YEARS.length - 1;

        // Colours: applicants green, enrolled blue; last AY light green, this AY dark green.
        // Compared years go light -> dark green (older -> newer).
        const GREEN = '#00674F', BLUE = '#2558D9', LIGHT_GREEN = '#7FC4AB', PALE_GREEN = '#A8D5C5';
        const YEAR_GREENS = ['#7FC4AB', '#2E8A6E', '#003D2E'];
        const GRID = '#E2ECEA';

        let trendType = 'line';
        const charts = {};
        const fmt = n => Math.round(n).toLocaleString('en-US');

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

        // ---- Applicants vs enrolled (line or bar) ----
        function renderTrend() {
            const line = trendType === 'line';
            make('trendChart', {
                type: trendType,
                data: {
                    labels: YEARS,
                    datasets: [['Applicants', APPLICANTS, GREEN], ['Enrolled', ENROLLED, BLUE]].map(([label, values, color]) => ({
                        label, data: values, borderColor: color, backgroundColor: color,
                        borderWidth: line ? 3 : 0, borderRadius: 4, pointRadius: 5, pointBackgroundColor: '#fff', pointBorderWidth: 3, tension: 0.3,
                    })),
                },
                options: options(),
            });
        }

        // ---- Funnel: one bar per stage; the label shows the count and % of applicants ----
        const applied = Object.values(FUNNEL)[0];
        make('funnelChart', {
            type: 'bar',
            data: {
                labels: Object.entries(FUNNEL).map(([stage, count]) => [stage, `${fmt(count)} · ${(count / applied * 100).toFixed(1)}%`]),
                datasets: [{ label: 'Applicants', data: Object.values(FUNNEL), backgroundColor: GREEN, borderRadius: 4, barPercentage: 0.7 }],
            },
            options: options({
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { title: items => items[0].label.split(',')[0], label: c => `${fmt(c.parsed.x)} · ${(c.parsed.x / applied * 100).toFixed(1)}% of applicants` } } },
            }),
        });

        @if ($isChair)
            // ---- Applicants by status (doughnut) ----
            make('statusChart', {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($status)),
                    datasets: [{ data: @json(array_values($status)), backgroundColor: @json(array_values($statusColors)), borderColor: '#fff', borderWidth: 2 }],
                },
                options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: false } } },
            });
        @else
            // ---- Applicants per program, last AY vs this AY. The selected program stays bright; the others fade. ----
            const codes = Object.keys(PROGRAMS);
            const fade = (color, code) => (SELECTED && SELECTED !== code) ? color + '4D' : color; // 4D = 30% opacity
            make('programChart', {
                type: 'bar',
                data: {
                    labels: codes,
                    datasets: [[YEARS[LAST - 1], LAST - 1, LIGHT_GREEN], [YEARS[LAST], LAST, GREEN]].map(([label, i, color]) => ({
                        label, data: codes.map(c => PROGRAMS[c].applicants[i]), backgroundColor: codes.map(c => fade(color, c)), borderRadius: 4, maxBarThickness: 40,
                    })),
                },
                options: options({ plugins: { legend: { display: false }, tooltip: { callbacks: { title: items => PROGRAMS[items[0].label].name, label: c => `${c.dataset.label}: ${fmt(c.parsed.y)}` } } } }),
            });
        @endif

        // ---- Applied -> enrolled per year (this year dark, earlier years light) ----
        const conversion = YEARS.map((_, i) => ENROLLED[i] / APPLICANTS[i] * 100);
        make('conversionChart', {
            type: 'bar',
            data: { labels: YEARS, datasets: [{ label: 'Applied → Enrolled', data: conversion, backgroundColor: YEARS.map((_, i) => i === LAST ? GREEN : PALE_GREEN), borderRadius: 6, maxBarThickness: 64 }] },
            options: options({
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => `Applied → Enrolled: ${c.parsed.y.toFixed(1)}%` } } },
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: GRID }, ticks: { callback: v => v + '%' } } },
            }),
        });

        // ---- Year comparison: the chosen measure per program, one bar per chosen year ----
        function renderCompare() {
            const picked = [...document.querySelectorAll('#yearPicks input:checked')].map(i => Number(i.value)).sort();
            const measure = document.getElementById('measure').value;
            const codes = Object.keys(CHART_PROGRAMS);
            // Newest picked year always gets the darkest green
            const colors = YEAR_GREENS.slice(YEAR_GREENS.length - picked.length);
            // One program (program chair): one bar per year, side by side. Several programs: bars grouped by program.
            const data = codes.length === 1
                ? { labels: picked.map(y => YEARS[y]), datasets: [{ label: codes[0], data: picked.map(y => CHART_PROGRAMS[codes[0]][measure][y]), backgroundColor: colors, borderRadius: 4, maxBarThickness: 64 }] }
                : { labels: codes, datasets: picked.map((y, j) => ({ label: YEARS[y], data: codes.map(c => CHART_PROGRAMS[c][measure][y]), backgroundColor: colors[j], borderRadius: 4, maxBarThickness: 48 })) };
            make('compareChart', { type: 'bar', data, options: options() });
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

        // ---- Controls ----
        // Line / Bar: the clicked one turns green
        const typeButtons = document.querySelectorAll('[data-type]');
        typeButtons.forEach(b => b.addEventListener('click', () => {
            trendType = b.dataset.type;
            typeButtons.forEach(x => {
                x.classList.toggle('btn-primary', x === b);
                x.classList.toggle('btn-outline-secondary', x !== b);
                x.setAttribute('aria-pressed', x === b);
            });
            renderTrend();
        }));

        // Export a chart as a PNG file
        document.querySelectorAll('[data-export]').forEach(b => b.addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = charts[b.dataset.export].toBase64Image('image/png', 1);
            link.download = `iaris-cite-${b.dataset.export.replace('Chart', '')}-chart.png`;
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

        // Start with the chart type chosen in Settings → Display, if any
        if (window.IARIS_SETTINGS?.display?.chart === 'bar') document.querySelector('[data-type="bar"]').click();

        renderTrend();
        renderCompare();
    </script>
@endsection
