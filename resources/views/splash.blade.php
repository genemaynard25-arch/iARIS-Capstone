@extends('layouts.guest')

@section('title', 'iARIS — Loading')
@section('body-class', 'auth-splash-page text-white text-center min-vh-100 d-flex flex-column align-items-center justify-content-center p-3')

@section('content')
    <noscript><meta http-equiv="refresh" content="0;url={{ $next }}"></noscript>

    <div class="auth-splash-circle auth-splash-circle--one" aria-hidden="true"></div>
    <div class="auth-splash-circle auth-splash-circle--two" aria-hidden="true"></div>
    <div class="auth-splash-circle auth-splash-circle--three" aria-hidden="true"></div>

    <main class="auth-splash-content d-flex flex-column align-items-center">
        @include('partials.auth-logo', ['size' => 100, 'variant' => 'glass'])

        <h1 class="font-brand">iARIS</h1>
        <p class="auth-splash-system">IATO Admissions Records and Information System</p>
        <p class="auth-splash-school font-brand text-uppercase text-iaris-pale">De La Salle Lipa</p>

        <div class="auth-splash-loader progress" id="loadingProgress" role="progressbar" aria-label="Loading iARIS" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="progress-bar" id="loadingBar" style="width: 0%;"></div>
        </div>
        <div class="auth-splash-status" id="loadingText" aria-live="polite">Initializing system...</div>
    </main>
    <footer class="auth-splash-footer">© {{ date('Y') }} Institutional Admissions and Testing Office · De La Salle Lipa</footer>
@endsection

@section('scripts')
    <script>
        // Each step fills more of the bar and changes the message.
        const steps = [
            { width: 30, text: 'Initializing system...' },
            { width: 60, text: 'Checking your account...' },
            { width: 85, text: 'Loading dashboard...' },
            { width: 100, text: 'Almost ready...' },
        ];
        const bar = document.getElementById('loadingBar');
        const text = document.getElementById('loadingText');

        steps.forEach((step, i) => {
            setTimeout(() => {
                bar.style.width = step.width + '%';
                document.getElementById('loadingProgress').setAttribute('aria-valuenow', step.width);
                text.textContent = step.text;
            }, i * 700);
        });

        // Then go on to the login page (or the dashboard if already signed in).
        setTimeout(() => window.location.replace(@json($next)), steps.length * 700);
    </script>
@endsection
