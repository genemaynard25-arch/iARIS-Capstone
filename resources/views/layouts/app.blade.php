{{-- Layout for signed-in pages: sidebar + topbar + page content --}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    <title>@yield('title', 'iARIS')</title>
</head>

<body>
    <div class="d-flex min-vh-100">
        {{-- Each kind of user gets their own sidebar. The /lamp and /shs pages use theirs too,
             so admins can preview them. (isLamp / isShsPrincipal are in app/Models/User.php)
             TODO (RBAC): keep these users out of the admin pages. --}}
        @if (request()->is('lamp*') || auth()->user()->isLamp())
            @include('layouts.partials.sidebar-lamp')
        @elseif (request()->is('shs*') || auth()->user()->isShsPrincipal())
            @include('layouts.partials.sidebar-shs')
        @else
            @include('layouts.partials.sidebar')
        @endif

        <main class="flex-grow-1 p-3 p-sm-4 p-lg-5" style="min-width: 0;">
            @include('layouts.partials.topbar')

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>

</html>
