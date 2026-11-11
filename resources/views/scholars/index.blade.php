@extends('layouts.app')

@section('title', 'iARIS — Scholarship Applicants')
@section('page-title', 'Scholarship Applicants')
@section('page-subtitle', 'Applicants who answered Yes to the scholarship question. LAMP checks them after they pass the entrance exam.')

@php
    // Records that don't match the LAMP Office (a different type, or missing from LAMP's list)
    $needsReview = $scholars->whereIn('lamp', ['Discrepancy', 'Not Found'])->count();

    // Admissions' own job here is just "did they check Yes for scholarship, and did they
    // pass or fail the exam" — the specific scholarship type is LAMP's call, not shown here.
    $passedScholars = $scholars->whereIn('exam', ['passed', 'qualified_other_degree', 'reconsidered']);
    $stats = [
        ['label' => 'Scholarship applicants', 'value' => $scholarTotals['total'], 'sub' => 'Answered Yes on the application', 'tone' => 'primary'],
        ['label' => 'Passed the exam', 'value' => $scholarTotals['passed'], 'sub' => 'Pass or Reconsidered', 'tone' => 'purple'],
        ['label' => 'Confirmed scholars', 'value' => $scholarTotals['confirmed'], 'sub' => 'LAMP decision confirmed by IATO', 'tone' => 'primary'],
        ['label' => 'Waiting for LAMP', 'value' => $scholarTotals['waiting'], 'sub' => 'Passed, not yet checked', 'tone' => 'warning'],
    ];
@endphp

@include('partials.admin-workspace', ['workspacePage' => 'scholars-page', 'workspaceGroup' => auth()->user()->role === 'iato_staff' ? 'Staff' : 'Admin', 'workspaceLabel' => 'Records · Scholarships'])

@push('styles')
    <link href="{{ asset('css/records.css') }}" rel="stylesheet">
@endpush

