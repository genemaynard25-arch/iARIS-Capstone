@extends('layouts.app')

@section('title', 'iARIS — Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your profile, security, notifications, and how iARIS works for you')

@php
    $sectionInfo = [
        'profile' => ['Profile', 'bi-person'],
        'security' => ['Security', 'bi-lock'],
        'notifications' => ['Notifications', 'bi-bell'],
        'briefing' => ['Briefing', 'bi-compass'],
        'reports' => ['Report Preferences', 'bi-file-earmark-text'],
        'ai' => ['AI Assistant', 'bi-stars'],
        'display' => ['Display', 'bi-sliders'],
        // Super Admin only (see settings/partials/system.blade.php)
        'year' => ['Academic Year', 'bi-calendar3'],
        'fees' => ['Reservation Fees', 'bi-cash-coin'],
        'review' => ['Review & Reports', 'bi-check2-square'],
        'retention' => ['Data Retention', 'bi-archive'],
        'backups' => ['Backups', 'bi-cloud-check'],
    ];
    // After changing the password (or a mistake in it), open the Security section again
    $passwordErrors = $errors->updatePassword;
    $openSection = $passwordErrors->any() || session('status') === 'password-updated' ? 'security' : $sections[0];
@endphp

@section('content')
    <div class="row g-4">
        {{-- Section menu --}}
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 sticky-lg-top" style="top: 1.5rem;">
                <div class="card-body p-2">
                    <nav class="nav nav-pills flex-row flex-lg-column gap-1" role="tablist" id="sectionNav">
                        @foreach ($sections as $key)
                            @if ($key === 'year')
                                {{-- A label before the system sections --}}
                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary px-3 pt-3 pb-1 d-none d-lg-block">System</div>
                            @endif
                            <button class="nav-link text-start fw-semibold {{ $key === $openSection ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#section-{{ $key }}" type="button" role="tab" data-section="{{ $key }}">
                                <i class="bi {{ $sectionInfo[$key][1] }} me-2"></i>{{ $sectionInfo[$key][0] }}
                            </button>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">
            <div class="tab-content">

                {{-- ========== PROFILE ========== --}}
                @if (in_array('profile', $sections))
                    <div class="tab-pane fade {{ $openSection === 'profile' ? 'show active' : '' }}" id="section-profile" role="tabpanel">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Profile</h2>
                                <p class="small text-body-secondary">Your name, title, and email are managed by the IATO Admin. You can change your photo.</p>

                                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                                    <div class="rounded-circle bg-primary text-white fs-3 fw-bold d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 80px; height: 80px;" id="bigAvatar" data-avatar>{{ $profile['initials'] }}</div>
                                    <div>
                                        <div class="fw-bold fs-5">{{ $profile['name'] }}</div>
                                        <div class="small text-body-secondary mb-2">{{ $profile['title'] }} · {{ $profile['office'] }}</div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <input type="file" class="d-none" id="photoInput" accept="image/*">
                                            <button type="button" class="btn btn-sm btn-light border" onclick="document.getElementById('photoInput').click()"><i class="bi bi-camera me-1"></i> Change photo</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="photoRemove"><i class="bi bi-trash me-1"></i> Remove</button>
                                            <span class="small text-body-secondary"><i class="bi bi-lock me-1"></i>Name and title managed by IATO Admin</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    @foreach (['Full name' => $profile['name'], 'Title' => $profile['title'], 'DLSL email' => $profile['email'], 'Office' => $profile['office']] as $label => $value)
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-uppercase text-body-secondary" for="profile{{ $loop->index }}">{{ $label }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control bg-body-tertiary" id="profile{{ $loop->index }}" value="{{ $value }}" readonly>
                                                <span class="input-group-text bg-body-tertiary text-body-secondary"><i class="bi bi-lock"></i></span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Your photo is saved in this browser for now.</p>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Your access</h2>
                                <p class="small text-body-secondary">Set by the IATO Admin in Access Control. Contact IATO if something needs to change.
                                    @if (auth()->user()->isLamp())
                                        Your corrections to scholar records go to the IATO Review Queue before other users see them.
                                    @elseif (auth()->user()->role === 'iato_staff' && ! auth()->user()->isAdmin())
                                        Your edits and imports go to the Review Queue before other users see them.
                                    @endif
                                </p>
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-2">
                                    @foreach ($access as $label => $value)
                                        <div class="col">
                                            <div class="rounded-3 bg-body-tertiary p-3 h-100">
                                                <div class="small fw-bold text-uppercase text-body-secondary">{{ $label }}</div>
                                                <div class="fw-semibold {{ $label === 'Account status' ? 'text-primary' : '' }}">{{ $value }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ========== SECURITY ========== --}}
                @if (in_array('security', $sections))
                    <div class="tab-pane fade {{ $openSection === 'security' ? 'show active' : '' }}" id="section-security" role="tabpanel">
                        {{-- Change password: Fortify's PUT /user/password (fields current_password, password, password_confirmation) --}}
                        <form method="POST" action="{{ route('user-password.update') }}" class="card border-0 shadow-sm rounded-4 mb-4" id="passwordForm" novalidate>
                            @csrf
                            @method('PUT')
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Change password</h2>
                                <p class="small text-body-secondary">Use at least 8 characters with a mix of letters and numbers.</p>
                                @if (session('status') === 'password-updated')
                                    <div class="alert alert-success small py-2"><i class="bi bi-check-circle me-1"></i> Your password was updated.</div>
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
                                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-key me-1"></i> Update password</button>
                            </div>
                        </form>

                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Two-factor authentication</h2>
                                <p class="small text-body-secondary">A code from your phone, asked for after your password.</p>
                                <div class="d-flex flex-wrap align-items-center gap-3 rounded-3 border p-3 mb-3 {{ $twoFactorOn ? 'border-primary-subtle bg-primary-subtle' : 'border-warning-subtle bg-warning-subtle' }}">
                                    <div class="icon-circle rounded-3 bg-white d-flex align-items-center justify-content-center {{ $twoFactorOn ? 'text-primary' : 'text-warning-emphasis' }}">
                                        <i class="bi {{ $twoFactorOn ? 'bi-shield-check' : 'bi-shield-exclamation' }} fs-5"></i>
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
                                        <i class="bi bi-phone me-1"></i> {{ $twoFactorOn ? 'View recovery codes' : 'Set up two-factor' }}
                                    </a>
                                </div>
                                <div class="small text-body-secondary d-flex gap-2"><i class="bi bi-info-circle"></i>
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
                                <span class="small text-body-secondary me-auto"><i class="bi bi-clock me-1"></i>You are signed out automatically after a period of inactivity.</span>
                                {{-- TODO (backend): a route that calls Auth::logoutOtherDevices($password) --}}
                                <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Coming soon"><i class="bi bi-box-arrow-right me-1"></i> Sign out of other devices</button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ========== NOTIFICATIONS ========== --}}
                @if (in_array('notifications', $sections))
                    <div class="tab-pane fade {{ $openSection === 'notifications' ? 'show active' : '' }}" id="section-notifications" role="tabpanel">
                        <form class="card border-0 shadow-sm rounded-4" data-preferences="notifications">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Email notifications</h2>
                                <p class="small text-body-secondary">Sent to <b class="text-body">{{ $profile['email'] }}</b>. In-app alerts on your Dashboard are always on.</p>
                                {{-- The switches come from SettingsController::notificationGroups() --}}
                                @foreach ($notificationGroups as $heading => $switches)
                                    @if ($heading)
                                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mt-4">{{ $heading }}</div>
                                    @endif
                                    @foreach ($switches as [$key, $label, $description, $default])
                                        @include('settings.partials.switch', ['name' => $key, 'label' => $label, 'description' => $description, 'checked' => $default])
                                    @endforeach
                                @endforeach
                                @if (count($notificationGroups) > 1)
                                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mt-4">Security</div>
                                @endif
                                @include('settings.partials.switch', ['name' => 'security', 'label' => 'Security alerts', 'description' => 'Sign-ins from a new device and password changes. Always on for your protection.', 'checked' => true, 'disabled' => true])
                            </div>
                            @include('settings.partials.save-footer')
                        </form>
                    </div>
                @endif

                {{-- ========== REPORT PREFERENCES (President / Chancellor) ========== --}}
                {{-- ========== BRIEFING (President / Chancellor) ========== --}}
                @if (in_array('briefing', $sections))
                    <div class="tab-pane fade {{ $openSection === 'briefing' ? 'show active' : '' }}" id="section-briefing" role="tabpanel">
                        <form class="card border-0 shadow-sm rounded-4" data-preferences="briefing">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Briefing</h2>
                                <p class="small text-body-secondary">Choose what your Briefing page shows.</p>
                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mt-3">Needs your attention</div>
                                @include('settings.partials.switch', ['name' => 'conversion', 'label' => 'Conversion changes', 'description' => 'Flag when applied-to-enrolled conversion moves by 0.5 points or more.', 'checked' => true])
                                @include('settings.partials.switch', ['name' => 'dropoff', 'label' => 'Funnel drop-offs', 'description' => "Flag the step where the most applicants are lost, such as passers who haven't paid.", 'checked' => true])
                                @include('settings.partials.switch', ['name' => 'growth', 'label' => 'Fast growth or decline', 'description' => 'Flag units whose applicants change by 15% or more from last year.', 'checked' => true])
                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mt-4 mb-2">Units at a glance</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($units as $i => $unit)
                                        <input type="checkbox" class="btn-check" name="unit:{{ $unit }}" id="briefUnit{{ $i }}" checked autocomplete="off">
                                        <label class="btn btn-sm btn-outline-primary rounded-pill px-3" for="briefUnit{{ $i }}">{{ $unit }}</label>
                                    @endforeach
                                </div>
                                <div class="form-text">Unchecked units are hidden from your Briefing. They still appear in Analytics.</div>
                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mt-4 mb-2">Compare with</div>
                                @foreach (['last' => 'Last academic year', 'week' => 'Same week last year (coming soon)', 'average' => 'Average of the last three years (coming soon)'] as $value => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="compare" id="compare-{{ $value }}" value="{{ $value }}" @checked($value === 'last') @disabled($value !== 'last')>
                                        <label class="form-check-label small" for="compare-{{ $value }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @include('settings.partials.save-footer')
                        </form>
                    </div>
                @endif

                @if (in_array('reports', $sections))
                    <div class="tab-pane fade {{ $openSection === 'reports' ? 'show active' : '' }}" id="section-reports" role="tabpanel">
                        <form class="card border-0 shadow-sm rounded-4" data-preferences="reports">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Report preferences</h2>
                                <p class="small text-body-secondary">These become the starting values whenever you generate a report.</p>

                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Defaults</div>
                                <div class="form-label small fw-bold text-uppercase text-body-secondary">Default format</div>
                                <div class="btn-group mb-4" role="group" aria-label="Default format">
                                    @foreach (['PDF', 'Excel', 'Print'] as $format)
                                        <input type="radio" class="btn-check" name="format" id="defaultFormat{{ $format }}" value="{{ $format }}" @checked($format === 'PDF')>
                                        <label class="btn btn-outline-primary px-4" for="defaultFormat{{ $format }}">{{ $format }}</label>
                                    @endforeach
                                </div>

                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-1">Options</div>
                                @include('settings.partials.switch', ['name' => 'compare', 'label' => 'Compare with the previous academic year', 'description' => 'Adds year-over-year change to new reports.', 'checked' => true])
                                @include('settings.partials.switch', ['name' => 'ai', 'label' => 'Include an AI summary', 'description' => 'Adds a short, descriptive summary of the figures. It is not a forecast.', 'checked' => false])

                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3 mt-3">Send a copy to</div>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    @foreach ($recipients as $person)
                                        <input type="checkbox" class="btn-check" name="to" id="copy{{ $person['initials'] }}" value="{{ $person['initials'] }}" autocomplete="off">
                                        <label class="btn btn-sm btn-outline-secondary rounded-pill" for="copy{{ $person['initials'] }}">{{ $person['name'] }}</label>
                                    @endforeach
                                </div>
                                <div class="small text-body-secondary">Selected people receive a copy each time you generate a report. You can change this for each report.</div>
                            </div>
                            @include('settings.partials.save-footer')
                        </form>
                    </div>
                @endif

                {{-- ========== AI ASSISTANT ========== --}}
                @if (in_array('ai', $sections))
                    <div class="tab-pane fade {{ $openSection === 'ai' ? 'show active' : '' }}" id="section-ai" role="tabpanel">
                        <form class="card border-0 shadow-sm rounded-4" data-preferences="ai">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">AI Assistant</h2>
                                <p class="small text-body-secondary">The assistant helps you read enrollment figures in plain language.</p>
                                @include('settings.partials.switch', ['name' => 'button', 'label' => 'Show the AI button on every page', 'description' => 'The round button at the bottom-right of your pages. Turn it off to hide it.', 'checked' => true])
                                @include('settings.partials.switch', ['name' => 'takeaways', 'label' => 'Show AI-written takeaways', 'description' => 'AI summaries on your pages, such as Key Takeaways on the Dashboard and AI Insights on Analytics.', 'checked' => true])
                                <div class="small text-body-secondary bg-body-tertiary rounded-3 p-3 mt-3 d-flex gap-2">
                                    <i class="bi bi-info-circle"></i>
                                    <span>The assistant only reads the figures on the page you are viewing. It never changes data, and each page starts a new chat. AI answers can contain mistakes, so verify important figures.</span>
                                </div>
                            </div>
                            @include('settings.partials.save-footer')
                        </form>
                    </div>
                @endif

                {{-- ========== DISPLAY (President / Chancellor) ========== --}}
                @if (in_array('display', $sections))
                    <div class="tab-pane fade {{ $openSection === 'display' ? 'show active' : '' }}" id="section-display" role="tabpanel">
                        <form class="card border-0 shadow-sm rounded-4" data-preferences="display">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-0">Display</h2>
                                <p class="small text-body-secondary">Choose what you see first when you open Analytics.</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="displayUnit" class="form-label small fw-bold text-uppercase text-body-secondary">Unit shown first on Analytics</label>
                                        <select class="form-select" name="unit" id="displayUnit">
                                            <option value="">All Units</option>
                                            @foreach ($units as $unit)
                                                <option>{{ $unit }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-label small fw-bold text-uppercase text-body-secondary">Default trend chart</div>
                                        <div class="btn-group" role="group" aria-label="Default trend chart">
                                            @foreach (['line' => 'Line', 'bar' => 'Bar'] as $value => $label)
                                                <input type="radio" class="btn-check" name="chart" id="chart{{ $label }}" value="{{ $value }}" @checked($value === 'line')>
                                                <label class="btn btn-outline-primary px-4" for="chart{{ $label }}">{{ $label }}</label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('settings.partials.save-footer')
                        </form>
                    </div>
                @endif
                @if (in_array('year', $sections))
                    @include('settings.partials.system')
                @endif
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3">
        <div class="toast align-items-center text-bg-dark border-0" id="settingsToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle me-1"></i> <span id="settingsToastText"></span></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Preferences are kept in this browser, one set per user (see layouts/app.blade.php for where they apply)
        const STORE = 'iaris.settings.{{ auth()->id() }}';
        const read = () => { try { return JSON.parse(localStorage.getItem(STORE)) || {}; } catch (e) { return {}; } };
        const write = settings => {
            try { localStorage.setItem(STORE, JSON.stringify(settings)); return true; } catch (e) { return false; }
        };
        const toast = text => {
            document.getElementById('settingsToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance(document.getElementById('settingsToast')).show();
        };

        // Open a section from the address bar, e.g. /settings#security
        function openFromHash() {
            const button = document.querySelector(`#sectionNav [data-section="${location.hash.slice(1)}"]`);
            if (button) bootstrap.Tab.getOrCreateInstance(button).show();
        }
        // (After a password mistake, stay on Security instead)
        if (!{{ $passwordErrors->any() ? 'true' : 'false' }}) openFromHash();
        window.addEventListener('hashchange', openFromHash);
        document.querySelectorAll('#sectionNav [data-section]').forEach(b => b.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + b.dataset.section)));

        // ---- Preference cards: fill from what was saved, enable "Save" after a change ----
        document.querySelectorAll('[data-preferences]').forEach(form => {
            const key = form.dataset.preferences;
            const saved = read()[key] || {};
            const save = form.querySelector('[data-save]');
            const dirty = form.querySelector('[data-dirty]');

            // Put saved values back into the form
            form.querySelectorAll('input, select').forEach(field => {
                if (!(field.name in saved) || field.disabled) return;
                if (field.type === 'checkbox' && field.name === 'to') field.checked = saved.to.includes(field.value);
                else if (field.type === 'checkbox') field.checked = saved[field.name];
                else if (field.type === 'radio') field.checked = field.value === saved[field.name];
                else field.value = saved[field.name];
            });

            form.addEventListener('change', () => { save.disabled = false; dirty.classList.remove('d-none'); });
            form.addEventListener('submit', e => {
                e.preventDefault();
                const values = {};
                form.querySelectorAll('input, select').forEach(field => {
                    if (field.disabled) return;
                    if (field.type === 'checkbox' && field.name === 'to') (values.to ??= []).push(...(field.checked ? [field.value] : []));
                    else if (field.type === 'checkbox') values[field.name] = field.checked;
                    else if (field.type === 'radio') { if (field.checked) values[field.name] = field.value; }
                    else values[field.name] = field.value;
                });
                const settings = read();
                settings[key] = values;
                if (!write(settings)) { toast('Could not save: this browser is blocking storage.'); return; }
                save.disabled = true;
                dirty.classList.add('d-none');
                if (key === 'ai') applyAi(values);
                toast('Settings saved');
            });
        });

        // ---- System settings (Super Admin): checked, but there's nowhere to save them yet ----
        // TODO (backend): a settings table and a route to save these
        document.querySelectorAll('[data-system]').forEach(form => {
            const save = form.querySelector('[data-save]');
            const dirty = form.querySelector('[data-dirty]');
            form.addEventListener('input', () => { save.disabled = false; dirty.classList.remove('d-none'); });
            form.addEventListener('change', () => { save.disabled = false; dirty.classList.remove('d-none'); });
            form.addEventListener('submit', e => {
                e.preventDefault();
                form.classList.add('was-validated');
                if (!form.checkValidity()) return form.querySelector(':invalid')?.focus();
                save.disabled = true;
                dirty.classList.add('d-none');
                toast('Checked, but not saved to the server yet: system settings need the backend.');
            });
        });

        // The AI choices apply right away on this page too
        function applyAi(ai) {
            document.getElementById('aiWidget')?.classList.toggle('d-none', ai.button === false);
        }

        // ---- Photo: shrunk to 160px and kept in this browser ----
        const avatar = document.getElementById('bigAvatar');
        const initials = avatar?.textContent;
        const photoRemove = document.getElementById('photoRemove');
        function showPhoto(src) {
            document.querySelectorAll('[data-avatar]').forEach(el => {
                if (src) { el.innerHTML = '<img alt="" class="w-100 h-100 object-fit-cover">'; el.firstChild.src = src; }
                else el.textContent = el.dataset.initials || initials;
            });
            photoRemove?.classList.toggle('d-none', !src);
        }
        showPhoto(read().photo);
        document.getElementById('photoInput')?.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const size = 160, side = Math.min(img.width, img.height);
                canvas.width = canvas.height = size;
                // Crop to a centred square, then shrink
                canvas.getContext('2d').drawImage(img, (img.width - side) / 2, (img.height - side) / 2, side, side, 0, 0, size, size);
                const data = canvas.toDataURL('image/jpeg', 0.85);
                const settings = read();
                settings.photo = data;
                if (!write(settings)) { toast('Could not save the photo in this browser.'); return; }
                showPhoto(data);
                toast('Photo updated');
                URL.revokeObjectURL(img.src);
            };
            img.src = URL.createObjectURL(file);
        });
        photoRemove?.addEventListener('click', () => {
            const settings = read();
            delete settings.photo;
            write(settings);
            showPhoto(null);
            document.getElementById('photoInput').value = '';
            toast('Photo removed');
        });

        // ---- Password: strength meter, and check the two new passwords match before sending ----
        const newPassword = document.getElementById('newPassword');
        newPassword?.addEventListener('input', () => {
            const v = newPassword.value;
            let score = 0;
            if (v.length >= 8) score++;
            if (v.length >= 12) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            score = v ? Math.max(1, Math.min(score, 4)) : 0;
            const [label, tone] = [['—', ''], ['Weak', 'danger'], ['Fair', 'warning'], ['Good', 'info'], ['Strong', 'primary']][score];
            const bar = document.getElementById('strengthBar');
            bar.style.width = score * 25 + '%';
            bar.className = `progress-bar ${tone ? 'bg-' + tone : ''}`;
            document.getElementById('strengthText').textContent = 'Strength: ' + label;
        });
        document.getElementById('passwordForm')?.addEventListener('submit', e => {
            const current = document.getElementById('currentPassword');
            const confirm = document.getElementById('confirmPassword');
            const checks = [
                [current, !current.value],
                [newPassword, !(newPassword.value.length >= 8 && /[A-Za-z]/.test(newPassword.value) && /\d/.test(newPassword.value))],
                [confirm, !confirm.value || confirm.value !== newPassword.value],
            ];
            checks.forEach(([field, bad]) => field.classList.toggle('is-invalid', bad));
            if (checks.some(([, bad]) => bad)) e.preventDefault();
        });
    </script>
@endsection
