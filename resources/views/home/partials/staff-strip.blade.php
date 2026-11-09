{{-- IATO Staff only (HomeController::staffStrip): Temporary Admin notice, and a summary of My Submissions --}}
@php($counts = $staffStrip['submissions'])
<div class="staff-dashboard-strip row g-3 mb-4">
    @if ($staffStrip['tempAdmin'])
        <div class="col-12 col-xl-7">
            <div class="alert alert-warning d-flex flex-wrap align-items-center gap-3 rounded-4 h-100 mb-0">
                <i class="bi bi-person-fill-gear fs-4"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold">You have Temporary Admin access until {{ $staffStrip['tempAdmin']['until'] }}</div>
                    <div class="small">Granted by {{ $staffStrip['tempAdmin']['by'] }} on {{ $staffStrip['tempAdmin']['since'] }}. Your view returns to IATO Staff after that.</div>
                </div>
                <a href="{{ url('/review-queue') }}" class="btn btn-warning fw-semibold text-nowrap">Open Review Queue <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </div>
    @endif
    <div class="col-12 {{ $staffStrip['tempAdmin'] ? 'col-xl-5' : '' }}">
        <a href="{{ url('/submissions') }}" class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none text-body">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div class="fw-bold text-primary"><i class="bi bi-send me-1"></i> My Submissions <small class="d-block fw-normal text-body-secondary mt-1">Preview review workflow</small></div>
                <div class="d-flex flex-wrap gap-3 small text-body-secondary ms-xl-auto">
                    <span><b class="fs-5 text-body">{{ $counts['waiting'] }}</b> waiting</span>
                    <span><b class="fs-5 text-danger">{{ $counts['sent'] }}</b> sent back</span>
                    <span><b class="fs-5 text-body">{{ $counts['approved'] }}</b> approved</span>
                </div>
                <i class="bi bi-chevron-right text-body-secondary"></i>
            </div>
        </a>
    </div>
</div>
