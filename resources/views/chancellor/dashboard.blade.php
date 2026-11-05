@extends('layouts.app')

@section('title', 'iARIS — Chancellor Dashboard')
@section('page-title', 'Institutional Enrollment Overview')
@section('page-subtitle', 'University-wide admissions and enrollment trends, with year-over-year change')

@php
    // This year and last year (the last two entries in $years)
    $current = $years[count($years) - 1];
    $previous = $years[count($years) - 2];

    // % change, e.g. 3016 -> 3294 = +9.2
    $change = fn ($old, $new) => round(($new - $old) / $old * 100, 1);
    $conversion = fn ($year) => $year['enrolled'] / $year['applicants'] * 100;

    $stats = [
        ['label' => 'Total Applicants', 'value' => number_format($current['applicants']), 'delta' => $change($previous['applicants'], $current['applicants']), 'unit' => '%', 'tone' => 'primary'],
        ['label' => 'Paid Reservations', 'value' => number_format($current['paid']), 'delta' => $change($previous['paid'], $current['paid']), 'unit' => '%', 'tone' => 'info'],
        ['label' => 'Enrolled', 'value' => number_format($current['enrolled']), 'delta' => $change($previous['enrolled'], $current['enrolled']), 'unit' => '%', 'tone' => 'dark'],
        // Conversion changes in percentage points, not percent
        ['label' => 'Applied → Enrolled', 'value' => number_format($conversion($current), 1) . '%', 'delta' => round($conversion($current) - $conversion($previous), 1), 'unit' => ' pts', 'tone' => 'warning'],
    ];

    $unitTotal = array_sum(array_column($units, 'current'));
    $unitPreviousTotal = array_sum(array_column($units, 'previous'));
    $collegeTotal = array_sum($colleges);
    $applied = reset($funnel);
    $newReports = collect($reports)->where('new', true)->count();

    // Chart colours: applicants green, enrolled blue (two different things)
    $chartColors = ['applicants' => '#00674F', 'enrolled' => '#2558D9'];
@endphp

