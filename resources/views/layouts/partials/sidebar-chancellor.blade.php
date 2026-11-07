{{-- Sidebar for the President / Chancellor office. Same look as the admin sidebar, read-only links. --}}
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
                    <div class="small text-white-50">President / Chancellor</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            @include('layouts.partials.signed-in')

            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MENU</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/chancellor') }}" class="nav-link {{ request()->is('chancellor') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                <a href="{{ url('/chancellor/analytics') }}" class="nav-link {{ request()->is('chancellor/analytics*') ? 'active' : '' }}"><i class="bi bi-bar-chart-line"></i> Analytics</a>
                {{-- The badge counts unread reports (the Reports page updates it as you open them) --}}
                @php
                    $unread = collect(\App\Http\Controllers\ChancellorController::received())->where('status', 'new')->count();
                @endphp
                <a href="{{ url('/chancellor/reports') }}" class="nav-link {{ request()->is('chancellor/reports*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Reports
                    <span class="badge rounded-pill bg-danger ms-auto {{ $unread ? '' : 'd-none' }}" id="reportsBadge">{{ $unread }}</span></a>
            </nav>
        </div>

        {{-- Footer (always visible) --}}
        <div class="nav nav-pills flex-column px-3 py-3 border-top border-white border-opacity-25">
            <a href="{{ url('/settings') }}" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}"><i class="bi bi-gear"></i> Settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link"><i class="bi bi-box-arrow-right"></i> Log Out</button>
            </form>
        </div>

    </div>
</aside>
