{{-- Layout for pages shown before signing in (splash, login) --}}
<!DOCTYPE html>
<html lang="en">

<head>
    @include('partials.head', ['authPage' => true])
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
    <title>@yield('title', 'iARIS')</title>
</head>

<body class="auth-page @yield('body-class')">
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>

</html>
