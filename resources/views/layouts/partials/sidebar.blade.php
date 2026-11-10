{{-- On large screens this is a fixed sidebar; on small screens it slides in (Bootstrap offcanvas) --}}
@php
    $user = auth()->user();

    // One sidebar link. $match is the URL pattern that makes it "active"; $show hides it when false;
    // $badge is a count shown on the right (0 = none).
    $link = fn ($label, $icon, $url, $match = null, $show = true, $badge = 0) => compact('label', 'icon', 'url', 'match', 'show', 'badge');

    // Only the real Super Admin (not a Temporary Admin) can hand over the role
    $isSuperAdmin = $user->role === 'admin' && ! $user->isShsPrincipal();

    // IATO Staff get a "My Work" section instead of the admin's System section (see below)
    $isStaff = $user->role === 'iato_staff';

    // The menu. A 'group' is a dropdown of links.
    $sections = [
        'MAIN' => [
            $link('Dashboard', 'bi-grid-1x2', '/home', 'home', $user->canAccess('dashboard')),
            $link('Applicants', 'bi-people', '/applicants', 'applicants*', $user->canAccess('applicants')),
            // One page with Overview / AI Insights / Reports tabs (Reports-only roles land on the Reports tab)
            $link('Analytics & Reports', 'bi-bar-chart-line', '/analytics', 'analytics*', $user->canAccess('analytics') || $user->canAccess('reports')),
            // The number: conversations waiting for IATO's reply
            $link('Messages', 'bi-chat-dots', '/messages', 'messages*', $isSuperAdmin || $isStaff, ($isSuperAdmin || $isStaff) ? \App\Http\Controllers\IatoMessagesController::needsReply() : 0),
        ],
        'RECORDS' => [
            ['group' => 'Academic Divisions', 'icon' => 'bi-bank', 'items' => [
                $link('Integrated School', 'bi-building', '/records/is', 'records/is*', $user->canAccess('is')),
                $link('College', 'bi-mortarboard', '/records/college', 'records/college*', $user->canAccess('college')),
                $link('Graduate Programs', 'bi-journal-bookmark', '/records/graduate', 'records/graduate*'),
                $link('College of Law', 'bi-bank2', '/records/law', 'records/law*'),
            ]],
            ['group' => 'Alternative Learning', 'icon' => 'bi-book', 'items' => [
                $link('iPACE', 'bi-laptop', '/records/ipace', 'records/ipace*'),
                $link('ETEEAP', 'bi-briefcase', '/records/eteeap', 'records/eteeap*'),
            ]],
            $link('Scholarships', 'bi-award', '/records/scholars', 'records/scholars*', $user->canAccess('scholars')),
        ],
        'SYSTEM' => [
            ['group' => 'Data Management', 'icon' => 'bi-database', 'items' => [
                $link('Import Data', 'bi-cloud-arrow-up', '/import', 'import*', $user->canAccess('import')),
                $link('Review Queue', 'bi-list-check', '/review-queue', 'review-queue*', $user->isAdmin(), $user->isAdmin() ? \App\Http\Controllers\ReviewQueueController::waitingCount() : 0),
                $link('Audit Logs', 'bi-clipboard-data', '/audit-logs', 'audit-logs*', $user->canAccess('audit')),
            ]],
            ['group' => 'Access & Security', 'icon' => 'bi-shield-lock', 'items' => [
                $link('Access Control', 'bi-shield-check', '/access-control', 'access-control*', $user->isAdmin()),
                $link('User Accounts', 'bi-person-gear', '/accounts', 'accounts*', $user->isAdmin(), $user->isAdmin() ? \App\Support\AccessRequests::waitingCount() : 0),
                $link('Super User Succession', 'bi-people', '/succession', 'succession*', $isSuperAdmin),
            ]],
        ],
    ];

    if ($isStaff) {
        unset($sections['SYSTEM']);
        $sections['MY WORK'] = [
            $link('Import Data', 'bi-cloud-arrow-up', '/import', 'import*', $user->canAccess('import')),
            // The number: submissions the Super Admin sent back to you
            $link('My Submissions', 'bi-send', '/submissions', 'submissions*', true, \App\Http\Controllers\SubmissionsController::counts()['sent']),
            $link('Audit Logs', 'bi-clipboard-data', '/audit-logs', 'audit-logs*', $user->canAccess('audit')),
        ];
        // While they are a Temporary Admin, they also get the admin pages
        if ($user->isAdmin()) {
            $sections['ADMIN (TEMPORARY)'] = [
                $link('Review Queue', 'bi-list-check', '/review-queue', 'review-queue*', true, \App\Http\Controllers\ReviewQueueController::waitingCount()),
                $link('Access Control', 'bi-shield-check', '/access-control', 'access-control*'),
                $link('User Accounts', 'bi-person-gear', '/accounts', 'accounts*', true, \App\Support\AccessRequests::waitingCount()),
            ];
        }
    }

    // Drop links this user can't open. A group left with no links disappears;
    // a group left with one link shows as that plain link (no one-item dropdowns).
    foreach ($sections as $title => $entries) {
        $kept = [];
        foreach ($entries as $entry) {
            if (isset($entry['group'])) {
                $entry['items'] = array_values(array_filter($entry['items'], fn ($i) => $i['show']));
                if (count($entry['items']) === 1) {
                    $kept[] = $entry['items'][0];
                } elseif (count($entry['items']) > 1) {
                    $kept[] = $entry;
                }
            } elseif ($entry['show']) {
                $kept[] = $entry;
            }
        }
        $sections[$title] = $kept;
    }

    $isActive = fn ($item) => $item['match'] && request()->is($item['match']);
