@extends('layouts.app')

@section('title', 'iARIS — Dashboard')
@section('page-title', 'Admissions Overview')
@section('page-subtitle', "Academic Year {$academicYear} · {$periodLabel}")

@section('content')
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
    @if (array_sum($weekChart['college']) + array_sum($weekChart['is']) > 0)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
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