@section('content')
    <div id="chancellorReport">
        {{-- Toolbar: read-only notice, academic year, export --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2">
                <i class="bi bi-eye me-1"></i> Read-only view
            </span>
            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                <i class="bi bi-calendar3 me-1"></i> AY {{ $academicYear }}
            </span>
            {{-- Opens the browser's print window; choose "Save as PDF" there --}}
            <button type="button" class="btn btn-primary fw-semibold ms-sm-auto no-print" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
        </div>

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
                                {{ $stat['delta'] >= 0 ? '+' : '' }}{{ $stat['delta'] }}{{ $stat['unit'] }} vs last AY
                            </div>
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
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Applicants vs Enrolled — {{ count($years) }} Academic Years</h2>
                                <div class="small text-body-secondary">All units combined</div>
                            </div>
                            <div class="d-flex gap-3 small text-body-secondary">
                                @foreach ($chartColors as $series => $color)
                                    <span class="d-flex align-items-center gap-1 text-capitalize">
                                        <span class="rounded-1" style="width: 10px; height: 10px; background: {{ $color }};"></span> {{ $series }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        {{-- flex-grow-1: the chart fills the card, as tall as the Key Takeaways next to it --}}
                        <div class="chart-box flex-grow-1">
                            <canvas id="trendChart" role="img" aria-label="Applicants and enrolled per academic year"></canvas>
                        </div>
                        {{-- The same numbers for screen readers --}}
                        <table class="visually-hidden">
                            <caption>Applicants and enrolled per academic year</caption>
                            <tr><th>Year</th><th>Applicants</th><th>Enrolled</th></tr>
                            @foreach ($years as $year)
                                <tr><td>{{ $year['label'] }}</td><td>{{ $year['applicants'] }}</td><td>{{ $year['enrolled'] }}</td></tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>

            {{-- Key takeaways (AI) --}}
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Key Takeaways</h2>
                                <div class="small text-body-secondary">Auto-summary of the admissions data</div>
                            </div>
                            <span class="badge bg-info-subtle text-info-emphasis"><i class="bi bi-stars me-1"></i> AI</span>
                        </div>
                        @foreach ($takeaways as $takeaway)
                            <div class="d-flex gap-3 py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                                <div class="icon-circle rounded-3 bg-{{ $takeaway['tone'] }}-subtle text-{{ $takeaway['tone'] }}-emphasis d-flex align-items-center justify-content-center">
                                    <i class="bi {{ $takeaway['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small mb-1">{{ $takeaway['title'] }}</div>
                                    <div class="small text-body-secondary">{{ $takeaway['body'] }}</div>
                                </div>
                            </div>
                        @endforeach
                        <div class="small text-body-secondary bg-body-tertiary rounded-3 p-2 mt-auto d-flex gap-2">
                            <i class="bi bi-info-circle text-warning-emphasis"></i>
                            <span>Generated from iARIS admissions data. These describe what happened; they are not forecasts. Decisions remain with university leadership.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- Applicants by unit, this AY vs last AY --}}
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 pb-2">
                        <h2 class="fs-6 fw-bold mb-0">Applicants by Unit</h2>
                        <div class="small text-body-secondary">{{ $current['label'] }} vs {{ $previous['label'] }}</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-uppercase text-nowrap">
                                    <th class="ps-4 text-body-secondary fw-bold">Unit</th>
                                    <th class="text-body-secondary fw-bold text-end">{{ str_replace('AY ', '', $previous['label']) }}</th>
                                    <th class="text-body-secondary fw-bold text-end">{{ str_replace('AY ', '', $current['label']) }}</th>
                                    <th class="text-body-secondary fw-bold">Change</th>
                                    <th class="pe-4 text-body-secondary fw-bold">Share of total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($units as $unit)
                                    @php
                                        $unitChange = $change($unit['previous'], $unit['current']);
                                        $share = round($unit['current'] / $unitTotal * 100, 1);
                                    @endphp
                                    <tr>
                                        <td class="ps-4 fw-bold">{{ $unit['name'] }}</td>
                                        <td class="text-end text-body-secondary">{{ number_format($unit['previous']) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($unit['current']) }}</td>
                                        <td>
                                            <span class="badge {{ $unitChange >= 0 ? 'bg-primary-subtle text-primary-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                                <i class="bi {{ $unitChange >= 0 ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} me-1"></i>{{ $unitChange >= 0 ? '+' : '' }}{{ $unitChange }}%
                                            </span>
                                        </td>
                                        <td class="pe-4" style="min-width: 150px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;" role="progressbar" aria-label="{{ $unit['name'] }} share" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar" style="width: {{ $share }}%; background-color: #00795A;"></div>
                                                </div>
                                                <span class="small fw-bold text-end" style="width: 3.2rem;">{{ $share }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-light fw-bold">
                                    <td class="ps-4">All units</td>
                                    <td class="text-end text-body-secondary">{{ number_format($unitPreviousTotal) }}</td>
                                    <td class="text-end">{{ number_format($unitTotal) }}</td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary-emphasis">
                                            <i class="bi bi-graph-up-arrow me-1"></i>{{ $change($unitPreviousTotal, $unitTotal) >= 0 ? '+' : '' }}{{ $change($unitPreviousTotal, $unitTotal) }}%
                                        </span>
                                    </td>
                                    <td class="pe-4 small">100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Conversion funnel: each stage as a share of everyone who applied --}}
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="fs-6 fw-bold mb-0">Conversion Funnel</h2>
                        <div class="small text-body-secondary mb-3">{{ $current['label'] }} · % of applicants</div>
                        <div class="d-flex flex-column gap-3">
                            @foreach ($funnel as $stage => $count)
                                @php
                                    $percent = round($count / $applied * 100, 1);
                                @endphp
                                <div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="fw-semibold">{{ $stage }}</span>
                                        <span><span class="fw-bold">{{ number_format($count) }}</span> <span class="text-body-secondary">· {{ number_format($percent, 1) }}%</span></span>
                                    </div>
                                    <div class="progress" style="height: 10px;" role="progressbar" aria-label="{{ $stage }}: {{ $percent }}% of applicants" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar" style="width: {{ $percent }}%; background-color: #00795A;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- College applicants per college --}}
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="fs-6 fw-bold mb-0">College Applicants by College</h2>
                        <div class="small text-body-secondary mb-3">{{ number_format($collegeTotal) }} applicants in {{ $current['label'] }}</div>
                        <div class="d-flex flex-column gap-3">
                            @foreach ($colleges as $college => $count)
                                @php
                                    // Longest bar = the biggest college
                                    $width = round($count / max($colleges) * 100, 1);
                                @endphp
                                <div class="d-flex align-items-center gap-3">
                                    <span class="small fw-bold" style="width: 4rem;">{{ $college }}</span>
                                    <div class="progress flex-grow-1" style="height: 10px;" role="progressbar" aria-label="{{ $college }}: {{ $count }} applicants" aria-valuenow="{{ $count }}" aria-valuemin="0" aria-valuemax="{{ max($colleges) }}">
                                        <div class="progress-bar" style="width: {{ $width }}%; background-color: #00795A;"></div>
                                    </div>
                                    <span class="small fw-bold text-end" style="width: 2.5rem;">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reports sent by the IATO --}}
            <div class="col-12 col-xl-5" id="reports">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-4 pb-2">
                        <h2 class="fs-6 fw-bold mb-0">Reports from IATO</h2>
                        <div class="small text-body-secondary">Sent to your office</div>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($reports as $report)
                            <li class="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                                <div class="icon-circle rounded-3 d-flex align-items-center justify-content-center {{ $report['new'] ? 'bg-danger-subtle text-danger-emphasis' : 'bg-primary-subtle text-primary-emphasis' }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div class="flex-grow-1" style="min-width: 0;">
                                    <div class="fw-bold small">{{ $report['title'] }}</div>
                                    <div class="small text-body-secondary">From {{ $report['from'] }} · {{ $report['date'] }}</div>
                                </div>
                                <span class="badge {{ $report['new'] ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-secondary text-body-secondary' }}">{{ $report['new'] ? 'New' : 'Read' }}</span>
                                {{-- TODO: link to the generated PDF once reports are saved by the backend --}}
                                <button type="button" class="btn btn-sm btn-light border no-print" disabled title="Download comes with the backend" aria-label="Download {{ $report['title'] }}">
                                    <i class="bi bi-download"></i>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Who can see this page --}}
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-0">Executive Office</h2>
                        <div class="small text-body-secondary">Officers with access to this read-only view</div>
                    </div>
                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis"><i class="bi bi-eye me-1"></i> Read-only</span>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xxl-5 g-3">
                    @foreach ($executives as $executive)
                        {{-- "You" = the signed-in user, matched by name --}}
                        @php
                            $isYou = str_contains($executive['name'], auth()->user()->name);
                        @endphp
                        <div class="col">
                            <div class="d-flex gap-2 align-items-start border rounded-3 p-3 h-100 {{ $isYou ? 'border-primary bg-primary-subtle' : '' }}">
                                <div class="icon-circle rounded-circle fw-bold small d-flex align-items-center justify-content-center bg-{{ $executive['tone'] }}-subtle text-{{ $executive['tone'] }}-emphasis">{{ $executive['initials'] }}</div>
                                <div>
                                    <div class="fw-bold small">
                                        {{ $executive['name'] }}
                                        @if ($isYou)
                                            <span class="badge bg-primary ms-1">You</span>
                                        @endif
                                    </div>
                                    <div class="small text-body-secondary">{{ $executive['title'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="small text-body-secondary text-center mb-0">Figures reflect admissions data imported into iARIS. This view is read-only.</p>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const YEARS = @json($years);
        const COLORS = @json($chartColors);

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: YEARS.map(y => y.label),
                datasets: ['applicants', 'enrolled'].map(key => ({
                    label: key[0].toUpperCase() + key.slice(1),
                    data: YEARS.map(y => y[key]),
                    borderColor: COLORS[key],
                    backgroundColor: COLORS[key],
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 5,
                    borderWidth: 3,
                    tension: 0.3,
                })),
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => `${c.dataset.label}: ${c.parsed.y.toLocaleString()}` } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() }, grid: { color: '#E2ECEA' } },
                    x: { grid: { display: false } },
                },
            },
        });
    </script>
@endsection
