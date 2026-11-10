{{-- Layout for signed-in pages: sidebar + topbar + page content --}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    <link href="{{ asset('css/app-shell.css') }}" rel="stylesheet">
    @stack('styles')
    <link href="{{ asset('css/sidebar.css') }}" rel="stylesheet">
    @if (auth()->user()->role === 'iato_staff')
        <link href="{{ asset('css/staff.css') }}" rel="stylesheet">
    @endif
    <title>@yield('title', 'iARIS')</title>
</head>

<body class="iaris-workspace @yield('body-class') {{ auth()->user()->role === 'iato_staff' ? 'staff-workspace' : '' }} {{ auth()->user()->isToic() ? 'toic-workspace' : '' }}">
    @hasSection('app-banner')
        @yield('app-banner')
    @else
        @include('partials.institution-bar')
    @endif
    <div class="app-shell d-flex min-vh-100">
        {{-- Each kind of user gets their own sidebar. The /lamp, /shs, /executive and /college pages use theirs too,
             so admins can preview them. (isLamp / isShsPrincipal are in app/Models/User.php)
             TODO (RBAC): keep these users out of the admin pages. --}}
        @if (request()->is('toic*') || auth()->user()->isToic())
            @include('layouts.partials.sidebar-toic')
        @elseif (request()->is('lamp*') || auth()->user()->isLamp())
            @include('layouts.partials.sidebar-lamp')
        @elseif (request()->is('shs*') || auth()->user()->isShsPrincipal())
            @include('layouts.partials.sidebar-shs')
        @elseif (request()->is('executive*') || auth()->user()->isExecutive())
            @include('layouts.partials.sidebar-executive')
        @elseif (request()->is('registrar*') || auth()->user()->role === 'registrar')
            @include('layouts.partials.sidebar-registrar')
        @elseif (request()->is('college*', 'principal*', 'director*') || auth()->user()->isDeanOrChair() || auth()->user()->isPrincipalOffice() || auth()->user()->isDirector())
            @include('layouts.partials.sidebar-college')
        @else
            @include('layouts.partials.sidebar')
        @endif

        <main class="app-main flex-grow-1 p-3 p-sm-4 p-lg-5" style="min-width: 0;">
            {{-- Maintenance notice for everyone (TOIC turns it on in System Overview) --}}
            @include('partials.maintenance-banner')
            @include('layouts.partials.topbar')

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preferences saved on the Settings page (kept in this browser for now, one set per user).
        // Pages read window.IARIS_SETTINGS for their defaults (e.g. Analytics, Reports).
        window.IARIS_SETTINGS = (() => { try { return JSON.parse(localStorage.getItem('iaris.settings.{{ auth()->id() }}')) || {}; } catch (e) { return {}; } })();
        // "Show AI-written takeaways" off: hide anything marked data-ai-content
        if (IARIS_SETTINGS.ai?.takeaways === false) document.querySelectorAll('[data-ai-content]').forEach(el => el.classList.add('d-none'));
        // Profile photo in the top bar
        if (IARIS_SETTINGS.photo) document.querySelectorAll('[data-avatar]').forEach(el => {
            el.innerHTML = '<img alt="" class="w-100 h-100 object-fit-cover">';
            el.firstChild.src = IARIS_SETTINGS.photo;
        });
    </script>
    {{-- Problems reported to TOIC and the maintenance notice: before the page scripts, which use it --}}
    @include('partials.issues-store')
    @yield('scripts')
    {{-- The AI button: not for the LAMP Office, Registrars or TOIC (their mockups have none; Settings has no AI section for them) --}}
    @unless (auth()->user()->isLamp() || auth()->user()->role === 'registrar' || auth()->user()->isToic())
        @include('partials.ai-widget')
    @endunless
    @include('partials.mfa-reminder')
    {{-- "Report a problem" (everyone except TOIC) --}}
    @unless (auth()->user()->isToic())
        @include('partials.report-problem')
    @endunless
    <script>
        // "Show the AI button on every page" off
        if (IARIS_SETTINGS.ai?.button === false) document.getElementById('aiWidget')?.classList.add('d-none');
    </script>
</body>

</html>
