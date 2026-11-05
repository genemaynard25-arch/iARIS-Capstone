@extends('layouts.guest')

{{--
    Two-factor authentication setup.

    Uses the same backend as before (TwoFactorAuthenticationController + Fortify):
      $enabled   true once POST /two-factor/enable has made a secret
      $confirmed true once POST /two-factor/confirm accepted a code
      $qrCode    the QR code SVG (only while enabled)

    Which step shows first depends on those:
      not enabled          -> 1 Start (2 Get the App is just information; its button POSTs /two-factor/enable)
      enabled, unconfirmed -> 3 Scan QR (or 4 Verify again, if the last code was wrong)
      confirmed            -> 5 Done (with the recovery codes)
--}}
@php
    $user = auth()->user();

    // A wrong code from Fortify comes back in its own error bag, not the default one
    $codeError = $errors->confirmTwoFactorAuthentication->first('code');

    $startStep = ! $enabled ? 1 : (! $confirmed ? ($codeError ? 4 : 3) : 5);

    // The text version of the QR code, for typing into the app by hand (shown in groups of 4)
    $setupKey = $enabled && ! $confirmed
        ? rescue(fn () => \Laravel\Fortify\Fortify::currentEncrypter()->decrypt($user->two_factor_secret), null, false)
        : null;

    // Fortify makes these when 2FA is confirmed. Each one can replace a code once (the login pop-up's "Use a recovery code").
    $recoveryCodes = $confirmed ? rescue(fn () => $user->recoveryCodes(), [], false) : [];

    $steps = ['Start', 'Get the App', 'Scan QR Code', 'Verify Code', 'Done'];
    $initials = collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp

@section('title', 'iARIS — Two-Factor Setup')

