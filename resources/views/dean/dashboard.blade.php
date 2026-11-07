@extends('layouts.app')

@section('title', 'iARIS — Dean Dashboard')
@section('page-title', $heading)
@section('page-subtitle', $description)

@php
    // % change, e.g. 184 -> 214 = +16.3
    $change = fn ($old, $new) => round(($new - $old) / $old * 100, 1);
    $conversionNow = $enrolled[3] / $applicants[3] * 100;
    $conversionBefore = $enrolled[2] / $applicants[2] * 100;

    $stats = [
        ['label' => 'Total Applicants', 'value' => number_format($applicants[3]), 'delta' => $change($applicants[2], $applicants[3]), 'unit' => '%', 'tone' => 'primary'],
        ['label' => 'Paid Reservations', 'value' => number_format($paid['current']), 'delta' => $change($paid['previous'], $paid['current']), 'unit' => '%', 'tone' => 'info'],
        ['label' => 'Enrolled', 'value' => number_format($enrolled[3]), 'delta' => $change($enrolled[2], $enrolled[3]), 'unit' => '%', 'tone' => 'dark'],
        // Conversion changes in percentage points, not percent
        ['label' => 'Applied → Enrolled', 'value' => number_format($conversionNow, 1) . '%', 'delta' => round($conversionNow - $conversionBefore, 1), 'unit' => ' pts', 'tone' => 'warning'],
    ];

    $applied = reset($funnel);
    $current = end($years);

    // Chart colours: applicants green, enrolled blue; status Pending red, For Exam orange, Paid blue
    $chartColors = ['applicants' => '#00674F', 'enrolled' => '#2558D9', 'lastYear' => '#C5E4D8'];
    $statusColors = ['Pending' => '#B91C1C', 'For Exam' => '#B96000', 'Paid' => '#2558D9'];
    $statusBadges = ['Pending' => 'bg-danger-subtle text-danger-emphasis', 'For Exam' => 'bg-warning-subtle text-warning-emphasis', 'Paid' => 'bg-info-subtle text-info-emphasis'];
@endphp