@section('content')
    {{-- Stat cards --}}
    <div class="admin-stat-grid row row-cols-2 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                {{-- Clicking the LAMP Discrepancies card does the same as "View Discrepancies" --}}
                <div class="admin-stat-card card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100"
                     @if ($stat['label'] === 'LAMP Discrepancies' && $needsReview) data-show-review role="button" tabindex="0" style="cursor: pointer;" title="Show the scholars that don't match LAMP" @endif>
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
            <i class="bi bi-exclamation-triangle-fill fs-5" aria-hidden="true"></i>
            <div class="flex-grow-1 small">
                <strong>{{ $needsReview }} {{ $needsReview === 1 ? 'record doesn\'t' : 'records don\'t' }} match LAMP Office data.</strong>
                {{-- The two numbers are kept up to date by the Ask LAMP script --}}
                <span id="lampThreadCounts"></span>
                @if ($canMessageLamp)
                    Open a scholar, go to the Scholarship tab, and use <b>Ask LAMP to check</b>.
                @endif
            </div>
            <button type="button" class="btn btn-warning btn-sm fw-semibold text-nowrap" id="showReview">
                <i class="bi bi-eye me-1" aria-hidden="true"></i> View Discrepancies
            </button>
        </div>
    @endif

    <div class="records-scholar-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Admissions only notes Yes when an applicant applies for a scholarship, and their exam result. The LAMP Office decides the scholarship type and collects the documents, so iARIS does not show them.</span></div>

    <div class="records-table-card card border-0 shadow-sm rounded-4">
        <form method="get" action="{{ route('records.scholars') }}" class="d-flex flex-wrap align-items-center gap-2 px-4 pt-3">
            <label for="scholarYear" class="small fw-semibold">School year</label>
            <select name="year" id="scholarYear" class="form-select w-auto">
                @foreach ($schoolYears as $year)
                    <option value="{{ $year }}" @selected($year === $selectedSchoolYear)>{{ $year }}</option>
                @endforeach
            </select>
            <button class="btn btn-light border" type="submit">Apply</button>
            <span class="small text-body-secondary">Active applicants for {{ $selectedSchoolYear }}</span>
        </form>
        <div id="scholarLoadError" class="alert alert-danger mx-4 mt-3 d-none" role="alert"></div>
        {{-- Search, filters, sort --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search by name or app number…" aria-label="Search scholars">
            </div>
            <select class="form-select w-auto" id="unitFilter" aria-label="Filter by unit">
                <option value="">All units</option>
                @foreach (collect(['CBEAM', 'CEAS', 'CIHTM', 'CITE', 'CON', 'CCJE', 'Integrated School · Preschool', 'Integrated School · Grade School', 'Integrated School · Junior High', 'Integrated School · Senior High', 'Graduate Programs', 'College of Law', 'iPACE', 'ETEEAP'])->merge($scholars->pluck('unit'))->unique()->sort() as $unit)
                    <option>{{ $unit }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="examFilter" aria-label="Filter by exam result">
                <option value="">All Exam Results</option>
                @foreach ($examStatuses as $key => [$tone, $label])
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto d-none" id="scholarshipFilter" aria-label="Filter by scholarship status">
                <option value="">All Scholarship Status</option>
                @foreach ($scholarshipTones as $status => $tone)
                    <option>{{ $status }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto d-none" id="lampFilter" aria-label="Filter by LAMP cross-check">
                <option value="">All LAMP Status</option>
                <option value="review">Needs Review (both below)</option>
                @foreach ($lampBadges as $lamp => $badge)
                    <option>{{ $lamp }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto d-none" id="sort" aria-label="Sort">
                <option value="newest">Sort: Newest First</option>
                <option value="az">Sort: Name A–Z</option>
                <option value="review">Sort: Discrepancies First</option>
            </select>
            @if ($canEdit)
                <button type="button" class="btn btn-primary fw-semibold ms-lg-auto" data-record-form="add"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Scholar</button>
            @endif
            <button type="button" class="btn btn-light border fw-semibold" id="exportButton" title="Download the filtered list as CSV">
                <i class="bi bi-download me-1" aria-hidden="true"></i> Export
            </button>
        </div>

        {{-- Admission status pills --}}
        <div class="d-none" id="statusPills">
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
                        <th class="text-body-secondary fw-bold">Unit</th>
                        <th class="text-body-secondary fw-bold">Program / Grade</th>
                        <th class="text-body-secondary fw-bold">Exam Result</th>
                        <th class="text-body-secondary fw-bold">LAMP Check</th>
                        {{-- No arrow column here: the table is wide, and clicking anywhere on a row opens it --}}
                        <th class="text-body-secondary fw-bold">Date applied</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="scholarRows">
                    @include('scholars.partials.rows')
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
                <span class="badge rounded-pill bg-white text-body" id="drawerExam"></span>
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
                <p class="small text-body-secondary">The specific scholarship type is LAMP's call once they process the secondary application stage — Admissions only tracks whether the applicant checked Yes for a scholarship.</p>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Exam Result</div><div class="fw-semibold" data-field="exam_label"></div></div>
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
                        <tbody><tr><td class="bg-transparent">Scholar Status</td><td class="bg-transparent fw-semibold">Yes</td><td class="bg-transparent fw-semibold" data-field="lamp_record" id="lampRecord"></td></tr></tbody>
                    </table>
                </div>
                {{-- Ask LAMP to check, or the conversation with LAMP (scholars/partials/ask-lamp) --}}
                <div id="lampAsk"></div>
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

        {{-- Buttons on the left: the AI button sits in the bottom-right corner --}}
        <div class="d-flex flex-wrap gap-2 border-top p-3 no-print">
            <button type="button" class="btn btn-light border" onclick="window.print()"><i class="bi bi-printer me-1" aria-hidden="true"></i> Print</button>
            @if ($canEdit)
                <button type="button" class="btn btn-outline-danger" data-record-remove><i class="bi bi-trash me-1" aria-hidden="true"></i> Remove</button>
                <button type="button" class="btn btn-primary fw-semibold" data-record-form="edit"><i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit Record</button>
            @endif
        </div>
    </div>

    {{-- Add / Edit / Remove (front end only), shared with the Records pages --}}
    @if ($canEdit)
        @include('applicants.partials.record-modals', ['scholarFields' => ['statuses' => array_keys($scholarshipTones)]])
    @endif

    {{-- Ask LAMP to check, and the conversations with LAMP --}}
    @include('scholars.partials.ask-lamp')
@endsection

@section('scripts')
    <script src="{{ asset('js/pagination.js') }}"></script>
    <script>
        let SCHOLARS = @json($scholars);
        let PAGE = @json($pagination);
        const FILTERS = @json((object) $filters);
        const OPEN_SCHOLAR = @json($openScholar);
        const SCHOLARSHIP_TONES = @json($scholarshipTones);
        const STATUS_ORDER = ['Pending', 'For Exam', 'Paid', 'Enrolled'];

        const state = { unit: FILTERS.unit || '', status: FILTERS.status || 'All', exam: FILTERS.exam || '', scholarship: FILTERS.scholarship || '', lamp: FILTERS.lamp || '', search: FILTERS.search || '', sort: FILTERS.sort || 'newest', page: PAGE.page };
        const tbody = document.getElementById('scholarRows');
        let rows = [...tbody.querySelectorAll('.scholar-row')];

        // ---- Filtering, sorting, paging ----
        let pendingRequest;
        let searchTimer;
        function queryParams() {
            const params = new URLSearchParams({year: @json($selectedSchoolYear)});
            Object.entries(state).forEach(([key,value]) => { if (value) params.set(key,value); });
            return params;
        }
        function drawPage() {
            state.page = PAGE.page;
            document.getElementById('noResults').classList.toggle('d-none', PAGE.total > 0);
            document.getElementById('showingText').textContent = PAGE.total ? `Showing ${PAGE.from}–${PAGE.to} of ${PAGE.total} scholars` : 'No scholars';
            renderPagination(PAGE.pages);
        }
        async function render() {
            pendingRequest?.abort(); pendingRequest = new AbortController();
            const signal = pendingRequest.signal;
            tbody.setAttribute('aria-busy', 'true');
            try {
                const params = queryParams();
                const response = await fetch(location.pathname + '?' + params, {headers: {'Accept':'application/json'}, signal});
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not load scholarship applicants.');
                if (signal.aborted) return;
                SCHOLARS = data.scholars; PAGE = data.pagination; tbody.innerHTML = data.html;
                rows = [...tbody.querySelectorAll('.scholar-row')];
                window.RECORD_FORM.items = SCHOLARS; window.RECORD_FORM.rows = rows;
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('applicantDrawer')).hide();
                bindRows(); drawPage();
                params.set('page',PAGE.page); history.replaceState(null,'',location.pathname+'?'+params);
                document.getElementById('scholarLoadError').classList.add('d-none');
            } catch(error) {
                if (error.name !== 'AbortError') { const alert = document.getElementById('scholarLoadError'); alert.textContent = error.message; alert.classList.remove('d-none'); }
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

        const setFilter = (key, value) => { state[key] = value; state.page = 1; render(); };
        document.getElementById('search').addEventListener('input', e => { state.search = e.target.value.trim(); state.page = 1; clearTimeout(searchTimer); searchTimer = setTimeout(render, 300); });
        document.getElementById('unitFilter').addEventListener('change', e => setFilter('unit', e.target.value));
        document.getElementById('examFilter').addEventListener('change', e => setFilter('exam', e.target.value));
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
        function showReview() {
            document.getElementById('lampFilter').value = 'review';
            setFilter('lamp', 'review');
            document.getElementById('scholarRows').closest('.card').scrollIntoView({ behavior: 'smooth' });
        }
        document.getElementById('showReview')?.addEventListener('click', showReview);
        document.querySelector('[data-show-review]')?.addEventListener('click', showReview);
        document.querySelector('[data-show-review]')?.addEventListener('keydown', e => { if (e.key === 'Enter') showReview(); });

        // ---- Export: the filtered rows as a CSV file ----
        document.getElementById('exportButton').addEventListener('click', () => {
            const params = queryParams(); params.delete('page'); params.set('export','csv'); window.location = location.pathname+'?'+params;
        });

        // ---- Profile drawer ----
        const drawerEl = document.getElementById('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

        // How each LAMP result looks in the drawer
        const LAMP_CARD = {
            'Awaiting IATO': { alert: 'info', icon: 'bi-hourglass-split', heading: 'LAMP checked — awaiting IATO confirmation', record: 'text-info-emphasis' },
            'Not Scholar': { alert: 'secondary', icon: 'bi-x-circle', heading: 'LAMP checked — not a scholar', record: 'text-secondary-emphasis' },
            'Match': { alert: 'success', icon: 'bi-check-circle-fill', heading: 'Records match', record: 'text-success-emphasis' },
            'Discrepancy': { alert: 'danger', icon: 'bi-exclamation-circle-fill', heading: 'Discrepancy detected', record: 'text-danger-emphasis' },
            'Not Found': { alert: 'secondary', icon: 'bi-question-circle-fill', heading: 'Not found in LAMP records', record: 'text-danger-emphasis' },
            'Pending Check': { alert: 'warning', icon: 'bi-clock-fill', heading: 'No saved LAMP decision yet', record: 'text-warning-emphasis' },
        };

        let drawerScholar = null;   // the scholar in the drawer (Edit / Remove use it)

        function openDrawer(s) {
            drawerScholar = s;
            document.getElementById('drawerInitials').textContent = s.initials.toUpperCase();
            document.getElementById('drawerName').textContent = s.name;
            document.getElementById('drawerSub').textContent = `${s.id} · ${s.program}`;
            document.getElementById('drawerExam').textContent = s.exam_label;
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
            document.getElementById('lampHeading').innerHTML = `<i class="bi ${card.icon} me-1" aria-hidden="true"></i>`;
            document.getElementById('lampHeading').append(card.heading);
            document.getElementById('lampRecord').className = `bg-transparent fw-semibold ${card.record}`;

            renderTimeline(s);
            window.onScholarDrawer?.(s);   // Ask LAMP box (scholars/partials/ask-lamp)
            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#tabInfo"]')).show();
            drawer.show();
        }

        // Steps up to the admission status are "done"; the last step is the LAMP cross-check
        function renderTimeline(s) {
            const reached = STATUS_ORDER.indexOf(s.status);
            const matched = s.lamp === 'Match';
            const steps = [
                { title: 'Application Submitted', date: s.applied_label, icon: 'bi-file-earmark-plus', tone: 'secondary', done: true },
                { title: 'Scholarship Verified by IATO', date: s.scholarship_status, icon: 'bi-award', tone: 'primary', done: true },
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
                        <i class="bi ${step.icon}" aria-hidden="true"></i>
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

        function bindRows() { rows.forEach(row => {
            const open = () => openDrawer(SCHOLARS[row.dataset.index]);
            row.addEventListener('click', e => {
                // The pencil opens the edit form instead of the drawer
                if (e.target.closest('[data-row-action="edit"]')) return window.openRecordForm?.('edit', SCHOLARS[row.dataset.index]);
                open();
            });
            row.addEventListener('keydown', e => { if (e.key === 'Enter') open(); });
        }); }
        bindRows();
        document.getElementById('search').value = state.search;
        [['unitFilter','unit'],['examFilter','exam'],['scholarshipFilter','scholarship'],['lampFilter','lamp'],['sort','sort']].forEach(([id,key]) => document.getElementById(id).value = state[key]);
        document.querySelectorAll('#statusPills [data-status]').forEach(p => { p.classList.toggle('btn-primary',p.dataset.status===state.status); p.classList.toggle('btn-outline-secondary',p.dataset.status!==state.status); });
        drawPage();

        // ?open=APP-2026-0383 opens that scholar's drawer (IATO's Messages "Open the record" links here)
        const linkedScholar = OPEN_SCHOLAR || SCHOLARS.find(s => s.id === new URLSearchParams(location.search).get('open'));
        if (linkedScholar) openDrawer(linkedScholar);

        // Tells the Add / Edit / Remove partial (applicants/partials/record-modals) where things are on this page
        window.RECORD_FORM = {
            items: SCHOLARS, rows, tbody: 'scholarRows', render, current: () => drawerScholar,
            drawer: bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('applicantDrawer')),
            labels: () => ({ group: 'College / Unit', program: 'Program' }),
            groupOrder: [], statuses: STATUS_ORDER, statusLabels: {}, noun: 'scholar',
            toForm: s => ({ ...s, group: s.unit, scholarship: s.scholarship_status }),
        };
    </script>
@endsection