@endphp
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                @hasSection('sidebar-brand')
                    @yield('sidebar-brand')
                @else
                    @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
                @endif
                <div>
                    <div class="fw-bold fs-5 font-brand" id="sidebarLabel">iARIS</div>
                    <div class="small text-white-50">{{ $isStaff ? 'Staff · IATO' : 'IATO · De La Salle Lipa' }}</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        {{-- Links (this part scrolls if the window is too short) --}}
        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            @include('layouts.partials.signed-in')

            @foreach ($sections as $title => $entries)
                @continue(empty($entries))
                <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">{{ $title }}</div>
                <nav class="nav nav-pills flex-column mb-3">
                    @foreach ($entries as $entry)
                        @if (isset($entry['group']))
                            {{-- Dropdown group: open when the current page is inside it --}}
                            @php($open = collect($entry['items'])->contains($isActive))
                            @php($id = 'nav-' . \Illuminate\Support\Str::slug($entry['group']))
                            <button type="button" class="nav-link nav-group-toggle {{ $open ? 'has-active' : '' }}" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $id }}">
                                <i class="bi {{ $entry['icon'] }}"></i> {{ $entry['group'] }}
                                {{-- A dot while closed if something inside is waiting --}}
                                @if (collect($entry['items'])->sum('badge'))
                                    <span class="nav-dot rounded-circle" title="Items waiting"></span>
                                @endif
                                <i class="bi bi-chevron-down chev ms-auto"></i>
                            </button>
                            <div class="collapse {{ $open ? 'show' : '' }}" id="{{ $id }}">
                                <div class="nav nav-pills flex-column nav-sub">
                                    @foreach ($entry['items'] as $item)
                                        <a href="{{ url($item['url']) }}" class="nav-link {{ $isActive($item) ? 'active' : '' }}" @if ($isActive($item)) aria-current="page" @endif><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}@if ($item['badge'])<span class="badge rounded-pill nav-count ms-auto" title="Waiting" id="nav-count-{{ \Illuminate\Support\Str::slug($item['label']) }}">{{ $item['badge'] }}</span>@endif</a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ url($entry['url']) }}" class="nav-link {{ $isActive($entry) ? 'active' : '' }}" @if ($isActive($entry)) aria-current="page" @endif><i class="bi {{ $entry['icon'] }}"></i> {{ $entry['label'] }}@if ($entry['badge'])<span class="badge rounded-pill nav-count ms-auto" title="Waiting" id="nav-count-{{ \Illuminate\Support\Str::slug($entry['label']) }}">{{ $entry['badge'] }}</span>@endif</a>
                        @endif
                    @endforeach
                </nav>
            @endforeach
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
