@extends('layouts.app')

@section('title', 'iARIS — Audit Logs')
@section('page-title', 'Audit Logs')
@section('page-subtitle', 'Complete record of system activity — logins, edits, imports, permissions, and more')

@php
    // The numbers are counted by the script from today's entries
    $stats = [
        ['key' => 'today', 'label' => 'Total Entries Today', 'sub' => 'All action types', 'tone' => 'primary'],
        ['key' => 'delete', 'label' => 'Deletions', 'sub' => 'Records removed today', 'tone' => 'danger'],
        ['key' => 'permission', 'label' => 'Permission Changes', 'sub' => 'Access control edits today', 'tone' => 'warning'],
        ['key' => 'export', 'label' => 'Exports', 'sub' => 'Files exported today', 'tone' => 'dark'],
    ];
@endphp

@include('partials.admin-workspace', ['workspacePage' => 'audit-page', 'workspaceGroup' => 'System', 'workspaceLabel' => 'Audit Logs'])

@section('content')
    {{-- Stat cards --}}
    <div class="admin-stat-grid row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="admin-stat-card card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
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
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search user, action, or record…" aria-label="Search logs">
            </div>
            <select class="form-select w-auto" id="userFilter" aria-label="Filter by user">
                <option value="">All Users</option>
                @foreach ($people as $key => $person)
                    <option value="{{ $key }}">{{ $person['name'] }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="moduleFilter" aria-label="Filter by module">
                <option value="">All Modules</option>
                @foreach ($modules as $module)
                    <option>{{ $module }}</option>
                @endforeach
            </select>
            {{-- Value = how many days back (blank = all time), 'ay' = this academic year, 'custom' = the two dates beside it --}}
            <select class="form-select w-auto" id="timeFilter" aria-label="Filter by time">
                <option value="">All Time</option>
                <option value="1">Today</option>
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="ay">This AY</option>
                <option value="custom">Custom Range</option>
            </select>
            <div class="d-none align-items-center gap-1" id="customRange">
                <input type="date" class="form-control form-control-sm w-auto" id="rangeFrom" aria-label="From date">
                <span class="small text-body-secondary">to</span>
                <input type="date" class="form-control form-control-sm w-auto" id="rangeTo" aria-label="To date">
            </div>
            <button type="button" class="btn btn-light border fw-semibold ms-lg-auto" id="exportButton">
                <i class="bi bi-download me-1" aria-hidden="true"></i> Export Logs
            </button>
        </div>

        {{-- Action type pills --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="actionPills">
            <span class="small fw-bold text-body-secondary me-1">Action:</span>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-primary" data-group="">All</button>
            @foreach ($actionGroups as $key => $label)
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-outline-secondary" data-group="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        <div id="logLoadError" class="alert alert-danger mx-4 mt-3 d-none" role="alert"></div>
        {{-- Table (rows are drawn by the script from the LOGS list) --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-nowrap">
                        <th class="ps-4 text-body-secondary fw-bold">#</th>
                        <th class="text-body-secondary fw-bold">User</th>
                        <th class="text-body-secondary fw-bold">Action</th>
                        <th class="text-body-secondary fw-bold">Module</th>
                        <th class="text-body-secondary fw-bold">Details</th>
                        <th class="text-body-secondary fw-bold">IP Address</th>
                        <th class="text-body-secondary fw-bold">Timestamp</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="logRows"></tbody>
            </table>
        </div>
        <div class="text-center text-body-secondary py-5 d-none" id="noResults">
            <i class="bi bi-clipboard-x fs-2 d-block mb-2" aria-hidden="true"></i> No log entries match your search or filters.
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3 border-top">
            <span class="small text-body-secondary" id="showingText"></span>
            <nav aria-label="Log pages"><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>

    {{-- One table row, copied by the script for every log entry --}}
    <template id="logRowTemplate">
        <tr role="button">
            <td class="ps-4 small text-body-secondary" data-cell="id"></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="icon-circle rounded-circle fw-bold small d-flex align-items-center justify-content-center" data-cell="initials"></div>
                    <div>
                        <div class="fw-semibold text-nowrap" data-cell="name"></div>
                        <div class="small text-body-secondary" data-cell="role"></div>
                    </div>
                </div>
            </td>
            <td><span class="badge rounded-2" data-cell="action"></span></td>
            <td><span class="badge rounded-2 text-wrap text-start lh-sm bg-body-secondary text-body-secondary" data-cell="module"></span></td>
            <td class="small" style="min-width: 200px;" data-cell="details"></td>
            <td class="small text-nowrap"><code class="text-body-secondary" data-cell="ip"></code></td>
            <td class="small text-nowrap">
                <div class="fw-semibold" data-cell="date"></div>
                <div class="text-body-secondary" data-cell="time"></div>
            </td>
            <td class="pe-4 text-end">
                <button type="button" class="btn btn-sm btn-light border" data-cell="view" title="View details"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </td>
        </tr>
    </template>

    {{-- Detail drawer (Bootstrap offcanvas) --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="logDrawer" aria-labelledby="drawerTitle" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerTitle"></h2>
                <div class="small text-white-50" id="drawerSub"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Log Details</div>
            <div class="row g-3 mb-3">
                @foreach (['logId' => 'Log ID', 'action' => 'Action Type', 'module' => 'Module', 'ip' => 'IP Address', 'by' => 'Performed By', 'role' => 'Role'] as $field => $label)
                    <div class="col-6">
                        <div class="small fw-bold text-uppercase text-body-secondary">{{ $label }}</div>
                        <div class="fw-semibold" data-field="{{ $field }}"></div>
                    </div>
                @endforeach
                <div class="col-12">
                    <div class="small fw-bold text-uppercase text-body-secondary">Timestamp</div>
                    <div class="fw-semibold" data-field="time"></div>
                </div>
                <div class="col-12">
                    <div class="small fw-bold text-uppercase text-body-secondary">Description</div>
                    <div class="fw-semibold" data-field="description"></div>
                </div>
            </div>

            {{-- Only shown when the entry changed some fields --}}
            <div id="diffSection" class="d-none">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3 mt-4">Before / After Changes</div>
                <div class="border rounded-3 overflow-hidden">
                    <table class="table table-sm mb-0 small">
                        <thead class="table-light">
                            <tr class="text-uppercase">
                                <th class="ps-3 text-body-secondary">Field</th>
                                <th class="text-body-secondary">Before</th>
                                <th class="pe-3 text-body-secondary">After</th>
                            </tr>
                        </thead>
                        <tbody id="diffRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pagination.js') }}"></script>
    <script>
        let LOGS = @json($logs);
        let PAGE = @json($pagination);
        let TOTALS = @json($totals);
        const INITIAL_FILTERS = @json((object) $filters);
        let PEOPLE = @json($people);
        const historicalActor = log => ({
            ...(PEOPLE[log.user] || { tone: 'secondary' }),
            name: log.actorName || PEOPLE[log.user]?.name || 'Deleted User',
            role: log.actorRole || PEOPLE[log.user]?.role || '',
        });
        const ACTIONS = @json($actions);

        const state = { search: INITIAL_FILTERS.search || '', user: String(INITIAL_FILTERS.user || ''), module: INITIAL_FILTERS.module || '', days: INITIAL_FILTERS.days || '', group: INITIAL_FILTERS.group || '', page: PAGE.page };
        const tbody = document.getElementById('logRows');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('logDrawer'));

        // ---- Helpers ----
        const initials = name => name.split(', ').reverse().map(w => w[0]).join('');
        const formatTime = iso => {
            const d = new Date(iso);
            return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
        };
        const logId = id => `LOG-${String(id).padStart(6, '0')}`;
        const plain = text => (text || '').replace(/\*\*/g, '');

        // Turn **bold** into <b>bold</b>, after escaping everything else
        function boldText(el, text) {
            el.replaceChildren(...text.split('**').map((part, i) => {
                if (i % 2 === 0) return document.createTextNode(part);
                const b = document.createElement('b');
                b.textContent = part;
                return b;
            }));
        }

        function setActionBadge(el, key) {
            const a = ACTIONS[key];
            el.className = `badge rounded-2 text-wrap text-start lh-sm bg-${a.tone}-subtle text-${a.tone}-emphasis`;
            el.innerHTML = `<i class="bi ${a.icon} me-1" aria-hidden="true"></i>`;
            el.append(a.label);
        }

        // ---- Table ----
        function buildRow(log) {
            const row = document.getElementById('logRowTemplate').content.firstElementChild.cloneNode(true);
            const cell = name => row.querySelector(`[data-cell="${name}"]`);
            const person = historicalActor(log);

            cell('id').textContent = log.id;
            cell('initials').textContent = initials(person.name);
            cell('initials').classList.add(`bg-${person.tone}-subtle`, `text-${person.tone}-emphasis`);
            cell('name').textContent = person.name;
            cell('role').textContent = person.role;
            setActionBadge(cell('action'), log.action);
            cell('module').textContent = log.module;
            boldText(cell('details'), log.details);
            cell('ip').textContent = log.ip;
            cell('date').textContent = new Date(log.at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            cell('time').textContent = new Date(log.at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            cell('view').setAttribute('aria-label', `View details of log ${log.id}`);

            row.addEventListener('click', () => openLog(log));
            return row;
        }

        let pendingRequest;
        let searchTimer;
        function queryParams() {
            const params = new URLSearchParams();
            Object.entries(state).forEach(([key,value]) => { if (value) params.set(key, value); });
            if (state.days === 'custom') ['From', 'To'].forEach(suffix => { const value = document.getElementById('range'+suffix).value; if (value) params.set(suffix.toLowerCase(), value); });
            return params;
        }
        function drawPage() {
            state.page = PAGE.page;
            tbody.replaceChildren(...LOGS.map(buildRow));
            document.getElementById('noResults').classList.toggle('d-none', PAGE.total > 0);
            document.getElementById('showingText').textContent = PAGE.total ? `Showing ${PAGE.from}–${PAGE.to} of ${PAGE.total} log entries` : 'No log entries';
            renderPagination(PAGE.pages);
        }
        async function render() {
            pendingRequest?.abort();
            pendingRequest = new AbortController();
            const signal = pendingRequest.signal;
            tbody.setAttribute('aria-busy', 'true');
            try {
                const params = queryParams();
                const response = await fetch(location.pathname + '?' + params, {headers: {'Accept': 'application/json'}, signal});
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not load the logs.');
                if (signal.aborted) return;
                LOGS = data.logs; PEOPLE = data.people; PAGE = data.pagination; TOTALS = data.totals;
                drawer.hide(); drawPage(); renderStats();
                params.set('page', PAGE.page); history.replaceState(null, '', location.pathname + '?' + params);
                document.getElementById('logLoadError').classList.add('d-none');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    const alert = document.getElementById('logLoadError'); alert.textContent = error.message; alert.classList.remove('d-none');
                }
            } finally { if (!signal.aborted) tbody.removeAttribute('aria-busy'); }
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
                btn.disabled = disabled;
                btn.setAttribute('aria-label', aria || `Page ${page}`);
                if (active) btn.setAttribute('aria-current', 'page');
                btn.addEventListener('click', () => { state.page = page; render(); });
                li.appendChild(btn);
                ul.appendChild(li);
            };
            item('<i class="bi bi-chevron-left" aria-hidden="true"></i>', state.page - 1, { disabled: state.page === 1, aria: 'Previous page' });
            IARIS_PAGINATION.pages(state.page, pages).forEach(page => {
                if (page !== null) return item(page, page, { active: page === state.page });
                const gap = document.createElement('li');
                gap.className = 'page-item disabled';
                gap.innerHTML = '<span class="page-link" aria-hidden="true">…</span>';
                ul.appendChild(gap);
            });
            item('<i class="bi bi-chevron-right" aria-hidden="true"></i>', state.page + 1, { disabled: state.page === pages, aria: 'Next page' });
        }

        // The stat cards always count today's entries, whatever the filters say
        function renderStats() {
            document.querySelectorAll('[data-stat]').forEach(el => el.textContent = TOTALS[el.dataset.stat] || 0);
        }

        // ---- Drawer ----
        function openLog(log) {
            const person = historicalActor(log);
            const field = name => document.querySelector(`#logDrawer [data-field="${name}"]`);
            document.getElementById('drawerTitle').textContent = log.title;
            document.getElementById('drawerSub').textContent = `${person.name} · ${person.role} · ${formatTime(log.at)}`;
            field('logId').textContent = logId(log.id);
            field('action').textContent = ACTIONS[log.action].label;
            field('module').textContent = log.module;
            field('ip').textContent = log.ip;
            field('by').textContent = person.name;
            field('role').textContent = person.role;
            field('time').textContent = formatTime(log.at);
            field('description').textContent = log.description;

            document.getElementById('diffSection').classList.toggle('d-none', !log.diff.length);
            document.getElementById('diffRows').replaceChildren(...log.diff.map(change => {
                const tr = document.createElement('tr');
                [['ps-3 fw-bold text-uppercase text-body-secondary', change.field],
                 ['text-danger-emphasis bg-danger-subtle fw-semibold', change.before],
                 ['pe-3 text-primary-emphasis bg-primary-subtle fw-semibold', change.after]].forEach(([cls, text]) => {
                    const td = document.createElement('td');
                    td.className = cls;
                    td.textContent = text;
                    tr.appendChild(td);
                });
                return tr;
            }));
            drawer.show();
        }

        // ---- Export: the filtered rows as a CSV file ----
        document.getElementById('exportButton').addEventListener('click', () => {
            const params = queryParams(); params.delete('page'); params.set('export', 'csv');
            window.location = location.pathname + '?' + params;
        });

        // ---- Filters ----
        document.getElementById('search').addEventListener('input', e => { state.search = e.target.value.trim().toLowerCase(); state.page = 1; clearTimeout(searchTimer); searchTimer = setTimeout(render, 300); });
        [['userFilter', 'user'], ['moduleFilter', 'module'], ['timeFilter', 'days']].forEach(([id, key]) =>
            document.getElementById(id).addEventListener('change', e => { state[key] = e.target.value; state.page = 1; render(); }));

        // "Custom Range" shows the two date boxes; changing either one re-filters
        document.getElementById('timeFilter').addEventListener('change', e => {
            document.getElementById('customRange').classList.toggle('d-none', e.target.value !== 'custom');
            document.getElementById('customRange').classList.toggle('d-flex', e.target.value === 'custom');
        });
        ['rangeFrom', 'rangeTo'].forEach(id => document.getElementById(id).addEventListener('change', () => { state.page = 1; render(); }));

        document.querySelectorAll('#actionPills [data-group]').forEach(pill => pill.addEventListener('click', () => {
            document.querySelectorAll('#actionPills [data-group]').forEach(p => {
                p.classList.toggle('btn-primary', p === pill);
                p.classList.toggle('btn-outline-secondary', p !== pill);
            });
            state.group = pill.dataset.group;
            state.page = 1;
            render();
        }));

        document.getElementById('search').value = state.search;
        [['userFilter','user'],['moduleFilter','module'],['timeFilter','days']].forEach(([id,key]) => document.getElementById(id).value = state[key]);
        ['From','To'].forEach(suffix => document.getElementById('range'+suffix).value = INITIAL_FILTERS[suffix.toLowerCase()] || '');
        document.getElementById('customRange').classList.toggle('d-none', state.days !== 'custom');
        document.getElementById('customRange').classList.toggle('d-flex', state.days === 'custom');
        document.querySelectorAll('#actionPills [data-group]').forEach(p => { p.classList.toggle('btn-primary', p.dataset.group === state.group); p.classList.toggle('btn-outline-secondary', p.dataset.group !== state.group); });
        renderStats();
        drawPage();
    </script>
@endsection
