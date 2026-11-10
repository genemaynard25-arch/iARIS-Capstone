{{-- Funnel stages: bar length = share of applicants who reached the stage. Colours are one hue, dark → light, because the stages are in order. --}}
@php
    $funnel = $charts['funnel'];
    $top = $funnel['values'][0];
    $colors = ['#00503D', '#00674F', '#2E8A6E', '#4DAB8E', '#7FC4AB'];
@endphp
<ol id="filteredFunnel" class="list-unstyled d-flex flex-column gap-2 mb-0">
    @foreach ($funnel['labels'] as $i => $stage)
        @php($value = $funnel['values'][$i])
        @php($share = $top > 0 ? $value / $top * 100 : 0)
        <li class="d-flex align-items-center gap-3">
            <div class="ar-funnel-label fw-semibold text-end text-nowrap flex-shrink-0">{{ $stage }}</div>
            <div class="ar-funnel-track flex-grow-1 bg-body-tertiary rounded-2" title="{{ $stage }}: {{ number_format($value) }} ({{ number_format($share, 1) }}%)">
                <div class="h-100 rounded-2" style="width: {{ min($share, 100) }}%; background: {{ $colors[$i] }};"></div>
            </div>
            <div class="ar-funnel-value fw-bold text-end flex-shrink-0">{{ number_format($value) }}</div>
            <div class="ar-funnel-share text-body-secondary text-end text-nowrap flex-shrink-0">{{ $share == 100 ? '100' : number_format($share, 1) }}%</div>
        </li>
    @endforeach
</ol>
