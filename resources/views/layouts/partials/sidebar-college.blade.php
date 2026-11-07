{{-- Sidebar for Deans and Program Chairs. Same look as the admin sidebar, read-only links. --}}
@php
    $isChair = auth()->user()->isProgramChair();
@endphp
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
                    <div class="small text-white-50">{{ $isChair ? 'Program Chair · BSCS' : 'Dean · CITE' }}</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            <div class="rounded-3 bg-white bg-opacity-10 border border-white border-opacity-10 p-3 mb-3">
                <div class="small fw-bold tracking-wide text-iaris-pale text-uppercase mb-1">Signed in as</div>
                <div class="fw-bold">{{ auth()->user()->name }}</div>
                <div class="small text-white-50">{{ $isChair ? 'Program Chair · BS Computer Science' : 'Dean · CITE' }}</div>
            </div>

            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MENU</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/college') }}" class="nav-link {{ request()->is('college') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                <a href="{{ url('/college/applicants') }}" class="nav-link {{ request()->is('college/applicants*') ? 'active' : '' }}"><i class="bi bi-people"></i> Applicants</a>
                {{-- TODO: Analytics and Reports pages scoped to the college / program --}}
                <a href="#" class="nav-link opacity-50" aria-disabled="true" title="Coming soon"><i class="bi bi-bar-chart-line"></i> Analytics</a>
                <a href="#" class="nav-link opacity-50" aria-disabled="true" title="Coming soon"><i class="bi bi-file-earmark-text"></i> Reports</a>
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
