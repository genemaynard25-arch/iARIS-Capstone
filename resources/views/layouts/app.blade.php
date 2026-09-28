{{-- Layout for signed-in pages: sidebar + topbar + page content --}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head')
    <title>@yield('title', 'iARIS')</title>
</head>

<body>
    <div class="d-flex min-vh-100">
        @include('layouts.partials.sidebar')

        <main class="flex-grow-1 p-3 p-sm-4 p-lg-5" style="min-width: 0;">
            @include('layouts.partials.topbar')

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>

</html>
