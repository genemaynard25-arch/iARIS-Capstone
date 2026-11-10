<div class="app-topbar bg-white rounded-4 shadow-sm px-4 py-3 mb-4 d-flex align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-primary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div>
            @php
                // Offices say where they are: "Dean · CITE", "Director · iPACE"
                $crumbUser = auth()->user();
                $crumbRoot = $crumbUser->isToic() ? 'TOIC' : ($crumbUser->role === 'iato_staff' ? 'Staff' : $crumbUser->roleInfo()[0]);
                if ($crumbUser->isDeanOrChair() || $crumbUser->isPrincipalOffice() || $crumbUser->isDirector()) {
                    $crumbRoot .= ' · ' . $crumbUser->roleInfo()[1];
                }
            @endphp
            @hasSection('page-breadcrumb')
                <div class="ac-breadcrumb">@yield('page-breadcrumb-root', in_array($crumbUser->role, ['admin', 'iato_staff']) ? 'System' : $crumbRoot) <i class="bi bi-chevron-right" aria-hidden="true"></i> <span>@yield('page-breadcrumb')</span></div>
            @else
                <div class="ac-breadcrumb">{{ $crumbRoot }} <i class="bi bi-chevron-right" aria-hidden="true"></i> <span>@yield('page-title', 'Dashboard')</span></div>
            @endif
            <h1 class="fs-5 fw-bold mb-0">@yield('page-title', 'Dashboard')</h1>
            <p class="small text-body-secondary mb-0">@yield('page-subtitle')</p>
        </div>
    </div>

    <div class="app-topbar-actions d-flex align-items-center gap-3">
        @yield('page-actions')
        @unless (auth()->user()->isToic())
        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis px-3 py-2 d-none d-sm-inline-flex align-items-center gap-2">
            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> System online
        </span>
        @endunless
        <div class="icon-circle rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center overflow-hidden" title="{{ auth()->user()->name }}" data-avatar data-initials="{{ auth()->user()->initials() }}">
            {{ auth()->user()->initials() }}
        </div>
    </div>
</div>
