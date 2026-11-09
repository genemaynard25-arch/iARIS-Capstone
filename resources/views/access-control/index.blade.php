@extends('layouts.app')

@section('title', 'iARIS — Access Control')
@section('page-title', 'Access Control')
@section('body-class', 'access-control-page')
@section('page-breadcrumb', 'Access & Security')
@push('styles')
    <link href="{{ asset('css/access-control.css') }}" rel="stylesheet">
@endpush
@section('sidebar-brand')
    @include('partials.auth-logo', ['size' => 44, 'variant' => 'glass'])
@endsection
@section('app-banner')
    @include('partials.institution-bar')
@endsection
@section('page-subtitle', 'Manage role permissions and temporary admin access across all iARIS modules')

@section('content')
            {{-- Permanent administrator / Temporary Admin --}}
            @if ($permanentAdmin['isRealAdmin'])
                <div class="ac-admin-banner text-white d-flex flex-wrap align-items-center gap-3">
                    <div class="ac-admin-icon d-flex align-items-center justify-content-center">
                        <i class="bi bi-star-fill" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="fs-6 fw-bold mb-1">{{ $permanentAdmin['name'] }} — Super Admin</h2>
                        <p class="small text-white-50 mb-0">The only Super Admin account · Full system access · Cannot be restricted · Access Control is managed only by this account</p>
                    </div>
                    <span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 px-3 py-2">
                        <i class="bi bi-lock-fill text-iaris-pale me-1" aria-hidden="true"></i> Full Access · Sole Super Admin
                    </span>
                </div>
            @elseif ($myActiveTempGrant)
                <div class="bg-warning-subtle border border-warning rounded-4 shadow-sm p-4 mb-4 d-flex flex-wrap align-items-center gap-3">
                    <div class="icon-circle rounded-3 bg-warning text-dark fs-5 d-flex align-items-center justify-content-center">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h2 class="fs-6 fw-bold mb-1">{{ $permanentAdmin['name'] }} — Temporary Admin Access</h2>
                        <p class="small text-body-secondary mb-0">Your admin access expires {{ $myActiveTempGrant->expires_at->format('M j, Y \a\t g:i A') }}</p>
                    </div>
                </div>
            @endif

    {{-- Temporary admin access --}}
    <div class="ac-temp-card card border-0">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="fs-6 fw-bold mb-1">Temporary Admin Access</h2>
                    <p class="small text-body-secondary mb-0">Grant another staff member admin-level access for a limited time. Access goes back to normal when the period ends.</p>
                </div>
                @if ($permanentAdmin['isRealAdmin'])
                    <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#tempModal">
                        <i class="bi bi-person-plus-fill me-1" aria-hidden="true"></i> Assign Temporary Admin
                    </button>
                @endif
            </div>

            {{-- The active temporary admin. Always in the page so the script can fill it in; hidden when there is none. --}}
            <div class="ac-temp-row d-flex flex-wrap align-items-center gap-3 {{ $tempAdmin ? '' : 'd-none' }}" id="tempRow">
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
                    <i class="bi bi-clock me-1" aria-hidden="true"></i> Expires <span data-temp="expires">{{ $tempAdmin['expires'] ?? '' }}</span>
                </span>
                {{-- TODO (backend): a route that moves expires_at later. Until then Extend is shown but disabled. --}}
                <span class="d-inline-block" tabindex="0" title="Extend is coming soon">
                    <button type="button" class="btn btn-sm btn-light border fw-semibold" disabled><i class="bi bi-calendar-plus me-1" aria-hidden="true"></i> Extend</button>
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger fw-semibold" data-bs-toggle="modal" data-bs-target="#revokeModal">
                    <i class="bi bi-slash-circle me-1" aria-hidden="true"></i> Revoke
                </button>
            </div>

            <div class="ac-temp-empty small text-body-secondary {{ $tempAdmin ? 'd-none' : '' }}" id="tempEmpty">
                <i class="bi bi-info-circle me-1" aria-hidden="true"></i> No temporary admin is active. Use <strong>Assign Temporary Admin</strong> to cover an absence.
            </div>
        </div>
    </div>


    @include('access-control.partials.permissions')

    {{-- Access change log --}}
    <div class="ac-log-card card border-0">
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

    {{-- A template row, copied after a permission change is saved successfully --}}
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
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <div>Temporary admins have <strong>full system access</strong> during the period. Access ends on its own at 11:59 PM on the end date. Only one temporary admin can be active at a time.</div>
                    </div>

                    {{-- Shown only when someone already has temporary access --}}
                    <div class="alert alert-light border d-flex gap-2 small {{ $tempAdmin ? '' : 'd-none' }}" id="replaceNote">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                        <div><strong id="replaceName">{{ $tempAdmin['name'] ?? '' }}</strong>'s current temporary access will end when you assign someone new.</div>
                    </div>

                    <div class="mb-3">
                        <label for="tempStaff" class="form-label small fw-bold text-uppercase text-body-secondary">Staff member</label>
                        <select class="form-select" id="tempStaff" required>
                            <option value="">Choose a staff member…</option>
                            @foreach ($staffOptions as $staff)
                                <option value="{{ $staff['id'] }}">{{ $staff['name'] }} — {{ $staff['role'] }}</option>
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
                        <i class="bi bi-person-fill-lock me-1" aria-hidden="true"></i> Assign Access
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
                        <i class="bi bi-slash-circle" aria-hidden="true"></i>
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
        // Permission and temporary-admin actions use the existing Laravel endpoints.
        const CURRENT_USER = @json(auth()->user()->name);
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

        // The comparison checkboxes and hidden editor controls are the source of truth.
        const switches = [...document.querySelectorAll('[data-perm]')];
        const previewSwitches = [...document.querySelectorAll('[data-preview-perm]')];
        const previewChanged = () => previewSwitches.filter(input => input.value !== input.dataset.initialLevel);
        const previewSwitch = (role, module) => previewSwitches.find(input => input.dataset.role === role && input.dataset.module === module);
        const editSwitches = [...document.querySelectorAll('[data-edit-perm]')];
        const changed = () => switches.filter(input => input.checked !== input.defaultChecked);
        const editChanged = () => editSwitches.filter(input => input.checked !== input.defaultChecked);
        const matrixSwitch = (role, module) => switches.find(input => input.dataset.role === role && input.dataset.module === module);
        const editorSwitch = (role, module) => editSwitches.find(input => input.dataset.role === role && input.dataset.module === module);
        let saving = false;

        function updateChanges() {
            const accessChanges = changed();
            const editorChanges = editChanged();
            const previewChanges = previewChanged();
            const count = new Set([...accessChanges.map(input => `${input.dataset.role}:${input.dataset.module}`), ...editorChanges.map(input => `${input.dataset.role}:${input.dataset.module}`), ...previewChanges.map(input => `${input.dataset.role}:${input.dataset.module}`)]).size;

            document.querySelectorAll('[data-permission-row]').forEach(row => {
                const input = matrixSwitch(row.dataset.rowRole, row.dataset.rowModule);
                const preview = previewSwitch(row.dataset.rowRole, row.dataset.rowModule);
                if (!input && !preview) return;
                const editor = editorSwitch(row.dataset.rowRole, row.dataset.rowModule);
                const level = preview ? Number(preview.value) : !input.checked ? 0 : editor?.checked ? 2 : 1;
                const dirty = preview ? preview.value !== preview.dataset.initialLevel : input.checked !== input.defaultChecked || (editor && editor.checked !== editor.defaultChecked);
                row.classList.toggle('is-changed', !!dirty);
                row.querySelectorAll('[data-level-choice]').forEach(button => {
                    const selected = Number(button.dataset.levelChoice) === level;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', String(selected));
                });
                (input || preview).closest('td').classList.toggle('ac-cell-changed', !!dirty);
            });

            previewSwitches.forEach(input => {
                const label = input.parentElement.querySelector('[data-preview-level-label]');
                label.textContent = ['None', 'View', 'Edit'][Number(input.value)];
                label.className = `ac-preview-level ac-preview-level-${input.value}`;
                input.closest('td').classList.toggle('ac-cell-changed', input.value !== input.dataset.initialLevel);
            });

            document.querySelectorAll('[data-pick-role]').forEach(button => {
                const role = button.dataset.pickRole;
                if (role !== 'admin') {
                    const enabled = switches.filter(input => input.dataset.role === role && input.checked).length + previewSwitches.filter(input => input.dataset.role === role && Number(input.value) > 0).length;
                    document.querySelectorAll('[data-role-count]').forEach(counter => {
                        if (counter.dataset.roleCount === role) counter.textContent = enabled;
                    });
                }
                const dirty = accessChanges.some(input => input.dataset.role === role) || editorChanges.some(input => input.dataset.role === role) || previewChanges.some(input => input.dataset.role === role);
                button.querySelector('[data-role-dirty]').classList.toggle('d-none', !dirty);
            });

            const liveCount = new Set([...accessChanges.map(input => `${input.dataset.role}:${input.dataset.module}`), ...editorChanges.map(input => `${input.dataset.role}:${input.dataset.module}`)]).size;
            document.getElementById('unsavedBadge').textContent = `${liveCount} connected · ${previewChanges.length} preview module changes`;
            document.getElementById('permissionSaveLabel').textContent = liveCount === 0 && previewChanges.length ? 'Apply Preview' : 'Save Changes';
            document.getElementById('permissionSaveBar').classList.toggle('d-none', count === 0);
            document.getElementById('saveButton').disabled = saving || count === 0;
            document.getElementById('discardButton').disabled = saving || count === 0;
            document.getElementById('saveButton').setAttribute('aria-busy', String(saving));
        }

        switches.forEach(input => input.addEventListener('change', updateChanges));
        document.querySelectorAll('[data-level-choice]').forEach(button => button.addEventListener('click', () => {
            const input = matrixSwitch(button.dataset.choiceRole, button.dataset.choiceModule);
            const editor = editorSwitch(button.dataset.choiceRole, button.dataset.choiceModule);
            const level = Number(button.dataset.levelChoice);
            if (button.dataset.previewChoice === 'true') {
                previewSwitch(button.dataset.choiceRole, button.dataset.choiceModule).value = String(level);
            } else {
                input.checked = level > 0;
                if (editor) editor.checked = level === 2;
            }
            updateChanges();
        }));

        document.querySelectorAll('[data-pick-role]').forEach(button => button.addEventListener('click', () => {
            document.querySelectorAll('[data-pick-role]').forEach(choice => {
                const selected = choice === button;
                choice.classList.toggle('is-selected', selected);
                choice.setAttribute('aria-pressed', String(selected));
            });
            document.querySelectorAll('[data-role-panel]').forEach(panel => panel.classList.toggle('d-none', panel.dataset.rolePanel !== button.dataset.pickRole));
        }));

        document.querySelectorAll('[name="permView"]').forEach(input => input.addEventListener('change', () => {
            const byRole = document.getElementById('viewByRole').checked;
            document.getElementById('byRoleView').classList.toggle('d-none', !byRole);
            document.getElementById('compareView').classList.toggle('d-none', byRole);
        }));

        document.getElementById('discardButton').addEventListener('click', () => {
            [...switches, ...editSwitches].forEach(input => input.checked = input.defaultChecked);
            previewSwitches.forEach(input => input.value = input.dataset.initialLevel);
            updateChanges();
        });

        document.getElementById('saveButton').addEventListener('click', async () => {
            if (saving) return;
            const accessChanges = changed().map(input => ({ input, value: input.checked }));
            const editorChanges = editChanged().map(input => ({ input, value: input.checked }));
            const previewChanges = previewChanged().map(input => ({ input, value: input.value }));
            if (!accessChanges.length && !editorChanges.length && !previewChanges.length) return;
            const changes = new Map();
            accessChanges.forEach(({ input, value }) => changes.set(`${input.dataset.role}:${input.dataset.module}`, {
                role: input.dataset.role, module: input.dataset.module, enabled: value,
            }));
            editorChanges.forEach(({ input, value }) => {
                const key = `${input.dataset.role}:${input.dataset.module}`;
                changes.set(key, { ...changes.get(key), role: input.dataset.role, module: input.dataset.module, can_edit: value });
            });

            saving = true;
            updateChanges();
            try {
                if (changes.size > 0) {
                    const response = await fetch('{{ url('/access-control/permissions') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                        body: JSON.stringify({ changes: [...changes.values()] }),
                    });
                    if (!response.ok) throw new Error('Permission save failed');
                }
                accessChanges.forEach(({ input, value }) => {
                    addLog({ action: `${input.dataset.moduleName} access ${value ? 'granted' : 'revoked'}`, meta: `${input.dataset.roleName} role can ${value ? 'now' : 'no longer'} access ${input.dataset.moduleName}`, affected: input.dataset.roleName, module: input.dataset.moduleName, type: value ? 'Granted' : 'Revoked' });
                    input.defaultChecked = value;
                });
                editorChanges.forEach(({ input, value }) => {
                    addLog({ action: `${input.dataset.moduleName} editor access ${value ? 'granted' : 'revoked'}`, meta: `${input.dataset.roleName} editor accounts ${value ? 'can now edit' : 'have view-only access to'} this module when module access is enabled.`, affected: input.dataset.roleName, module: input.dataset.moduleName, type: 'Modified' });
                    input.defaultChecked = value;
                });
                previewChanges.forEach(({ input, value }) => input.dataset.initialLevel = value);
                const saved = changes.size ? `${changes.size} connected module permission${changes.size === 1 ? '' : 's'} saved.` : '';
                const preview = previewChanges.length ? `${previewChanges.length} preview change${previewChanges.length === 1 ? '' : 's'} applied to this page only.` : '';
                toast([saved, preview].filter(Boolean).join(' '));
            } catch {
                toast('Permissions could not be saved. Your changes are still here; please try again.');
            } finally {
                saving = false;
                updateChanges();
            }
        });

        window.addEventListener('beforeunload', event => {
            if (changed().length || editChanged().length || previewChanged().length) event.preventDefault();
        });
        updateChanges();
        applyTypeFilter();

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

       tempForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            checkDates();
            tempForm.classList.add('was-validated');
            if (!tempForm.checkValidity()) return;

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const body = new URLSearchParams({
                user_id: document.getElementById('tempStaff').value,
                starts_at: start.value,
                expires_at: end.value + 'T23:59',
                reason: document.getElementById('tempReason').value,
            });

            const res = await fetch('{{ url('/access-control/temp-admin') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrf },
                body,
            });

            if (res.ok) {
                window.location.reload();
            } else {
                toast('Something went wrong assigning temporary admin access.');
            }
        });

        document.getElementById('revokeModal').addEventListener('show.bs.modal', () => {
            document.getElementById('revokeName').textContent = tempName();
        });

        document.getElementById('revokeButton').addEventListener('click', async () => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch('{{ url('/access-control/temp-admin/revoke') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
            });

            if (res.ok) {
                window.location.href = '{{ route('home') }}';
            } else {
                toast('Something went wrong revoking access.');
            }
        });
    </script>
@endsection
