<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="fs-6 fw-bold mb-0">Notifications</h2>
            @if (count($notifications))
                {{-- Front end only: greys the list out until the next reload --}}
                <button type="button" class="btn btn-link btn-sm p-0 fw-semibold text-decoration-none" id="markAllRead">Mark all read</button>
            @endif
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
                        <span class="small text-body-secondary">{{ $notif['body'] }}{{ isset($notif['time']) ? ' · ' . $notif['time'] : '' }}</span>
                    </div>
                </div>
            @endforeach
        @empty
            <p class="small text-body-secondary text-center py-4 mb-0">You're all caught up.</p>
        @endforelse
    </div>
</div>
<script>
    document.getElementById('markAllRead')?.addEventListener('click', e => {
        e.target.closest('.card').querySelectorAll('.icon-circle').forEach(el => el.closest('.d-flex').classList.add('opacity-50'));
        e.target.remove();
    });
</script>
