@extends('layouts.app')

@php
    $total = array_sum(array_column($counts, 'total'));
    $sum = fn ($status) => array_sum(array_column($counts, $status));
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    // Status => colour, in pipeline order (the bar segments and the legend)
    $segments = ['Pending' => 'danger', 'For Exam' => 'warning', 'Passed' => 'primary', 'Paid' => 'info'];
    $widest = max(1, max(array_column($counts, 'total')));
    // status => [label, colour, icon] for the questions list
    $questionStatus = ['waiting' => ['Waiting for IATO', 'warning', 'bi-hourglass-split'], 'answered' => ['IATO answered', 'info', 'bi-reply-fill'], 'resolved' => ['Resolved', 'success', 'bi-check-circle-fill']];
@endphp

@section('title', 'iARIS — Registrar Dashboard')
@section('page-title', $greeting . ', ' . \Illuminate\Support\Str::after($me['name'], ', '))
@section('page-subtitle', 'Admissions for ' . (count($units) > 1 ? implode(', ', array_slice($units, 0, -1)) . ', and ' . last($units) : $units[0]) . " · AY {$academicYear}")

@section('content')
    <div class="alert alert-info d-flex align-items-center gap-2 rounded-4 small mb-4">
        <i class="bi bi-eye"></i>
        <span>You can view these records but not change them. If something looks wrong, open the record and use <b>Ask IATO about this record</b>.</span>
    </div>

    {{-- Stat cards --}}
    <div class="row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ([
            ['Applicants', $total, 'primary', 'In your units'],
            ['Pending', $sum('Pending'), 'danger', 'Awaiting action'],
            ['For Exam / Assessment', $sum('For Exam'), 'warning', 'Scheduled / waiting'],
            ['Paid', $sum('Paid'), 'info', 'Ready for enrollment'],
        ] as [$label, $value, $tone, $sub])
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $tone }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                        <div class="fs-3 fw-bold {{ $tone === 'primary' ? '' : "text-{$tone}-emphasis" }}">{{ number_format($value) }}</div>
                        <div class="small text-body-secondary">{{ $sub }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- Applicants by unit: one bar per unit, split by status --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Applicants by {{ $levels ? 'level' : 'unit' }}</h2>
                    <span class="small text-body-secondary">AY {{ $academicYear }}</span>
                </div>
                <div class="card-body px-4 pb-4">
                    @foreach ($counts as $unit => $c)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <a href="{{ $c['url'] }}" class="small fw-bold text-body text-decoration-none flex-shrink-0" style="width: 130px;">{{ $unit }}</a>
                            <div class="flex-grow-1">
                                @if ($c['total'])
                                    {{-- Bar length = the unit's total, compared with the biggest unit --}}
                                    <div class="progress-stacked" style="height: 12px; width: {{ max(12, round($c['total'] / $widest * 100)) }}%;">
                                        @foreach ($segments as $status => $tone)
                                            @if ($c[$status])
                                                <div class="progress" role="progressbar" style="width: {{ $c[$status] / $c['total'] * 100 }}%; height: 12px;"
                                                     aria-label="{{ $status }}: {{ $c[$status] }}" title="{{ $status }}: {{ $c[$status] }}">
                                                    <div class="progress-bar bg-{{ $tone }}"></div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="small text-body-secondary">No applicants imported yet</span>
                                @endif
                            </div>
                            <span class="small fw-bold text-end" style="width: 40px;">{{ number_format($c['total']) }}</span>
                        </div>
                    @endforeach
                    <div class="d-flex flex-wrap gap-3 small text-body-secondary mt-2">
                        @foreach ($segments as $status => $tone)
                            <span><i class="bi bi-square-fill text-{{ $tone }} me-1"></i>{{ $status === 'For Exam' ? 'For Exam / Assessment' : $status }}</span>
                        @endforeach
                        {{-- The grey end of a bar --}}
                        <span><i class="bi bi-square-fill me-1" style="color: var(--bs-progress-bg, var(--bs-secondary-bg));"></i>Not Qualified</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Latest paid reservations --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Latest paid reservations</h2>
                    <a href="{{ url('/registrar/enrollment') }}" class="small fw-bold text-decoration-none">Ready for Enrollment →</a>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @forelse ($latestPaid as $a)
                        <a href="{{ $a['url'] }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-2">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">{{ $a['name'] }}</div>
                                <div class="small text-body-secondary">{{ $a['unit'] }} · {{ $a['program'] }}</div>
                            </div>
                            <div class="text-end small text-nowrap">
                                <div class="fw-bold text-info-emphasis">Paid</div>
                                <div class="text-body-secondary">{{ $a['paid_label'] }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="text-center text-body-secondary small py-5">No paid reservations yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Your questions to IATO --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
            <h2 class="fs-6 fw-bold mb-0">Your questions to IATO</h2>
            <a href="{{ url('/registrar/questions') }}" class="small fw-bold text-decoration-none">See all →</a>
        </div>
        <div class="list-group list-group-flush rounded-bottom-4">
            @forelse (array_slice($questions, 0, 4) as $q)
                @php([$label, $tone, $icon] = $questionStatus[$q['status']])
                <a href="{{ url('/registrar/questions') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-2">
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ $q['name'] }}</div>
                        <div class="small text-body-secondary">{{ $q['topic'] }} · {{ end($q['messages'])['at'] }}</div>
                    </div>
                    <span class="badge rounded-pill bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span>
                </a>
            @empty
                <div class="text-center text-body-secondary small py-4">No questions yet.</div>
            @endforelse
        </div>
    </div>
@endsection