@section('content')
    {{-- Toolbar: read-only notice, then the program filter (dean) or the locked program (program chair) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-2 px-4 py-3">
            @if ($isChair)
                <span class="small fw-bold text-uppercase tracking-wide text-body-secondary me-1">Your program</span>
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                    <i class="bi bi-lock-fill me-1"></i> {{ $scopeName }}
                </span>
                <span class="small text-body-secondary">You can see figures for this program only.</span>
            @else
                <span class="small fw-bold text-uppercase tracking-wide text-body-secondary me-1">Program</span>
                {{-- Each pill reloads the page for that program (?program=BSCS) --}}
                <nav class="nav nav-pills gap-2" aria-label="Program">
                    <a href="{{ route('dean.dashboard') }}" class="nav-link rounded-pill border px-3 py-1 small fw-semibold {{ $selected ? '' : 'active' }}">All Programs</a>
                    @foreach ($programs as $code => $program)
                        <a href="{{ route('dean.dashboard', ['program' => $code]) }}" title="{{ $program['name'] }}"
                           class="nav-link rounded-pill border px-3 py-1 small fw-semibold {{ $selected === $code ? 'active' : '' }}">{{ $code }}</a>
                    @endforeach
                </nav>
            @endif
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2 ms-sm-auto">
                <i class="bi bi-eye me-1"></i> Read-only view
            </span>
        </div>
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
                            {{ $stat['delta'] >= 0 ? '+' : '−' }}{{ abs($stat['delta']) }}{{ $stat['unit'] }} vs last AY
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
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h2 class="fs-6 fw-bold mb-0">Applicants vs Enrolled</h2>
                            <div class="small text-body-secondary">Showing: <span class="fw-bold text-primary">{{ $scopeName }}</span> · {{ count($years) }} academic years</div>
                        </div>
                        <div class="d-flex gap-3 small text-body-secondary">
                            <span class="d-flex align-items-center gap-1"><span class="rounded-1" style="width: 10px; height: 10px; background: {{ $chartColors['applicants'] }};"></span> Applicants</span>
                            <span class="d-flex align-items-center gap-1"><span class="rounded-1" style="width: 10px; height: 10px; background: {{ $chartColors['enrolled'] }};"></span> Enrolled</span>
                        </div>
                    </div>
                    <div class="chart-box">
                        <canvas id="trendChart" role="img" aria-label="Applicants and enrolled per academic year"></canvas>
                    </div>
                    {{-- The same numbers for screen readers --}}
                    <table class="visually-hidden">
                        <caption>Applicants and enrolled per academic year</caption>
                        <tr><th>Year</th><th>Applicants</th><th>Enrolled</th></tr>
                        @foreach ($years as $i => $year)
                            <tr><td>{{ $year }}</td><td>{{ $applicants[$i] }}</td><td>{{ $enrolled[$i] }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        {{-- Conversion funnel: each stage as a share of everyone who applied --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="fs-6 fw-bold mb-0">Conversion Funnel</h2>
                    <div class="small text-body-secondary mb-3">Showing: <span class="fw-bold text-primary">{{ $scopeName }}</span> · {{ $current }}</div>
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
        {{-- Dean: applicants per program (this AY vs last AY). Program chair: their applicants by status. --}}
        <div class="col-12 col-xl">
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
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <h2 class="fs-6 fw-bold mb-0">Applicants by Program</h2>
                                <div class="small text-body-secondary">{{ $current }} vs {{ $years[count($years) - 2] }}{{ $selected ? ' · selected program is highlighted' : '' }}</div>
                            </div>
                            <div class="d-flex gap-3 small text-body-secondary">
                                <span class="d-flex align-items-center gap-1"><span class="rounded-1" style="width: 10px; height: 10px; background: {{ $chartColors['lastYear'] }};"></span> {{ $years[count($years) - 2] }}</span>
                                <span class="d-flex align-items-center gap-1"><span class="rounded-1" style="width: 10px; height: 10px; background: {{ $chartColors['applicants'] }};"></span> {{ $current }}</span>
                            </div>
                        </div>
                        {{-- flex-grow-1: the chart fills the card, as tall as the Key Takeaways next to it --}}
                        <div class="chart-box flex-grow-1">
                            <canvas id="programChart" role="img" aria-label="Applicants per program, this AY vs last AY"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Key takeaways. data-ai-content: hidden when "Show AI-written takeaways" is off in Settings --}}
        <div class="col-12 col-xl-5" data-ai-content>
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h2 class="fs-6 fw-bold mb-0">Key Takeaways</h2>
                            <div class="small text-body-secondary">Auto-summary for <span class="fw-bold text-primary">{{ $scopeName }}</span></div>
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
                        <span>Generated from iARIS historical data. These are descriptive summaries, not forecasts. Decisions remain with you and university leadership.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Newest applicants in scope --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                {{-- flex-grow-0: keep the heading its own height so the table sits right under it --}}
                <div class="card-body p-4 pb-2 flex-grow-0">
                    <h2 class="fs-6 fw-bold mb-0">Recent Applicants</h2>
                    <div class="small text-body-secondary">Latest applications to {{ $selected ? $scopeName : 'CITE programs' }}</div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-nowrap">
                                <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                                <th class="text-body-secondary fw-bold">Program</th>
                                <th class="text-body-secondary fw-bold">Status</th>
                                <th class="pe-4 text-body-secondary fw-bold">Date applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $applicant)
                                <tr>
                                    <td class="ps-4 fw-bold small">{{ $applicant['name'] }}</td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis" title="{{ $programs[$applicant['program']]['name'] }}">{{ $applicant['program'] }}</span></td>
                                    <td><span class="badge {{ $statusBadges[$applicant['status']] }}">{{ $applicant['status'] }}</span></td>
                                    <td class="pe-4 small text-body-secondary text-nowrap">{{ $applicant['date'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-body-secondary small py-5">No applicants to show.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Reports the IATO sent to this office --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 pb-2 flex-grow-0">
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
                                <div class="small text-body-secondary">From Renegado, Randolph · {{ $report['sent'] }}</div>
                            </div>
                            <span class="badge {{ $report['new'] ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-secondary text-body-secondary' }}">{{ $report['new'] ? 'New' : 'Read' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <p class="small text-body-secondary text-center mb-0">Figures reflect admissions data imported into iARIS. This view is read-only.</p>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const YEARS = @json($years);
        const COLORS = @json($chartColors);
        const fmt = n => n.toLocaleString('en-US');

        // Applicants vs enrolled, per academic year
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: YEARS,
                datasets: [
                    { label: 'Applicants', data: @json($applicants), borderColor: COLORS.applicants, backgroundColor: COLORS.applicants },
                    { label: 'Enrolled', data: @json($enrolled), borderColor: COLORS.enrolled, backgroundColor: COLORS.enrolled },
                ].map(d => ({ ...d, pointBackgroundColor: '#fff', pointBorderWidth: 3, pointRadius: 5, borderWidth: 3, tension: 0.3 })),
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => `${c.dataset.label}: ${fmt(c.parsed.y)}` } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: fmt }, grid: { color: '#E2ECEA' } },
                    x: { grid: { display: false } },
                },
            },
        });

        @if ($isChair)
            // Applicants by status (doughnut)
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: @json(array_keys($status)),
                    datasets: [{ data: @json(array_values($status)), backgroundColor: @json(array_values($statusColors)), borderColor: '#fff', borderWidth: 2 }],
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: { legend: { display: false } },
                },
            });
        @else
            // Applicants per program: last AY (light) and this AY (dark). The selected program stays bright; the others fade.
            const PROGRAMS = @json($programs);
            const SELECTED = @json($selected);
            const codes = Object.keys(PROGRAMS);
            const fade = (color, code) => (SELECTED && SELECTED !== code) ? color + '4D' : color; // 4D = 30% opacity
            new Chart(document.getElementById('programChart'), {
                type: 'bar',
                data: {
                    labels: codes,
                    datasets: [
                        { label: YEARS[YEARS.length - 2], data: codes.map(c => PROGRAMS[c].applicants[YEARS.length - 2]), backgroundColor: codes.map(c => fade(COLORS.lastYear, c)) },
                        { label: YEARS[YEARS.length - 1], data: codes.map(c => PROGRAMS[c].applicants[YEARS.length - 1]), backgroundColor: codes.map(c => fade(COLORS.applicants, c)) },
                    ].map(d => ({ ...d, borderRadius: 4, maxBarThickness: 36 })),
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { title: items => PROGRAMS[items[0].label].name, label: c => `${c.dataset.label}: ${fmt(c.parsed.y)}` } },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: fmt }, grid: { color: '#E2ECEA' } },
                        x: { grid: { display: false } },
                    },
                },
            });
        @endif
    </script>
@endsection
