<div class="iaris-topbar d-flex align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-success btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#iarisSidebar" aria-controls="iarisSidebar" aria-label="Open menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div>
            <h2 class="fs-5 fw-bold mb-0">@yield('page-title', 'Dashboard')</h2>
            <p class="iaris-text-soft small mb-0">@yield('page-subtitle')</p>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="iaris-status-pill d-none d-sm-flex align-items-center gap-2">
            <span class="iaris-pulse"></span> System online
        </div>
        <div class="iaris-avatar d-flex align-items-center justify-content-center" title="{{ auth()->user()->name }}">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
    </div>
</div>