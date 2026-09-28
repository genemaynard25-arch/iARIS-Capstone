<div class="bg-white rounded-4 shadow-sm px-4 py-3 mb-4 d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-primary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div>
            <h1 class="fs-5 fw-bold mb-0">@yield('page-title', 'Dashboard')</h1>
            <p class="small text-body-secondary mb-0">@yield('page-subtitle')</p>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2 d-none d-sm-inline-flex align-items-center gap-2">
            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> System online
        </span>
        <div class="icon-circle rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center" title="{{ auth()->user()->name }}">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
    </div>
</div>
