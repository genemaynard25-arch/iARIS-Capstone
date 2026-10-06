{{-- Sidebar for LAMP Office users: just their three pages. Same look as the admin sidebar. --}}
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-10 border border-white border-opacity-25 icon-circle">
                    @include('partials.logo-mark', ['size' => 24])
                </div>
                <div>
                    <div class="fw-bold fs-5 font-brand" id="sidebarLabel">iARIS</div>
                    <div class="small text-white-50">LAMP Office</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            <div class="d-flex align-items-center gap-2 rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10 p-2 mb-3">
                <div class="icon-circle rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="small lh-sm">
                    <div class="fw-bold">{{ auth()->user()->name }}</div>
                    <div class="text-white-50">LAMP Office Staff</div>
                </div>
            </div>

            {{-- The red numbers: records that need review, and unread reports --}}
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MENU</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/lamp') }}" class="nav-link {{ request()->is('lamp') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                <a href="{{ url('/lamp/scholars') }}" class="nav-link {{ request()->is('lamp/scholars*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> Scholars
                    @if ($navCounts['scholars'] ?? 0)
                        <span class="badge rounded-pill text-bg-danger ms-auto">{{ $navCounts['scholars'] }}</span>
                    @endif
                </a>
                <a href="{{ url('/lamp/reports') }}" class="nav-link {{ request()->is('lamp/reports*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> Reports
                    @if ($navCounts['reports'] ?? 0)
                        <span class="badge rounded-pill text-bg-danger ms-auto" id="navReportsBadge">{{ $navCounts['reports'] }}</span>
                    @endif
                </a>
            </nav>
        </div>

        {{-- Footer (always visible) --}}
        <div class="nav nav-pills flex-column px-3 py-3 border-top border-white border-opacity-25">
            <a href="{{ url('/two-factor') }}" class="nav-link"><i class="bi bi-gear"></i> Settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link"><i class="bi bi-box-arrow-right"></i> Log Out</button>
            </form>
        </div>

    </div>
</aside>
