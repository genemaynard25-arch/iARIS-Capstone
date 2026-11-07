{{-- Chart legend: a coloured square + name for each series. Usage: @include('chancellor.partials.legend', ['items' => ['Applicants' => '#00674F']]) --}}
<div class="d-flex flex-wrap gap-3 small text-body-secondary mb-2">
    @foreach ($items as $label => $color)
        <span class="d-flex align-items-center gap-1">
            <span class="rounded-1" style="width: 10px; height: 10px; background: {{ $color }};"></span> {{ $label }}
        </span>
    @endforeach
</div>
