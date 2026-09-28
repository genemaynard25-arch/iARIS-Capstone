<div class="row g-4 mb-4">
    @foreach ($stats as $stat)
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-3">{{ $stat['label'] }}</div>
                    <div class="fs-2 fw-bold mb-1">{{ number_format($stat['value']) }}</div>
                    <div class="small fw-semibold mb-3 {{ $stat['trend'] === 'up' ? 'text-primary' : 'text-body-secondary' }}">
                        @if ($stat['trend'] === 'up')
                            <i class="bi bi-arrow-up-right"></i>
                        @endif
                        {{ $stat['change'] }}
                    </div>
                    <span class="badge rounded-pill bg-{{ $stat['tone'] }}-subtle text-{{ $stat['tone'] }}-emphasis px-3 py-2">{{ $stat['tag'] }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>
