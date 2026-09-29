{{-- Funnel stages: bar length = share of applicants who reached the stage. Colours are one hue, dark → light, because the stages are in order. --}}
@php
    $funnel = $charts['funnel'];
    $top = $funnel['values'][0];
    $colors = ['#00503D', '#00674F', '#2E8A6E', '#4DAB8E', '#7FC4AB'];
@endphp
<ol class="list-unstyled d-flex flex-column gap-2 mb-0">
    @foreach ($funnel['labels'] as $i => $stage)
        @php($value = $funnel['values'][$i])
        @php($share = $value / $top * 100)
        <li class="d-flex align-items-center gap-3">
            <div class="small fw-semibold text-end text-nowrap flex-shrink-0" style="width: 96px;">{{ $stage }}</div>
            <div class="flex-grow-1 bg-body-tertiary rounded-2" style="height: 28px;" title="{{ $stage }}: {{ number_format($value) }} ({{ number_format($share, 1) }}%)">
                <div class="h-100 rounded-2" style="width: {{ $share }}%; background: {{ $colors[$i] }};"></div>
            </div>
            <div class="small fw-bold text-end flex-shrink-0" style="width: 52px;">{{ number_format($value) }}</div>
            <div class="small text-body-secondary text-end text-nowrap flex-shrink-0" style="width: 52px;">{{ $share == 100 ? '100' : number_format($share, 1) }}%</div>
        </li>
    @endforeach
</ol>
