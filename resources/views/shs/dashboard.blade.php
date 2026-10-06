@extends('layouts.app')

@section('title', 'iARIS — SHS Dashboard')
@section('page-title', 'Senior High School — Admissions Overview')
@section('page-subtitle', "Academic Year {$academicYear} · {$periodLabel}")

@php
    // Bar colours: the two grades. Strand bars use one colour (one kind of number).
    $gradeColors = ['Grade 11' => '#2E8A6E', 'Grade 12' => '#7FC4AB'];
@endphp

@section('content')
    {{-- Same stat cards as the admin dashboard, with SHS numbers --}}
    @include('home.partials.stats')

    <div class="row g-4 mb-4">
        {{-- Applications this week, Grade 11 vs Grade 12 --}}
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <h2 class="fs-6 fw-bold mb-0">SHS Applications This Week</h2>
                        <div class="d-flex gap-3 small text-body-secondary">
                            @foreach ($gradeColors as $grade => $color)
                                <span class="d-flex align-items-center gap-1">
                                    <span class="rounded-1" style="width: 10px; height: 10px; background: {{ $color }};"></span> {{ $grade }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="chart-box">
                        <canvas id="weeklyChart" role="img" aria-label="Applications per day this week for Grade 11 and Grade 12"></canvas>
                    </div>
                    {{-- The same numbers for screen readers --}}
                    <table class="visually-hidden">
                        <caption>SHS applications this week</caption>
                        <tr><th>Day</th>@foreach ($gradeColors as $grade => $color)<th>{{ $grade }}</th>@endforeach</tr>
                        @foreach ($weekly['labels'] as $i => $day)
                            <tr><td>{{ $day }}</td>@foreach ($gradeColors as $grade => $color)<td>{{ $weekly[$grade][$i] }}</td>@endforeach</tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>

        {{-- Applicants per strand, against the slots each strand has --}}
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h2 class="fs-6 fw-bold mb-0">Applicants by Strand</h2>
                        <span class="small text-body-secondary">Applicants / slots</span>
                    </div>
                    <div class="d-flex flex-column gap-3">
                        @foreach ($strands as $strand)
                            @php
                                $percent = round($strand['applicants'] / $strand['slots'] * 100);
                            @endphp
                            <div>
                                <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                                    <div>
                                        <span class="fw-bold small">{{ $strand['code'] }}</span>
                                        <span class="small text-body-secondary d-none d-sm-inline">· {{ $strand['name'] }}</span>
                                    </div>
                                    <div class="small text-nowrap">
                                        @if ($percent >= 90)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis me-1">Almost full</span>
                                        @endif
                                        <span class="fw-bold">{{ $strand['applicants'] }}</span><span class="text-body-secondary"> / {{ $strand['slots'] }}</span>
                                    </div>
                                </div>
                                <div class="progress" style="height: 8px;" role="progressbar" aria-label="{{ $strand['code'] }}: {{ $percent }}% of slots"
                                     aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" style="width: {{ $percent }}%; background-color: #00795A;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            @include('home.partials.recent-applicants', ['recentTitle' => 'Recent SHS Applicants', 'recentLink' => url('/records/is')])
        </div>
        <div class="col-12 col-xl-5">
            @include('home.partials.notifications')
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const WEEKLY = @json($weekly);
        const GRADE_COLORS = @json($gradeColors);

        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = 'rgba(26, 43, 38, 0.6)';

        // Grouped bars, same style as the Analytics page: thin bars, rounded tops, light grid
        new Chart(document.getElementById('weeklyChart'), {
            type: 'bar',
            data: {
                labels: WEEKLY.labels,
                datasets: Object.entries(GRADE_COLORS).map(([grade, color]) => ({
                    label: grade, data: WEEKLY[grade], backgroundColor: color,
                    borderRadius: 4, borderSkipped: 'start', maxBarThickness: 24,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },   // the legend is the HTML above the chart
                    tooltip: { backgroundColor: '#1A2B26', padding: 10 },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false } },
                    y: { beginAtZero: true, grid: { color: '#E7ECEA', drawTicks: false }, border: { display: false }, ticks: { padding: 8 } },
                },
            },
        });
    </script>
@endsection
