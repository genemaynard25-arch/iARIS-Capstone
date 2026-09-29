@extends('layouts.app')

@section('title', 'iARIS — User Accounts')
@section('page-title', 'User Accounts')
@section('page-subtitle', 'Add, edit, suspend or delete iARIS accounts across all roles')

@php
    // The numbers are filled in by the script, so they stay right after changes
    $stats = [
        ['key' => 'total', 'label' => 'Total Accounts', 'sub' => 'All roles combined', 'tone' => 'primary'],
        ['key' => 'active', 'label' => 'Active', 'sub' => 'Currently with access', 'tone' => 'primary'],
        ['key' => 'pending', 'label' => 'Pending', 'sub' => 'Awaiting first login', 'tone' => 'warning'],
        ['key' => 'suspended', 'label' => 'Suspended', 'sub' => 'Temporarily blocked', 'tone' => 'danger'],
        ['key' => 'noMfa', 'label' => 'MFA Not Set Up', 'sub' => 'Authenticator pending', 'tone' => 'info'],
    ];
@endphp

@section('content')
    {{-- Stat cards --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] === 'primary' ? 'body' : $stat['tone'] . '-emphasis' }}" data-stat="{{ $stat['key'] }}">0</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        {{-- Search and filters --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search name or email…" aria-label="Search accounts">
            </div>
            <select class="form-select w-auto" id="roleFilter" aria-label="Filter by role">
                <option value="">All Roles</option>
                @foreach ($roles as $key => $role)
                    <option value="{{ $key }}">{{ $role['name'] }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="statusFilter" aria-label="Filter by status">
                <option value="">All Statuses</option>
                @foreach ($statuses as $status => $tone)
                    <option>{{ $status }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="mfaFilter" aria-label="Filter by MFA">
                <option value="">All MFA Status</option>
                <option value="on">MFA Enabled</option>
                <option value="off">MFA Not Set Up</option>
            </select>
            <select class="form-select w-auto" id="departmentFilter" aria-label="Filter by department">
                <option value="">All Departments</option>
                @foreach ($departments as $department)
                    <option>{{ $department }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-primary fw-semibold ms-lg-auto" id="addUserButton">
                <i class="bi bi-plus-lg me-1"></i> Add User
            </button>
        </div>

        {{-- Bulk actions: shown when at least one row is ticked --}}
        <div class="d-none flex-wrap align-items-center gap-2 px-4 py-2 bg-primary-subtle border-bottom" id="bulkBar">
            <span class="fw-bold text-primary-emphasis me-2" id="bulkLabel"></span>
            <button type="button" class="btn btn-sm btn-light border" data-bulk="reset"><i class="bi bi-key me-1"></i> Force Password Reset</button>
            <button type="button" class="btn btn-sm btn-light border" data-bulk="suspend"><i class="bi bi-slash-circle me-1"></i> Suspend</button>
            <button type="button" class="btn btn-sm btn-light border" data-bulk="deactivate"><i class="bi bi-toggle-off me-1"></i> Deactivate</button>
            <button type="button" class="btn btn-sm btn-light border text-danger" data-bulk="delete"><i class="bi bi-trash me-1"></i> Delete</button>
            <button type="button" class="btn btn-sm btn-light border ms-auto" id="clearSelection"><i class="bi bi-x-lg me-1"></i> Clear</button>
        </div>

        {{-- Table (rows are drawn by the script from the USERS list) --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-nowrap">
                        <th class="ps-4" style="width: 1%;"><input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all on this page"></th>
                        <th class="text-body-secondary fw-bold">User</th>
                        <th class="text-body-secondary fw-bold">Role</th>
                        <th class="text-body-secondary fw-bold">Department</th>
                        <th class="text-body-secondary fw-bold">Status / MFA</th>
                        <th class="text-body-secondary fw-bold">Last Login</th>
                        <th class="text-body-secondary fw-bold">Joined</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="userRows"></tbody>
            </table>
        </div>
        <div class="text-center text-body-secondary py-5 d-none" id="noResults">
            <i class="bi bi-person-x fs-2 d-block mb-2"></i> No accounts match your search or filters.
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3 border-top">
            <span class="small text-body-secondary" id="showingText"></span>
            <nav aria-label="Accounts pages"><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>

    {{-- One table row, copied by the script for every account --}}
    <template id="userRowTemplate">
        <tr>
            <td class="ps-4"><input type="checkbox" class="form-check-input row-check"></td>
            <td>
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-circle rounded-circle fw-bold d-flex align-items-center justify-content-center" data-cell="initials"></div>
                    <div>
                        <div class="fw-semibold text-nowrap" data-cell="name"></div>
                        <div class="small text-body-secondary" data-cell="email"></div>
                    </div>
                </div>
            </td>
            <td><span data-cell="role"></span></td>
            <td class="small" data-cell="department"></td>
            <td>
                <div class="mb-1"><span data-cell="status"></span></div>
                <span data-cell="mfa"></span>
            </td>
            <td class="text-nowrap">
                <div class="small fw-semibold" data-cell="activity"></div>
                <div class="small text-body-secondary" data-cell="lastLogin"></div>
            </td>
            <td class="small text-body-secondary text-nowrap" data-cell="joined"></td>
            <td class="pe-4 text-end text-nowrap">
                {{-- Edit, plus a "more" menu. The script shows only the menu items that fit the account's status. --}}
                <button type="button" class="btn btn-sm btn-light border" data-action="edit" title="Edit"><i class="bi bi-pencil"></i></button>
                <div class="dropdown d-inline-block" data-cell="menu">
                    {{-- strategy "fixed" stops the menu from being cut off by the scrolling table --}}
                    <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="More actions">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><button type="button" class="dropdown-item" data-action="reset"><i class="bi bi-key me-2"></i>Force password reset</button></li>
                        <li><button type="button" class="dropdown-item" data-action="resend"><i class="bi bi-send me-2"></i>Resend invitation</button></li>
                        <li><button type="button" class="dropdown-item" data-action="reactivate"><i class="bi bi-arrow-counterclockwise me-2"></i>Reactivate</button></li>
                        <li><button type="button" class="dropdown-item text-danger" data-action="suspend"><i class="bi bi-slash-circle me-2"></i>Suspend</button></li>
                        <li><button type="button" class="dropdown-item text-danger" data-action="delete"><i class="bi bi-trash me-2"></i>Delete</button></li>
                    </ul>
                </div>
                <span class="badge rounded-pill bg-body-tertiary text-body-secondary border" data-cell="locked" title="The permanent administrator can't be changed here">
                    <i class="bi bi-lock-fill me-1"></i> Protected
                </span>
            </td>
        </tr>
    </template>

    {{-- Add / edit user (one pop-up for both) --}}
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="userForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="userModalTitle"></h3>
                        <p class="small text-white-50 mb-0" id="userModalSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="userLast" class="form-label small fw-bold text-uppercase text-body-secondary">Last name</label>
                            <input type="text" class="form-control" id="userLast" placeholder="e.g. Renegado" required>
                            <div class="invalid-feedback">Enter a last name.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="userFirst" class="form-label small fw-bold text-uppercase text-body-secondary">First name</label>
                            <input type="text" class="form-control" id="userFirst" placeholder="e.g. Randolph" required>
                            <div class="invalid-feedback">Enter a first name.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="userEmail" class="form-label small fw-bold text-uppercase text-body-secondary">DLSL email address</label>
                        <input type="email" class="form-control" id="userEmail" placeholder="name@dlsl.edu.ph" required>
                        <div class="invalid-feedback" id="userEmailError">Use a @dlsl.edu.ph address.</div>
                        <div class="form-text" id="userEmailHint"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="userRole" class="form-label small fw-bold text-uppercase text-body-secondary">Role</label>
                            <select class="form-select" id="userRole" required>
                                <option value="">Select role</option>
                                {{-- IATO Admin isn't offered: there is one permanent admin, and temporary admins are set on Access Control --}}
                                @foreach ($roles as $key => $role)
                                    @continue($key === 'admin')
                                    <option value="{{ $key }}">{{ $role['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Choose a role.</div>
                        </div>
                        <div class="col-sm-6">
                            <label for="userDepartment" class="form-label small fw-bold text-uppercase text-body-secondary">Department / unit</label>
                            <select class="form-select" id="userDepartment" required>
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option>{{ $department }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Choose a department.</div>
                        </div>
                        {{-- Edit only --}}
                        <div class="col-12" id="userStatusField">
                            <label for="userStatus" class="form-label small fw-bold text-uppercase text-body-secondary">Account status</label>
                            <select class="form-select" id="userStatus">
                                @foreach ($statuses as $status => $tone)
                                    <option>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill" id="userSubmit"></button>
                </div>
            </form>
        </div>
    </div>

    {{-- One confirm pop-up for reset, resend, suspend, deactivate, reactivate and delete. The script fills it in. --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="confirmForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="confirmTitle"></h3>
                        <p class="small text-white-50 mb-0" id="confirmSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert d-flex gap-2 small" id="confirmNote">
                        <i class="bi" id="confirmNoteIcon"></i>
                        <div id="confirmNoteText"></div>
                    </div>

                    <div class="small fw-bold text-uppercase text-body-secondary mb-2" id="confirmTargetsLabel"></div>
                    <ul class="list-group mb-3" id="confirmTargets"></ul>

                    <div class="mb-3 d-none" id="confirmReasonField">
                        <label for="confirmReason" class="form-label small fw-bold text-uppercase text-body-secondary">Reason</label>
                        <input type="text" class="form-control" id="confirmReason" placeholder="e.g. Staff on extended leave">
                        <div class="invalid-feedback">Add a short reason.</div>
                    </div>

                    <div class="d-none" id="confirmTypeField">
                        <label for="confirmType" class="form-label small fw-bold text-uppercase text-body-secondary">Type DELETE to confirm</label>
                        <input type="text" class="form-control" id="confirmType" autocomplete="off" placeholder="DELETE">
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-semibold flex-fill" id="confirmButton"></button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="accountToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="accountToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Front end only: every change below lives in this USERS list in the browser
        // and is gone on reload. No emails are sent.
        // TODO: send each action to a backend route instead.
        const USERS = @json($users);
        const ROLES = @json($roles);
        const STATUSES = @json($statuses);
        const PAGE_SIZE = 10;

        // Which row buttons each status gets
        const ROW_ACTIONS = {
            Active: ['edit', 'reset', 'suspend', 'delete'],
            Pending: ['edit', 'resend', 'delete'],
            Suspended: ['edit', 'reactivate', 'delete'],
            Inactive: ['edit', 'reactivate', 'delete'],
        };

        const state = { search: '', role: '', status: '', mfa: '', department: '', page: 1 };
        const selected = new Set();   // ids of ticked rows
        const tbody = document.getElementById('userRows');

        // ---- Small helpers ----
        const fullName = u => `${u.last}, ${u.first}`;
        const initials = u => (u.first.charAt(0) + u.last.charAt(0)).toUpperCase();
        const byId = id => USERS.find(u => u.id === id);

        function formatDate(value, withTime = false) {
            const date = new Date(value.length === 10 ? value + 'T00:00' : value);
            const day = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return withTime ? `${day} · ${date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}` : day;
        }

        // How long ago the last login was, as a label and a colour
        function activity(lastLogin) {
            if (!lastLogin) return ['Never logged in', 'text-danger-emphasis'];
            const last = new Date(lastLogin);
            const days = (Date.now() - last) / 86400000;
            if (last.toDateString() === new Date().toDateString()) return ['Active today', 'text-primary'];
            if (days <= 7) return ['Last 7 days', 'text-body-secondary'];
            if (days <= 30) return ['Last 30 days', 'text-warning-emphasis'];
            return ['30+ days ago', 'text-danger-emphasis'];
        }

        // Turn a <span> into a coloured badge, optionally with an icon in front
        function setBadge(el, text, tone, icon) {
            el.className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
            el.textContent = text;
            if (icon) {
                const i = document.createElement('i');
                i.className = `bi ${icon} me-1`;
                el.prepend(i);
            }
        }

        function toast(text) {
            document.getElementById('accountToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance(document.getElementById('accountToast')).show();
        }

        // ---- Table ----
        function buildRow(u) {
            const row = document.getElementById('userRowTemplate').content.firstElementChild.cloneNode(true);
            const cell = name => row.querySelector(`[data-cell="${name}"]`);
            const role = ROLES[u.role];
            const [activityText, activityClass] = activity(u.lastLogin);

            cell('initials').textContent = initials(u);
            cell('initials').classList.add(`bg-${role.tone}-subtle`, `text-${role.tone}-emphasis`);
            cell('name').textContent = fullName(u);
            cell('email').textContent = u.email;
            setBadge(cell('role'), role.name, role.tone, u.permanent ? 'bi-star-fill' : null);
            cell('department').textContent = u.department;
            setBadge(cell('status'), u.status, STATUSES[u.status], 'bi-circle-fill small');
            setBadge(cell('mfa'), u.mfa ? 'MFA on' : 'No MFA', u.mfa ? 'primary' : 'danger', u.mfa ? 'bi-shield-check' : 'bi-exclamation-triangle');
            cell('activity').textContent = activityText;
            cell('activity').classList.add(activityClass);
            cell('lastLogin').textContent = u.lastLogin ? formatDate(u.lastLogin, true) : '';
            cell('joined').textContent = formatDate(u.joined);

            const check = row.querySelector('.row-check');
            check.setAttribute('aria-label', `Select ${fullName(u)}`);
            check.checked = selected.has(u.id);
            check.dataset.id = u.id;

            // The permanent admin can't be selected or changed
            const allowed = u.permanent ? [] : ROW_ACTIONS[u.status];
            check.disabled = !!u.permanent;
            cell('locked').classList.toggle('d-none', !u.permanent);
            cell('menu').classList.toggle('d-none', !!u.permanent);
            row.querySelectorAll('[data-action]').forEach(btn => {
                // Menu items sit inside an <li>, so hide that instead of the button
                (btn.closest('li') || btn).classList.toggle('d-none', !allowed.includes(btn.dataset.action));
                btn.dataset.id = u.id;
            });
            row.querySelector('[data-action="edit"]').setAttribute('aria-label', `Edit ${fullName(u)}`);
            row.querySelector('[data-bs-toggle="dropdown"]').setAttribute('aria-label', `More actions for ${fullName(u)}`);
            return row;
        }

        function render() {
            const matches = USERS.filter(u =>
                (!state.role || u.role === state.role) &&
                (!state.status || u.status === state.status) &&
                (!state.mfa || (state.mfa === 'on') === u.mfa) &&
                (!state.department || u.department === state.department) &&
                `${fullName(u)} ${u.email}`.toLowerCase().includes(state.search)
            ).sort((a, b) => fullName(a).localeCompare(fullName(b)));

            const pages = Math.max(1, Math.ceil(matches.length / PAGE_SIZE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PAGE_SIZE;
            const pageUsers = matches.slice(start, start + PAGE_SIZE);

            tbody.replaceChildren(...pageUsers.map(buildRow));
            document.getElementById('noResults').classList.toggle('d-none', matches.length > 0);
            document.getElementById('showingText').textContent = matches.length
                ? `Showing ${start + 1}–${start + pageUsers.length} of ${matches.length} account${matches.length === 1 ? '' : 's'}`
                : '';
            renderPagination(pages);
            renderStats();
            renderBulkBar();
        }

        function renderPagination(pages) {
            const ul = document.getElementById('pagination');
            ul.replaceChildren();
            const item = (label, page, { active = false, disabled = false, aria = '' } = {}) => {
                const li = document.createElement('li');
                li.className = `page-item${active ? ' active' : ''}${disabled ? ' disabled' : ''}`;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-link';
                btn.innerHTML = label;
                if (aria) btn.setAttribute('aria-label', aria);
                btn.addEventListener('click', () => { state.page = page; render(); });
                li.appendChild(btn);
                ul.appendChild(li);
            };
            item('<i class="bi bi-chevron-left"></i>', state.page - 1, { disabled: state.page === 1, aria: 'Previous page' });
            for (let p = 1; p <= pages; p++) item(p, p, { active: p === state.page });
            item('<i class="bi bi-chevron-right"></i>', state.page + 1, { disabled: state.page === pages, aria: 'Next page' });
        }

        function renderStats() {
            const counts = {
                total: USERS.length,
                active: USERS.filter(u => u.status === 'Active').length,
                pending: USERS.filter(u => u.status === 'Pending').length,
                suspended: USERS.filter(u => u.status === 'Suspended').length,
                noMfa: USERS.filter(u => !u.mfa).length,
            };
            document.querySelectorAll('[data-stat]').forEach(el => el.textContent = counts[el.dataset.stat]);
        }

        // ---- Selection and bulk bar ----
        function renderBulkBar() {
            const bar = document.getElementById('bulkBar');
            bar.classList.toggle('d-none', selected.size === 0);
            bar.classList.toggle('d-flex', selected.size > 0);
            document.getElementById('bulkLabel').textContent = `${selected.size} selected`;
            const boxes = [...tbody.querySelectorAll('.row-check:not(:disabled)')];
            document.getElementById('selectAll').checked = boxes.length > 0 && boxes.every(b => b.checked);
        }

        tbody.addEventListener('change', e => {
            if (!e.target.matches('.row-check')) return;
            const id = Number(e.target.dataset.id);
            e.target.checked ? selected.add(id) : selected.delete(id);
            renderBulkBar();
        });

        // Select all = the rows on this page
        document.getElementById('selectAll').addEventListener('change', e => {
            tbody.querySelectorAll('.row-check:not(:disabled)').forEach(box => {
                box.checked = e.target.checked;
                e.target.checked ? selected.add(Number(box.dataset.id)) : selected.delete(Number(box.dataset.id));
            });
            renderBulkBar();
        });

        document.getElementById('clearSelection').addEventListener('click', () => { selected.clear(); render(); });

        // Changing a filter clears the selection, so a bulk action never hits rows you can't see
        function onFilter(key, value) {
            state[key] = value;
            state.page = 1;
            selected.clear();
            render();
        }
        document.getElementById('search').addEventListener('input', e => onFilter('search', e.target.value.trim().toLowerCase()));
        document.getElementById('roleFilter').addEventListener('change', e => onFilter('role', e.target.value));
        document.getElementById('statusFilter').addEventListener('change', e => onFilter('status', e.target.value));
        document.getElementById('mfaFilter').addEventListener('change', e => onFilter('mfa', e.target.value));
        document.getElementById('departmentFilter').addEventListener('change', e => onFilter('department', e.target.value));

        // ---- Confirm pop-up ----
        // Each action: the pop-up's wording, its colour, and what it does to one user.
        const ACTIONS = {
            reset: {
                title: 'Force Password Reset', subtitle: "Send a password reset link to the user's email",
                note: "A reset link will be sent to each user's DLSL email. The user must set a new password before logging in again.",
                tone: 'warning', icon: 'bi-key', button: 'Send Reset Link',
                done: n => `Sample only: no reset link was sent to ${n}.`,
            },
            resend: {
                title: 'Resend Invitation', subtitle: 'Send the account set-up email again',
                note: 'The user gets a new invitation link. They must set up MFA before their first login.',
                tone: 'primary', icon: 'bi-send', button: 'Resend Invitation',
                done: n => `Sample only: no invitation was sent to ${n}.`,
            },
            suspend: {
                title: 'Suspend Account', subtitle: 'Temporarily block access to iARIS',
                note: 'Suspended users are blocked from logging in right away. Their data and settings are kept, and you can reactivate them at any time.',
                tone: 'warning', icon: 'bi-slash-circle', button: 'Suspend', reason: true,
                apply: u => u.status = 'Suspended',
                done: n => `${n} suspended (sample only).`,
            },
            deactivate: {
                title: 'Deactivate Account', subtitle: 'Turn off accounts that are no longer needed',
                note: "Deactivated users can't log in. Use this for people who have left the office. You can reactivate them later.",
                tone: 'secondary', icon: 'bi-toggle-off', button: 'Deactivate',
                apply: u => u.status = 'Inactive',
                done: n => `${n} deactivated (sample only).`,
            },
            reactivate: {
                title: 'Reactivate Account', subtitle: 'Give access back',
                note: 'The user can log in again with their current password and MFA.',
                tone: 'primary', icon: 'bi-arrow-counterclockwise', button: 'Reactivate',
                apply: u => u.status = 'Active',
                done: n => `${n} reactivated (sample only).`,
            },
            delete: {
                title: 'Delete Account', subtitle: 'This is permanent and cannot be undone',
                note: 'Deleting removes the user and all their access from iARIS. Reports and logs they made are kept.',
                tone: 'danger', icon: 'bi-exclamation-triangle-fill', button: 'Delete', typeToConfirm: true,
                remove: true,
                done: n => `${n} deleted (sample only — reload to bring back).`,
            },
        };

        const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal'));
        const confirmForm = document.getElementById('confirmForm');
        const reasonInput = document.getElementById('confirmReason');
        const typeInput = document.getElementById('confirmType');
        let pending = null;   // { key, ids }

        function openConfirm(key, ids) {
            const action = ACTIONS[key];
            pending = { key, ids };
            const users = ids.map(byId);

            document.getElementById('confirmTitle').textContent = action.title + (ids.length > 1 ? ` (${ids.length} users)` : '');
            document.getElementById('confirmSubtitle').textContent = action.subtitle;
            const noteTone = action.tone === 'danger' ? 'danger' : action.tone === 'warning' ? 'warning' : 'light';
            document.getElementById('confirmNote').className = `alert alert-${noteTone} d-flex gap-2 small ${noteTone === 'light' ? 'border' : ''}`;
            document.getElementById('confirmNoteIcon').className = `bi ${action.icon}`;
            document.getElementById('confirmNoteText').textContent = action.note;

            document.getElementById('confirmTargetsLabel').textContent = ids.length > 1 ? 'Users' : 'User';
            document.getElementById('confirmTargets').replaceChildren(...users.map(u => {
                const li = document.createElement('li');
                li.className = 'list-group-item small d-flex justify-content-between gap-2';
                li.textContent = fullName(u);
                const meta = document.createElement('span');
                meta.className = 'text-body-secondary text-truncate';
                meta.textContent = `${ROLES[u.role].name} · ${u.email}`;
                li.appendChild(meta);
                return li;
            }));

            document.getElementById('confirmReasonField').classList.toggle('d-none', !action.reason);
            reasonInput.required = !!action.reason;
            reasonInput.value = '';
            document.getElementById('confirmTypeField').classList.toggle('d-none', !action.typeToConfirm);
            typeInput.value = '';

            const button = document.getElementById('confirmButton');
            button.className = `btn btn-${action.tone} fw-semibold flex-fill`;
            button.textContent = action.button;
            button.disabled = !!action.typeToConfirm;
            confirmForm.classList.remove('was-validated');
            confirmModal.show();
        }

        // Delete stays disabled until DELETE is typed exactly
        typeInput.addEventListener('input', () => {
            document.getElementById('confirmButton').disabled = typeInput.value !== 'DELETE';
        });

        confirmForm.addEventListener('submit', e => {
            e.preventDefault();
            confirmForm.classList.add('was-validated');
            if (!confirmForm.checkValidity()) return;

            const action = ACTIONS[pending.key];
            const users = pending.ids.map(byId);
            const names = users.length === 1 ? fullName(users[0]) : `${users.length} users`;
            users.forEach(u => {
                if (action.apply) action.apply(u);
                if (action.remove) USERS.splice(USERS.indexOf(u), 1);
            });

            selected.clear();
            confirmModal.hide();
            render();
            toast(action.done(names));
        });

        // Row buttons (edit has its own pop-up; the rest use the confirm pop-up)
        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const id = Number(btn.dataset.id);
            btn.dataset.action === 'edit' ? openUserForm(byId(id)) : openConfirm(btn.dataset.action, [id]);
        });

        document.querySelectorAll('[data-bulk]').forEach(btn =>
            btn.addEventListener('click', () => openConfirm(btn.dataset.bulk, [...selected])));

        // ---- Add / edit pop-up ----
        const userModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal'));
        const userForm = document.getElementById('userForm');
        const field = id => document.getElementById(id);
        let editing = null;   // the user being edited, or null when adding

        function openUserForm(user = null) {
            editing = user;
            userForm.reset();
            userForm.classList.remove('was-validated');
            field('userModalTitle').textContent = user ? 'Edit User Account' : 'Add New User';
            field('userModalSubtitle').textContent = user
                ? 'Update role, department or account status'
                : 'The new user gets an invitation email to set up their account';
            field('userSubmit').innerHTML = user
                ? '<i class="bi bi-floppy me-1"></i> Save Changes'
                : '<i class="bi bi-send me-1"></i> Send Invitation';
            field('userEmailHint').textContent = user
                ? 'Email addresses can\'t be changed. Contact IT support if a change is needed.'
                : 'An invitation link goes to this address. The user must set up MFA before their first login.';
            field('userEmail').disabled = !!user;
            field('userStatusField').classList.toggle('d-none', !user);

            if (user) {
                field('userLast').value = user.last;
                field('userFirst').value = user.first;
                field('userEmail').value = user.email;
                field('userRole').value = user.role;
                field('userDepartment').value = user.department;
                field('userStatus').value = user.status;
            }
            userModal.show();
        }

        document.getElementById('addUserButton').addEventListener('click', () => openUserForm());

        // Email must be @dlsl.edu.ph and not already used
        field('userEmail').addEventListener('input', checkEmail);
        function checkEmail() {
            const input = field('userEmail');
            const value = input.value.trim().toLowerCase();
            let message = '';
            if (!/^[^@\s]+@dlsl\.edu\.ph$/.test(value)) message = 'Use a @dlsl.edu.ph address.';
            else if (USERS.some(u => u.email === value)) message = 'An account with this email already exists.';
            input.setCustomValidity(message);
            field('userEmailError').textContent = message;
        }

        userForm.addEventListener('submit', e => {
            e.preventDefault();
            if (!editing) checkEmail();
            userForm.classList.add('was-validated');
            if (!userForm.checkValidity()) return;

            const values = {
                last: field('userLast').value.trim(),
                first: field('userFirst').value.trim(),
                role: field('userRole').value,
                department: field('userDepartment').value,
            };

            if (editing) {
                Object.assign(editing, values, { status: field('userStatus').value });
                toast(`${fullName(editing)} updated (sample only).`);
            } else {
                const user = {
                    ...values,
                    id: Math.max(...USERS.map(u => u.id)) + 1,
                    email: field('userEmail').value.trim().toLowerCase(),
                    status: 'Pending', mfa: false, lastLogin: null,
                    joined: new Date().toLocaleDateString('en-CA'),   // yyyy-mm-dd in local time
                };
                USERS.push(user);
                toast(`${fullName(user)} added as Pending (sample only — no invitation email sent).`);
            }
            userModal.hide();
            render();
        });

        render();
    </script>
@endsection
