<aside class="iaris-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="iarisSidebar" aria-labelledby="iarisSidebarLabel">
    <div class="offcanvas-body">

        <div class="iaris-logo d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="iaris-logo-mark d-flex align-items-center justify-content-center rounded-3">
                    <span class="text-white fw-bold">i</span>
                </div>
                <div class="ms-2">
                    <div class="text-white fw-bold fs-5" id="iarisSidebarLabel">iARIS</div>
                    <div class="text-white-50 small">IATO · De La Salle Lipa</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#iarisSidebar" aria-label="Close"></button>
        </div>
<div class="iaris-nav-scroll">
        <nav class="iaris-nav-section">
            <div class="iaris-nav-label">MAIN</div>
            <a href="{{ url('/home') }}" class="iaris-nav-item {{ request()->is('home') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-people"></i> Applicants</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-file-earmark-text"></i> Reports</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-bar-chart-line"></i> Analytics</a>
            <a href="{{ url('/ai-chat') }}" class="iaris-nav-item {{ request()->is('ai-chat') ? 'active' : '' }}"><i class="bi bi-stars"></i> AI Assistant</a>
        </nav>

        <nav class="iaris-nav-section">
            <div class="iaris-nav-label">RECORDS</div>
            <a href="#" class="iaris-nav-item"><i class="bi bi-mortarboard"></i> College</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-building"></i> Senior High</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-award"></i> Scholars</a>
            {{-- RBAC: wrap in @can('import-applicants') --}}
            <a href="{{ url('/import') }}" class="iaris-nav-item {{ request()->is('import*') ? 'active' : '' }}"><i class="bi bi-cloud-arrow-up"></i> Import Data</a>
        </nav>

        {{-- RBAC: admin only --}}
        <nav class="iaris-nav-section">
            <div class="iaris-nav-label">SYSTEM</div>
            <a href="#" class="iaris-nav-item"><i class="bi bi-shield-lock"></i> Access Control</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-person-badge"></i> Staff Accounts</a>
            <a href="#" class="iaris-nav-item"><i class="bi bi-clipboard-data"></i> Audit Logs</a>
        </nav>
</div>
        <div class="iaris-sidebar-footer">
            <a href="{{ url('/two-factor') }}" class="iaris-nav-item"><i class="bi bi-gear"></i> Settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="iaris-nav-item"><i class="bi bi-box-arrow-right"></i> Log Out</button>
            </form>
        </div>

    </div>
</aside>