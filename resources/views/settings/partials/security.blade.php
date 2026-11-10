{{-- Change password: Fortify's PUT /user/password (fields current_password, password, password_confirmation) --}}
<form method="POST" action="{{ route('user-password.update') }}" class="card border-0 shadow-sm rounded-4 mb-4" id="passwordForm" novalidate>
    @csrf
    @method('PUT')
    <div class="card-body p-4">
        <h2 class="fs-6 fw-bold mb-0">Change password</h2>
        <p class="small text-body-secondary">Use at least 8 characters with a mix of letters and numbers.</p>
        @if (session('status') === 'password-updated')
            <div class="alert alert-success small py-2"><i class="bi bi-check-circle me-1" aria-hidden="true"></i> Your password was updated.</div>
        @endif
        <div class="row g-3">
            <div class="col-12">
                <label for="currentPassword" class="form-label small fw-bold text-uppercase text-body-secondary">Current password</label>
                <input type="password" name="current_password" id="currentPassword" autocomplete="current-password" required
                    class="form-control {{ $passwordErrors->has('current_password') ? 'is-invalid' : '' }}">
                <div class="invalid-feedback">{{ $passwordErrors->first('current_password') ?: 'Enter your current password.' }}</div>
            </div>
            <div class="col-md-6">
                <label for="newPassword" class="form-label small fw-bold text-uppercase text-body-secondary">New password</label>
                <input type="password" name="password" id="newPassword" autocomplete="new-password" required
                    class="form-control {{ $passwordErrors->has('password') ? 'is-invalid' : '' }}">
                <div class="invalid-feedback">{{ $passwordErrors->first('password') ?: 'Use 8+ characters with letters and numbers.' }}</div>
                <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-label="Password strength">
                    <div class="progress-bar" id="strengthBar" style="width: 0%;"></div>
                </div>
                <div class="small text-body-secondary mt-1" id="strengthText">Strength: —</div>
            </div>
            <div class="col-md-6">
                <label for="confirmPassword" class="form-label small fw-bold text-uppercase text-body-secondary">Confirm new password</label>
                <input type="password" name="password_confirmation" id="confirmPassword" autocomplete="new-password" required class="form-control">
                <div class="invalid-feedback">Passwords do not match.</div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-transparent border-top d-flex justify-content-end p-3">
        <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-key me-1" aria-hidden="true"></i> Update password</button>
    </div>
</form>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h2 class="fs-6 fw-bold mb-0">Two-factor authentication</h2>
        <p class="small text-body-secondary">A code from your phone, asked for after your password.</p>
        <div class="d-flex flex-wrap align-items-center gap-3 rounded-3 border p-3 mb-3 {{ $twoFactorOn ? 'border-primary-subtle bg-primary-subtle' : 'border-warning-subtle bg-warning-subtle' }}">
            <div class="icon-circle rounded-3 bg-white d-flex align-items-center justify-content-center {{ $twoFactorOn ? 'text-primary' : 'text-warning-emphasis' }}">
                <i class="bi {{ $twoFactorOn ? 'bi-shield-check' : 'bi-shield-exclamation' }} fs-5" aria-hidden="true"></i>
            </div>
            <div class="flex-grow-1">
                @if ($twoFactorOn)
                    <div class="fw-bold">Authenticator app is active</div>
                    <div class="small text-body-secondary">Turned on {{ $twoFactorSince }}</div>
                @else
                    <div class="fw-bold text-warning-emphasis">Not set up yet</div>
                    <div class="small text-body-secondary">Set it up now to protect your account.</div>
                @endif
            </div>
            <a href="{{ url('/two-factor') }}" class="btn btn-sm {{ $twoFactorOn ? 'btn-light border' : 'btn-primary' }} fw-semibold">
                <i class="bi bi-phone me-1" aria-hidden="true"></i> {{ $twoFactorOn ? 'View recovery codes' : 'Set up two-factor' }}
            </a>
        </div>
        <div class="small text-body-secondary d-flex gap-2"><i class="bi bi-info-circle" aria-hidden="true"></i>
            <span>Lost your phone? Use one of your recovery codes to sign in, or contact the IATO Admin to reset two-factor.</span></div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4 pb-2">
        <h2 class="fs-6 fw-bold mb-0">Recent sign-ins</h2>
        <p class="small text-body-secondary mb-0">Check for activity you do not recognize.</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 small">
            <thead class="table-light">
                <tr class="text-uppercase text-nowrap">
                    <th class="ps-4 text-body-secondary">Last active</th>
                    <th class="text-body-secondary">Device</th>
                    <th class="text-body-secondary">IP address</th>
                    <th class="pe-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($signIns as $signIn)
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $signIn['when'] }}</td>
                        <td>{{ $signIn['device'] }}</td>
                        <td class="font-monospace">{{ $signIn['ip'] }}</td>
                        <td class="pe-4 text-end">
                            @if ($signIn['current'])
                                <span class="badge bg-primary-subtle text-primary-emphasis">This device</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-body-secondary py-4">No sign-ins recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-transparent border-top d-flex flex-wrap align-items-center gap-2 p-3">
        <span class="small text-body-secondary me-auto"><i class="bi bi-clock me-1" aria-hidden="true"></i>You are signed out automatically after a period of inactivity.</span>
        {{-- TODO (backend): a route that calls Auth::logoutOtherDevices($password) --}}
        <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Coming soon"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Sign out of other devices</button>
    </div>
</div>
