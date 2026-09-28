@extends('layouts.guest')

@section('title', 'iARIS — Loading')
@section('body-class', 'bg-iaris bg-iaris-circles text-white text-center min-vh-100 d-flex flex-column align-items-center justify-content-center p-3')

@section('content')
    <noscript><meta http-equiv="refresh" content="0;url={{ $next }}"></noscript>

    <div class="d-inline-flex p-4 mb-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25 shadow-lg">
        @include('partials.logo-mark', ['size' => 54])
    </div>

    <h1 class="display-3 fw-bold font-brand mb-1">iARIS</h1>
    <p class="small text-white-50 mb-1">IATO Admissions Records and Information System</p>
    <p class="small fw-bold text-uppercase tracking-wide text-iaris-pale mb-5">De La Salle Lipa</p>

    <div class="progress bg-white bg-opacity-10 mb-2" style="width: 240px; height: 3px;" role="progressbar" aria-label="Loading">
        <div class="progress-bar bg-white" id="loadingBar" style="width: 0%;"></div>
    </div>
    <div class="small text-white-50" id="loadingText" aria-live="polite">Initializing system...</div>

    <div class="position-absolute bottom-0 mb-4 px-3 small text-white-50">© 2026 Institutional Admissions and Testing Office · De La Salle Lipa</div>
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
                text.textContent = step.text;
            }, i * 700);
        });

        // Then go on to the login page (or the dashboard if already signed in).
        setTimeout(() => window.location.replace(@json($next)), steps.length * 700);
    </script>
@endsection
