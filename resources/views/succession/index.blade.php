@extends('layouts.app')

{{--
    Super User Succession (SuccessionController). The accounts are real; the handover runs on this page only.
    "View as" switches between you and the nominee so the whole flow can be shown.
--}}

@section('title', 'iARIS — Super User Succession')
@section('page-title', 'Super User Succession')
@section('page-subtitle', 'Hand over the Super User role to another account. For short absences, use Temporary Admin in Access Control.')

@php
    $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->pipe(fn ($c) => mb_strtoupper($c->first() . ($c->count() > 1 ? $c->last() : '')));
    $steps = [
        ['Nominate', 'You pick an eligible account'],
        ['Nominee accepts', 'They accept and set up a new authenticator'],
        ['You confirm', 'A second confirmation from you'],
        ['Role moves', 'Nominee becomes Super User; your account is deactivated'],
    ];
@endphp

@section('content')
    <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 small">
        <i class="bi bi-info-circle"></i>
        <span class="me-auto"><strong>Front end only:</strong> the steps below run on this page and nothing changes in the database.</span>
        {{-- Demo switch: see the page as the nominee would --}}
        <span class="fw-bold text-uppercase">View as</span>
        <div class="btn-group btn-group-sm" role="group" aria-label="View as">
            <input type="radio" class="btn-check" name="viewAs" id="viewMe" value="me" checked>
            <label class="btn btn-outline-dark" for="viewMe">Current Super User</label>
            <input type="radio" class="btn-check" name="viewAs" id="viewNominee" value="nominee">
            <label class="btn btn-outline-dark" for="viewNominee">Nominee</label>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h2 class="fs-6 fw-bold mb-1">Handover</h2>
                    <p class="small text-body-secondary">iARIS always has exactly one Super User. A handover moves the role to another account in four steps.</p>

                    <div class="d-flex align-items-center gap-3 rounded-3 bg-iaris text-white p-3 mb-4">
                        <span class="rounded-circle bg-white bg-opacity-25 fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;" id="currentInitials">{{ $initials($me['name']) }}</span>
                        <div class="flex-grow-1">
                            <div class="fw-bold" id="currentName">{{ $me['name'] }}</div>
                            <div class="small text-white-50" id="currentMeta">Super User since {{ $me['since'] }} · {{ $me['email'] }}</div>
                        </div>
                        <span class="badge rounded-pill bg-white text-primary-emphasis"><i class="bi bi-star-fill me-1"></i> Current Super User</span>
                    </div>

                    {{-- The four steps: the script marks done / current --}}
                    <ol class="list-unstyled row row-cols-2 row-cols-md-4 g-3 mb-4" id="steps">
                        @foreach ($steps as $i => [$title, $text])
                            <li class="col" data-step="{{ $i }}">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="rounded-circle border border-2 fw-bold small d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;" data-dot>{{ $i + 1 }}</span>
                                    <span class="small fw-bold">{{ $title }}</span>
                                </div>
                                <div class="small text-body-secondary">{{ $text }}</div>
                            </li>
                        @endforeach
                    </ol>

                    <div id="stage"></div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="fs-6 fw-bold mb-1">Succession history</h2>
                    <p class="small text-body-secondary mb-3">Every handover step</p>
                    <div class="table-responsive">
                        <table class="table align-middle small mb-0">
                            <thead class="table-light"><tr class="text-uppercase text-nowrap"><th class="text-body-secondary">When</th><th class="text-body-secondary">By</th><th class="text-body-secondary">Event</th><th class="text-body-secondary">Details</th></tr></thead>
                            <tbody id="history">
                                <tr><td class="text-nowrap text-body-secondary">{{ $me['since'] }}</td><td class="fw-semibold">System</td><td><span class="badge rounded-pill bg-body-tertiary text-body border">Super User created</span></td><td>{{ $me['name'] }} · initial setup</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Safeguards --}}
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h2 class="fs-6 fw-bold mb-1">Safeguards</h2>
                    <p class="small text-body-secondary mb-3">Built into every change on this page</p>
                    @foreach ([
                        ['bi-shield-lock', 'Password and MFA code for every change', 'Nominating, cancelling, and confirming all ask you to sign in again, so an unattended session can\'t be used.'],
                        ['bi-person-check', 'Never zero Super Users', 'The Super User can\'t be deleted, demoted, or suspended. The role only moves through a completed handover.'],
                        ['bi-check2-all', 'Two confirmations', 'The nominee must accept and set up a new authenticator, then you confirm once more before the role moves.'],
                        ['bi-archive', 'Old account is deactivated, not deleted', 'After the handover, your account switches to Inactive in User Accounts, so its history stays intact.'],
                        ['bi-calendar-x', 'Nominations expire', 'A nomination that isn\'t accepted within 7 days is cancelled automatically.'],
                        ['bi-journal-text', 'Every step is logged', 'Each step appears in the history.'],
                    ] as [$icon, $title, $text])
                        <div class="d-flex gap-3 {{ $loop->first ? '' : 'border-top pt-3' }} pb-3">
                            <span class="icon-circle rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi {{ $icon }}"></i></span>
                            <div class="small"><div class="fw-bold">{{ $title }}</div><div class="text-body-secondary">{{ $text }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm it's you: password + 6-digit code (checked on the page only) --}}
    <div class="modal fade" id="reauthModal" tabindex="-1" aria-labelledby="reauthTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4" id="reauthForm" novalidate>
                <div class="modal-body p-4">
                    <h3 class="fs-5 fw-bold mb-1" id="reauthTitle">Confirm it's you</h3>
                    <p class="small text-body-secondary" id="reauthText">Enter your password and the 6-digit code from your authenticator app.</p>
                    <label for="reauthPassword" class="form-label small fw-bold text-uppercase">Password</label>
                    <input type="password" class="form-control mb-3" id="reauthPassword" autocomplete="current-password" required>
                    <label for="reauthCode" class="form-label small fw-bold text-uppercase">Authenticator code</label>
                    <input type="text" class="form-control font-monospace fs-5 text-center" id="reauthCode" inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="one-time-code" placeholder="••••••" required>
                    <div class="invalid-feedback">Enter your password and all 6 digits.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Nominee: set up a new authenticator before accepting --}}
    <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4" id="authForm" novalidate>
                <div class="modal-body p-4">
                    <h3 class="fs-5 fw-bold mb-1" id="authTitle">Set up a new authenticator</h3>
                    <p class="small text-body-secondary">Super User accounts need their own authenticator entry. Open Google Authenticator or Authy, tap <strong>Add account</strong>, scan the code, then enter the 6-digit number.</p>
                    <div class="rounded-3 border bg-body-tertiary d-flex align-items-center justify-content-center mx-auto mb-3 text-body-secondary small" style="width: 160px; height: 160px;">
                        <span class="text-center"><i class="bi bi-qr-code fs-1 d-block"></i>QR code appears here once the backend makes it</span>
                    </div>
                    <input type="text" class="form-control font-monospace fs-5 text-center" id="authCode" inputmode="numeric" maxlength="6" pattern="\d{6}" placeholder="••••••" aria-label="6-digit code" required>
                    <div class="invalid-feedback">Enter all 6 digits.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Verify and accept</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="succToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="succToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const ACCOUNTS = @json($accounts);
        const ME = @json($me);
        // idle -> nominated -> accepted -> done
        const state = { step: 'idle', view: 'me', picked: null, nominee: null };

        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const initials = name => { const w = name.trim().split(/\s+/); return (w[0][0] + (w.length > 1 ? w[w.length - 1][0] : '')).toUpperCase(); };
        const toast = text => { $('succToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('succToast')).show(); };
        const reauthModal = bootstrap.Modal.getOrCreateInstance($('reauthModal'));
        const authModal = bootstrap.Modal.getOrCreateInstance($('authModal'));
        const expires = () => { const d = new Date(); d.setDate(d.getDate() + 7); return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); };
        const stamp = () => new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });

        function log(by, event, details, tone = 'secondary') {
            $('history').insertAdjacentHTML('afterbegin', `<tr class="table-warning"><td class="text-nowrap text-body-secondary">${stamp()}</td><td class="fw-semibold">${esc(by)}</td>
                <td><span class="badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis">${esc(event)}</span></td><td>${esc(details)}</td></tr>`);
        }

        // "Confirm it's you" before every change
        let afterReauth = null;
        function reauth(title, text, then) {
            afterReauth = then;
            $('reauthTitle').textContent = title;
            $('reauthText').textContent = text + ' Enter your password and the 6-digit code from your authenticator app.';
            $('reauthForm').reset();
            $('reauthForm').classList.remove('was-validated');
            reauthModal.show();
        }
        $('reauthForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return;
            reauthModal.hide();
            const then = afterReauth; afterReauth = null; then?.();
        });

        const nomineeCard = n => `<div class="d-flex align-items-center gap-3 rounded-3 border p-3 mb-3">
            <span class="rounded-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">${initials(n.name)}</span>
            <div><div class="fw-bold">${esc(n.name)}</div><div class="small text-body-secondary">${esc(n.role)} · ${esc(n.office)} · ${esc(n.email)}</div></div></div>`;

        function render() {
            const index = { idle: 0, nominated: 1, accepted: 2, done: 4 }[state.step];
            document.querySelectorAll('#steps [data-step]').forEach(li => {
                const i = Number(li.dataset.step), dot = li.querySelector('[data-dot]');
                dot.className = 'rounded-circle border border-2 fw-bold small d-flex align-items-center justify-content-center flex-shrink-0 '
                    + (i < index ? 'bg-primary border-primary text-white' : i === index ? 'border-primary text-primary' : 'text-body-secondary');
                dot.innerHTML = i < index ? '<i class="bi bi-check-lg"></i>' : i + 1;
            });

            const n = state.nominee;
            let html = '';
            if (state.view === 'nominee') {
                html = state.step === 'nominated'
                    ? `<div class="alert alert-info small"><strong>${esc(ME.name)} nominated you as the next Super User.</strong> Accepting asks you to set up a new authenticator. Then ${esc(ME.name)} confirms once more before the role moves.</div>
                       ${nomineeCard(n)}<div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-primary fw-semibold" data-act="accept"><i class="bi bi-check2-circle me-1"></i> Accept and set up authenticator</button>
                       <button type="button" class="btn btn-outline-danger" data-act="decline">Decline</button></div>`
                    : `<p class="small text-body-secondary mb-0"><i class="bi bi-info-circle me-1"></i> ${state.step === 'idle' ? 'No one has been nominated yet. Switch back to the Current Super User to nominate someone.' : state.step === 'accepted' ? 'You accepted. Waiting for the Current Super User to confirm.' : 'The handover is complete.'}</p>`;
            } else if (state.step === 'idle') {
                html = `<div class="small fw-bold text-uppercase text-body-secondary mb-2">Choose a successor</div>
                    <div class="list-group mb-2">${ACCOUNTS.map(a => `<label class="list-group-item d-flex align-items-center gap-3 ${a.eligible ? '' : 'bg-body-tertiary text-body-secondary'}">
                        <input class="form-check-input m-0" type="radio" name="successor" value="${a.id}" ${a.eligible ? '' : 'disabled'} ${state.picked === a.id ? 'checked' : ''}>
                        <span class="flex-grow-1"><span class="d-block fw-semibold">${esc(a.name)}</span><span class="d-block small">${esc(a.role)} · ${esc(a.office)}</span></span>
                        <span class="badge rounded-pill ${a.eligible ? 'bg-primary-subtle text-primary-emphasis' : 'bg-body-secondary text-body-secondary'}">${esc(a.why)}</span></label>`).join('')}</div>
                    <p class="small text-body-secondary"><i class="bi bi-info-circle me-1"></i> Only active accounts with MFA set up can be nominated.</p>
                    <button type="button" class="btn btn-primary fw-semibold" data-act="nominate" ${state.picked === null ? 'disabled' : ''}><i class="bi bi-person-plus me-1"></i> Nominate as successor</button>`;
            } else if (state.step === 'nominated') {
                html = `<div class="alert alert-warning small d-flex gap-2"><i class="bi bi-hourglass-split mt-1"></i><div><strong>Waiting for ${esc(n.name)} to accept.</strong> They'll see the request when they sign in. The nomination expires on ${expires()}.</div></div>
                    ${nomineeCard(n)}<button type="button" class="btn btn-outline-danger" data-act="cancel"><i class="bi bi-x-lg me-1"></i> Cancel nomination</button>`;
            } else if (state.step === 'accepted') {
                html = `<div class="alert alert-info small d-flex gap-2"><i class="bi bi-person-check mt-1"></i><div><strong>${esc(n.name)} accepted and set up a new authenticator.</strong> Confirm once more to move the role. Your account becomes Inactive afterwards.</div></div>
                    ${nomineeCard(n)}<div class="d-flex flex-wrap gap-2"><button type="button" class="btn btn-danger fw-semibold" data-act="confirm"><i class="bi bi-arrow-left-right me-1"></i> Confirm handover</button>
                    <button type="button" class="btn btn-light border" data-act="cancel">Cancel nomination</button></div>`;
            } else {
                html = `<div class="alert alert-success small d-flex gap-2"><i class="bi bi-check-circle mt-1"></i><div><strong>Handover shown as complete.</strong> ${esc(n.name)} would now be the Super User and your account Inactive. Nothing was changed: this needs the backend.</div></div>
                    <button type="button" class="btn btn-light border" data-act="reset"><i class="bi bi-arrow-counterclockwise me-1"></i> Start the demo again</button>`;
            }
            $('stage').innerHTML = html;
        }

        $('stage').addEventListener('change', e => {
            if (e.target.name !== 'successor') return;
            state.picked = Number(e.target.value);
            render();
        });

        $('stage').addEventListener('click', e => {
            const act = e.target.closest('[data-act]')?.dataset.act;
            const n = state.nominee;
            if (act === 'nominate') {
                const pick = ACCOUNTS.find(a => a.id === state.picked);
                reauth('Nominate a successor', `You're nominating ${pick.name}.`, () => {
                    state.nominee = pick; state.step = 'nominated';
                    log(ME.name, 'Nominated', `${pick.name} · expires ${expires()}`, 'warning');
                    render(); toast(`${pick.name} nominated (on this page only).`);
                });
            } else if (act === 'cancel') {
                reauth('Cancel the nomination', `${n.name} will no longer be able to accept.`, () => {
                    log(ME.name, 'Nomination cancelled', n.name, 'danger');
                    Object.assign(state, { step: 'idle', nominee: null, picked: null });
                    render(); toast('Nomination cancelled.');
                });
            } else if (act === 'accept') {
                $('authForm').reset(); $('authForm').classList.remove('was-validated'); authModal.show();
            } else if (act === 'decline') {
                log(n.name, 'Declined', 'Nomination declined', 'danger');
                Object.assign(state, { step: 'idle', nominee: null, picked: null });
                render(); toast('Nomination declined.');
            } else if (act === 'confirm') {
                reauth('Confirm the handover', `The Super User role moves to ${n.name}.`, () => {
                    state.step = 'done';
                    log(ME.name, 'Handover confirmed', `${n.name} becomes Super User · ${ME.name} becomes Inactive`, 'primary');
                    render(); toast('Handover shown as complete. Nothing was changed: needs the backend.');
                });
            } else if (act === 'reset') {
                Object.assign(state, { step: 'idle', nominee: null, picked: null });
                render();
            }
        });

        $('authForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return;
            authModal.hide();
            state.step = 'accepted';
            log(state.nominee.name, 'Accepted', 'New authenticator set up', 'info');
            render(); toast('Accepted. Switch to the Current Super User to confirm.');
        });

        document.querySelectorAll('[name="viewAs"]').forEach(r => r.addEventListener('change', () => { state.view = r.value; render(); }));
        render();
    </script>
@endsection
