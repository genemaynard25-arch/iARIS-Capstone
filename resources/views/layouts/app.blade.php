{{-- Layout for signed-in pages: sidebar + topbar + page content --}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    <title>@yield('title', 'iARIS')</title>
</head>

<body>
    <div class="d-flex min-vh-100">
        {{-- LAMP Office users get their own short sidebar. The /lamp pages use it too, so admins can preview them.
             TODO (RBAC): also send 'lamp' users to /lamp after login, and keep them out of the admin pages. --}}
        @if (request()->is('lamp*') || auth()->user()->role === 'lamp')
            @include('layouts.partials.sidebar-lamp')
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
