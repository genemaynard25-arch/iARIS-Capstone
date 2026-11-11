@extends('layouts.app')

@section('title', 'iARIS — Dashboard')
@section('page-title', 'Admissions Overview')
@section('page-subtitle', "Academic Year {$academicYear} · {$periodLabel}")

@include('partials.admin-workspace', ['workspacePage' => 'dashboard-page', 'workspaceGroup' => 'Main', 'workspaceLabel' => 'Dashboard'])

@section('content')
    @includeWhen($staffStrip, 'home.partials.staff-strip')
    @include('home.partials.stats')
    @include('home.partials.charts')

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            @include('home.partials.recent-applicants', ['recentLink' => url('/applicants')])
        </div>
        <div class="col-12 col-xl-5">
            @include('home.partials.notifications')
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    @if (array_sum($locationChart) > 0)
        <script>
            const LOCATION = @json(array_filter($locationChart, fn ($count) => $count > 0));
            const PROVINCE_COLORS = @json(\App\Support\AddressClassifier::COLORS);
            new Chart(document.getElementById('dashboardLocationChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(LOCATION), datasets: [{ data: Object.values(LOCATION), backgroundColor: Object.keys(LOCATION).map(label => PROVINCE_COLORS[label] || '#859791'), borderWidth: 0, hoverOffset: 4 }] },
                options: { maintainAspectRatio: false, cutout: '76%', plugins: { legend: { display: false } } },
            });
        </script>
    @endif
    @if (array_sum($weekChart['college']) + array_sum($weekChart['is']) > 0)
        <script>
            // Applications this week: two series side by side, same look as the Analytics charts
            const WEEK = @json($weekChart);
            Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
            Chart.defaults.color = 'rgba(26, 43, 38, 0.6)';
            const bar = (label, data, color) => ({ label, data, backgroundColor: color, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 22 });
            new Chart(document.getElementById('weekChart'), {
                type: 'bar',
                data: { labels: WEEK.labels, datasets: [bar('College', WEEK.college, '#00674F'), bar('Integrated School', WEEK.is, '#7FC4AB')] },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1A2B26', padding: 10, boxPadding: 4 } },
                    scales: {
                        x: { grid: { display: false }, border: { display: false } },
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#E7ECEA', drawTicks: false }, border: { display: false } },
                    },
                },
            });
        </script>
    @endif
@endsection
