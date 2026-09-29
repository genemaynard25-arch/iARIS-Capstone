@extends('layouts.app')

@section('title', 'iARIS — Access Control')
@section('page-title', 'Access Control')
@section('page-subtitle', 'Manage role permissions and temporary admin access across all iARIS modules')

@section('content')
    {{-- Permanent administrator --}}
    <div class="bg-iaris text-white rounded-4 shadow-sm p-4 mb-4 d-flex flex-wrap align-items-center gap-3">
        <div class="icon-circle rounded-3 bg-white bg-opacity-10 border border-white border-opacity-25 fs-5 d-flex align-items-center justify-content-center">
            <i class="bi bi-star-fill"></i>
        </div>
        <div class="flex-grow-1">
            <h2 class="fs-6 fw-bold mb-1">{{ $permanentAdmin['name'] }} — Permanent Administrator</h2>
            <p class="small text-white-50 mb-0">Full system access · Cannot be restricted · Access Control is managed only by this account</p>
        </div>
        <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2">
            <i class="bi bi-lock-fill text-iaris-pale me-1"></i> Full Access · Permanent
        </span>
    </div>

    {{-- Temporary admin --}}
    <div class="card border-0 border-start border-4 border-warning shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="fs-6 fw-bold mb-1">Temporary Admin Access</h2>
                    <p class="small text-body-secondary mb-0">Give another staff member admin-level access for a limited time. Access ends on its own when the period expires.</p>
                </div>
                <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#tempModal">
                    <i class="bi bi-person-fill-lock me-1"></i> Assign Temporary Admin
                </button>
            </div>

            {{-- The active temporary admin. Always in the page so the script can fill it in; hidden when there is none. --}}
            <div class="d-flex flex-wrap align-items-center gap-3 p-3 rounded-3 bg-warning-subtle border border-warning-subtle {{ $tempAdmin ? '' : 'd-none' }}" id="tempRow">
                <div class="icon-circle rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center" data-temp="initials">{{ $tempAdmin['initials'] ?? '' }}</div>
                <div class="flex-grow-1">
                    <div class="fw-bold"><span data-temp="name">{{ $tempAdmin['name'] ?? '' }}</span> — <span data-temp="role">{{ $tempAdmin['role'] ?? '' }}</span></div>
                    <div class="small text-body-secondary" data-temp="meta">
                        @if ($tempAdmin)
                            {{ $tempAdmin['email'] }} · Granted by {{ $tempAdmin['grantedBy'] }} on {{ $tempAdmin['grantedOn'] }}
                        @endif
                    </div>
                </div>
                <span class="badge rounded-pill bg-white text-warning-emphasis border border-warning-subtle px-3 py-2">
                    <i class="bi bi-clock me-1"></i> Expires <span data-temp="expires">{{ $tempAdmin['expires'] ?? '' }}</span>
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#revokeModal">
                    <i class="bi bi-slash-circle me-1"></i> Revoke
                </button>
            </div>

            <div class="small text-body-secondary p-3 rounded-3 bg-body-tertiary border {{ $tempAdmin ? 'd-none' : '' }}" id="tempEmpty">
                <i class="bi bi-info-circle me-1"></i> No temporary admin is active right now.
            </div>
        </div>
    </div>

    {{-- Permission matrix --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="fs-6 fw-bold mb-1">Role Permission Matrix</h2>
                    <p class="small text-body-secondary mb-0">Turn access on or off per role and module. Changes take effect when you save.</p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis px-3 py-2 d-none" id="unsavedBadge"></span>
                    <button type="button" class="btn btn-light border fw-semibold" id="discardButton" disabled>Discard</button>
                    <button type="button" class="btn btn-primary fw-semibold" id="saveButton" disabled>
                        <i class="bi bi-floppy me-1"></i> Save Changes
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0 matrix-table">
                    <thead>
                        <tr>
                            <th class="small text-uppercase text-body-secondary fw-bold align-bottom">Module</th>
                            @foreach ($roles as $role)
                                <th class="text-center">
                                    <div class="icon-circle rounded-3 bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis d-flex align-items-center justify-content-center mx-auto mb-1">
                                        <i class="bi {{ $role['icon'] }}"></i>
                                    </div>
                                    <div class="small fw-bold text-nowrap">{{ $role['name'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($modules as $section => $sectionModules)
                            {{-- Section label in its own cell so it stays visible when the table scrolls sideways --}}
                            <tr>
                                <td class="bg-body-tertiary small fw-bold tracking-wide text-primary text-uppercase py-2">{{ $section }}</td>
                                <td colspan="{{ count($roles) }}" class="bg-body-tertiary"></td>
                            </tr>
                            @foreach ($sectionModules as $module)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $module['name'] }}</div>
                                        <div class="small text-body-secondary">{{ $module['description'] }}</div>
                                    </td>
                                    @foreach ($roles as $role)
                                        @php
                                            $isAdmin = $role['key'] === 'admin';
                                            $locked = $isAdmin || ! empty($module['adminOnly']);
                                            $on = $isAdmin || in_array($role['key'], $module['on']);
                                        @endphp
                                        <td class="text-center">
                                            <div class="form-switch d-inline-block p-0 m-0">
                                                <input class="form-check-input m-0" type="checkbox" role="switch"
                                                    aria-label="{{ $role['name'] }}: {{ $module['name'] }}"
                                                    {{ $on ? 'checked' : '' }}
                                                    @if ($locked)
                                                        disabled title="{{ $isAdmin ? 'IATO Admin always has access' : 'Only the IATO Admin can have this' }}"
                                                    @else
                                                        data-perm data-role-name="{{ $role['name'] }}" data-module-name="{{ $module['name'] }}"
                                                    @endif
                                                >
                                            </div>
                                            @if ($locked)
                                                <div><i class="bi bi-lock-fill small text-body-secondary" aria-hidden="true"></i></div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="small text-body-secondary mt-3 mb-0">
                <i class="bi bi-lock-fill me-1"></i> Locked: IATO Admin always has every module, and Access Control and User Accounts are for the IATO Admin only.
            </p>
        </div>
    </div>

    {{-- Access change log --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="fs-6 fw-bold mb-1">Access Change Log</h2>
                    <p class="small text-body-secondary mb-0">History of all permission changes made by the Administrator</p>
                </div>
                <select class="form-select w-auto" id="typeFilter" aria-label="Filter by change type">
                    <option value="">All Changes</option>
                    @foreach ($changeTypes as $type => $tone)
                        <option>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-uppercase text-nowrap">
                            <th class="text-body-secondary fw-bold">Change</th>
                            <th class="text-body-secondary fw-bold">Role / User Affected</th>
                            <th class="text-body-secondary fw-bold">Module</th>
                            <th class="text-body-secondary fw-bold">Type</th>
                            <th class="text-body-secondary fw-bold">Changed By</th>
                            <th class="text-body-secondary fw-bold">Date &amp; Time</th>
                        </tr>
                    </thead>
                    <tbody id="logRows">
                        @foreach ($log as $entry)
                            @include('access-control.partials.log-row', ['entry' => $entry])
                        @endforeach
                        <tr id="logEmpty" class="d-none">
                            <td colspan="6" class="text-center text-body-secondary py-4">No changes of this type yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- A template row, copied by the script when a (sample) change is made --}}
    <template id="logRowTemplate">
        @include('access-control.partials.log-row', ['entry' => ['action' => '', 'meta' => '', 'affected' => '', 'module' => '', 'type' => '', 'by' => '', 'at' => '']])
    </template>

    {{-- Assign temporary admin --}}
    <div class="modal fade" id="tempModal" tabindex="-1" aria-labelledby="tempModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="tempForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="tempModalTitle">Assign Temporary Admin</h3>
                        <p class="small text-white-50 mb-0">Give a staff member full admin access for a limited period</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-warning d-flex gap-2 small">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>Temporary admins have <strong>full system access</strong> during the period. Access ends on its own at 11:59 PM on the end date. Only one temporary admin can be active at a time.</div>
                    </div>

                    {{-- Shown only when someone already has temporary access --}}
                    <div class="alert alert-light border d-flex gap-2 small {{ $tempAdmin ? '' : 'd-none' }}" id="replaceNote">
                        <i class="bi bi-arrow-repeat"></i>
                        <div><strong id="replaceName">{{ $tempAdmin['name'] ?? '' }}</strong>'s current temporary access will end when you assign someone new.</div>
                    </div>

                    <div class="mb-3">
                        <label for="tempStaff" class="form-label small fw-bold text-uppercase text-body-secondary">Staff member</label>
                        <select class="form-select" id="tempStaff" required>
                            <option value="">Choose a staff member…</option>
                            @foreach ($staffOptions as $staff)
                                <option value="{{ $loop->index }}">{{ $staff['name'] }} — {{ $staff['role'] }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Choose who gets temporary access.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="tempStart" class="form-label small fw-bold text-uppercase text-body-secondary">Start date</label>
                            <input type="date" class="form-control" id="tempStart" required>
                            <div class="invalid-feedback">Pick a start date from today on.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="tempEnd" class="form-label small fw-bold text-uppercase text-body-secondary">End date</label>
                            <input type="date" class="form-control" id="tempEnd" required>
                            <div class="invalid-feedback">The end date can't be before the start date.</div>
                        </div>
                    </div>

                    <div>
                        <label for="tempReason" class="form-label small fw-bold text-uppercase text-body-secondary">Reason / notes</label>
                        <input type="text" class="form-control" id="tempReason" placeholder="e.g. Admin on leave — covering operations" required>
                        <div class="invalid-feedback">Add a short reason. It goes in the change log.</div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill">
                        <i class="bi bi-person-fill-lock me-1"></i> Assign Access
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Confirm before revoking --}}
    <div class="modal fade" id="revokeModal" tabindex="-1" aria-labelledby="revokeModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-body p-4 text-center">
                    <div class="icon-circle rounded-circle bg-danger-subtle text-danger-emphasis fs-5 d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="bi bi-slash-circle"></i>
                    </div>
                    <h3 class="fs-6 fw-bold mb-2" id="revokeModalTitle">Revoke temporary access?</h3>
                    <p class="small text-body-secondary mb-0"><span id="revokeName"></span> will lose admin access right away.</p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger fw-semibold flex-fill" id="revokeButton">Revoke</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="accessToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="accessToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Front end only: nothing here is saved yet. Each action updates the page,
        // adds a "Sample only — not saved" row to the log and shows a toast.
        // TODO: send each action to a backend route instead.
        const CURRENT_USER = @json(auth()->user()->name);
        const STAFF = @json($staffOptions);
        const TYPES = @json($changeTypes);

        function toast(text) {
            document.getElementById('accessToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance(document.getElementById('accessToast')).show();
        }

        // "2026-06-14" -> "Jun 14, 2026". The T00:00 keeps it in local time instead of UTC.
        function formatDate(value) {
            const date = value instanceof Date ? value : new Date(value + 'T00:00');
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function now() {
            const date = new Date();
            return formatDate(date) + ' · ' + date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        }

        // ---- Change log ----
        function addLog(entry) {
            const row = document.getElementById('logRowTemplate').content.firstElementChild.cloneNode(true);
            const tone = TYPES[entry.type] || 'secondary';
            row.dataset.type = entry.type;
            row.querySelector('[data-cell="action"]').textContent = entry.action;
            row.querySelector('[data-cell="meta"]').textContent = entry.meta;
            row.querySelector('[data-cell="affected"]').textContent = entry.affected;
            row.querySelector('[data-cell="module"]').textContent = entry.module;
            row.querySelector('[data-cell="type"]').textContent = entry.type;
            row.querySelector('[data-cell="type"]').className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
            row.querySelector('[data-cell="by"]').textContent = CURRENT_USER;
            row.querySelector('[data-cell="at"]').textContent = now();
            document.getElementById('logRows').prepend(row);
            applyTypeFilter();
        }

        function applyTypeFilter() {
            const wanted = document.getElementById('typeFilter').value;
            let shown = 0;
            document.querySelectorAll('#logRows tr[data-type]').forEach(row => {
                const match = !wanted || row.dataset.type === wanted;
                row.classList.toggle('d-none', !match);
                if (match) shown++;
            });
            document.getElementById('logEmpty').classList.toggle('d-none', shown > 0);
        }
        document.getElementById('typeFilter').addEventListener('change', applyTypeFilter);

        // ---- Permission matrix ----
        // A checkbox remembers the state it loaded with in .defaultChecked,
        // so "changed" just means checked is different from defaultChecked.
        const switches = [...document.querySelectorAll('[data-perm]')];
        const changed = () => switches.filter(input => input.checked !== input.defaultChecked);

        function updateChanges() {
            const count = changed().length;
            switches.forEach(input => input.closest('td').classList.toggle('bg-warning-subtle', input.checked !== input.defaultChecked));
            const badge = document.getElementById('unsavedBadge');
            badge.textContent = `${count} unsaved change${count === 1 ? '' : 's'}`;
            badge.classList.toggle('d-none', count === 0);
            document.getElementById('saveButton').disabled = count === 0;
            document.getElementById('discardButton').disabled = count === 0;
        }
        switches.forEach(input => input.addEventListener('change', updateChanges));

        document.getElementById('discardButton').addEventListener('click', () => {
            switches.forEach(input => input.checked = input.defaultChecked);
            updateChanges();
        });

        document.getElementById('saveButton').addEventListener('click', () => {
            const list = changed();
            list.forEach(input => {
                const granted = input.checked;
                addLog({
                    action: `${input.dataset.moduleName} access ${granted ? 'granted' : 'revoked'}`,
                    meta: 'Sample only — not saved',
                    affected: input.dataset.roleName,
                    module: input.dataset.moduleName,
                    type: granted ? 'Granted' : 'Revoked',
                });
                input.defaultChecked = input.checked;
            });
            updateChanges();
            toast(`${list.length} change${list.length === 1 ? '' : 's'} added to the log as samples. Permissions will really save once the backend is connected.`);
        });

        // Warn before leaving the page with unsaved switches
        window.addEventListener('beforeunload', e => {
            if (changed().length) e.preventDefault();
        });

        // ---- Temporary admin ----
        const tempRow = document.getElementById('tempRow');
        const tempForm = document.getElementById('tempForm');
        const start = document.getElementById('tempStart');
        const end = document.getElementById('tempEnd');
        const hasTempAdmin = () => !tempRow.classList.contains('d-none');
        const tempName = () => tempRow.querySelector('[data-temp="name"]').textContent;

        function showTempAdmin(show) {
            tempRow.classList.toggle('d-none', !show);
            document.getElementById('tempEmpty').classList.toggle('d-none', show);
            document.getElementById('replaceNote').classList.toggle('d-none', !show);
            document.getElementById('replaceName').textContent = show ? tempName() : '';
        }

        // Fill in today's date (and two days after) each time the pop-up opens with empty dates
        document.getElementById('tempModal').addEventListener('show.bs.modal', () => {
            const today = new Date();
            const iso = date => new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
            start.min = iso(today);
            if (!start.value) start.value = iso(today);
            if (!end.value) end.value = iso(new Date(today.getTime() + 2 * 86400000));
        });

        // Dates in yyyy-mm-dd form compare correctly as plain text
        function checkDates() {
            end.min = start.value;
            end.setCustomValidity(end.value && start.value && end.value < start.value ? 'End date is before start date' : '');
        }
        start.addEventListener('change', checkDates);
        end.addEventListener('change', checkDates);

        tempForm.addEventListener('submit', e => {
            e.preventDefault();
            checkDates();
            tempForm.classList.add('was-validated');
            if (!tempForm.checkValidity()) return;

            const staff = STAFF[document.getElementById('tempStaff').value];
            const replaced = hasTempAdmin() ? tempName() : null;
            // "Cruz, Jasmine" -> "JC"
            const [last, first = ''] = staff.name.split(',').map(part => part.trim());
            const initials = (first.charAt(0) + last.charAt(0)).toUpperCase();

            tempRow.querySelector('[data-temp="initials"]').textContent = initials;
            tempRow.querySelector('[data-temp="name"]').textContent = staff.name;
            tempRow.querySelector('[data-temp="role"]').textContent = staff.role;
            tempRow.querySelector('[data-temp="meta"]').textContent =
                `${staff.email} · Granted by ${CURRENT_USER} on ${formatDate(new Date())} · Starts ${formatDate(start.value)}`;
            tempRow.querySelector('[data-temp="expires"]').textContent = `${formatDate(end.value)} · 11:59 PM`;
            showTempAdmin(true);

            addLog({
                action: 'Temporary admin access assigned',
                meta: `${staff.name} until ${formatDate(end.value)}` + (replaced && replaced !== staff.name ? ` (replaces ${replaced})` : '') +
                    ` · ${document.getElementById('tempReason').value} · Sample only — not saved`,
                affected: staff.role,
                module: 'All Modules',
                type: 'Temporary',
            });

            bootstrap.Modal.getInstance(document.getElementById('tempModal')).hide();
            tempForm.reset();
            tempForm.classList.remove('was-validated');
            toast(`${staff.name} shown as temporary admin (sample only). Real access will change once the backend is connected.`);
        });

        document.getElementById('revokeModal').addEventListener('show.bs.modal', () => {
            document.getElementById('revokeName').textContent = tempName();
        });

        document.getElementById('revokeButton').addEventListener('click', () => {
            const name = tempName();
            addLog({
                action: 'Temporary admin access revoked',
                meta: `${name}'s admin access ended early · Sample only — not saved`,
                affected: tempRow.querySelector('[data-temp="role"]').textContent,
                module: 'All Modules',
                type: 'Revoked',
            });
            showTempAdmin(false);
            bootstrap.Modal.getInstance(document.getElementById('revokeModal')).hide();
            toast(`${name}'s temporary access removed (sample only).`);
        });
    </script>
@endsection
