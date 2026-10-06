@extends('layouts.app')

@php
    $stats = [
        ['key' => 'total', 'label' => 'Total Scholars', 'sub' => 'All types combined', 'tone' => 'primary'],
        ['key' => 'Confirmed Match', 'label' => 'Confirmed Match', 'sub' => 'Records verified', 'tone' => 'primary'],
        ['key' => 'Pending Check', 'label' => 'Pending Check', 'sub' => 'Awaiting review', 'tone' => 'warning'],
        ['key' => 'Discrepancy', 'label' => 'Discrepancies', 'sub' => 'Need attention', 'tone' => 'danger'],
        ['key' => 'Not Found', 'label' => 'Not Found', 'sub' => 'Missing in LAMP records', 'tone' => 'secondary'],
    ];
@endphp

@section('title', 'iARIS — LAMP Office · Scholars')
@section('page-title', 'Scholar Records')
@section('page-subtitle', "Cross-check scholar applicant data against LAMP Office records for AY {$academicYear}")

@section('content')
    {{-- Stat cards (numbers filled in by the script) --}}
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

    <div class="alert alert-danger d-flex align-items-center gap-3 rounded-4 mb-4" role="alert" id="reviewAlert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div class="small">
            <strong><span id="reviewCount"></span> need your review.</strong>
            The scholarship data doesn't match between IATO and the LAMP Office. Confirm a match or flag an issue — IATO Admin will be notified.
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4" id="scholarCard">
        {{-- Search, filters, sort --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search by name or app number…" aria-label="Search scholars">
            </div>
            <select class="form-select w-auto" id="typeFilter" aria-label="Filter by scholarship type">
                <option value="">All Scholarship Types</option>
                @foreach ($types as $type)
                    <option>{{ $type }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="scholarshipFilter" aria-label="Filter by scholarship status">
                <option value="">All Scholarship Status</option>
                @foreach ($scholarshipTones as $status => $tone)
                    <option>{{ $status }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="review">Sort: Needs Review First</option>
                <option value="newest">Sort: Newest First</option>
                <option value="az">Sort: Name A–Z</option>
            </select>
            <button type="button" class="btn btn-light border fw-semibold" id="exportButton" title="Download the filtered list as CSV">
                <i class="bi bi-download me-1"></i> Export
            </button>
        </div>

        {{-- LAMP status pills --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="lampPills">
            <span class="small fw-bold text-body-secondary me-1">LAMP Status:</span>
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-primary" data-lamp="">All</button>
            @foreach ($lampBadges as $status => $badge)
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-outline-secondary" data-lamp="{{ $status }}">{{ $status }}</button>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Scholarship Type</th>
                        <th class="text-body-secondary fw-bold">Scholarship Status</th>
                        <th class="text-body-secondary fw-bold">LAMP Cross-Check</th>
                        <th class="text-body-secondary fw-bold">Last Updated</th>
                        <th class="pe-4 text-end text-body-secondary fw-bold">Actions</th>
                    </tr>
                </thead>
                <tbody id="scholarRows"></tbody>
            </table>
        </div>
        <div class="text-center text-body-secondary py-5 d-none" id="noResults">
            <i class="bi bi-search fs-3 d-block mb-2"></i> No scholars match your search or filters.
        </div>

        <div class="card-footer bg-white rounded-bottom-4 d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3">
            <span class="small text-body-secondary" id="showingText"></span>
            <nav aria-label="Scholar pages"><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>

    {{-- One table row, copied by the script --}}
    <template id="scholarRowTemplate">
        <tr style="cursor: pointer;" tabindex="0">
            <td class="ps-4">
                <div class="fw-semibold text-nowrap" data-cell="name"></div>
                <div class="small text-body-secondary" data-cell="id"></div>
            </td>
            <td><span data-cell="type"></span></td>
            <td><span data-cell="scholarship"></span></td>
            <td><span data-cell="lamp"></span></td>
            <td class="small text-body-secondary" data-cell="updated"></td>
            <td class="pe-4" data-cell="actions"></td>
        </tr>
    </template>

    {{-- Details drawer. id="applicantDrawer" so the print rules in iaris.css work here too. --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="drawerName" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="rounded-4 bg-white bg-opacity-25 border border-white border-opacity-25 fs-3 fw-bold font-brand d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 72px; height: 72px;" id="drawerInitials"></div>
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerName"></h2>
                <div class="small text-white-50 mb-2" id="drawerSub"></div>
                <span class="badge rounded-pill bg-white text-body" id="drawerType"></span>
                <span id="drawerScholarship"></span>
            </div>
            <button type="button" class="btn-close btn-close-white no-print" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <ul class="nav nav-underline px-4 border-bottom no-print" role="tablist">
            <li class="nav-item"><button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabDetails" type="button" role="tab">Scholar Details</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabCheck" type="button" role="tab">Cross-Check</button></li>
        </ul>

        <div class="offcanvas-body tab-content p-4">
            <div class="tab-pane fade show active" id="tabDetails" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Applicant Info</div>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Full Name</div><div class="fw-semibold" data-field="name"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">App Number</div><div class="fw-semibold" data-field="id"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Email</div><div class="fw-semibold text-break" data-field="email"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Last Updated</div><div class="fw-semibold" data-field="updated_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">College / Unit</div><div class="fw-semibold" data-field="unit"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Program</div><div class="fw-semibold" data-field="program"></div></div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Scholarship</div>
                <div class="row g-3">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Scholarship Type</div><div class="fw-semibold" data-field="type"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Status</div><div class="fw-semibold" data-field="scholarship_status"></div></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tabCheck" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">LAMP Cross-Check Result</div>
                {{-- The script sets the colour and heading --}}
                <div class="alert mb-3" id="checkCard">
                    <div class="fw-bold mb-2" id="checkHeading"></div>
                    <div class="d-flex justify-content-between small mb-1"><span>IATO Record</span><span class="fw-bold" data-field="type"></span></div>
                    <div class="d-flex justify-content-between small"><span>LAMP Record</span><span class="fw-bold" data-field="lamp_record"></span></div>
                </div>
                <div class="d-flex gap-2 no-print" id="checkActions">
                    <button type="button" class="btn btn-primary fw-semibold flex-fill" id="drawerConfirm"><i class="bi bi-check-lg me-1"></i> Confirm Match</button>
                    <button type="button" class="btn btn-outline-danger fw-semibold flex-fill" id="drawerFlag"><i class="bi bi-flag me-1"></i> Flag Issue</button>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 border-top p-3 no-print">
            <button type="button" class="btn btn-light border flex-fill" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
            <button type="button" class="btn btn-primary fw-semibold flex-fill" id="drawerEdit"><i class="bi bi-pencil me-1"></i> Edit Record</button>
        </div>
    </div>

    @include('lamp.partials.scholar-modals')
@endsection

@section('scripts')
    <script>
        const SCHOLARS = @json($scholars);
        const PAGE_SIZE = 10;
        const ORDER = Object.keys(@json($lampBadges));   // needs-attention order

        const state = { search: '', type: '', scholarship: '', lamp: '', sort: 'review', page: 1 };
        let currentMatches = [];
        let drawerId = null;   // scholar shown in the drawer
        const count = status => SCHOLARS.filter(s => s.lamp_status === status).length;

        // ---- Redraw everything (also called after Confirm / Flag / Edit) ----
        function scholarsChanged() {
            document.querySelectorAll('[data-stat]').forEach(el =>
                el.textContent = el.dataset.stat === 'total' ? SCHOLARS.length : count(el.dataset.stat));
            const review = count('Discrepancy') + count('Not Found');
            document.getElementById('reviewAlert').classList.toggle('d-none', review === 0);
            document.getElementById('reviewCount').textContent = `${review} ${review === 1 ? 'record' : 'records'}`;
            render();
            if (drawerId) fillDrawer(scholarById(drawerId));
        }

        function render() {
            const matches = SCHOLARS.filter(s =>
                (!state.type || s.type === state.type) &&
                (!state.scholarship || s.scholarship_status === state.scholarship) &&
                (!state.lamp || s.lamp_status === state.lamp) &&
                `${s.name} ${s.id}`.toLowerCase().includes(state.search)
            );
            const byNewest = (a, b) => b.applied.localeCompare(a.applied);
            matches.sort({
                review: (a, b) => ORDER.indexOf(a.lamp_status) - ORDER.indexOf(b.lamp_status) || byNewest(a, b),
                newest: byNewest,
                az: (a, b) => a.name.localeCompare(b.name),
            }[state.sort]);
            currentMatches = matches;

            const pages = Math.max(1, Math.ceil(matches.length / PAGE_SIZE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PAGE_SIZE;
            const pageRows = matches.slice(start, start + PAGE_SIZE);

            const template = document.getElementById('scholarRowTemplate').content.firstElementChild;
            document.getElementById('scholarRows').replaceChildren(...pageRows.map(s => {
                const row = template.cloneNode(true);
                // A red edge on rows that need review (a red background would hide the red badge)
                const needsReview = s.lamp_status === 'Discrepancy' || s.lamp_status === 'Not Found';
                if (needsReview) row.classList.add('row-flag');   // see iaris.css section 7
                row.dataset.id = s.id;
                row.querySelector('[data-cell="name"]').textContent = s.name;
                row.querySelector('[data-cell="id"]').textContent = s.id;
                setTypeBadge(row.querySelector('[data-cell="type"]'), s);
                setScholarshipBadge(row.querySelector('[data-cell="scholarship"]'), s);
                setLampBadge(row.querySelector('[data-cell="lamp"]'), s.lamp_status);
                row.querySelector('[data-cell="updated"]').textContent = s.updated_label;
                row.querySelector('[data-cell="actions"]').appendChild(buildActions(s, true));
                return row;
            }));
            document.getElementById('noResults').classList.toggle('d-none', matches.length > 0);
            document.getElementById('showingText').textContent = matches.length
                ? `Showing ${start + 1}–${start + pageRows.length} of ${matches.length} scholar${matches.length === 1 ? '' : 's'}`
                : '';
            renderPagination(pages);
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

        // ---- Filters ----
        const setFilter = (key, value) => { state[key] = value; state.page = 1; render(); };
        document.getElementById('search').addEventListener('input', e => setFilter('search', e.target.value.trim().toLowerCase()));
        document.getElementById('typeFilter').addEventListener('change', e => setFilter('type', e.target.value));
        document.getElementById('scholarshipFilter').addEventListener('change', e => setFilter('scholarship', e.target.value));
        document.getElementById('sort').addEventListener('change', e => setFilter('sort', e.target.value));
        document.querySelectorAll('#lampPills button').forEach(pill => pill.addEventListener('click', () => {
            document.querySelectorAll('#lampPills button').forEach(p => {
                p.classList.toggle('btn-primary', p === pill);
                p.classList.toggle('btn-outline-secondary', p !== pill);
            });
            setFilter('lamp', pill.dataset.lamp);
        }));

        document.getElementById('exportButton').addEventListener('click', () => {
            const header = ['App No.', 'Name', 'Scholarship Type', 'Scholarship Status', 'LAMP Status', 'IATO Record', 'LAMP Record', 'Last Updated'];
            const lines = currentMatches.map(s => [s.id, s.name, s.type, s.scholarship_status, s.lamp_status, s.type, s.lamp_record, s.updated_label]);
            const csv = [header, ...lines].map(cols => cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = 'lamp-scholars.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- Drawer ----
        const drawerEl = document.getElementById('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
        const CHECK = {
            'Confirmed Match': ['success', 'bi-check-circle-fill', 'Confirmed match'],
            'Pending Check': ['warning', 'bi-clock-fill', 'Pending cross-check'],
            'Discrepancy': ['danger', 'bi-exclamation-circle-fill', 'Discrepancy detected'],
            'Not Found': ['secondary', 'bi-question-circle-fill', 'Not found in LAMP records'],
        };

        function fillDrawer(s) {
            document.getElementById('drawerInitials').textContent = s.initials.toUpperCase();
            document.getElementById('drawerName').textContent = s.name;
            document.getElementById('drawerSub').textContent = `${s.id} · ${s.program}`;
            document.getElementById('drawerType').textContent = s.type;
            setScholarshipBadge(document.getElementById('drawerScholarship'), s);
            drawerEl.querySelectorAll('[data-field]').forEach(el => el.textContent = s[el.dataset.field] ?? '—');

            const [tone, icon, heading] = CHECK[s.lamp_status];
            document.getElementById('checkCard').className = `alert alert-${tone} mb-3`;
            document.getElementById('checkHeading').innerHTML = `<i class="bi ${icon} me-1"></i>`;
            document.getElementById('checkHeading').append(heading);
            // Nothing left to confirm or flag once a record is confirmed
            document.getElementById('checkActions').classList.toggle('d-none', s.lamp_status === 'Confirmed Match');
        }

        function openDrawer(id) {
            drawerId = id;
            fillDrawer(scholarById(id));
            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#tabDetails"]')).show();
            drawer.show();
        }

        // Pop-ups opened from the drawer: hide the drawer first so they don't stack
        const fromDrawer = action => () => { drawer.hide(); openAction(action, drawerId); };
        document.getElementById('drawerConfirm').addEventListener('click', fromDrawer('confirm'));
        document.getElementById('drawerFlag').addEventListener('click', fromDrawer('flag'));
        document.getElementById('drawerEdit').addEventListener('click', fromDrawer('edit'));
        drawerEl.addEventListener('hidden.bs.offcanvas', () => { drawerId = null; });

        // Clicking a row (but not its buttons) or the eye button opens the drawer
        document.getElementById('scholarRows').addEventListener('click', e => {
            const view = e.target.closest('[data-lamp-action="view"]');
            if (view) return openDrawer(view.dataset.id);
            if (e.target.closest('[data-lamp-action]')) return;
            const row = e.target.closest('tr[data-id]');
            if (row) openDrawer(row.dataset.id);
        });
        document.getElementById('scholarRows').addEventListener('keydown', e => {
            const row = e.target.closest('tr[data-id]');
            if (e.key === 'Enter' && row && e.target === row) openDrawer(row.dataset.id);
        });
    </script>
    @include('lamp.partials.scholar-actions-script')
    <script>scholarsChanged();</script>
@endsection
