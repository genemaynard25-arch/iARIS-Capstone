{{-- One choice in the "Add an applicant" window: a link to that Records page, or greyed out if it can't be opened --}}
@php([$color, $background] = $option['colors'])
@if ($option['allowed'])
    <a href="{{ url($option['records']) }}" class="d-flex align-items-center gap-2 rounded-3 border p-2 text-decoration-none text-body fw-semibold add-option">
        <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px;color:{{ $color }};background:{{ $background }}"><i class="bi {{ $option['icon'] }}"></i></span>
        {{ $option['name'] }}
    </a>
@else
    <div class="d-flex align-items-center gap-2 rounded-3 border p-2 text-body-secondary fw-semibold opacity-75" title="{{ $option['records'] ? 'Your role cannot open this records page' : 'Records page coming soon' }}">
        <span class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 bg-body-tertiary" style="width:36px;height:36px"><i class="bi {{ $option['icon'] }}"></i></span>
        <span class="lh-sm">{{ $option['name'] }}<br><span class="small fw-normal">{{ $option['records'] ? 'No access' : 'Coming soon' }}</span></span>
    </div>
@endif
