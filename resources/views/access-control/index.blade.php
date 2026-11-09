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
    <div class="ac-institution-bar"><span><strong>De La Salle Lipa</strong><span class="mx-2 opacity-50">|</span>Institutional Admissions and Testing Office</span><span class="d-none d-md-inline">iARIS · Administration</span></div>
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


    {{-- Both permission views share the database-backed controls below. --}}
    <section class="ac-permissions card border-0" aria-labelledby="permissionsTitle">
        <div class="ac-permissions-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 id="permissionsTitle">Role Permissions</h2>
                <p>Choose a role and set its module access. Staff editor access also allows changes to records.</p>
            </div>
            <div class="ac-view-switch btn-group" role="group" aria-label="Permission view">
                <input type="radio" class="btn-check" name="permView" id="viewByRole" value="role" checked>
                <label class="btn btn-sm" for="viewByRole"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> By role</label>
                <input type="radio" class="btn-check" name="permView" id="viewCompare" value="compare">
                <label class="btn btn-sm" for="viewCompare"><i class="bi bi-grid-3x3 me-1" aria-hidden="true"></i> Compare all roles</label>
            </div>
        </div>

        <div class="ac-permissions-body" id="byRoleView">
            <nav class="ac-roles" aria-label="Choose a role">
                @foreach ($roles as $role)
                    @php
                        $moduleCount = collect($modules)->flatten(1)->filter(fn ($module) => $role['key'] === 'admin' || in_array($role['key'], $module['on']))->count();
                    @endphp
                    <button type="button" class="ac-role-button {{ $loop->index === 1 ? 'is-selected' : '' }}" data-pick-role="{{ $role['key'] }}" aria-pressed="{{ $loop->index === 1 ? 'true' : 'false' }}" aria-controls="role-panel-{{ $role['key'] }}">
                        <span class="ac-role-icon bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis"><i class="bi {{ $role['icon'] }}" aria-hidden="true"></i></span>
                        <span class="ac-role-text"><strong>{{ $role['name'] }}</strong><small>{{ $role['key'] === 'admin' ? 'Permanent administrator' : ($role['key'] === 'iato_staff' ? 'Module access & editing' : 'Module access') }}</small></span>
                        <span class="ac-role-count" data-role-count="{{ $role['key'] }}" aria-label="Enabled modules">{{ $moduleCount }}</span>
                        <span class="ac-role-dirty d-none" data-role-dirty="{{ $role['key'] }}" aria-label="Unsaved changes"></span>
                    </button>
                @endforeach
            </nav>

            <div class="ac-role-details">
                @foreach ($roles as $role)
                    <section class="{{ $loop->index === 1 ? '' : 'd-none' }}" id="role-panel-{{ $role['key'] }}" data-role-panel="{{ $role['key'] }}" aria-labelledby="role-title-{{ $role['key'] }}">
                        <div class="ac-role-heading d-flex align-items-start gap-3">
                            <span class="ac-role-icon ac-role-icon-lg bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis"><i class="bi {{ $role['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <h3 id="role-title-{{ $role['key'] }}">{{ $role['name'] }}</h3>
                                <p>{{ $role['key'] === 'admin' ? 'Full system access. The Super Admin’s module permissions cannot be restricted.' : ($role['key'] === 'iato_staff' ? 'Choose None, View, or Edit for supported modules. Edit applies to staff assigned Level Editor access.' : 'Choose which modules this role can open. Individual account editing permissions also apply.') }}</p>
                                <div class="ac-role-meta"><span><i class="bi bi-grid" aria-hidden="true"></i> <b data-role-count="{{ $role['key'] }}">{{ collect($modules)->flatten(1)->filter(fn ($module) => $role['key'] === 'admin' || in_array($role['key'], $module['on']))->count() }}</b> modules enabled</span><a href="{{ url('/accounts') }}">Manage accounts <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
                            </div>
                        </div>

                        @foreach ($modules as $section => $sectionModules)
                            <h4 class="ac-module-section">{{ $section }}</h4>
                            @foreach ($sectionModules as $module)
                                @php
                                    $isAdmin = $role['key'] === 'admin';
                                    $locked = $isAdmin || ! empty($module['adminOnly']);
                                    $supportsEditing = $role['key'] === 'iato_staff' && collect($editableModules)->contains('key', $module['key']);
                                    $on = $isAdmin || in_array($role['key'], $module['on']);
                                    $canEdit = $supportsEditing && (bool) collect($editableModules)->firstWhere('key', $module['key'])['can_edit'];
                                    $level = ! $on ? 0 : ($canEdit ? 2 : 1);
                                @endphp
                                <div class="ac-module-row" data-permission-row data-row-role="{{ $role['key'] }}" data-row-module="{{ $module['key'] }}">
                                    <div class="ac-module-text"><strong>{{ $module['name'] }}</strong><small>{{ $module['description'] }}</small></div>
                                    @if ($locked)
                                        <span class="ac-locked"><i class="bi bi-lock-fill" aria-hidden="true"></i> {{ $isAdmin ? 'Full access' : 'Super Admin only' }}</span>
                                    @else
                                        <div class="ac-levels btn-group" role="group" aria-label="{{ $role['name'] }}: {{ $module['name'] }}">
                                            @foreach ($supportsEditing ? [0 => 'None', 1 => 'View', 2 => 'Edit'] : [0 => 'None', 1 => 'Access'] as $value => $label)
                                                <button type="button" class="btn {{ $level === $value ? 'is-selected' : '' }}" data-level-choice="{{ $value }}" data-choice-role="{{ $role['key'] }}" data-choice-module="{{ $module['key'] }}" aria-pressed="{{ $level === $value ? 'true' : 'false' }}">{{ $label }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
                    </section>
                @endforeach
            </div>
        </div>

        <div class="table-responsive d-none ac-compare" id="compareView">
            <table class="table align-middle mb-0 matrix-table">
                <caption class="visually-hidden">Module access by role. IATO Staff editing is configured in the By role view.</caption>
                <thead><tr><th scope="col">Module</th>
                    @foreach ($roles as $role)
                        <th scope="col" class="text-center"><span class="ac-role-icon bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis mx-auto mb-1"><i class="bi {{ $role['icon'] }}" aria-hidden="true"></i></span><span class="d-block">{{ $role['name'] }}</span></th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @foreach ($modules as $section => $sectionModules)
                        <tr class="ac-matrix-section"><th scope="rowgroup">{{ $section }}</th><td colspan="{{ count($roles) }}"></td></tr>
                        @foreach ($sectionModules as $module)
                            <tr><th scope="row"><strong>{{ $module['name'] }}</strong><small>{{ $module['description'] }}</small></th>
                                @foreach ($roles as $role)
                                    @php
                                        $isAdmin = $role['key'] === 'admin';
                                        $locked = $isAdmin || ! empty($module['adminOnly']);
                                        $on = $isAdmin || in_array($role['key'], $module['on']);
                                    @endphp
                                    <td class="text-center">
                                        <div class="form-switch d-inline-block p-0 m-0"><input class="form-check-input m-0" type="checkbox" role="switch" aria-label="{{ $role['name'] }}: {{ $module['name'] }}" {{ $on ? 'checked' : '' }}
                                            @if ($locked) disabled title="{{ $isAdmin ? 'The Super Admin always has access' : 'Super Admin only' }}"
                                            @else data-perm data-role="{{ $role['key'] }}" data-module="{{ $module['key'] }}" data-role-name="{{ $role['name'] }}" data-module-name="{{ $module['name'] }}"
                                            @endif></div>
                                        @if ($locked)<div class="ac-matrix-lock"><i class="bi bi-lock-fill" aria-hidden="true"></i></div>@endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Canonical editor values come from the existing RolePermission records. --}}
        <div hidden>
            @foreach ($editableModules as $module)
                <input type="checkbox" data-edit-perm data-module="{{ $module['key'] }}" data-module-name="{{ $module['name'] }}" {{ $module['can_edit'] ? 'checked' : '' }}>
            @endforeach
        </div>
        <div class="ac-permission-note"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i> Locked permissions are reserved for the Super Admin.</div>
        <div class="ac-save-bar d-none" id="permissionSaveBar" aria-label="Unsaved permission changes">
            <span id="unsavedBadge" role="status"></span>
            <button type="button" class="btn btn-light border fw-semibold" id="discardButton" disabled>Discard</button>
            <button type="button" class="btn btn-primary fw-semibold" id="saveButton" disabled><i class="bi bi-floppy me-1" aria-hidden="true"></i> Save Changes</button>
        </div>
    </section>

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
        const editSwitches = [...document.querySelectorAll('[data-edit-perm]')];
        const changed = () => switches.filter(input => input.checked !== input.defaultChecked);
        const editChanged = () => editSwitches.filter(input => input.checked !== input.defaultChecked);
        const matrixSwitch = (role, module) => switches.find(input => input.dataset.role === role && input.dataset.module === module);
        const editorSwitch = module => editSwitches.find(input => input.dataset.module === module);
        let saving = false;

        function updateChanges() {
            const accessChanges = changed();
            const editorChanges = editChanged();
            const count = new Set([...accessChanges.map(input => `${input.dataset.role}:${input.dataset.module}`), ...editorChanges.map(input => `iato_staff:${input.dataset.module}`)]).size;

            document.querySelectorAll('[data-permission-row]').forEach(row => {
                const input = matrixSwitch(row.dataset.rowRole, row.dataset.rowModule);
                if (!input) return;
                const editor = row.dataset.rowRole === 'iato_staff' ? editorSwitch(row.dataset.rowModule) : null;
                const level = !input.checked ? 0 : editor?.checked ? 2 : 1;
                const dirty = input.checked !== input.defaultChecked || (editor && editor.checked !== editor.defaultChecked);
                row.classList.toggle('is-changed', !!dirty);
                row.querySelectorAll('[data-level-choice]').forEach(button => {
                    const selected = Number(button.dataset.levelChoice) === level;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', String(selected));
                });
                input.closest('td').classList.toggle('ac-cell-changed', !!dirty);
            });

            document.querySelectorAll('[data-pick-role]').forEach(button => {
                const role = button.dataset.pickRole;
                if (role !== 'admin') {
                    const enabled = switches.filter(input => input.dataset.role === role && input.checked).length;
                    document.querySelectorAll('[data-role-count]').forEach(counter => {
                        if (counter.dataset.roleCount === role) counter.textContent = enabled;
                    });
                }
                const dirty = accessChanges.some(input => input.dataset.role === role) || (role === 'iato_staff' && editorChanges.length > 0);
                button.querySelector('[data-role-dirty]').classList.toggle('d-none', !dirty);
            });

            document.getElementById('unsavedBadge').textContent = `${count} module${count === 1 ? '' : 's'} with unsaved changes`;
            document.getElementById('permissionSaveBar').classList.toggle('d-none', count === 0);
            document.getElementById('saveButton').disabled = saving || count === 0;
            document.getElementById('discardButton').disabled = saving || count === 0;
            document.getElementById('saveButton').setAttribute('aria-busy', String(saving));
        }

        switches.forEach(input => input.addEventListener('change', updateChanges));
        document.querySelectorAll('[data-level-choice]').forEach(button => button.addEventListener('click', () => {
            const input = matrixSwitch(button.dataset.choiceRole, button.dataset.choiceModule);
            const editor = button.dataset.choiceRole === 'iato_staff' ? editorSwitch(button.dataset.choiceModule) : null;
            const level = Number(button.dataset.levelChoice);
            input.checked = level > 0;
            if (editor) editor.checked = level === 2;
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
            updateChanges();
        });

        document.getElementById('saveButton').addEventListener('click', async () => {
            if (saving) return;
            const accessChanges = changed().map(input => ({ input, value: input.checked }));
            const editorChanges = editChanged().map(input => ({ input, value: input.checked }));
            if (!accessChanges.length && !editorChanges.length) return;
            const changes = new Map();
            accessChanges.forEach(({ input, value }) => changes.set(`${input.dataset.role}:${input.dataset.module}`, {
                role: input.dataset.role, module: input.dataset.module, enabled: value,
            }));
            editorChanges.forEach(({ input, value }) => {
                const key = `iato_staff:${input.dataset.module}`;
                changes.set(key, { ...changes.get(key), role: 'iato_staff', module: input.dataset.module, can_edit: value });
            });

            saving = true;
            updateChanges();
            try {
                const response = await fetch('{{ url('/access-control/permissions') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({ changes: [...changes.values()] }),
                });
                if (!response.ok) throw new Error('Permission save failed');
                accessChanges.forEach(({ input, value }) => {
                    addLog({ action: `${input.dataset.moduleName} access ${value ? 'granted' : 'revoked'}`, meta: `${input.dataset.roleName} role can ${value ? 'now' : 'no longer'} access ${input.dataset.moduleName}`, affected: input.dataset.roleName, module: input.dataset.moduleName, type: value ? 'Granted' : 'Revoked' });
                    input.defaultChecked = value;
                });
                editorChanges.forEach(({ input, value }) => {
                    addLog({ action: `${input.dataset.moduleName} editor access ${value ? 'granted' : 'revoked'}`, meta: `IATO Staff Level Editors ${value ? 'can now edit' : 'have view-only access to'} this module when module access is enabled.`, affected: 'IATO Staff', module: input.dataset.moduleName, type: 'Modified' });
                    input.defaultChecked = value;
                });
                toast(`${changes.size} module permission${changes.size === 1 ? '' : 's'} saved.`);
            } catch {
                toast('Permissions could not be saved. Your changes are still here; please try again.');
            } finally {
                saving = false;
                updateChanges();
            }
        });

        window.addEventListener('beforeunload', event => {
            if (changed().length || editChanged().length) event.preventDefault();
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
