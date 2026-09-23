<div class="row g-4 mb-4">
    @foreach ($stats as $stat)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="iaris-card">
                <div class="stat-label mb-3">{{ $stat['label'] }}</div>
                <div class="stat-value mb-1">{{ number_format($stat['value']) }}</div>
                <div class="stat-change mb-3 {{ $stat['trend'] === 'up' ? '' : 'neutral' }}">
                    @if ($stat['trend'] === 'up')
                        <i class="bi bi-arrow-up-right"></i>
                    @endif
                    {{ $stat['change'] }}
                </div>
                <span class="iaris-tag tone-{{ $stat['tone'] }}">{{ $stat['tag'] }}</span>
            </div>
        </div>
    @endforeach
</div>