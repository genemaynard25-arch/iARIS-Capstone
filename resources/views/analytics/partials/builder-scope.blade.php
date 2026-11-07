{{-- "Date range · Department · Academic year" at the top of a report builder. The script fills it in. $prefix: 'view' or 'tray' --}}
<div class="row g-2 small mb-4">
    @foreach (['Range' => 'Date range', 'Dept' => 'Department', 'Year' => 'Academic year'] as $key => $label)
        <div class="col-4">
            <div class="rounded-3 bg-body-tertiary p-2 h-100">
                <div class="text-body-secondary text-uppercase fw-bold small">{{ $label }}</div>
                <div class="fw-semibold" id="{{ $prefix }}{{ $key }}">—</div>
            </div>
        </div>
    @endforeach
</div>
