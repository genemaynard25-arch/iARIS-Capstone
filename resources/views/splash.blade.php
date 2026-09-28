<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iARIS — Loading</title>
    <noscript><meta http-equiv="refresh" content="0;url={{ $next }}"></noscript>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --emerald-1: #00674F;
            --emerald-2: #005C46;
            --emerald-3: #00503D;
            --emerald-4: #003D2E;
            --emerald-pale: #A8D5C5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(150deg, var(--emerald-1) 0%, var(--emerald-3) 50%, var(--emerald-4) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
            position: relative;
            overflow: hidden;
        }

        .bg-circle { position: absolute; border-radius: 50%; background: rgba(255, 255, 255, 0.03); pointer-events: none; }
        .bg-circle.c1 { width: 500px; height: 500px; top: -160px; right: -160px; }
        .bg-circle.c2 { width: 380px; height: 380px; bottom: -140px; left: -120px; }
        .bg-circle.c3 { width: 200px; height: 200px; bottom: 120px; right: 80px; }

        .splash {
            text-align: center;
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            animation: fadeUp 0.9s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .logo-mark {
            width: 100px;
            height: 100px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.10);
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
            box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.4);
        }

        .logo-mark svg { width: 54px; height: 54px; }

        .splash h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 52px;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .system-name {
            font-size: 13.5px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.75);
            letter-spacing: 0.3px;
            margin-bottom: 6px;
            line-height: 1.6;
        }

        .school-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--emerald-pale);
            margin-bottom: 52px;
        }

        .loader-wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; width: 240px; }
        .loader { width: 100%; height: 3px; border-radius: 4px; background: rgba(255, 255, 255, 0.12); overflow: hidden; }

        .loader-fill {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--emerald-pale), #ffffff);
            animation: load 2.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        .loading-text {
            font-size: 12px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.55);
            letter-spacing: 0.5px;
            transition: opacity 0.2s ease;
        }

        .footer {
            position: absolute;
            bottom: 32px;
            left: 16px;
            right: 16px;
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.3);
            letter-spacing: 0.5px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes load {
            0% { width: 0%; }
            50% { width: 70%; }
            80% { width: 88%; }
            100% { width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            .splash { animation: none; }
            .loader-fill { animation-duration: 0.01s; }
        }
    </style>
</head>

<body>
    <div class="bg-circle c1"></div>
    <div class="bg-circle c2"></div>
    <div class="bg-circle c3"></div>

    <div class="splash">
        <div class="logo-mark">@include('partials.logo-mark')</div>

        <h1>iARIS</h1>
        <p class="system-name">IATO Admissions Records and Information System</p>
        <p class="school-name">De La Salle Lipa</p>

        <div class="loader-wrap">
            <div class="loader"><div class="loader-fill"></div></div>
            <div class="loading-text" id="loadingText" aria-live="polite">Initializing system...</div>
        </div>
    </div>

    <div class="footer">© 2026 Institutional Admissions and Testing Office · De La Salle Lipa</div>

    <script>
        const messages = [
            'Initializing system...',
            'Checking your account...',
            'Loading dashboard...',
            'Almost ready...'
        ];
        const el = document.getElementById('loadingText');
        let i = 0;
        const interval = setInterval(() => {
            i++;
            if (i < messages.length) {
                el.style.opacity = 0;
                setTimeout(() => {
                    el.textContent = messages[i];
                    el.style.opacity = 1;
                }, 200);
            } else {
                clearInterval(interval);
            }
        }, 700);

        // Go on to the login page (or the dashboard if already signed in) once the bar fills.
        setTimeout(() => window.location.replace(@json($next)), 2800);
    </script>
</body>

</html>
