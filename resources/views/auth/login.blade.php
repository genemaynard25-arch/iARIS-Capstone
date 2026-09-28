@extends('layouts.guest')

@section('title', 'iARIS — Login')

{{-- $twoFactor is true on the second step of signing in (see FortifyServiceProvider) --}}
@php($twoFactor = $twoFactor ?? false)
{{-- $resetPassword is true when opened from the reset link in the email (see reset-password.blade.php) --}}
@php($resetPassword = $resetPassword ?? false)
@php($openForgot = $openForgot ?? false)

@section('content')
    <div class="container-fluid">
        <div class="row min-vh-100">

            {{-- Left panel --}}
            <section class="col-lg-6 bg-iaris bg-iaris-circles text-white d-flex flex-column justify-content-between gap-5 p-4 p-md-5">
                <div class="d-flex align-items-center gap-3 position-relative">
                    <div class="d-flex p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25">
                        @include('partials.logo-mark', ['size' => 28])
                    </div>
                    <div>
                        <div class="fs-3 fw-bold font-brand lh-1">iARIS</div>
                        <div class="small fw-semibold text-white-50 mt-1">IATO · De La Salle Lipa</div>
                    </div>
                </div>

                <div class="position-relative" style="max-width: 460px;">
                    <h1 class="display-6 fw-bold font-brand mb-3">Admissions records, organized for everyone who needs them.</h1>
                    <p class="text-white-50 lh-lg mb-0">iARIS gives the Institutional Admissions and Testing Office, college deans, the registrar, and institutional leadership a shared, real-time view of admissions data — each from the perspective that matters to their role.</p>
                </div>

                <div class="position-relative">
                    <div class="small fw-bold text-uppercase tracking-wide text-iaris-pale mb-3">Who uses iARIS</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ([
                            ['bi-shield-lock', 'IATO Admin'],
                            ['bi-person-badge', 'IATO Staff'],
                            ['bi-award', 'LAMP Office'],
                            ['bi-person-vcard', 'Registrar'],
                            ['bi-bank', 'Chancellor / President'],
                            ['bi-person-video3', 'Dean / Program Chair'],
                            ['bi-building', 'SHS Principal'],
                        ] as [$icon, $role])
                            <span class="d-inline-flex align-items-center gap-2 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2 small fw-semibold">
                                <i class="bi {{ $icon }} text-iaris-pale"></i> {{ $role }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Right panel --}}
            <section class="col-lg-6 d-flex align-items-center justify-content-center p-4 p-md-5">
                <div class="auth-card w-100">
                    <h2 class="fs-3 fw-bold font-brand mb-2">Welcome back</h2>
                    <p class="text-body-secondary mb-4">Sign in with your DLSL credentials to access your dashboard.</p>

                    @if (session('status'))
                        <div class="alert alert-success d-flex gap-2" role="status">
                            <i class="bi bi-check-circle"></i>
                            <div>{{ session('status') }}</div>
                        </div>
                    @endif

                    @if ($errors->any() && ! $twoFactor && ! $resetPassword && ! $openForgot)
                        <div class="alert alert-danger d-flex gap-2" role="alert">
                            <i class="bi bi-exclamation-circle"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label small fw-bold text-uppercase">Email Address</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                    class="form-control fs-6 @error('email') is-invalid @enderror"
                                    placeholder="name@dlsl.edu.ph" autocomplete="username" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-bold text-uppercase">Password</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password"
                                    class="form-control fs-6 @error('password') is-invalid @enderror"
                                    placeholder="Enter your password" autocomplete="current-password" required>
                                <button type="button" class="input-group-text bg-white text-body-secondary" id="togglePassword" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="remember" id="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                                <label for="remember" class="form-check-label text-body-secondary">Keep me signed in</label>
                            </div>
                            <button type="button" class="btn btn-link p-0 fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot password?</button>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                        </button>
                    </form>

                    <div class="d-flex align-items-center gap-3 my-4">
                        <hr class="flex-grow-1 m-0">
                        <span class="small fw-semibold text-body-secondary">or</span>
                        <hr class="flex-grow-1 m-0">
                    </div>

                    {{-- TODO: wire up Google sign-in (e.g. Laravel Socialite). Visual only for now. --}}
                    <button type="button" class="btn btn-lg bg-white border w-100 fs-6 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Continue with Google
                    </button>

                    <p class="text-center text-body-secondary mt-4 mb-0">
                        Don't have an account?
                        <button type="button" class="btn btn-link p-0 align-baseline fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#registerModal">Request Access</button>
                    </p>

                    <p class="text-center small text-body-secondary mt-3 mb-0">© 2026 Institutional Admissions and Testing Office · De La Salle Lipa</p>
                </div>
            </section>

        </div>
    </div>

    {{-- Two-factor code modal: opens automatically after a correct password when the account has 2FA on --}}
    @if ($twoFactor)
        @php($useRecovery = $errors->has('recovery_code'))
        <div class="modal fade" id="twoFactorModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="twoFactorTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="modal-header bg-iaris text-white border-0 p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle rounded-circle bg-white bg-opacity-10 fs-5 d-flex align-items-center justify-content-center">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <div>
                                <h3 class="modal-title fs-5 fw-bold" id="twoFactorTitle">Two-Factor Verification</h3>
                                <p class="small text-white-50 mb-0" id="twoFactorHint">
                                    {{ $useRecovery ? 'Enter one of your emergency recovery codes' : 'Enter the 6-digit code from your authenticator app' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('two-factor.login') }}" class="modal-body p-4" id="twoFactorForm">
                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger d-flex gap-2 small" role="alert">
                                <i class="bi bi-exclamation-circle"></i>
                                <div>{{ $errors->first() }}</div>
                            </div>
                        @endif

                        {{-- Only one of these two inputs is enabled at a time, so only one gets sent --}}
                        <div class="mb-4 {{ $useRecovery ? 'd-none' : '' }}" id="codeField">
                            <label for="code" class="form-label small fw-bold text-uppercase">Authentication Code</label>
                            <input type="text" name="code" id="code"
                                class="form-control form-control-lg text-center fs-2 fw-bold tracking-wide @error('code') is-invalid @enderror"
                                inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                                placeholder="••••••" {{ $useRecovery ? 'disabled' : 'required' }}>
                        </div>

                        <div class="mb-4 {{ $useRecovery ? '' : 'd-none' }}" id="recoveryField">
                            <label for="recovery_code" class="form-label small fw-bold text-uppercase">Recovery Code</label>
                            <input type="text" name="recovery_code" id="recovery_code"
                                class="form-control form-control-lg text-center @error('recovery_code') is-invalid @enderror"
                                autocomplete="off" placeholder="xxxxxxxxxx-xxxxxxxxxx" {{ $useRecovery ? 'required' : 'disabled' }}>
                            <div class="form-text">Each recovery code can only be used once.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Verify
                        </button>

                        <div class="d-flex flex-wrap justify-content-between gap-2 mt-3 small">
                            <button type="button" class="btn btn-link btn-sm p-0 fw-semibold text-decoration-none" id="toggleRecovery">
                                {{ $useRecovery ? 'Use authenticator code instead' : 'Use a recovery code instead' }}
                            </button>
                            <a href="{{ route('login') }}" class="fw-semibold text-body-secondary text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to sign in
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Reset password modal: opens automatically from the link in the reset email --}}
    @if ($resetPassword)
        <div class="modal fade" id="resetModal" data-open-on-load data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="resetTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 overflow-hidden">
                    <div class="modal-header bg-iaris text-white border-0 p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle rounded-circle bg-white bg-opacity-10 fs-5 d-flex align-items-center justify-content-center">
                                <i class="bi bi-key"></i>
                            </div>
                            <div>
                                <h3 class="modal-title fs-5 fw-bold" id="resetTitle">Set a New Password</h3>
                                <p class="small text-white-50 mb-0">Choose a new password for your iARIS account</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="modal-body p-4">
                        @csrf
                        {{-- The token from the email link proves this person may reset the password --}}
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        @if ($errors->any())
                            <div class="alert alert-danger d-flex gap-2 small" role="alert">
                                <i class="bi bi-exclamation-circle"></i>
                                <div>
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="resetEmail" class="form-label small fw-bold text-uppercase">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary text-body-secondary"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="resetEmail" value="{{ old('email', $request->email) }}"
                                    class="form-control bg-body-tertiary @error('email') is-invalid @enderror" readonly required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="resetPassword" class="form-label small fw-bold text-uppercase">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="resetPassword"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Enter a new password" autocomplete="new-password" minlength="8" required>
                                <button type="button" class="input-group-text bg-white text-body-secondary" data-toggle-password="resetPassword" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">At least 8 characters.</div>
                        </div>

                        <div class="mb-4">
                            <label for="resetPasswordConfirm" class="form-label small fw-bold text-uppercase">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password_confirmation" id="resetPasswordConfirm"
                                    class="form-control" placeholder="Type it again" autocomplete="new-password" required>
                                <button type="button" class="input-group-text bg-white text-body-secondary" data-toggle-password="resetPasswordConfirm" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback d-block d-none" id="resetMismatch">The passwords don't match.</div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> Reset Password
                        </button>

                        <div class="text-center mt-3 small">
                            <a href="{{ route('login') }}" class="fw-semibold text-body-secondary text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to sign in
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Forgot password modal. TODO: submit to Fortify's password.email route once reset emails are set up. Front end only for now. --}}
    <div class="modal fade" id="forgotModal" {{ $openForgot ? 'data-open-on-load' : '' }} tabindex="-1" aria-labelledby="forgotTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="forgotTitle">Forgot Password</h3>
                        <p class="small text-white-50 mb-0">We'll send a reset link to your email</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Forgot Email -->
               <form method="POST" action="{{ route('password.email') }}" class="modal-body p-4">
                @csrf
                @if ($openForgot && $errors->any())
                    <div class="alert alert-danger d-flex gap-2 small" role="alert">
                        <i class="bi bi-exclamation-circle"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif
                <label for="forgotEmail" class="form-label small fw-bold text-uppercase">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="forgotEmail" class="form-control" placeholder="name@dlsl.edu.ph" required>
                </div>
                    <div class="form-text mb-4">Enter the email address linked to your iARIS account. You'll receive a password reset link within a few minutes.</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold flex-fill"><i class="bi bi-send me-1"></i> Send Reset Link</button>
                    </div>
                </form>

                <div class="modal-body text-center p-5 d-none js-demo-success">
                    <div class="icon-circle rounded-circle bg-primary-subtle text-primary fs-4 d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="bi bi-envelope-check"></i>
                    </div>
                    <h4 class="fs-5 fw-bold">Reset link sent!</h4>
                    <p class="text-body-secondary">Check your DLSL email inbox for the password reset link. It will expire in 30 minutes.</p>
                    <button type="button" class="btn btn-primary fw-bold w-100" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Request access modal. TODO: needs an access-request table + admin approval before it can submit. Front end only for now. --}}
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="registerTitle">Request System Access</h3>
                        <p class="small text-white-50 mb-0">Fill in your details — IATO Admin will review and approve</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('password.email') }}" class="modal-body p-4">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="regLast" class="form-label small fw-bold text-uppercase">Last Name</label>
                            <input type="text" id="regLast" class="form-control" placeholder="e.g. Renegado" required>
                        </div>
                         <div class="col-sm-6">
                            <label for="regFirst" class="form-label small fw-bold text-uppercase">First Name</label>
                            <input type="text" id="regFirst" class="form-control" placeholder="e.g. Randolph" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="regEmail" class="form-label small fw-bold text-uppercase">DLSL Email Address</label>
                        <input type="email" id="regEmail" class="form-control" placeholder="name@dlsl.edu.ph" required>
                        <div class="form-text">Use your official DLSL email address only.</div>
                    </div>

                    <div class="mb-3">
                        <label for="regPassword" class="form-label small fw-bold text-uppercase">Password</label>
                        <input type="password" id="regPassword" class="form-control" placeholder="Create a password" autocomplete="new-password" required>
                    </div>

                    <div class="mb-3">
                        <label for="regRole" class="form-label small fw-bold text-uppercase">Position / Role</label>
                        <select id="regRole" class="form-select" required>
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

                    <div class="mb-4">
                        <label for="regDept" class="form-label small fw-bold text-uppercase">Department / Unit</label>
                        <select id="regDept" class="form-select" required>
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

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary fw-bold flex-fill"><i class="bi bi-send me-1"></i> Submit Request</button>
                    </div>
                </form>

                <div class="modal-body text-center p-5 d-none js-demo-success">
                    <div class="icon-circle rounded-circle bg-primary-subtle text-primary fs-4 d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h4 class="fs-5 fw-bold">Request Submitted!</h4>
                    <p class="text-body-secondary">Your access request has been sent to the IATO Admin for review. You will receive an email at your DLSL address once your account has been approved.</p>
                    <button type="button" class="btn btn-primary fw-bold w-100" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Show / hide password
        const toggle = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });

        // Forgot Password and Request Access are front end only: on submit, show the success message instead of sending anything.
        document.querySelectorAll('.js-demo-form').forEach(form => {
            const modal = form.closest('.modal');
            const success = modal.querySelector('.js-demo-success');

            form.addEventListener('submit', e => {
                e.preventDefault();
                form.classList.add('d-none');
                success.classList.remove('d-none');
                success.querySelector('button').focus(); // keep focus inside the modal so Esc still closes it
            });

            // Reset each time the modal opens (Bootstrap fires 'show.bs.modal')
            modal.addEventListener('show.bs.modal', () => {
                form.reset();
                form.classList.remove('d-none');
                success.classList.add('d-none');
            });
        });

        // Open any modal marked data-open-on-load as soon as the page loads (reset password, /forgot-password)
        document.querySelectorAll('.modal[data-open-on-load]').forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => modal.querySelector('input:not([type=hidden]):not([readonly])')?.focus());
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });

        // Show / hide buttons for the reset password fields
        document.querySelectorAll('[data-toggle-password]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.togglePassword);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        // Reset password: warn before submitting if the two passwords don't match
        const resetConfirm = document.getElementById('resetPasswordConfirm');
        if (resetConfirm) {
            const resetNew = document.getElementById('resetPassword');
            const mismatch = document.getElementById('resetMismatch');
            const check = () => {
                const bad = resetConfirm.value !== '' && resetConfirm.value !== resetNew.value;
                resetConfirm.classList.toggle('is-invalid', bad);
                mismatch.classList.toggle('d-none', !bad);
                resetConfirm.setCustomValidity(bad ? "The passwords don't match." : '');
            };
            resetNew.addEventListener('input', check);
            resetConfirm.addEventListener('input', check);
        }

        // Two-factor code modal (only on the page when $twoFactor is true)
        const twoFactorModal = document.getElementById('twoFactorModal');
        if (twoFactorModal) {
            const code = document.getElementById('code');
            const recovery = document.getElementById('recovery_code');
            const codeField = document.getElementById('codeField');
            const recoveryField = document.getElementById('recoveryField');
            const toggleRecovery = document.getElementById('toggleRecovery');
            const hint = document.getElementById('twoFactorHint');

            // Open it as soon as the page loads, and put the cursor in the box
            twoFactorModal.addEventListener('shown.bs.modal', () => (recovery.disabled ? code : recovery).focus());
            bootstrap.Modal.getOrCreateInstance(twoFactorModal).show();

            // Digits only; submit automatically once all 6 are typed
            code.addEventListener('input', () => {
                code.value = code.value.replace(/\D/g, '').slice(0, 6);
                if (code.value.length === 6) document.getElementById('twoFactorForm').requestSubmit();
            });

            // Switch between the 6-digit code and a recovery code
            toggleRecovery.addEventListener('click', () => {
                const useRecovery = recovery.disabled;
                codeField.classList.toggle('d-none', useRecovery);
                recoveryField.classList.toggle('d-none', !useRecovery);
                code.disabled = useRecovery;
                code.required = !useRecovery;
                recovery.disabled = !useRecovery;
                recovery.required = useRecovery;
                toggleRecovery.textContent = useRecovery ? 'Use authenticator code instead' : 'Use a recovery code instead';
                hint.textContent = useRecovery ? 'Enter one of your emergency recovery codes' : 'Enter the 6-digit code from your authenticator app';
                (useRecovery ? recovery : code).focus();
            });
        }
    </script>
@endsection