@section('content')
    <div class="min-vh-100 d-flex flex-column align-items-center justify-content-center p-3 py-5">
        <div class="w-100" style="max-width: 540px;">

            {{-- Top strip --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <a href="{{ url('/home') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                    <span class="d-flex align-items-center justify-content-center rounded-3 bg-iaris icon-circle">
                        @include('partials.logo-mark', ['size' => 20])
                    </span>
                    <span>
                        <span class="d-block fw-bold fs-5 font-brand text-primary lh-1">iARIS</span>
                        <span class="small text-body-secondary">De La Salle Lipa · IATO</span>
                    </span>
                </a>
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                    <i class="bi bi-lock-fill me-1"></i> Secure Setup
                </span>
            </div>

            <div class="card border-0 shadow rounded-4 overflow-hidden">
                {{-- Progress: one dot per step. The script colours them. --}}
                <ol class="list-unstyled d-flex align-items-start px-4 pt-4 mb-0" id="progress" aria-label="Setup progress">
                    @foreach ($steps as $i => $label)
                        <li class="d-flex flex-column align-items-center text-center flex-fill" data-progress="{{ $i + 1 }}">
                            <span class="rounded-circle border border-2 d-flex align-items-center justify-content-center fw-bold small" style="width: 32px; height: 32px;" data-dot>
                                <span data-num>{{ $i + 1 }}</span><i class="bi bi-check-lg d-none" data-check></i>
                            </span>
                            <span class="small fw-bold mt-1 lh-sm" style="font-size: 0.7rem;" data-label>{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>

                <div class="card-body p-4 p-sm-5">
                    {{-- Anything else that went wrong (not the code) --}}
                    @if ($errors->any())
                        <div class="alert alert-danger small">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    {{-- STEP 1: Start --}}
                    <section data-step="1" class="d-none">
                        <div class="icon-circle rounded-4 bg-primary-subtle text-primary-emphasis fs-3 d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h1 class="fs-4 fw-bold mb-2">Protect your account</h1>
                        <p class="text-body-secondary mb-4">
                            Set up <strong class="text-body">two-factor authentication (MFA)</strong>. After you sign in with your password,
                            iARIS will also ask for a 6-digit code from your phone, so a stolen password alone isn't enough.
                        </p>

                        <div class="d-flex align-items-center gap-3 rounded-3 bg-body-tertiary p-3 mb-4">
                            <div class="icon-circle rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center">{{ strtoupper($initials) }}</div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-bold">{{ $user->name }}</div>
                                <div class="small text-body-secondary text-truncate">{{ $user->email }}</div>
                            </div>
                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis text-capitalize">{{ str_replace('_', ' ', $user->role ?? 'user') }}</span>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg w-100 fw-semibold" data-go="2">
                            Set Up Two-Factor Authentication <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <a href="{{ url('/home') }}" class="btn btn-link w-100 mt-2 text-body-secondary text-decoration-none">Not now — back to iARIS</a>
                    </section>

                    {{-- STEP 2: Get the app --}}
                    <section data-step="2" class="d-none">
                        <div class="icon-circle rounded-4 bg-info-subtle text-info-emphasis fs-3 d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-phone"></i>
                        </div>
                        <h1 class="fs-4 fw-bold mb-2">Get an authenticator app</h1>
                        <p class="text-body-secondary mb-4">You'll need an authenticator app on your phone to make the codes. Either of these works with iARIS.</p>

                        <div class="row g-3 mb-4">
                            @foreach ([
                                ['Google Authenticator', 'Simple and widely used', 'bi-google', 'danger', 'https://apps.apple.com/app/google-authenticator/id388497605', 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2'],
                                ['Microsoft Authenticator', 'Can back up to your Microsoft account', 'bi-microsoft', 'info', 'https://apps.apple.com/app/microsoft-authenticator/id983156458', 'https://play.google.com/store/apps/details?id=com.azure.authenticator'],
                            ] as [$name, $desc, $icon, $tone, $ios, $android])
                                <div class="col-sm-6">
                                    <div class="border rounded-3 p-3 h-100 text-center">
                                        <div class="icon-circle rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis fs-4 d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 52px; height: 52px;">
                                            <i class="bi {{ $icon }}"></i>
                                        </div>
                                        <div class="fw-bold small">{{ $name }}</div>
                                        <div class="small text-body-secondary mb-2">{{ $desc }}</div>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ $ios }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm py-0 px-2 small"><i class="bi bi-apple"></i> App Store</a>
                                            <a href="{{ $android }}" target="_blank" rel="noopener" class="btn btn-dark btn-sm py-0 px-2 small"><i class="bi bi-google-play"></i> Play</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="small text-body-secondary">Already have an authenticator app? You can continue.</p>

                        {{-- Before 2FA is enabled, this button asks the server to make the secret and QR code --}}
                        @if (! $enabled)
                            <form method="POST" action="/two-factor/enable" id="enableForm">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                    I Have the App — Continue <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-light border w-100 mt-2" data-go="1"><i class="bi bi-arrow-left me-1"></i> Back</button>
                        @else
                            <button type="button" class="btn btn-primary btn-lg w-100 fw-semibold" data-go="3">I Have the App — Continue <i class="bi bi-arrow-right ms-1"></i></button>
                        @endif
                    </section>

                    {{-- STEP 3: Scan the QR code --}}
                    <section data-step="3" class="d-none">
                        <div class="icon-circle rounded-4 bg-secondary-subtle text-secondary-emphasis fs-3 d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <h1 class="fs-4 fw-bold mb-2">Scan the QR code</h1>
                        <p class="text-body-secondary mb-4">Open your authenticator app, tap <strong class="text-body">Add account</strong> or <strong class="text-body">+</strong>, then scan this code.</p>

                        <div class="d-flex flex-column flex-sm-row gap-4 align-items-center align-items-sm-start mb-4">
                            <div class="qr-box border border-2 rounded-4 p-2 bg-white flex-shrink-0" style="width: 172px; height: 172px;" id="qrBox">
                                {{-- SVG made by Fortify from this account's secret --}}
                                {!! $qrCode !!}
                            </div>
                            <div class="flex-grow-1 w-100">
                                <div class="fw-bold small mb-1">Can't scan it?</div>
                                <p class="small text-body-secondary mb-2">Type this setup key into your app instead:</p>
                                @if ($setupKey)
                                    <div class="d-flex align-items-center justify-content-between gap-2 rounded-3 bg-body-tertiary px-3 py-2">
                                        <code class="text-body fw-bold small text-break" style="letter-spacing: 0.1em;" id="setupKey">{{ trim(chunk_split($setupKey, 4, ' ')) }}</code>
                                        <button type="button" class="btn btn-sm btn-link p-0 text-primary" id="copyKey" title="Copy key" aria-label="Copy setup key"><i class="bi bi-copy"></i></button>
                                    </div>
                                @endif
                                <p class="small text-body-secondary mt-2 mb-0">This key belongs to your account only. Don't share it.</p>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg w-100 fw-semibold" data-go="4">I've Scanned It — Continue <i class="bi bi-arrow-right ms-1"></i></button>
                        <button type="button" class="btn btn-light border w-100 mt-2" data-go="2"><i class="bi bi-arrow-left me-1"></i> Back</button>
                    </section>

                    {{-- STEP 4: Enter the code (sent as "code" to /two-factor/confirm) --}}
                    <section data-step="4" class="d-none">
                        <div class="icon-circle rounded-4 bg-warning-subtle text-warning-emphasis fs-3 d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-key"></i>
                        </div>
                        <h1 class="fs-4 fw-bold mb-2">Enter the verification code</h1>
                        <p class="text-body-secondary mb-4">Type the <strong class="text-body">6-digit code</strong> your app shows for iARIS.</p>

                        <form method="POST" action="/two-factor/confirm" id="confirmForm" novalidate>
                            @csrf
                            <input type="hidden" name="code" id="codeField">

                            <label class="form-label small fw-bold text-uppercase text-body-secondary" for="otp1">6-digit code</label>
                            {{-- Six boxes for typing; the script joins them into "code". Pasting all 6 digits also works. --}}
                            <div class="d-flex gap-2 mb-2" id="otpBoxes">
                                @for ($i = 1; $i <= 6; $i++)
                                    <input type="text" inputmode="numeric" maxlength="1" id="otp{{ $i }}"
                                        class="form-control text-center fs-4 fw-bold font-brand px-0 {{ $codeError ? 'is-invalid' : '' }}" style="height: 58px;"
                                        aria-label="Digit {{ $i }}" @if ($i === 1) autocomplete="one-time-code" @else autocomplete="off" @endif>
                                @endfor
                            </div>
                            <div class="small fw-semibold text-danger mb-2 {{ $codeError ? '' : 'd-none' }}" id="otpError" role="alert">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <span>{{ $codeError ? 'That code didn\'t match. Use the newest code from your app.' : '' }}</span>
                            </div>
                            {{-- Codes change every 30 seconds; this counts down to the next one --}}
                            <div class="small text-body-secondary mb-4">
                                <i class="bi bi-clock me-1"></i> Your app shows a new code in <b class="text-warning-emphasis" id="timer">30s</b>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold">
                                <span class="spinner-border spinner-border-sm me-2 d-none" aria-hidden="true"></span>
                                <i class="bi bi-shield-check me-1"></i> Verify &amp; Enable MFA
                            </button>
                        </form>
                        <button type="button" class="btn btn-light border w-100 mt-2" data-go="3"><i class="bi bi-arrow-left me-1"></i> Back</button>
                    </section>

                    {{-- STEP 5: Done --}}
                    <section data-step="5" class="d-none">
                        <div class="rounded-circle bg-primary text-white fs-2 d-flex align-items-center justify-content-center mx-auto mb-3 shadow" style="width: 76px; height: 76px;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h1 class="fs-4 fw-bold text-center mb-2">Two-factor authentication is on</h1>
                        <p class="text-body-secondary text-center mb-4">From now on, signing in to iARIS asks for a code from your authenticator app.</p>

                        @if (count($recoveryCodes))
                            <div class="rounded-3 border border-warning-subtle bg-warning-subtle p-3 mb-4">
                                <div class="fw-bold small text-warning-emphasis mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Save your recovery codes</div>
                                <p class="small mb-3">If you lose your phone, each code below can be used once instead of an app code. Keep them somewhere safe.</p>
                                <div class="row row-cols-1 row-cols-sm-2 g-2 mb-3" id="recoveryCodes">
                                    @foreach ($recoveryCodes as $code)
                                        <div class="col"><code class="d-block bg-white rounded-2 px-2 py-1 text-body small text-center">{{ $code }}</code></div>
                                    @endforeach
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-light border flex-fill" id="copyCodes"><i class="bi bi-copy me-1"></i> Copy</button>
                                    <button type="button" class="btn btn-sm btn-light border flex-fill" id="downloadCodes"><i class="bi bi-download me-1"></i> Download .txt</button>
                                </div>
                            </div>
                        @endif

                        <ul class="list-unstyled small mb-4">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Authenticator app connected</li>
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Two-factor authentication enabled</li>
                            <li><i class="bi bi-check-circle-fill text-primary me-2"></i> Recovery codes ready</li>
                        </ul>

                        <a href="{{ url('/home') }}" class="btn btn-primary btn-lg w-100 fw-semibold">Continue to iARIS <i class="bi bi-arrow-right ms-1"></i></a>
                    </section>
                </div>
            </div>

            <p class="small text-body-secondary text-center mt-4 mb-0">
                <b>iARIS · De La Salle Lipa</b> · IATO Admissions Records and Information System
            </p>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const START = {{ $startStep }};
        let current = START;

        // ---- Steps: show one panel and colour the progress dots ----
        function goTo(n) {
            current = n;
            document.querySelectorAll('[data-step]').forEach(s => s.classList.toggle('d-none', Number(s.dataset.step) !== n));
            document.querySelectorAll('[data-progress]').forEach(item => {
                const i = Number(item.dataset.progress);
                const dot = item.querySelector('[data-dot]');
                const done = i < n || n === 5;
                dot.className = `rounded-circle border border-2 d-flex align-items-center justify-content-center fw-bold small ${
                    i <= n ? 'bg-primary border-primary text-white' : 'bg-white text-body-secondary'}`;
                if (i === n && n !== 5) dot.classList.add('shadow-sm');
                item.querySelector('[data-num]').classList.toggle('d-none', done);
                item.querySelector('[data-check]').classList.toggle('d-none', !done);
                item.querySelector('[data-label]').className = `small fw-bold mt-1 lh-sm ${i <= n ? 'text-primary' : 'text-body-secondary'}`;
                if (i === n) item.setAttribute('aria-current', 'step'); else item.removeAttribute('aria-current');
            });
            if (n === 4) document.getElementById('otp1').focus();
        }
        document.querySelectorAll('[data-go]').forEach(b => b.addEventListener('click', () => goTo(Number(b.dataset.go))));

        // Show a spinner and stop double-clicks while a form is sending
        document.querySelectorAll('#enableForm, #confirmForm').forEach(form => form.addEventListener('submit', () => {
            if (form.dataset.blocked) return;
            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true;
            btn.querySelector('.spinner-border').classList.remove('d-none');
        }));

        // ---- Copy helpers ----
        function copyText(text, button) {
            const done = () => {
                const old = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check-lg"></i>' + (button.textContent.trim() ? ' Copied' : '');
                setTimeout(() => button.innerHTML = old, 1500);
            };
            navigator.clipboard?.writeText(text).then(done, done) ?? done();
        }
        document.getElementById('copyKey')?.addEventListener('click', e =>
            copyText(document.getElementById('setupKey').textContent.replace(/\s/g, ''), e.currentTarget));

        const codesText = () => [...document.querySelectorAll('#recoveryCodes code')].map(c => c.textContent.trim()).join('\n');
        document.getElementById('copyCodes')?.addEventListener('click', e => copyText(codesText(), e.currentTarget));
        document.getElementById('downloadCodes')?.addEventListener('click', () => {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([`iARIS recovery codes (each works once)\n\n${codesText()}\n`], { type: 'text/plain' }));
            link.download = 'iaris-recovery-codes.txt';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- The six code boxes ----
        const boxes = [...document.querySelectorAll('#otpBoxes input')];
        const otpError = document.getElementById('otpError');

        function showError(text) {
            otpError.querySelector('span').textContent = text;
            otpError.classList.toggle('d-none', !text);
            boxes.forEach(b => b.classList.toggle('is-invalid', !!text));
        }

        boxes.forEach((box, i) => {
            box.addEventListener('input', () => {
                const digits = box.value.replace(/\D/g, '');
                // Typing (or the phone auto-filling) several digits spreads them across the boxes
                if (digits.length > 1) {
                    digits.slice(0, 6 - i).split('').forEach((d, k) => boxes[i + k].value = d);
                    boxes[Math.min(i + digits.length, 5)].focus();
                } else {
                    box.value = digits;
                    if (digits && boxes[i + 1]) boxes[i + 1].focus();
                }
                showError('');
                // All six filled: send it
                if (boxes.every(b => b.value)) document.getElementById('confirmForm').requestSubmit();
            });
            box.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !box.value && boxes[i - 1]) boxes[i - 1].focus();
                if (e.key === 'ArrowLeft' && boxes[i - 1]) boxes[i - 1].focus();
                if (e.key === 'ArrowRight' && boxes[i + 1]) boxes[i + 1].focus();
            });
            box.addEventListener('paste', e => {
                const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
                if (!digits) return;
                e.preventDefault();
                digits.split('').forEach((d, k) => { if (boxes[k]) boxes[k].value = d; });
                boxes[Math.min(digits.length, 5)].focus();
                showError('');
                if (digits.length === 6) document.getElementById('confirmForm').requestSubmit();
            });
        });

        // Join the boxes into the "code" field the backend expects
        document.getElementById('confirmForm').addEventListener('submit', e => {
            const code = boxes.map(b => b.value).join('');
            const form = e.currentTarget;
            if (code.length < 6) {
                e.preventDefault();
                form.dataset.blocked = '1';
                showError('Please enter all 6 digits.');
                boxes.find(b => !b.value)?.focus();
                return;
            }
            delete form.dataset.blocked;
            document.getElementById('codeField').value = code;
        }, true);

        // ---- Countdown to the app's next code (codes change at :00 and :30 of every minute) ----
        function tick() {
            document.getElementById('timer').textContent = `${30 - (Math.floor(Date.now() / 1000) % 30)}s`;
        }
        tick();
        setInterval(tick, 1000);

        goTo(START);
    </script>
@endsection
