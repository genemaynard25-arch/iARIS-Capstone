@php
    $provinceTotal = array_sum($provinceCounts);
    $provinceCounts = array_filter($provinceCounts, fn ($count) => $count > 0);
    $provinceMessage = count($provinceCounts) === 1
        ? 'All '.number_format($provinceTotal).' applicants in this scope are classified under '.array_key_first($provinceCounts).'.'
        : ($provinceTotal === 0 ? 'No applicants match this scope.' : '');
@endphp
<div class="province-chart-layout">
    <div class="province-chart-plot" id="{{ $provinceIds['box'] }}">
        <canvas id="{{ $provinceIds['canvas'] }}" role="img" aria-label="Applicants by province"></canvas>
        <div class="province-chart-center" id="{{ $provinceIds['extras'] }}">
            <strong id="{{ $provinceIds['total'] }}">{{ number_format($provinceTotal) }}</strong>
            <small>APPLICANTS</small>
        </div>
    </div>
    <ul class="province-chart-legend list-unstyled mb-0" id="{{ $provinceIds['legend'] }}">
        @foreach ($provinceCounts as $province => $count)
            <li>
                <i style="background: {{ \App\Support\AddressClassifier::COLORS[$province] ?? '#859791' }}" aria-hidden="true"></i>
                <span>{{ $province }}</span>
                <strong>{{ number_format($count) }} <small>({{ round($count / $provinceTotal * 100, 1) }}%)</small></strong>
            </li>
        @endforeach
    </ul>
</div>
<p class="small text-body-secondary mt-3 mb-0" id="{{ $provinceIds['message'] }}" @if ($provinceMessage === '') hidden @endif>{{ $provinceMessage }}</p>
