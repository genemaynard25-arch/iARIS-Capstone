{{--
    "Signed in as" card at the top of every sidebar: initials (or profile photo), name as "Last, First",
    and the role in gold. Clicking it opens Settings (your profile).
--}}
@php
    $me = auth()->user();

    // e.g. ['Super Admin', 'IATO', 'bi-star-fill'] (see User::roleInfo())
    [$roleLabel, $office, $roleIcon] = $me->roleInfo();

    // "Cherrie Villafuerte" -> "Villafuerte, Cherrie" and "CV" (a suffix like FSC stays at the end; see User.php)
    $displayName = $me->displayName();
    $initials = $me->initials();
@endphp
<a href="{{ url('/settings') }}" class="signed-in d-flex align-items-center gap-3 rounded-3 p-2 ps-3 mb-3 text-decoration-none text-white" title="Your profile and settings">
    <span class="signed-in-avatar rounded-circle fw-bold d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" data-avatar>{{ $initials }}</span>
    <span class="d-flex flex-column lh-sm overflow-hidden">
        <span class="text-white-50 text-uppercase fw-bold tracking-wide signed-in-label">Signed in as</span>
        <span class="fw-bold text-truncate" title="{{ $displayName }}">{{ $displayName }}</span>
        <span class="signed-in-role small fw-semibold lh-sm" title="{{ $roleLabel }} · {{ $office }}"><i class="bi {{ $roleIcon }} me-1"></i>{{ $roleLabel }} · {{ $office }}</span>
    </span>
</a>
