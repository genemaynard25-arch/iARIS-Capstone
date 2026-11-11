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

    {{-- Current school year's applicants, bucketed by address (App\Support\AddressClassifier) --}}
    <div class="col-12 col-xl-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fs-6 fw-bold mb-0">Applicants by Province</h2>
                    <a href="{{ url('/analytics') }}" class="small fw-semibold text-decoration-none">Full report</a>
                </div>
                @include('partials.province-chart', [
                    'provinceCounts' => $locationChart,
                    'provinceIds' => ['box' => 'dashboardLocationBox', 'canvas' => 'dashboardLocationChart', 'extras' => 'dashboardLocationExtras', 'total' => 'dashboardLocationTotal', 'legend' => 'dashboardLocationLegend', 'message' => 'dashboardLocationMessage'],
                ])
                <p class="small text-body-secondary mt-3 mb-0">Province inferred from the address; unrecognized addresses are grouped as Other / Unspecified province.</p>
            </div>
        </div>
    </div>
</div>
