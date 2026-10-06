{{-- On large screens this is a fixed sidebar; on small screens it slides in (Bootstrap offcanvas) --}}
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
                    <div class="small text-white-50">IATO · De La Salle Lipa</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        {{-- Links (this part scrolls if the window is too short) --}}
        <div class="flex-grow-1 overflow-auto px-3 py-3">
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MAIN</div>
            <nav class="nav nav-pills flex-column mb-3">
                <a href="{{ url('/home') }}" class="nav-link {{ request()->is('home') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                <a href="{{ url('/applicants') }}" class="nav-link {{ request()->is('applicants*') ? 'active' : '' }}"><i class="bi bi-people"></i> Applicants</a>
                <a href="{{ url('/reports') }}" class="nav-link {{ request()->is('reports*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Reports</a>
                <a href="{{ url('/analytics') }}" class="nav-link {{ request()->is('analytics*') ? 'active' : '' }}"><i class="bi bi-bar-chart-line"></i> Analytics</a>
                <a href="{{ url('/ai-chat') }}" class="nav-link {{ request()->is('ai-chat') ? 'active' : '' }}"><i class="bi bi-stars"></i> AI Assistant</a>
            </nav>

            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">RECORDS</div>
            <nav class="nav nav-pills flex-column mb-3">
                <a href="{{ url('/records/is') }}" class="nav-link {{ request()->is('records/is*') ? 'active' : '' }}"><i class="bi bi-building"></i> Integrated School</a>
                <a href="{{ url('/records/college') }}" class="nav-link {{ request()->is('records/college*') ? 'active' : '' }}"><i class="bi bi-mortarboard"></i> College</a>
                <a href="{{ url('/records/scholars') }}" class="nav-link {{ request()->is('records/scholars*') ? 'active' : '' }}"><i class="bi bi-award"></i> Scholars</a>
                <a href="{{ url('/records/graduate') }}" class="nav-link {{ request()->is('records/graduate*') ? 'active' : '' }}"><i class="bi bi-journal-bookmark"></i> Graduate Programs</a>
                <a href="{{ url('/records/law') }}" class="nav-link {{ request()->is('records/law*') ? 'active' : '' }}"><i class="bi bi-bank2"></i> College of Law</a>
                <a href="{{ url('/records/ipace') }}" class="nav-link {{ request()->is('records/ipace*') ? 'active' : '' }}"><i class="bi bi-laptop"></i> iPACE</a>
                <a href="#" class="nav-link"><i class="bi bi-briefcase"></i> ETEEAP</a>
                {{-- RBAC: wrap in @can('import-applicants') --}}
                <a href="{{ url('/import') }}" class="nav-link {{ request()->is('import*') ? 'active' : '' }}"><i class="bi bi-cloud-arrow-up"></i> Import Data</a>
            </nav>

            {{-- RBAC: admin only --}}
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">SYSTEM</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/access-control') }}" class="nav-link {{ request()->is('access-control*') ? 'active' : '' }}"><i class="bi bi-shield-lock"></i> Access Control</a>
                <a href="{{ url('/accounts') }}" class="nav-link {{ request()->is('accounts*') ? 'active' : '' }}"><i class="bi bi-person-gear"></i> User Accounts</a>
                <a href="#" class="nav-link"><i class="bi bi-clipboard-data"></i> Audit Logs</a>
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
