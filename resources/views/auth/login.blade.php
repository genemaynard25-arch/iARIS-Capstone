<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iARIS — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --e1: #00674F;
            --e2: #005C46;
            --e3: #00503D;
            --e4: #003D2E;
            --epale: #A8D5C5;
            --page-bg: #F3F6F5;
            --text-main: #1A2B26;
            --text-soft: rgba(26, 43, 38, 0.55);
            --border: #D2DDD9;
            --danger: #B42318;
            --danger-bg: #FEF3F2;
            --danger-border: #FECDCA;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { min-height: 100vh; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* Left panel */
        .left-panel {
            flex: 1.1;
            background: linear-gradient(160deg, var(--e1) 0%, var(--e3) 60%, var(--e4) 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 40px;
            padding: 52px 60px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before,
        .left-panel::after {
            content: '';
            position: absolute;
            border-radius: 50%;
        }

        .left-panel::before { top: -140px; right: -140px; width: 400px; height: 400px; background: rgba(255, 255, 255, 0.05); }
        .left-panel::after { bottom: -120px; left: -100px; width: 320px; height: 320px; background: rgba(255, 255, 255, 0.04); }

        .lp-logo { display: flex; align-items: center; gap: 16px; position: relative; z-index: 1; }

        .lp-logo .mark {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .lp-logo .mark svg { width: 32px; height: 32px; }
        .lp-logo h1 { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 30px; letter-spacing: 1px; }
        .lp-logo p { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 13.5px; color: rgba(255, 255, 255, 0.75); margin-top: 3px; }

        .lp-content { position: relative; z-index: 1; max-width: 460px; }
        .lp-content h2 { font-family: 'Montserrat', sans-serif; font-size: 36px; font-weight: 800; line-height: 1.3; margin-bottom: 16px; }
        .lp-content p { font-size: 16px; color: rgba(255, 255, 255, 0.82); line-height: 1.75; }

        .lp-roles { position: relative; z-index: 1; }
        .lp-roles-label { font-size: 12px; font-weight: 700; letter-spacing: 2px; color: var(--epale); text-transform: uppercase; margin-bottom: 14px; }
        .role-pill-row { display: flex; flex-wrap: wrap; gap: 10px; }

        .role-pill {
            display: flex;
            align-items: center;
            gap: 9px;
            background: rgba(255, 255, 255, 0.09);
            border: 1px solid rgba(255, 255, 255, 0.14);
            padding: 10px 18px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
        }

        .role-pill i { font-size: 13px; color: var(--epale); }

        /* Right panel */
        .right-panel {
            flex: 1;
            background: var(--page-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 52px 48px;
        }

        .login-card { width: 100%; max-width: 420px; }
        .login-card h2 { font-family: 'Montserrat', sans-serif; font-size: 26px; font-weight: 800; margin-bottom: 8px; }
        .login-card .sub { font-size: 15.5px; color: var(--text-soft); margin-bottom: 34px; line-height: 1.6; }

        /* Alerts */
        .alert {
            display: flex;
            gap: 10px;
            border-radius: 11px;
            padding: 13px 16px;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 22px;
        }

        .alert i { margin-top: 3px; }
        .alert ul { list-style: none; }
        .alert-error { background: var(--danger-bg); border: 1px solid var(--danger-border); color: var(--danger); }
        .alert-success { background: #E6F5EF; border: 1px solid #CBE0D8; color: var(--e2); }

        /* Form */
        .field-group { margin-bottom: 22px; }

        .field-group > label,
        .m-field > label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 9px;
        }

        .input-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1.5px solid var(--border);
            border-radius: 11px;
            padding: 15px 18px;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .input-wrap:focus-within { border-color: var(--e1); box-shadow: 0 0 0 3px rgba(0, 103, 79, 0.09); }
        .input-wrap.has-error { border-color: var(--danger); }
        .input-wrap > i { color: #9BB5AE; font-size: 16px; }

        .input-wrap input,
        .input-wrap select {
            border: none;
            outline: none;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            width: 100%;
            min-width: 0;
            background: transparent;
            color: var(--text-main);
        }

        .input-wrap input::placeholder { color: #B2C5BF; }

        .toggle-pass {
            background: none;
            border: none;
            cursor: pointer;
            color: #9BB5AE;
            font-size: 14px;
            padding: 0;
        }

        .toggle-pass:hover { color: var(--e1); }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            margin: -6px 0 26px;
        }

        .remember { display: flex; align-items: center; gap: 9px; font-size: 14.5px; color: var(--text-soft); cursor: pointer; }
        .remember input { accent-color: var(--e1); width: 16px; height: 16px; }

        .link-btn {
            background: none;
            border: none;
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 700;
            color: var(--e1);
            cursor: pointer;
            padding: 0;
        }

        .link-btn:hover { color: var(--e2); text-decoration: underline; }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--e1), var(--e3));
            color: #fff;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 700;
            padding: 16px;
            border-radius: 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity 0.15s ease, transform 0.1s ease;
            box-shadow: 0 6px 20px -4px rgba(0, 80, 61, 0.38);
        }

        .btn-login:hover { opacity: 0.91; transform: translateY(-1px); }

        .divider { display: flex; align-items: center; gap: 14px; margin: 20px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1.5px; background: var(--border); }
        .divider span { font-size: 13px; font-weight: 600; color: var(--text-soft); }

        .btn-google {
            width: 100%;
            background: #fff;
            border: 1.5px solid var(--border);
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 600;
            padding: 14px;
            border-radius: 11px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: var(--text-main);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .btn-google:hover { border-color: #9BB5AE; box-shadow: 0 2px 8px -2px rgba(0, 0, 0, 0.08); }

        .register-row { text-align: center; margin-top: 22px; font-size: 14.5px; color: var(--text-soft); }
        .copyright { text-align: center; font-size: 12.5px; color: var(--text-soft); margin-top: 20px; }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 30, 22, 0.45);
            align-items: center;
            justify-content: center;
            z-index: 200;
            padding: 16px;
        }

        .modal-overlay.show { display: flex; }

        .modal {
            background: #fff;
            border-radius: 18px;
            width: 100%;
            max-width: 460px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 60px -10px rgba(0, 0, 0, 0.22);
            animation: popIn 0.2s cubic-bezier(0.22, 1, 0.36, 1);
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.96) translateY(8px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--e1), var(--e3));
            padding: 24px 26px;
            border-radius: 18px 18px 0 0;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            color: #fff;
        }

        .modal-header h3 { font-size: 18px; font-weight: 800; margin-bottom: 4px; }
        .modal-header p { font-size: 13.5px; color: rgba(255, 255, 255, 0.78); }

        .modal-close {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .modal-body { padding: 26px; }
        .m-field { margin-bottom: 18px; }
        .m-field > label { font-size: 12.5px; letter-spacing: 0.5px; margin-bottom: 8px; }
        .m-field .input-wrap { padding: 13px 16px; }
        .m-field .input-wrap input, .m-field .input-wrap select { font-size: 14.5px; }
        .field-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .modal-actions { display: flex; gap: 10px; margin-top: 8px; }

        .btn-modal-cancel {
            flex: 1;
            background: #fff;
            border: 1.5px solid var(--border);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            font-size: 14.5px;
            font-weight: 600;
            padding: 13px;
            border-radius: 10px;
            cursor: pointer;
        }

        .btn-modal-primary {
            flex: 2;
            background: linear-gradient(135deg, var(--e1), var(--e3));
            color: #fff;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 14.5px;
            font-weight: 700;
            padding: 13px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-modal-primary:hover { opacity: 0.9; }
        .btn-modal-primary.full { width: 100%; margin-top: 22px; }
        .hint { font-size: 12.5px; color: var(--text-soft); margin-top: 6px; line-height: 1.5; }

        .success-state { display: none; text-align: center; padding: 36px 26px; }
        .success-state.show { display: block; }

        .success-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #E6F5EF;
            color: var(--e1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin: 0 auto 18px;
        }

        .success-state h4 { font-size: 18px; font-weight: 800; margin-bottom: 10px; }
        .success-state p { font-size: 14.5px; color: var(--text-soft); line-height: 1.7; }

        @media (max-width: 860px) {
            body { flex-direction: column; }
            .left-panel { flex: none; padding: 36px 24px; }
            .lp-content h2 { font-size: 26px; }
            .lp-content p { font-size: 15px; }
            .right-panel { flex: none; padding: 36px 16px 40px; }
        }

        @media (max-width: 480px) {
            .field-row-2 { grid-template-columns: 1fr; gap: 0; }
            .role-pill { padding: 8px 14px; font-size: 13px; }
        }
    </style>
</head>

<body>
    <!-- Left panel -->
    <section class="left-panel">
        <div class="lp-logo">
            <div class="mark">@include('partials.logo-mark')</div>
            <div>
                <h1>iARIS</h1>
                <p>IATO · De La Salle Lipa</p>
            </div>
        </div>

        <div class="lp-content">
            <h2>Admissions records, organized for everyone who needs them.</h2>
            <p>iARIS gives the Institutional Admissions and Testing Office, college deans, the registrar, and institutional leadership a shared, real-time view of admissions data — each from the perspective that matters to their role.</p>
        </div>

        <div class="lp-roles">
            <div class="lp-roles-label">Who uses iARIS</div>
            <div class="role-pill-row">
                <div class="role-pill"><i class="fa-solid fa-user-shield"></i> IATO Admin</div>
                <div class="role-pill"><i class="fa-solid fa-id-badge"></i> IATO Staff</div>
                <div class="role-pill"><i class="fa-solid fa-award"></i> LAMP Office</div>
                <div class="role-pill"><i class="fa-solid fa-id-card"></i> Registrar</div>
                <div class="role-pill"><i class="fa-solid fa-building-columns"></i> Chancellor / President</div>
                <div class="role-pill"><i class="fa-solid fa-chalkboard-user"></i> Dean / Program Chair</div>
                <div class="role-pill"><i class="fa-solid fa-school"></i> SHS Principal</div>
            </div>
        </div>
    </section>

    <!-- Right panel -->
    <section class="right-panel">
        <div class="login-card">
            <h2>Welcome back</h2>
            <p class="sub">Sign in with your DLSL credentials to access your dashboard.</p>

            @if (session('status'))
                <div class="alert alert-success" role="status">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrap {{ $errors->has('email') ? 'has-error' : '' }}">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="name@dlsl.edu.ph" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="field-group">
                    <label for="password">Password</label>
                    <div class="input-wrap {{ $errors->has('password') ? 'has-error' : '' }}">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>

                <div class="field-row">
                    <label class="remember">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Keep me signed in
                    </label>
                    <button type="button" class="link-btn" data-open="forgotModal">Forgot password?</button>
                </div>

                <button type="submit" class="btn-login"><i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In</button>
            </form>

            <div class="divider"><span>or</span></div>

            {{-- TODO: wire up Google sign-in (e.g. Laravel Socialite). Visual only for now. --}}
            <button type="button" class="btn-google">
                <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Continue with Google
            </button>

            <div class="register-row">
                Don't have an account? <button type="button" class="link-btn" data-open="registerModal">Request Access</button>
            </div>

            <div class="copyright">© 2026 Institutional Admissions and Testing Office · De La Salle Lipa</div>
        </div>
    </section>

    {{-- Forgot password modal. TODO: submit to Fortify's password.email route once reset emails are set up. Front end only for now. --}}
    <div class="modal-overlay" id="forgotModal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h3 id="forgotTitle">Forgot Password</h3>
                    <p>We'll send a reset link to your email</p>
                </div>
                <button type="button" class="modal-close" data-close="forgotModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form class="modal-body" data-success="forgotSuccess">
                <div class="m-field">
                    <label for="forgotEmail">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="forgotEmail" placeholder="name@dlsl.edu.ph" required>
                    </div>
                    <div class="hint">Enter the email address linked to your iARIS account. You'll receive a password reset link within a few minutes.</div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" data-close="forgotModal">Cancel</button>
                    <button type="submit" class="btn-modal-primary"><i class="fa-solid fa-paper-plane"></i> Send Reset Link</button>
                </div>
            </form>
            <div class="success-state" id="forgotSuccess">
                <div class="success-icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
                <h4>Reset link sent!</h4>
                <p>Check your DLSL email inbox for the password reset link. It will expire in 30 minutes.</p>
                <button type="button" class="btn-modal-primary full" data-close="forgotModal">Done</button>
            </div>
        </div>
    </div>

    {{-- Request access modal. TODO: needs an access-request table + admin approval before it can submit. Front end only for now. --}}
    <div class="modal-overlay" id="registerModal" role="dialog" aria-modal="true" aria-labelledby="registerTitle">
        <div class="modal">
            <div class="modal-header">
                <div>
                    <h3 id="registerTitle">Request System Access</h3>
                    <p>Fill in your details — IATO Admin will review and approve</p>
                </div>
                <button type="button" class="modal-close" data-close="registerModal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form class="modal-body" data-success="registerPending">
                <div class="field-row-2">
                    <div class="m-field">
                        <label for="regLast">Last Name</label>
                        <div class="input-wrap"><i class="fa-solid fa-user"></i><input type="text" id="regLast" placeholder="e.g. Renegado" required></div>
                    </div>
                    <div class="m-field">
                        <label for="regFirst">First Name</label>
                        <div class="input-wrap"><i class="fa-solid fa-user"></i><input type="text" id="regFirst" placeholder="e.g. Randolph" required></div>
                    </div>
                </div>
                <div class="m-field">
                    <label for="regEmail">DLSL Email Address</label>
                    <div class="input-wrap"><i class="fa-solid fa-envelope"></i><input type="email" id="regEmail" placeholder="name@dlsl.edu.ph" required></div>
                    <div class="hint">Use your official DLSL email address only.</div>
                </div>
                <div class="m-field">
                    <label for="regPassword">Password</label>
                    <div class="input-wrap"><i class="fa-solid fa-lock"></i><input type="password" id="regPassword" placeholder="Create a password" autocomplete="new-password" required></div>
                </div>
                <div class="m-field">
                    <label for="regRole">Position / Role</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-id-badge"></i>
                        <select id="regRole" required>
                            <option value="">Select your position</option>
                            <option>IATO Staff</option>
                            <option>Dean</option>
                            <option>Program Chair</option>
                            <option>SHS Principal</option>
                            <option>Registrar</option>
                            <option>LAMP Office</option>
                            <option>Chancellor / President</option>
                        </select>
                    </div>
                </div>
                <div class="m-field">
                    <label for="regDept">Department / Unit</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-building"></i>
                        <select id="regDept" required>
                            <option value="">Select your department</option>
                            <option>Institutional Admissions &amp; Testing Office</option>
                            <option>CBEAM</option>
                            <option>CEAS</option>
                            <option>CIHTM</option>
                            <option>CITE</option>
                            <option>College of Law</option>
                            <option>College of Nursing</option>
                            <option>Integrated School</option>
                            <option>College Registrar</option>
                            <option>IS Registrar</option>
                            <option>Lasallian Mission Office</option>
                            <option>Office of the President</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" data-close="registerModal">Cancel</button>
                    <button type="submit" class="btn-modal-primary"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
                </div>
            </form>
            <div class="success-state" id="registerPending">
                <div class="success-icon"><i class="fa-solid fa-clock"></i></div>
                <h4>Request Submitted!</h4>
                <p>Your access request has been sent to the IATO Admin for review. You will receive an email at your DLSL address once your account has been approved.</p>
                <button type="button" class="btn-modal-primary full" data-close="registerModal">Got it</button>
            </div>
        </div>
    </div>

    <script>
        // Show / hide password
        const toggle = document.getElementById('togglePass');
        const pass = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const show = pass.type === 'password';
            pass.type = show ? 'text' : 'password';
            toggle.innerHTML = show ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });

        // Modals
        function openModal(id) {
            const modal = document.getElementById(id);
            const form = modal.querySelector('form');
            form.reset();
            form.style.display = '';
            modal.querySelector('.success-state').classList.remove('show');
            modal.classList.add('show');
            modal.querySelector('input, select')?.focus();
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        document.querySelectorAll('[data-open]').forEach(btn =>
            btn.addEventListener('click', () => openModal(btn.dataset.open)));

        document.querySelectorAll('[data-close]').forEach(btn =>
            btn.addEventListener('click', () => closeModal(btn.dataset.close)));

        document.querySelectorAll('.modal-overlay').forEach(overlay =>
            overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(overlay.id); }));

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(m => closeModal(m.id));
        });

        // Front-end only: show the success state instead of submitting.
        document.querySelectorAll('.modal form').forEach(form =>
            form.addEventListener('submit', e => {
                e.preventDefault();
                form.style.display = 'none';
                document.getElementById(form.dataset.success).classList.add('show');
            }));
    </script>
</body>

</html>
