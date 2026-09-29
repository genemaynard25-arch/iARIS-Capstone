{{--
    A chart card: title, controls (optional chart-type switch, table view, CSV export) and the chart area.
    Usage: @include('analytics.partials.chart-card', ['id' => 'trends', 'title' => '...', 'subtitle' => '...', 'types' => ['bar' => 'Bar chart', 'line' => 'Line chart']])
    By default the card holds a Chart.js <canvas id="chart-{id}">. Pass 'body' => 'a.view.name' to show a custom view instead.
--}}
<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <h2 class="fs-6 fw-bold mb-1">{{ $title }}</h2>
                <p class="small text-body-secondary mb-0">{{ $subtitle }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @isset($types)
                    <select class="form-select form-select-sm w-auto" data-chart-type="{{ $id }}" aria-label="Chart type for {{ $title }}">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @endisset
                <button type="button" class="btn btn-sm btn-light border" data-table-toggle="{{ $id }}" aria-pressed="false">
                    <i class="bi bi-table me-1"></i> Table
                </button>
                <button type="button" class="btn btn-sm btn-light border" data-export="{{ $id }}">
                    <i class="bi bi-download me-1"></i> CSV
                </button>
            </div>
        </div>

        <div data-chart-area="{{ $id }}">
            @isset($body)
                @include($body)
            @else
                <div class="chart-box"><canvas id="chart-{{ $id }}" role="img" aria-label="{{ $title }}"></canvas></div>
            @endisset
        </div>
        <div class="table-responsive d-none" data-table-view="{{ $id }}"></div>
    </div>
</div>
