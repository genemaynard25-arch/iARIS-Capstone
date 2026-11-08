@extends('layouts.app')

{{--
    System Overview: TOIC's home page (ToicController::dashboard).
    From the database: accounts per role, who is signed in now, accounts without MFA.
    Sample until measured: uptime, page load, storage, backups, service checks.
    FRONT END ONLY: "Back up now", "Restart", "Notify all users" (toasts). Maintenance mode is kept in this
    browser (partials/issues-store) and shows the notice to every user there.
--}}

@section('title', 'iARIS — System Overview')
@section('page-title', 'System Overview')
@section('page-subtitle', 'How iARIS is running this week. Updated every 5 minutes.')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2"><i class="bi bi-person-lock me-1"></i> No applicant data</span>
    </div>
    @include('toic.partials.privacy')

    {{-- Four tiles --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        @foreach (array_merge(array_slice($tiles, 0, 2), [['People signed in now', $signedIn, "of {$accounts} accounts"]], array_slice($tiles, 2)) as [$label, $value, $note])
            <div class="col">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                        <div class="fs-3 fw-bold">{{ $value }}</div>
                        <div class="small text-body-secondary">{{ $note }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Needs your attention: the newest open issue, the certificate, accounts without MFA --}}
    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
        <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-bell text-warning me-1"></i> Needs your attention</h2>
        <span class="small text-body-secondary">From system checks and IATO</span>
    </div>
    <div class="row row-cols-1 row-cols-lg-3 g-3 mb-4">
        <div class="col" id="topIssue"></div>
        <div class="col">
            <a href="{{ url('/toic/security') }}" class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 h-100 text-decoration-none text-body">
                <div class="card-body d-flex gap-3">
                    <span class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-lock"></i></span>
                    <div><div class="fw-bold">SSL certificate renews in 21 days</div><div class="small text-body-secondary">Expires Jul 3, 2026. Auto-renewal is on; check that it completes.</div><div class="small fw-semibold text-primary mt-1">See Security →</div></div>
                </div>
            </a>
        </div>
        @if ($withoutMfa)
            <div class="col">
                <a href="{{ url('/toic/security') }}" class="card border-0 border-start border-4 border-danger shadow-sm rounded-4 h-100 text-decoration-none text-body">
                    <div class="card-body d-flex gap-3">
                        <span class="rounded-circle bg-danger-subtle text-danger-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-shield-exclamation"></i></span>
                        <div><div class="fw-bold">{{ $withoutMfa }} {{ \Illuminate\Support\Str::plural('account', $withoutMfa) }} {{ $withoutMfa === 1 ? 'has' : 'have' }} no MFA yet</div><div class="small text-body-secondary">Most are new accounts waiting for their first sign-in. IATO follows up with each office.</div><div class="small fw-semibold text-primary mt-1">See Security →</div></div>
                    </div>
                </a>
            </div>
        @endif
    </div>

    <div class="row g-4 mb-4">
        {{-- Signed in now, by role (counts only) --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Signed in now, by role</h2>
                    <span class="small text-body-secondary">Counts only</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light"><tr><th class="ps-4 text-body-secondary">Role</th><th class="text-end text-body-secondary">Signed in</th><th class="text-end pe-4 text-body-secondary">Accounts</th></tr></thead>
                        <tbody>
                            @foreach ($roles as $r)
                                <tr><td class="ps-4">{{ $r['label'] }}</td><td class="text-end fw-bold">{{ $r['now'] }}</td><td class="text-end pe-4">{{ $r['accounts'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="small text-body-secondary px-4 py-2">Active in the last 30 minutes.</div>
            </div>
        </div>

        {{-- Backups --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Backups</h2>
                    <button type="button" class="btn btn-sm btn-primary fw-semibold" id="backupNow"><i class="bi bi-cloud-arrow-up me-1"></i> Back up now</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light"><tr><th class="ps-4 text-body-secondary">Backup</th><th class="text-body-secondary">When</th><th class="text-end text-body-secondary">Size</th><th class="pe-4 text-body-secondary">Status</th></tr></thead>
                        <tbody id="backups"></tbody>
                    </table>
                </div>
                <div class="small text-body-secondary px-4 py-2"><i class="bi bi-lock me-1"></i>Daily at 11:00 PM. Encrypted (AES-256) and kept on the DLSL server. IATO sets how long backups are kept.</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- Service checks --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Service checks</h2>
                    <span class="small text-body-secondary">Last check 8:05 AM</span>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4" id="services"></div>
            </div>
        </div>

        {{-- Maintenance mode --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="fs-6 fw-bold mb-0">Maintenance mode</h2>
                        <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" id="maintenanceOn" aria-label="Maintenance mode"></div>
                    </div>
                    <p class="small text-body-secondary">When on, every user sees a notice at the top of iARIS and changes are paused. Use it while you fix something.</p>
                    <label for="maintenanceMsg" class="form-label small fw-bold text-uppercase text-body-secondary">Notice users will see</label>
                    <textarea class="form-control" id="maintenanceMsg" rows="2" maxlength="200">iARIS is in maintenance. You can view data, but changes are paused until TOIC finishes.</textarea>
                    <div class="small mt-2" id="maintenanceState"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scheduled maintenance --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
            <h2 class="fs-6 fw-bold mb-0">Scheduled maintenance</h2>
            <button type="button" class="btn btn-sm btn-light border fw-semibold" id="notifyAll"><i class="bi bi-megaphone me-1"></i> Notify all users</button>
        </div>
        <div class="list-group list-group-flush rounded-bottom-4">
            <div class="list-group-item d-flex gap-3 px-4 py-3">
                <i class="bi bi-calendar-event text-primary fs-5"></i>
                <div><div class="fw-semibold small">Saturday, Jun 20, 2026 · 10:00 PM to 12:00 AM</div><div class="small text-body-secondary">Security updates and database tuning for faster imports. iARIS will be read-only during this window.</div></div>
            </div>
            <div class="list-group-item d-flex gap-3 px-4 py-3">
                <i class="bi bi-check-circle text-success fs-5"></i>
                <div><div class="fw-semibold small">Last: Saturday, May 16, 2026</div><div class="small text-body-secondary">Server updates. Finished 25 minutes early, no issues.</div></div>
            </div>
        </div>
    </div>

    @include('toic.partials.kit')
@endsection

@section('scripts')
    <script>
        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const BACKUPS = @json($backups).map(b => [...b, 'done']);
        const SERVICES = @json($services);
        const ACCOUNTS = @json($accounts);

        // ---- Needs your attention: the newest issue that still needs work ----
        (() => {
            const issue = IARIS_ISSUES.all().find(x => x.st === 'new' || x.st === 'investigating');
            if (!issue) return $('topIssue').remove();
            const last = issue.tl[issue.tl.length - 1];
            $('topIssue').innerHTML = `<a href="{{ url('/toic/issues') }}#${issue.id}" class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 h-100 text-decoration-none text-body">
                <div class="card-body d-flex gap-3">
                    <span class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-bug"></i></span>
                    <div><div class="fw-bold">${esc(issue.title)}</div><div class="small text-body-secondary">${esc(issue.src)}. ${esc(last.text)}</div><div class="small fw-semibold text-primary mt-1">Open the issue →</div></div>
                </div></a>`;
        })();

        // ---- Backups ----
        function drawBackups() {
            $('backups').innerHTML = BACKUPS.map(([what, when, size, st]) => `<tr><td class="ps-4">${esc(what)}</td><td class="text-nowrap">${esc(when)}</td><td class="text-end text-nowrap">${size}</td>
                <td class="pe-4"><span class="badge rounded-pill ${st === 'done' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'}">${st === 'done' ? 'Completed' : 'Running…'}</span></td></tr>`).join('');
        }
        drawBackups();
        $('backupNow').addEventListener('click', () => {
            BACKUPS.unshift(['Manual backup by TOIC', IARIS_ISSUES.now(), '48.3 MB', 'running']);
            drawBackups();
            toast('Backup started. (Demo only — not saved yet.)');
            setTimeout(() => { BACKUPS[0][3] = 'done'; drawBackups(); toast('Backup completed and encrypted. (Demo only — not saved yet.)'); }, 1600);
        });

        // ---- Service checks ----
        function drawServices() {
            $('services').innerHTML = SERVICES.map(([name, status, ok], i) => `
                <div class="list-group-item d-flex align-items-center gap-3 px-4 py-2">
                    <i class="bi ${ok === 'run' ? 'bi-arrow-repeat text-primary' : ok ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-warning'}"></i>
                    <div class="flex-grow-1 small"><div class="fw-semibold">${esc(name)}</div><div class="text-body-secondary">${esc(status)}</div></div>
                    ${name === 'SSL certificate' ? '' : `<button type="button" class="btn btn-sm btn-light border" data-restart="${i}"><i class="bi bi-arrow-clockwise me-1"></i>Restart</button>`}
                </div>`).join('');
            $('services').querySelectorAll('[data-restart]').forEach(b => b.addEventListener('click', () => {
                const s = SERVICES[+b.dataset.restart];
                s[1] = 'Restarting…'; s[2] = 'run'; drawServices();
                setTimeout(() => {
                    const slow = s[0] === 'Import worker';
                    s[1] = slow ? 'Running · restarted, still slow on large files' : 'Running · restarted just now'; s[2] = !slow;
                    drawServices();
                    toast(`${s[0]} restarted. (Demo only — not saved yet.)`);
                }, 1200);
            }));
        }
        drawServices();

        // ---- Maintenance mode (every user in this browser sees the notice) ----
        function drawMode() {
            const m = IARIS_ISSUES.mode();
            $('maintenanceOn').checked = !!m.on;
            if (m.msg) $('maintenanceMsg').value = m.msg;
            $('maintenanceState').innerHTML = m.on ? '<span class="text-warning-emphasis fw-semibold">On · every user sees the notice now</span>' : '<span class="text-body-secondary">Off · iARIS is running normally</span>';
        }
        $('maintenanceOn').addEventListener('change', () => {
            IARIS_ISSUES.setMode({ on: $('maintenanceOn').checked, msg: $('maintenanceMsg').value.trim() });
            drawMode();
            toast($('maintenanceOn').checked ? 'Maintenance mode on. All users see the notice. (Demo only — saved in this browser.)' : 'Maintenance mode off.');
            // Show or hide the notice on this page too
            const banner = $('maintenanceBanner');
            banner.querySelector('span').textContent = $('maintenanceMsg').value.trim();
            banner.classList.toggle('d-none', !$('maintenanceOn').checked);
        });
        $('maintenanceMsg').addEventListener('change', () => {
            if (!IARIS_ISSUES.mode().on) return;
            IARIS_ISSUES.setMode({ on: true, msg: $('maintenanceMsg').value.trim() });
            $('maintenanceBanner').querySelector('span').textContent = $('maintenanceMsg').value.trim();
            toast('Notice updated.');
        });
        drawMode();

        $('notifyAll').addEventListener('click', () => toast(`Notice sent to all ${ACCOUNTS} accounts: maintenance on Jun 20, 10 PM to 12 AM. (Demo only — not sent yet.)`));
    </script>
@endsection
