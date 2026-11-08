{{-- Sidebar for Registrar users: their pages, then the records they can view (not edit). Same look as the admin sidebar. --}}
@php
    $answered = \App\Support\RegistrarQuestions::answeredCount();
    $link = fn ($label, $icon, $url, $match) => ['label' => $label, 'icon' => $icon, 'url' => $url, 'active' => request()->is($match)];
    $sections = [
        'MAIN' => [
            $link('Dashboard', 'bi-grid-1x2', '/registrar', 'registrar'),
            $link('Ready for Enrollment', 'bi-clipboard-check', '/registrar/enrollment', 'registrar/enrollment*'),
            $link('Reports', 'bi-file-earmark-text', '/registrar/reports', 'registrar/reports*'),
        ],
        // IS Registrar: only the Integrated School
        'RECORDS · VIEW ONLY' => \App\Support\RegistrarData::scope() === 'is' ? [
            $link('Integrated School', 'bi-building', '/records/is', 'records/is*'),
        ] : [
            $link('College', 'bi-mortarboard', '/records/college', 'records/college*'),
            $link('Graduate Programs', 'bi-journal-bookmark', '/records/graduate', 'records/graduate*'),
            $link('College of Law', 'bi-bank2', '/records/law', 'records/law*'),
            $link('iPACE', 'bi-laptop', '/records/ipace', 'records/ipace*'),
            $link('ETEEAP', 'bi-briefcase', '/records/eteeap', 'records/eteeap*'),
        ],
    ];
@endphp
<aside class="sidebar bg-iaris text-white offcanvas-lg offcanvas-start sticky-lg-top vh-100 flex-shrink-0" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
    <div class="offcanvas-body d-flex flex-column h-100 p-0">

        {{-- Logo --}}
        <div class="d-flex align-items-center justify-content-between px-4 py-4 border-bottom border-white border-opacity-25">
            <div class="d-flex align-items-center gap-2">
                @include('partials.logo-mark', ['size' => 40])
                <div>
                    <div class="fw-bold fs-5 font-brand" id="sidebarLabel">iARIS</div>
                    <div class="small text-white-50">{{ auth()->user()->assigned_level === 'is' ? 'IS Registrar' : 'College Registrar' }}</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="flex-grow-1 overflow-auto px-3 py-3">
            {{-- Who is signed in --}}
            @include('layouts.partials.signed-in')

            @foreach ($sections as $title => $links)
                <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">{{ $title }}</div>
                <nav class="nav nav-pills flex-column mb-3">
                    @foreach ($links as $item)
                        <a href="{{ url($item['url']) }}" class="nav-link {{ $item['active'] ? 'active' : '' }}" @if ($item['active']) aria-current="page" @endif><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
                    @endforeach
                </nav>
            @endforeach

            <div class="small fw-bold tracking-wide text-iaris-pale px-3 mb-1">HELP</div>
            <nav class="nav nav-pills flex-column">
                {{-- Amber: questions IATO answered (the Questions page keeps it up to date) --}}
                <a href="{{ url('/registrar/questions') }}" class="nav-link {{ request()->is('registrar/questions*') ? 'active' : '' }}">
                    <i class="bi bi-question-circle"></i> Questions to IATO
                    <span class="badge rounded-pill nav-count ms-auto {{ $answered ? '' : 'd-none' }}" id="navAnsweredBadge" title="IATO answered">{{ $answered }}</span>
                </a>
                {{-- Messages: everything else for IATO (Questions to IATO are about one record) --}}
                @php
                    $answeredMessages = \App\Http\Controllers\MessagesController::answeredMessages();
                @endphp
                <a href="{{ url('/registrar/messages') }}" class="nav-link {{ request()->is('registrar/messages*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots"></i> Messages
                    <span class="badge rounded-pill nav-count ms-auto {{ $answeredMessages ? '' : 'd-none' }}" id="messagesBadge" title="IATO answered">{{ $answeredMessages }}</span>
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
