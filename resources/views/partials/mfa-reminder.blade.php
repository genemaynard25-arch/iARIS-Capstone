{{--
    "Set Up Multi-Factor Authentication" pop-up. Shown once per browser session (on the first page after
    signing in) to anyone who hasn't turned on two-factor yet. "Do this later" just closes it.
    Included in layouts/app.blade.php.
--}}
@php($me = auth()->user())
@if ($me && ! $me->two_factor_confirmed_at && ! request()->is('two-factor*'))
    <div class="modal fade" id="mfaReminder" tabindex="-1" aria-labelledby="mfaReminderTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="mfaReminderTitle">Set Up Multi-Factor Authentication</h3>
                        <p class="small text-white-50 mb-0">Protect your iARIS account in a few minutes</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="rounded-3 bg-primary-subtle border border-primary-subtle small text-body-secondary p-3 mb-4">
                        <strong class="text-primary-emphasis">Two-factor authentication is not on yet.</strong>
                        iARIS holds applicants' personal data, so connect an authenticator app to secure your account with Multi-Factor Authentication (MFA).
                    </div>
                    <ol class="list-unstyled d-grid gap-3 mb-0">
                        @foreach ([
                            ['Download an Authenticator App', 'Install <b>Google Authenticator</b> or <b>Authy</b> on your phone from the App Store or Google Play.'],
                            ['Scan the QR Code', 'Open the app and scan the QR code that appears after you click "Set Up MFA Now".'],
                            ['Enter the 6-Digit Code', 'Type the code from your app to confirm the setup.'],
                            ["You're all set!", 'From now on, every sign-in asks for your password and a one-time code from your app.'],
                        ] as [$title, $text])
                            <li class="d-flex gap-3">
                                <span class="badge rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px;">{{ $loop->iteration }}</span>
                                <div class="small">
                                    <div class="fw-bold">{{ $title }}</div>
                                    <div class="text-body-secondary">{!! $text !!}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Do this later</button>
                    <a href="{{ url('/two-factor') }}" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-shield-check me-1"></i> Set Up MFA Now</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Once per browser session, per user: sessionStorage is cleared when the browser closes
        (() => {
            const key = 'iaris.mfaReminder.{{ $me->id }}';
            try { if (sessionStorage.getItem(key)) return; sessionStorage.setItem(key, '1'); } catch (e) { /* storage blocked: show it anyway */ }
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mfaReminder')).show();
        })();
    </script>
@endif
