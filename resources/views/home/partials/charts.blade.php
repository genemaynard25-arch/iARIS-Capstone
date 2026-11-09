<div class="row g-4 mb-4">
    {{-- Applicants added each day this week, College vs Integrated School (from the applicants table) --}}
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fs-6 fw-bold mb-0">Applications This Week</h2>
                    <a href="{{ url('/applicants') }}" class="small fw-semibold text-decoration-none">View all</a>
                </div>
                @if (array_sum($weekChart['college']) + array_sum($weekChart['is']) === 0)
                    <div class="rounded-3 bg-body-tertiary text-body-secondary text-center py-5 flex-grow-1 d-flex flex-column justify-content-center">
                        <i class="bi bi-bar-chart-line fs-2 d-block mb-2"></i>
                        <span class="small">No College or IS applicants added this week yet.</span>
                    </div>
                @else
                    <div class="chart-box"><canvas id="weekChart" role="img" aria-label="Applications this week, College and Integrated School"></canvas></div>
                    {{-- Legend (both series are always shown) --}}
                    <div class="d-flex gap-3 small text-body-secondary mt-3">
                        <span class="d-flex align-items-center gap-2"><span class="rounded-1" style="width:10px;height:10px;background:#00674F"></span> College</span>
                        <span class="d-flex align-items-center gap-2"><span class="rounded-1" style="width:10px;height:10px;background:#7FC4AB"></span> Integrated School</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- The applicants table has no address / location yet --}}
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fs-6 fw-bold mb-0">Applicants by Location</h2>
                    <a href="{{ url('/analytics') }}" class="small fw-semibold text-decoration-none">Full report</a>
                </div>
                <div class="dashboard-location-preview flex-grow-1">
                    <div id="dashboardLocationBox">
                        <canvas id="dashboardLocationChart" role="img" aria-label="Preview applicant locations: Lipa City 1,377, Batangas 895, CALABARZON 723, Others 447"></canvas>
                        <div class="dashboard-location-center"><strong>3,442</strong><small>PREVIEW APPLICANTS</small></div>
                    </div>
                    <ul class="dashboard-location-legend list-unstyled mb-0">
                        @foreach ([['Lipa City', '1,377', '#00674F'], ['Batangas', '895', '#339879'], ['CALABARZON', '723', '#7FC4AB'], ['Others', '447', '#C9E8DC']] as [$location, $count, $color])
                            <li><i style="background: {{ $color }}" aria-hidden="true"></i><span>{{ $location }}</span><strong class="ms-auto">{{ $count }}</strong></li>
                        @endforeach
                    </ul>
                </div>
                <p class="admin-preview-note mt-3 mb-0"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Preview data · Imported applicants do not include location data yet.</p>
            </div>
        </div>
    </div>
</div>
