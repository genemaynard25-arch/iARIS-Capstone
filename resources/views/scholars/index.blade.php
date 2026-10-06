@extends('layouts.app')

@section('title', 'iARIS — Scholar Records')
@section('page-title', 'Scholar Records')
@section('page-subtitle', "Scholar applicants for AY {$academicYear} — cross-checked against LAMP Office records")

@php
    // Records that don't match the LAMP Office (a different type, or missing from LAMP's list)
    $needsReview = $scholars->where('lamp', '!=', 'Match')->count();

    $stats = [
        ['label' => 'Total Scholars', 'value' => $scholars->count(), 'sub' => 'All scholarship types', 'tone' => 'primary'],
        ['label' => 'DLSL Scholar', 'value' => $scholars->where('type_group', 'DLSL')->count(), 'sub' => 'Institutional grant', 'tone' => 'primary'],
        ['label' => 'Academic / Athletic', 'value' => $scholars->where('type_group', 'Merit')->count(), 'sub' => 'Merit-based', 'tone' => 'info'],
        ['label' => 'Government', 'value' => $scholars->where('type_group', 'Government')->count(), 'sub' => 'CHED / DOST / Other', 'tone' => 'warning'],
        ['label' => 'LAMP Discrepancies', 'value' => $needsReview, 'sub' => 'Records need review', 'tone' => 'danger'],
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
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] === 'primary' ? 'body' : $stat['tone'] . '-emphasis' }}">{{ $stat['value'] }}</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- LAMP notice, only when something needs review --}}
    @if ($needsReview > 0)
        <div class="alert alert-warning d-flex flex-wrap flex-md-nowrap align-items-center gap-3 rounded-4 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div class="flex-grow-1 small">
                <strong>{{ $needsReview }} {{ $needsReview === 1 ? 'record doesn\'t' : 'records don\'t' }} match LAMP Office data.</strong>
                The scholarship type is different, or the scholar is missing from LAMP's list.
                Coordinate with the LAMP Office before finalizing the report.
            </div>
            <button type="button" class="btn btn-warning btn-sm fw-semibold text-nowrap" id="showReview">
                <i class="bi bi-eye me-1"></i> View Discrepancies
            </button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
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
            <select class="form-select w-auto" id="lampFilter" aria-label="Filter by LAMP cross-check">
                <option value="">All LAMP Status</option>
                <option value="review">Needs Review (both below)</option>
                @foreach ($lampBadges as $lamp => $badge)
                    <option>{{ $lamp }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="newest">Sort: Newest First</option>
                <option value="az">Sort: Name A–Z</option>
                <option value="review">Sort: Discrepancies First</option>
            </select>
            <button type="button" class="btn btn-light border fw-semibold" id="exportButton" title="Download the filtered list as CSV">
                <i class="bi bi-download me-1"></i> Export
            </button>
        </div>

        {{-- Admission status pills --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="statusPills">
            <span class="small fw-bold text-body-secondary me-1">Admission:</span>
            @foreach (['All', ...array_keys($statusTones)] as $status)
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold {{ $loop->first ? 'btn-primary' : 'btn-outline-secondary' }}" data-status="{{ $status }}">{{ $status }}</button>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Program</th>
                        <th class="text-body-secondary fw-bold">Scholarship Type</th>
                        <th class="text-body-secondary fw-bold">Scholarship Status</th>
                        <th class="text-body-secondary fw-bold">Admission</th>
                        <th class="text-body-secondary fw-bold">LAMP Check</th>
                        {{-- No arrow column here: the table is wide, and clicking anywhere on a row opens it --}}
                        <th class="pe-4 text-body-secondary fw-bold">Updated</th>
                    </tr>
                </thead>
                <tbody id="scholarRows">
                    @foreach ($scholars as $i => $s)
                        @php
                            $typeTone = $typeTones[$s['type_group']];
                            [$lampTone, $lampIcon] = $lampBadges[$s['lamp']];
                        @endphp
                        <tr class="scholar-row" style="cursor: pointer;" tabindex="0"
                            data-index="{{ $i }}" data-status="{{ $s['status'] }}" data-type="{{ $s['type'] }}"
                            data-scholarship="{{ $s['scholarship_status'] }}" data-lamp="{{ $s['lamp'] }}"
                            data-search="{{ strtolower($s['name'] . ' ' . $s['id']) }}"
                            data-applied="{{ $s['applied'] }}" data-name="{{ $s['name'] }}">
                            <td class="ps-4">
                                <div class="fw-semibold text-nowrap">{{ $s['name'] }}</div>
                                <div class="small text-body-secondary">{{ $s['id'] }}</div>
                            </td>
                            <td>
                                <div>{{ $s['program'] }}</div>
                                <div class="small text-body-secondary">{{ $s['unit'] }}</div>
                            </td>
                            <td><span class="badge rounded-pill bg-{{ $typeTone }}-subtle text-{{ $typeTone }}-emphasis">{{ $s['type'] }}</span></td>
                            <td><span class="badge rounded-pill bg-{{ $scholarshipTones[$s['scholarship_status']] }}-subtle text-{{ $scholarshipTones[$s['scholarship_status']] }}-emphasis"><i class="bi bi-circle-fill me-1" style="font-size: 0.45rem; vertical-align: middle;"></i>{{ $s['scholarship_status'] }}</span></td>
                            <td><span class="badge rounded-pill bg-{{ $statusTones[$s['status']] }}-subtle text-{{ $statusTones[$s['status']] }}-emphasis">{{ $s['status'] }}</span></td>
                            <td><span class="badge rounded-pill bg-{{ $lampTone }}-subtle text-{{ $lampTone }}-emphasis"><i class="bi {{ $lampIcon }} me-1"></i>{{ $s['lamp'] }}</span></td>
                            <td class="pe-4 small text-body-secondary">{{ $s['updated_label'] }}</td>
                        </tr>
                    @endforeach
                    <tr id="noResults" class="d-none">
                        <td colspan="7" class="text-center text-body-secondary py-5">
                            <i class="bi bi-search fs-3 d-block mb-2"></i>
                            No scholars match your search or filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white rounded-bottom-4 d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3">
            <span class="small text-body-secondary" id="showingText"></span>
            <nav aria-label="Scholar pages"><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>

    {{-- Profile drawer. id="applicantDrawer" so the print rules in iaris.css work here too. --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="drawerName" style="--bs-offcanvas-width: 560px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="rounded-4 bg-white bg-opacity-25 border border-white border-opacity-25 fs-3 fw-bold font-brand d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 72px; height: 72px;" id="drawerInitials"></div>
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerName"></h2>
                <div class="small text-white-50 mb-2" id="drawerSub"></div>
                <span class="badge rounded-pill bg-white text-body" id="drawerType"></span>
                <span class="badge rounded-pill" id="drawerScholarship"></span>
            </div>
            <button type="button" class="btn-close btn-close-white no-print" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <ul class="nav nav-underline px-4 border-bottom no-print" role="tablist">
            <li class="nav-item"><button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabInfo" type="button" role="tab">Personal Info</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabScholarship" type="button" role="tab">Scholarship</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabApplication" type="button" role="tab">Application</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabTimeline" type="button" role="tab">Timeline</button></li>
        </ul>

        <div class="offcanvas-body tab-content p-4">
            {{-- Personal info --}}
            <div class="tab-pane fade show active" id="tabInfo" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Personal Information</div>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Full Name</div><div class="fw-semibold" data-field="name"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">App Number</div><div class="fw-semibold" data-field="id"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date of Birth</div><div class="fw-semibold" data-field="dob_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Gender</div><div class="fw-semibold" data-field="gender"></div></div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Contact Details</div>
                <div class="row g-3">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Email Address</div><div class="fw-semibold text-break" data-field="email"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Contact Number</div><div class="fw-semibold" data-field="contact"></div></div>
                    <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">Home Address</div><div class="fw-semibold" data-field="address"></div></div>
                </div>
            </div>

            {{-- Scholarship + LAMP cross-check --}}
            <div class="tab-pane fade" id="tabScholarship" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Scholarship Details</div>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Scholarship Type</div><div class="fw-semibold" data-field="type"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Scholarship Status</div><div class="fw-semibold" data-field="scholarship_status"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">College / Unit</div><div class="fw-semibold" data-field="unit"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Program</div><div class="fw-semibold" data-field="program"></div></div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">LAMP Office Cross-Check</div>
                {{-- The script sets the colour (alert-success / danger / secondary) and the heading --}}
                <div class="alert mb-0" id="lampCard">
                    <div class="fw-bold mb-2" id="lampHeading"></div>
                    <table class="table table-sm small mb-0 bg-transparent">
                        <thead><tr><th class="bg-transparent">Field</th><th class="bg-transparent">IATO Record</th><th class="bg-transparent">LAMP Record</th></tr></thead>
                        <tbody><tr><td class="bg-transparent">Scholarship Type</td><td class="bg-transparent fw-semibold" data-field="type"></td><td class="bg-transparent fw-semibold" data-field="lamp_record" id="lampRecord"></td></tr></tbody>
                    </table>
                </div>
            </div>

            {{-- Application --}}
            <div class="tab-pane fade" id="tabApplication" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Application Details</div>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date Applied</div><div class="fw-semibold" data-field="applied_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Exam Schedule</div><div class="fw-semibold" data-field="exam"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Admission Status</div><div class="fw-semibold" data-field="status"></div></div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Payment</div>
                <div class="rounded-3 border bg-body-tertiary p-3">
                    <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary fw-semibold">Reservation Payment</span><span class="fw-bold" data-field="amount_label"></span></div>
                    <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary fw-semibold">Date of Payment</span><span class="fw-bold" data-field="paid_label"></span></div>
                    <div class="d-flex justify-content-between small"><span class="text-body-secondary fw-semibold">Payment Status</span><span class="fw-bold" id="drawerPayStatus"></span></div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="tab-pane fade" id="tabTimeline" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Application Timeline</div>
                <ol class="list-unstyled mb-0" id="timeline"></ol>
            </div>
        </div>

        <div class="d-flex gap-2 border-top p-3 no-print">
            <button type="button" class="btn btn-light border flex-fill" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
            {{-- TODO: needs an edit form + backend. Disabled for now. --}}
            <button type="button" class="btn btn-primary fw-semibold flex-fill" disabled title="Coming soon"><i class="bi bi-pencil me-1"></i> Edit Record</button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const SCHOLARS = @json($scholars);
        const TYPE_TONES = @json($typeTones);
        const SCHOLARSHIP_TONES = @json($scholarshipTones);
        const PAGE_SIZE = 10;
        const STATUS_ORDER = ['Pending', 'For Exam', 'Paid', 'Enrolled'];

        const state = { status: 'All', type: '', scholarship: '', lamp: '', search: '', sort: 'newest', page: 1 };
        const tbody = document.getElementById('scholarRows');
        const rows = [...tbody.querySelectorAll('.scholar-row')];
        let currentMatches = [];   // rows passing the filters (all pages), used by Export

        // ---- Filtering, sorting, paging ----
        function render() {
            const lampMatches = r => !state.lamp
                || (state.lamp === 'review' ? r.dataset.lamp !== 'Match' : r.dataset.lamp === state.lamp);

            const matches = rows.filter(r =>
                (state.status === 'All' || r.dataset.status === state.status) &&
                (!state.type || r.dataset.type === state.type) &&
                (!state.scholarship || r.dataset.scholarship === state.scholarship) &&
                lampMatches(r) &&
                r.dataset.search.includes(state.search)
            );

            const byNewest = (a, b) => b.dataset.applied.localeCompare(a.dataset.applied);
            const compare = {
                newest: byNewest,
                az: (a, b) => a.dataset.name.localeCompare(b.dataset.name),
                // Records that need review on top, newest first within each group
                review: (a, b) => (a.dataset.lamp === 'Match') - (b.dataset.lamp === 'Match') || byNewest(a, b),
            }[state.sort];
            matches.sort(compare);
            currentMatches = matches;

            const pages = Math.max(1, Math.ceil(matches.length / PAGE_SIZE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PAGE_SIZE;
            const pageRows = matches.slice(start, start + PAGE_SIZE);

            rows.forEach(r => r.classList.add('d-none'));
            pageRows.forEach(r => { r.classList.remove('d-none'); tbody.insertBefore(r, document.getElementById('noResults')); });
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

        const setFilter = (key, value) => { state[key] = value; state.page = 1; render(); };
        document.getElementById('search').addEventListener('input', e => setFilter('search', e.target.value.trim().toLowerCase()));
        document.getElementById('typeFilter').addEventListener('change', e => setFilter('type', e.target.value));
        document.getElementById('scholarshipFilter').addEventListener('change', e => setFilter('scholarship', e.target.value));
        document.getElementById('lampFilter').addEventListener('change', e => setFilter('lamp', e.target.value));
        document.getElementById('sort').addEventListener('change', e => setFilter('sort', e.target.value));

        document.querySelectorAll('#statusPills button').forEach(pill => pill.addEventListener('click', () => {
            document.querySelectorAll('#statusPills button').forEach(p => {
                p.classList.toggle('btn-primary', p === pill);
                p.classList.toggle('btn-outline-secondary', p !== pill);
            });
            setFilter('status', pill.dataset.status);
        }));

        // "View Discrepancies" in the notice: show only records that need review
        document.getElementById('showReview')?.addEventListener('click', () => {
            document.getElementById('lampFilter').value = 'review';
            setFilter('lamp', 'review');
            document.getElementById('scholarRows').closest('.card').scrollIntoView({ behavior: 'smooth' });
        });

        // ---- Export: the filtered rows as a CSV file ----
        document.getElementById('exportButton').addEventListener('click', () => {
            const header = ['App No.', 'Name', 'College / Unit', 'Program', 'Scholarship Type', 'Scholarship Status', 'Admission', 'LAMP Check', 'LAMP Record', 'Updated'];
            const lines = currentMatches.map(row => {
                const s = SCHOLARS[row.dataset.index];
                return [s.id, s.name, s.unit, s.program, s.type, s.scholarship_status, s.status, s.lamp, s.lamp_record, s.updated_label];
            });
            const csv = [header, ...lines].map(cols => cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = 'scholar-records.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- Profile drawer ----
        const drawerEl = document.getElementById('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

        // How each LAMP result looks in the drawer
        const LAMP_CARD = {
            'Match': { alert: 'success', icon: 'bi-check-circle-fill', heading: 'Records match', record: 'text-success-emphasis' },
            'Discrepancy': { alert: 'danger', icon: 'bi-exclamation-circle-fill', heading: 'Discrepancy detected', record: 'text-danger-emphasis' },
            'Not Found': { alert: 'secondary', icon: 'bi-question-circle-fill', heading: 'Not found in LAMP records', record: 'text-danger-emphasis' },
        };

        function openDrawer(s) {
            document.getElementById('drawerInitials').textContent = s.initials.toUpperCase();
            document.getElementById('drawerName').textContent = s.name;
            document.getElementById('drawerSub').textContent = `${s.id} · ${s.program}`;
            document.getElementById('drawerType').textContent = s.type;
            const sch = document.getElementById('drawerScholarship');
            const tone = SCHOLARSHIP_TONES[s.scholarship_status];
            sch.textContent = s.scholarship_status;
            sch.className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;

            // Every element with data-field="x" shows s[x] ("—" if empty)
            drawerEl.querySelectorAll('[data-field]').forEach(el => {
                el.textContent = s[el.dataset.field] ?? (el.dataset.field === 'exam' ? 'Not yet scheduled' : '—');
            });
            document.getElementById('drawerPayStatus').textContent = s.paid_label ? 'Confirmed' : 'Pending';

            const card = LAMP_CARD[s.lamp];
            document.getElementById('lampCard').className = `alert alert-${card.alert} mb-0`;
            document.getElementById('lampHeading').innerHTML = `<i class="bi ${card.icon} me-1"></i>`;
            document.getElementById('lampHeading').append(card.heading);
            document.getElementById('lampRecord').className = `bg-transparent fw-semibold ${card.record}`;

            renderTimeline(s);
            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#tabInfo"]')).show();
            drawer.show();
        }

        // Steps up to the admission status are "done"; the last step is the LAMP cross-check
        function renderTimeline(s) {
            const reached = STATUS_ORDER.indexOf(s.status);
            const matched = s.lamp === 'Match';
            const steps = [
                { title: 'Application Submitted', date: s.applied_label, icon: 'bi-file-earmark-plus', tone: 'secondary', done: true },
                { title: 'Scholarship Verified by IATO', date: `${s.type} · ${s.scholarship_status}`, icon: 'bi-award', tone: 'primary', done: true },
                { title: 'Entrance Exam Scheduled', date: s.exam ?? 'Not yet scheduled', icon: 'bi-calendar-check', tone: 'warning', done: reached >= 1 },
                { title: 'Reservation Payment', date: s.paid_label ? `${s.paid_label} · ${s.amount_label}` : 'Not yet paid', icon: 'bi-cash-coin', tone: 'info', done: reached >= 2 },
                matched
                    ? { title: 'LAMP Cross-Check', date: 'Matches LAMP Office records', icon: 'bi-check2-circle', tone: 'primary', done: true }
                    : { title: 'LAMP Cross-Check', date: 'Needs review with the LAMP Office', icon: 'bi-arrow-repeat', tone: 'danger', done: true },
            ];
            const list = document.getElementById('timeline');
            list.replaceChildren();
            steps.forEach((step, i) => {
                const li = document.createElement('li');
                li.className = `d-flex gap-3 ${i < steps.length - 1 ? 'pb-4' : ''} ${step.done ? '' : 'opacity-50'}`;
                li.innerHTML = `
                    <div class="icon-circle rounded-circle bg-${step.tone}-subtle text-${step.tone}-emphasis d-flex align-items-center justify-content-center">
                        <i class="bi ${step.icon}"></i>
                    </div>
                    <div>
                        <div class="fw-bold small"></div>
                        <div class="small text-body-secondary"></div>
                    </div>`;
                li.querySelector('.fw-bold').textContent = step.title;
                li.querySelector('.text-body-secondary').textContent = step.date;
                list.appendChild(li);
            });
        }

        rows.forEach(row => {
            const open = () => openDrawer(SCHOLARS[row.dataset.index]);
            row.addEventListener('click', open);
            row.addEventListener('keydown', e => { if (e.key === 'Enter') open(); });
        });

        render();
    </script>
@endsection
