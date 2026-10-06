{{-- Sidebar for the SHS principal. Same look as the admin sidebar, fewer links. --}}
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

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in, and which unit they see --}}
            <div class="rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10 p-3 mb-3">
                <div class="small fw-bold tracking-wide text-iaris-pale text-uppercase mb-1">Signed in as</div>
                <div class="fw-bold">{{ auth()->user()->name }}</div>
                <div class="small text-white-50">SHS Principal — Senior High School</div>
            </div>

            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MAIN</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/shs') }}" class="nav-link {{ request()->is('shs') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                {{-- IS Records shows only Senior High for this user (see IsRecordController) --}}
                <a href="{{ url('/records/is') }}" class="nav-link {{ request()->is('records/is*') ? 'active' : '' }}"><i class="bi bi-people"></i> Applicants</a>
                {{-- TODO: SHS-only Reports and Analytics. The admin pages show every unit, so these wait. --}}
                <a href="#" class="nav-link opacity-50" aria-disabled="true" title="Coming soon"><i class="bi bi-file-earmark-text"></i> Reports</a>
                <a href="#" class="nav-link opacity-50" aria-disabled="true" title="Coming soon"><i class="bi bi-bar-chart-line"></i> Analytics</a>
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
