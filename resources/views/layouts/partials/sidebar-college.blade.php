{{-- Sidebar for Deans, Program Chairs, the IS Principal and the iPACE Director. Same look as the Executive sidebar, read-only links. --}}
@php
    $isChair = auth()->user()->isProgramChair();
    $isPrincipal = auth()->user()->isPrincipalOffice();
    $isDirector = auth()->user()->isDirector();
    $college = \App\Http\Controllers\CollegeController::college();
    $code = $college['label'] ?? $college['code'];
    // The pages' address: /principal for the IS Principal, /college for everyone else here
    $base = $isPrincipal ? 'principal' : ($isDirector ? 'director' : 'college');
@endphp
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
                <div>
                    <div class="fw-bold fs-5 font-brand" id="sidebarLabel">iARIS</div>
                    <div class="small text-white-50">{{ $isChair || auth()->user()->isGradChair() ? 'Program Chair View' : ($isPrincipal ? (auth()->user()->isAssistantPrincipal() ? 'Assistant Principal View' : 'Principal View') : ($isDirector ? 'Director View' : 'Dean View')) }}</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            @include('layouts.partials.signed-in')

            @php
                // Badges: unread reports (red; the Reports page updates it) and conversations IATO answered
                $unread = collect(\App\Http\Controllers\CollegeController::received($isChair))->where('status', 'new')->count();
                $answered = \App\Http\Controllers\CollegeController::answeredMessages();
                // [label, what it's for, icon, url, active pattern, badge html]
                $menu = [
                    'MENU' => [
                        ['Briefing', 'Summary and alerts', 'bi-compass', "/{$base}", $base, ''],
                        ['Applicants', ($isChair ? 'BSCS' : $code) . ' · view only', 'bi-people', "/{$base}/applicants", "{$base}/applicants*", ''],
                        ['Analytics', $isPrincipal ? 'Trends, levels, insights' : 'Trends, programs, insights', 'bi-bar-chart-line', "/{$base}/analytics", "{$base}/analytics*", ''],
                        ['Reports', 'From IATO and your own', 'bi-file-earmark-text', "/{$base}/reports", "{$base}/reports*",
                            '<span class="badge rounded-pill bg-danger ms-auto ' . ($unread ? '' : 'd-none') . '" id="reportsBadge">' . $unread . '</span>'],
                    ],
                    'HELP' => [
                        ['Messages', 'Talk with IATO', 'bi-chat-dots', "/{$base}/messages", "{$base}/messages*",
                            '<span class="badge rounded-pill nav-count ms-auto ' . ($answered ? '' : 'd-none') . '" id="messagesBadge" title="IATO answered">' . $answered . '</span>'],
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
            @include('partials.report-problem-link')
            <a href="{{ url('/settings') }}" class="nav-link {{ request()->is('settings*') ? 'active' : '' }}"><i class="bi bi-gear"></i> Settings</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link"><i class="bi bi-box-arrow-right"></i> Log Out</button>
            </form>
        </div>

    </div>
</aside>
