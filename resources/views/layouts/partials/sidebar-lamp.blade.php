{{-- Sidebar for LAMP Office users: Dashboard and Reports, Scholars, Messages. Same look as the admin sidebar. --}}
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
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

            {{-- The red number: reports from IATO you haven't opened (the Reports page keeps it up to date) --}}
            @php
                $newReports = \App\Support\LampOffice::unreadReports();
                $answeredMessages = \App\Http\Controllers\MessagesController::answeredMessages();
            @endphp
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">MAIN</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/lamp') }}" class="nav-link {{ request()->is('lamp') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Dashboard</a>
                <a href="{{ url('/lamp/reports') }}" class="nav-link {{ request()->is('lamp/reports*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> Reports
                    <span class="badge rounded-pill text-bg-danger ms-auto {{ $newReports ? '' : 'd-none' }}" data-lamp-reports-badge>{{ $newReports }}</span>
                </a>
            </nav>
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1 mt-3">RECORDS</div>
            <nav class="nav nav-pills flex-column">
                <a href="{{ url('/lamp/scholars') }}" class="nav-link {{ request()->is('lamp/scholars*') ? 'active' : '' }}"><i class="bi bi-award"></i> Scholars</a>
            </nav>
            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1 mt-3">HELP</div>
            <nav class="nav nav-pills flex-column">
                {{-- The number: conversations that need you (IATO's answers and scholar checks IATO asked) --}}
                <a href="{{ url('/lamp/messages') }}" class="nav-link {{ request()->is('lamp/messages*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots"></i> Messages
                    <span class="badge rounded-pill nav-count ms-auto {{ $answeredMessages ? '' : 'd-none' }}" id="messagesBadge" title="Needs you">{{ $answeredMessages }}</span>
                </a>
            </nav>
            <script>
                // Reports you opened are remembered in this browser (see the Reports page)
                (() => {
                    const ids = @json(collect(\App\Support\LampOffice::reports())->pluck('id'));
                    let read = @json(\App\Support\LampOffice::READ_REPORTS);
                    try { read = JSON.parse(localStorage.getItem('iaris.lampReportsRead')) || read; } catch (e) {}
                    const n = ids.filter(id => !read.includes(id)).length;
                    document.querySelectorAll('[data-lamp-reports-badge]').forEach(e => { e.textContent = n; e.classList.toggle('d-none', !n); });
                })();
            </script>
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
