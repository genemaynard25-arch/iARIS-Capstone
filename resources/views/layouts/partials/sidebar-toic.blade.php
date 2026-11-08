{{-- Sidebar for TOIC (system support). Same look as the Executive sidebar. No "Report a problem": problems come to TOIC. --}}
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                @include('partials.logo-mark', ['size' => 40])
                <div>
                    <div class="fw-bold fs-5 font-brand" id="sidebarLabel">iARIS</div>
                    <div class="small text-white-50">TOIC View</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            @include('layouts.partials.signed-in')

            @php
                // The Issues badge counts problems that need work (filled in by partials/issues-store)
                // [label, what it's for, icon, url, active pattern, badge html]
                $menu = [
                    'MENU' => [
                        ['System Overview', 'Uptime, backups, alerts', 'bi-speedometer2', '/toic', 'toic', ''],
                        ['Issues', 'Problems to fix', 'bi-bug', '/toic/issues', 'toic/issues*',
                            '<span class="badge rounded-pill bg-danger ms-auto d-none" id="issuesBadge"></span>'],
                        ['Security', 'MFA, sign-ins, encryption', 'bi-shield-lock', '/toic/security', 'toic/security*', ''],
                        ['Documentation', 'Features, roles, data lifecycle', 'bi-journal-text', '/toic/docs', 'toic/docs*', ''],
                        ['Budget & Maintenance', 'Costs and who shoulders them', 'bi-cash-coin', '/toic/budget', 'toic/budget*', ''],
                        ['Reports', 'System reports', 'bi-file-earmark-text', '/toic/reports', 'toic/reports*', ''],
                    ],
                    'HELP' => [
                        ['Messages', 'Talk with IATO', 'bi-chat-dots', '/toic/messages', 'toic/messages*', ''],
                    ],
                ];
            @endphp
            @foreach ($menu as $title => $links)
                <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">{{ $title }}</div>
                <nav class="nav nav-pills flex-column mb-3">
                    @foreach ($links as [$label, $hint, $icon, $url, $match, $badge])
                        <a href="{{ url($url) }}" class="nav-link {{ request()->is($match) ? 'active' : '' }}" @if (request()->is($match)) aria-current="page" @endif>
                            <i class="bi {{ $icon }}"></i>
                            <span class="d-flex flex-column lh-sm"><span>{{ $label }}</span><small class="fw-normal text-white-50">{{ $hint }}</small></span>
                            {!! $badge !!}
                        </a>
                    @endforeach
                </nav>
            @endforeach
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
