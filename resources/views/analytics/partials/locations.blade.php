<div class="d-flex flex-wrap align-items-center gap-4">
    <div class="chart-box position-relative flex-grow-1" id="locationsBox">
        <canvas id="chart-locations" role="img" aria-label="Applicants by location"></canvas>
    </div>
    {{-- Shown only for the donut: total in the middle + legend with percentages --}}
    <div id="locationsDonutExtras" class="flex-grow-1">
        <div class="mb-3">
            <div class="fs-3 fw-bold lh-1" id="locationsTotal"></div>
            <div class="small text-body-secondary">total applicants</div>
        </div>
        <ul class="list-unstyled d-flex flex-column gap-2 mb-0" id="locationsLegend"></ul>
    </div>
</div>
