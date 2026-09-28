<div class="iaris-card">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="fs-6 fw-bold mb-0">Notifications</h3>
        <a href="#" class="iaris-card-link">Mark all read</a>
    </div>

    @forelse ($notifications as $date => $items)
        <div class="notif-date">{{ $date }}</div>
        @foreach ($items as $notif)
            <div class="notif-item d-flex gap-3 py-3">
                <div class="notif-icon tone-{{ $notif['tone'] }} rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <i class="bi {{ $notif['icon'] }}"></i>
                </div>
                <div>
                    <p class="fw-semibold small mb-1">{{ $notif['title'] }}</p>
                    <span class="iaris-text-soft small">{{ $notif['body'] }}</span>
                </div>
            </div>
        @endforeach
    @empty
        <p class="iaris-text-soft small text-center py-4 mb-0">You're all caught up.</p>
    @endforelse
</div>
