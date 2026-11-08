{{-- Sidebar for LAMP Office users: just their three pages. Same look as the admin sidebar. --}}
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                @include('partials.logo-mark', ['size' => 40])
                <div>
                    <div class="fw-bold fs-5 font-brand" id="sidebarLabel">iARIS</div>
                    <div class="small text-white-50">LAMP Office</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            @include('layouts.partials.signed-in')

            {{-- The numbers: requests from IATO (amber) and unread reports (red) --}}
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MENU</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/lamp') }}" class="nav-link {{ request()->is('lamp') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                <a href="{{ url('/lamp/scholars') }}" class="nav-link {{ request()->is('lamp/scholars*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> Scholars
                    {{-- Amber: requests from IATO waiting for your answer (the Scholars page updates it) --}}
                    <span class="badge rounded-pill text-bg-warning ms-auto {{ ($navCounts['requests'] ?? 0) ? '' : 'd-none' }}" id="navRequestsBadge"
                          title="Requests from IATO">{{ $navCounts['requests'] ?? 0 }}</span>
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
            @include('partials.report-problem-link')
            <a href="{{ url('/settings') }}" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}"><i class="bi bi-gear"></i> Settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link"><i class="bi bi-box-arrow-right"></i> Log Out</button>
            </form>
        </div>

    </div>
</aside>
