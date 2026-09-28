<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="fs-6 fw-bold mb-0">Notifications</h2>
            <a href="#" class="small fw-semibold text-decoration-none">Mark all read</a>
        </div>

        @forelse ($notifications as $date => $items)
            <div class="small fw-bold text-uppercase tracking-wide text-primary {{ $loop->first ? '' : 'mt-3' }}">{{ $date }}</div>
            @foreach ($items as $notif)
                <div class="d-flex gap-3 py-3 {{ $loop->first ? '' : 'border-top' }}">
                    <div class="icon-circle rounded-circle bg-{{ $notif['tone'] }}-subtle text-{{ $notif['tone'] }}-emphasis d-flex align-items-center justify-content-center">
                        <i class="bi {{ $notif['icon'] }}"></i>
                    </div>
                    <div>
                        <p class="small fw-semibold mb-1">{{ $notif['title'] }}</p>
                        <span class="small text-body-secondary">{{ $notif['body'] }}</span>
                    </div>
                </div>
            @endforeach
        @empty
            <p class="small text-body-secondary text-center py-4 mb-0">You're all caught up.</p>
        @endforelse
    </div>
</div>
