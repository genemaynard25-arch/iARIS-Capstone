{{-- Layout for signed-in pages: sidebar + topbar + page content --}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    <title>@yield('title', 'iARIS')</title>
</head>

<body>
    <div class="d-flex min-vh-100">
        {{-- Each kind of user gets their own sidebar. The /lamp, /shs and /chancellor pages use theirs too,
             so admins can preview them. (isLamp / isShsPrincipal are in app/Models/User.php)
             TODO (RBAC): keep these users out of the admin pages. --}}
        @if (request()->is('lamp*') || auth()->user()->isLamp())
            @include('layouts.partials.sidebar-lamp')
        @elseif (request()->is('shs*') || auth()->user()->isShsPrincipal())
            @include('layouts.partials.sidebar-shs')
        @elseif (request()->is('chancellor*') || auth()->user()->isChancellor())
            @include('layouts.partials.sidebar-chancellor')
        @else
            @include('layouts.partials.sidebar')
        @endif

        <main class="flex-grow-1 p-3 p-sm-4 p-lg-5" style="min-width: 0;">
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
    @yield('scripts')
    @include('partials.ai-widget')
    <script>
        // "Show the AI button on every page" off
        if (IARIS_SETTINGS.ai?.button === false) document.getElementById('aiWidget')?.classList.add('d-none');
    </script>
</body>

</html>
