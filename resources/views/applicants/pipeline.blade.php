@extends('layouts.app')

{{--
    Applicants: the master pipeline of every division (ApplicantController::index).
    Everything is drawn by the script at the bottom from $applicants, so filters, counts,
    the table and the drawer all stay in step.

    Filters (they combine):
      Academic Division / Alternative Learning  pick a group of divisions or one division (one or the other)
      Scholarships                              scholars only, non-scholars, or one scholarship type
      Status pills                              Pending / For Exam / Passed / Not Qualified / Paid
      Department pills                          only when one division is picked (e.g. CITE, CBEAM for College)
--}}

@section('title', 'iARIS — Applicants')
@section('page-title', 'Applicants')
@section('page-subtitle', "Master pipeline of every division for AY {$academicYear}")

@php
    $user = auth()->user();
    $scholarshipTypes = collect($applicants)->pluck('scholarship')->filter()->unique()->sort()->values();

    // The statuses (see App\Support\ApplicantRecords::status()): Bootstrap colour, stat card text.
    // "Enrolled" isn't in the database yet.
    $statuses = [
        'Pending' => ['tone' => 'danger', 'label' => 'Pending', 'sub' => 'Awaiting action'],
        'For Exam' => ['tone' => 'warning', 'label' => 'For Exam / Assessment', 'sub' => 'Scheduled / waiting'],
        'Passed' => ['tone' => 'primary', 'label' => 'Passed', 'sub' => 'Passed the entrance test'],
        'Not Qualified' => ['tone' => 'secondary', 'label' => 'Not Qualified', 'sub' => 'Did not pass the test'],
        'Paid' => ['tone' => 'info', 'label' => 'Paid', 'sub' => 'Reservation confirmed'],
    ];

    // Records pages for "Add Applicant". Some need a permission from Access Control.
    $recordPermission = ['Integrated School' => 'is', 'College' => 'college'];
    $addOptions = collect($divisions)->map(fn ($d, $name) => $d + [
        'name' => $name,
        'allowed' => $d['records'] && (! isset($recordPermission[$name]) || $user->canAccess($recordPermission[$name])),
    ]);
@endphp

@section('content')
    {{-- Stat cards: the numbers follow the filters (the script fills them in) --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-3 mb-4">
        <div class="col">
            <div class="card border-0 border-top border-3 border-dark shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">Total Applicants</div>
                    <div class="fs-3 fw-bold" data-stat="All">0</div>
                    <div class="small text-body-secondary" id="scopeLabel">All divisions</div>
                </div>
            </div>
        </div>
        @foreach ($statuses as $key => $status)
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $status['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $status['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $status['tone'] }}-emphasis" data-stat="{{ $key }}">0</div>
                        <div class="small text-body-secondary">{{ $status['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        {{-- Search, Export, Add --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 pt-4 pb-3">
            <div class="input-group flex-grow-1" style="max-width: 420px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control border-start-0" id="search" placeholder="Search name or applicant number…" aria-label="Search applicants">
            </div>
            <div class="d-flex gap-2 ms-md-auto">
                <button type="button" class="btn btn-light border fw-semibold" id="exportCsv"><i class="bi bi-download me-1"></i> Export</button>
                <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-plus-lg me-1"></i> Add Applicant</button>
            </div>
        </div>

        {{-- The four filters --}}
        <div class="row g-3 px-4 pb-3">
            <div class="col-6 col-lg-3">
                <label for="filterAd" class="form-label small fw-bold text-uppercase text-body-secondary mb-1">Academic Division</label>
                <select class="form-select" id="filterAd">
                    <option value="">Any</option>
                    <option value="*">All academic divisions</option>
                    @foreach ($divisions as $name => $division)
                        @if ($division['group'] === 'ad') <option>{{ $name }}</option> @endif
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="filterAl" class="form-label small fw-bold text-uppercase text-body-secondary mb-1">Alternative Learning</label>
                <select class="form-select" id="filterAl">
                    <option value="">Any</option>
                    <option value="*">All alternative learning</option>
                    @foreach ($divisions as $name => $division)
                        @if ($division['group'] === 'al') <option>{{ $name }}</option> @endif
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="filterScholarship" class="form-label small fw-bold text-uppercase text-body-secondary mb-1">Scholarships</label>
                <select class="form-select" id="filterScholarship">
                    <option value="">Any</option>
                    <option value="*">Scholars only</option>
                    <option value="none">Non-scholars</option>
                    @foreach ($scholarshipTypes as $type)
                        <option>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="sort" class="form-label small fw-bold text-uppercase text-body-secondary mb-1">Sort</label>
                <select class="form-select" id="sort">
                    <option value="new">Newest first</option>
                    <option value="az">Name A–Z</option>
                    <option value="za">Name Z–A</option>
                    <option value="status">Status</option>
                </select>
            </div>
        </div>

        {{-- Active filters as removable chips --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 pb-3 d-none" id="chips"></div>

        {{-- Status pills, then department pills (only when one division is picked) --}}
        <div class="border-top px-4 py-3">
            <div class="d-flex flex-wrap align-items-center gap-2" id="statusPills" role="group" aria-label="Filter by status"></div>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2 d-none" id="deptPills" role="group" aria-label="Filter by department"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-nowrap">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Division</th>
                        <th class="text-body-secondary fw-bold">Department</th>
                        <th class="text-body-secondary fw-bold">Program / Grade</th>
                        <th class="text-body-secondary fw-bold">Scholarship</th>
                        <th class="text-body-secondary fw-bold">Status</th>
                        <th class="text-body-secondary fw-bold">Date Applied</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3 border-top">
            <div class="small text-body-secondary" id="footerText"></div>
            <nav aria-label="Applicant pages"><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>

    {{-- Profile drawer (Bootstrap offcanvas). Same id as the Records pages, so the print rules in iaris.css apply. --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="drawerName" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="rounded-circle bg-white bg-opacity-25 fs-4 fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 64px; height: 64px;" id="drawerInitials"></div>
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerName"></h2>
                <div class="small text-white-50 mb-2" id="drawerSub"></div>
                <span class="badge rounded-pill" id="drawerBadge"></span>
            </div>
            <button type="button" class="btn-close btn-close-white no-print" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <ul class="nav nav-underline px-4 border-bottom no-print" role="tablist">
            <li class="nav-item" role="presentation"><button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#paneInfo" type="button" role="tab" aria-controls="paneInfo" aria-selected="true">Personal</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#paneApplication" type="button" role="tab" aria-controls="paneApplication" aria-selected="false">Application</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#paneTimeline" type="button" role="tab" aria-controls="paneTimeline" aria-selected="false">Timeline</button></li>
        </ul>

        <div class="offcanvas-body tab-content p-4">
            {{-- Personal --}}
            <div class="tab-pane fade show active" id="paneInfo" role="tabpanel" tabindex="0">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Applicant</div>
                <dl class="row g-3 mb-0 small">
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Full Name</dt><dd class="fw-bold mb-0" data-field="name"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Applicant No.</dt><dd class="fw-bold mb-0 font-monospace" data-field="id"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Date of Birth</dt><dd class="fw-bold mb-0" data-field="dob"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Gender</dt><dd class="fw-bold mb-0" data-field="gender"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold" id="emailLabel">Email</dt><dd class="fw-bold mb-0 text-break" data-field="email"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold" id="contactLabel">Contact No.</dt><dd class="fw-bold mb-0" data-field="contact"></dd></div>
                    <div class="col-12"><dt class="text-body-secondary fw-semibold">Address</dt><dd class="fw-bold mb-0" data-field="address"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">PWD</dt><dd class="fw-bold mb-0" data-field="pwd"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">SSEN</dt><dd class="fw-bold mb-0" data-field="ssen"></dd></div>
                </dl>
            </div>

            {{-- Application --}}
            <div class="tab-pane fade" id="paneApplication" role="tabpanel" tabindex="0">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Placement</div>
                <dl class="row g-3 small mb-4">
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Division</dt><dd class="fw-bold mb-0" data-field="division"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold" id="deptLabel">Department</dt><dd class="fw-bold mb-0" data-field="dept_label"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Scholarship</dt><dd class="fw-bold mb-0" data-field="scholarship"></dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Date Applied</dt><dd class="fw-bold mb-0" data-field="applied"></dd></div>
                    <div class="col-12"><dt class="text-body-secondary fw-semibold" id="examLabel">Entrance Exam</dt><dd class="fw-bold mb-0" data-field="exam"></dd></div>
                    <div class="col-12">
                        <dt class="text-body-secondary fw-semibold">Exam Result</dt>
                        <dd class="fw-bold mb-0" id="examResultLabel"></dd>
                        <div class="d-none mt-2" id="examResultEdit">
                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                <select class="form-select" id="examResultSelect">
                                    @foreach ($examStatuses as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-primary fw-semibold" id="examResultSave">Save</button>
                            </div>
                            <div class="form-text">Passing (Passed / Qualified for Another Program / Reconsidered) automatically issues the Notice of Admission.</div>
                        </div>
                    </div>
                    <div class="col-12"><dt class="text-body-secondary fw-semibold">Program / Grade</dt><dd class="fw-bold mb-0" data-field="program"></dd></div>
                </dl>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Payment &amp; Status</div>
                <div class="rounded-3 bg-body-tertiary p-3 d-grid gap-2 small">
                    <div class="d-flex justify-content-between"><span class="text-body-secondary fw-semibold">Reservation Payment</span><span class="fw-bold" data-field="amount"></span></div>
                    <div class="d-flex justify-content-between"><span class="text-body-secondary fw-semibold">Date of Payment</span><span class="fw-bold" data-field="paid"></span></div>
                    <div class="d-flex justify-content-between"><span class="text-body-secondary fw-semibold">Payment Status</span><span class="fw-bold" data-field="payStatus"></span></div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="tab-pane fade" id="paneTimeline" role="tabpanel" tabindex="0">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Application Timeline</div>
                <ol class="list-unstyled mb-0" id="timeline"></ol>
            </div>
        </div>

        {{-- Buttons on the left: the AI button sits in the bottom-right corner --}}
        <div class="d-flex flex-wrap gap-2 p-3 border-top no-print">
            <a class="btn btn-primary fw-semibold" id="drawerRecords" href="#"><i class="bi bi-pencil me-1"></i> Edit in records</a>
            <button type="button" class="btn btn-light border fw-semibold" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
        </div>
    </div>

    {{-- Add Applicant: pick the division first; adding happens on that Records page --}}
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="addTitle">Add an applicant</h3>
                        <p class="small text-white-50 mb-0">Choose where the applicant belongs. You add them on that records page.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    @foreach (['ad' => 'Academic Divisions', 'al' => 'Alternative Learning'] as $group => $groupName)
                        <div class="small fw-bold text-uppercase text-body-secondary mb-2">{{ $groupName }}</div>
                        <div class="row row-cols-2 g-2 mb-3">
                            @foreach ($addOptions->where('group', $group) as $option)
                                <div class="col">
                                    @include('applicants.partials.add-option', ['option' => $option])
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    <div class="small fw-bold text-uppercase text-body-secondary mb-2">Scholarships</div>
                    <div class="row row-cols-2 g-2">
                        <div class="col">
                            @include('applicants.partials.add-option', ['option' => [
                                'name' => 'Scholarships', 'icon' => 'bi-award', 'colors' => ['#2558D9', '#EAF1FF'],
                                'records' => '/records/scholars', 'allowed' => $user->canAccess('scholars'),
                            ]])
                        </div>
                    </div>
                    {{-- TODO: the Records pages' Add buttons are disabled until the backend is ready --}}
                    <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i> The add forms on the records pages come with the backend.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const APPLICANTS = @json($applicants);
        const DIVISIONS = @json($divisions);
        const STATUSES = @json($statuses);
        const CAN_EDIT_EXAM = @json($canEditExam);
        const EXAM_STATUSES = @json($examStatuses);
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const PAGE_SIZE = 10;

        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const formatDate = (value, long = false) => value
            ? new Date(value + 'T00:00').toLocaleDateString('en-US', { month: long ? 'long' : 'short', day: 'numeric', year: 'numeric' })
            : null;
        const money = n => n ? '₱' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2 }) : null;

        // What each division calls things
        const statusLabel = a => a.status === 'For Exam'
            ? (a.division === 'ETEEAP' ? 'For Assessment' : ['College of Law', 'Graduate Programs'].includes(a.division) ? 'For Exam / Interview' : 'For Exam')
            : a.status;
        const examLabel = a => a.division === 'ETEEAP' ? 'Portfolio Assessment'
            : a.division === 'Integrated School' && a.dept === 'Preschool' ? 'Readiness Assessment' : 'Entrance Exam / Interview';

        const state = { status: 'All', dept: 'All', page: 1 };

        // ---- Which divisions are in view: from Academic Division or Alternative Learning (only one is used at a time) ----
        function divisionsInView() {
            const ad = $('filterAd').value, al = $('filterAl').value, all = Object.keys(DIVISIONS);
            if (ad) return ad === '*' ? all.filter(d => DIVISIONS[d].group === 'ad') : [ad];
            if (al) return al === '*' ? all.filter(d => DIVISIONS[d].group === 'al') : [al];
            return all;
        }
        const singleDivision = () => { const d = divisionsInView(); return d.length === 1 ? d[0] : null; };

        function matchesScholarship(a) {
            const s = $('filterScholarship').value;
            return !s || (s === '*' ? !!a.scholarship : s === 'none' ? !a.scholarship : a.scholarship === s);
        }

        // Everything except the status pill, so the pills can show how many each status would give
        function baseRows() {
            const divs = divisionsInView(), one = singleDivision(), q = $('search').value.trim().toLowerCase();
            return APPLICANTS.filter(a => divs.includes(a.division)
                && (!one || state.dept === 'All' || a.dept === state.dept)
                && matchesScholarship(a)
                && (!q || a.name.toLowerCase().includes(q) || a.id.toLowerCase().includes(q)));
        }

        function visibleRows() {
            const order = { Pending: 0, 'For Exam': 1, Passed: 2, 'Not Qualified': 3, Paid: 4 }, sort = $('sort').value;
            return baseRows()
                .filter(a => state.status === 'All' || a.status === state.status)
                .sort((x, y) => sort === 'az' ? x.name.localeCompare(y.name)
                    : sort === 'za' ? y.name.localeCompare(x.name)
                    : sort === 'status' ? (order[x.status] - order[y.status]) || y.applied.localeCompare(x.applied)
                    : y.applied.localeCompare(x.applied));
        }

        function scopeLabel() {
            const s = $('filterScholarship').value, one = singleDivision();
            if (s && s !== 'none') return s === '*' ? 'Scholars only' : s + 's';
            if (one) return one + (state.dept !== 'All' ? ' · ' + state.dept : '');
            if ($('filterAd').value === '*') return 'Academic divisions';
            if ($('filterAl').value === '*') return 'Alternative learning';
            return s === 'none' ? 'Non-scholars' : 'All divisions';
        }

        // ---- Stat cards, chips, status pills and department pills ----
        function renderFilters() {
            const base = baseRows();
            const counts = { All: base.length };
            Object.keys(STATUSES).forEach(k => counts[k] = base.filter(a => a.status === k).length);
            document.querySelectorAll('[data-stat]').forEach(el => el.textContent = counts[el.dataset.stat].toLocaleString());
            $('scopeLabel').textContent = scopeLabel();

            $('statusPills').innerHTML = '<span class="small fw-bold text-body-secondary me-1">Status:</span>' +
                ['All', ...Object.keys(STATUSES)].map(k => `<button type="button" class="btn btn-sm rounded-pill fw-semibold ${k === state.status ? 'btn-primary' : 'btn-light border'}" data-status="${esc(k)}" aria-pressed="${k === state.status}">
                    ${esc(k === 'All' ? 'All' : STATUSES[k].label)} <span class="badge rounded-pill ${k === state.status ? 'bg-white text-primary' : 'bg-body-secondary text-body'} ms-1">${counts[k]}</span></button>`).join('');

            // Department pills, counted within the other filters (but not the department itself)
            const one = singleDivision();
            $('deptPills').classList.toggle('d-none', !one);
            if (one) {
                const inDivision = APPLICANTS.filter(a => a.division === one && matchesScholarship(a));
                const depts = [...new Set(inDivision.map(a => a.dept))];
                $('deptPills').innerHTML = `<span class="small fw-bold text-body-secondary me-1">${esc(one)} · ${esc(DIVISIONS[one].dept)}:</span>` +
                    ['All', ...depts].map(k => `<button type="button" class="btn btn-sm rounded-pill fw-semibold ${k === state.dept ? 'btn-dark' : 'btn-light border'}" data-dept="${esc(k)}" aria-pressed="${k === state.dept}">
                        ${esc(k)}${k === 'All' ? '' : ` <span class="text-body-secondary fw-normal ${k === state.dept ? 'text-white-50' : ''}">${inDivision.filter(a => a.dept === k).length}</span>`}</button>`).join('');
            } else {
                state.dept = 'All';
            }

            // Chips: one per active filter, each with an ✕
            const chips = [];
            if ($('filterAd').value) chips.push(['filterAd', 'Academic Division: ' + ($('filterAd').value === '*' ? 'All' : $('filterAd').value)]);
            if ($('filterAl').value) chips.push(['filterAl', 'Alternative Learning: ' + ($('filterAl').value === '*' ? 'All' : $('filterAl').value)]);
            if (one && state.dept !== 'All') chips.push(['dept', DIVISIONS[one].dept + ': ' + state.dept]);
            if ($('filterScholarship').value) chips.push(['filterScholarship', 'Scholarship: ' + $('filterScholarship').selectedOptions[0].text]);
            $('chips').classList.toggle('d-none', !chips.length);
            $('chips').innerHTML = chips.map(([key, text]) => `<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle d-inline-flex align-items-center gap-1 py-2 ps-3 pe-2 fw-semibold">
                    ${esc(text)}<button type="button" class="btn-close" style="font-size:.55rem" data-chip="${key}" aria-label="Remove filter ${esc(text)}"></button></span>`).join('')
                + (chips.length > 1 ? '<button type="button" class="btn btn-sm btn-link fw-semibold" id="clearAll">Clear all</button>' : '');
        }

        // ---- Table + pagination ----
        function renderRows() {
            const rows = visibleRows(), pages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PAGE_SIZE, pageRows = rows.slice(start, start + PAGE_SIZE);

            $('rows').innerHTML = pageRows.length ? pageRows.map(a => {
                const [color, background] = DIVISIONS[a.division].colors;
                return `<tr role="button" tabindex="0" data-id="${esc(a.id)}">
                    <td class="ps-4"><div class="fw-bold text-nowrap">${esc(a.name)}</div><div class="small text-body-secondary font-monospace">${esc(a.id)}</div></td>
                    <td><span class="badge rounded-pill fw-semibold" style="color:${color};background:${background}">${esc(a.division)}</span></td>
                    <td><span class="badge bg-body-tertiary text-body border fw-semibold" title="${esc(a.dept_label)}">${esc(a.dept)}</span></td>
                    <td class="small">${esc(a.program)}</td>
                    <td>${a.scholarship ? `<span class="badge bg-warning-subtle text-warning-emphasis fw-semibold"><i class="bi bi-award me-1"></i>${esc(a.scholarship)}</span>` : '<span class="text-body-secondary">—</span>'}</td>
                    <td><span class="badge rounded-pill bg-${STATUSES[a.status].tone}-subtle text-${STATUSES[a.status].tone}-emphasis">${esc(statusLabel(a))}</span></td>
                    <td class="small text-body-secondary text-nowrap">${formatDate(a.applied)}</td>
                    <td class="pe-4 text-end"><i class="bi bi-chevron-right text-body-secondary"></i></td></tr>`;
            }).join('') : `<tr><td colspan="8" class="text-center text-body-secondary py-5"><i class="bi bi-folder2-open fs-3 d-block mb-2"></i>No applicants match these filters.</td></tr>`;

            $('footerText').textContent = rows.length
                ? `Showing ${start + 1}–${start + pageRows.length} of ${rows.length} applicant${rows.length === 1 ? '' : 's'}`
                : 'No applicants';

            const pageButton = (label, page, { disabled = false, active = false, aria = '' } = {}) =>
                `<li class="page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}"><button type="button" class="page-link" data-page="${page}" ${aria ? `aria-label="${aria}"` : ''} ${active ? 'aria-current="page"' : ''}>${label}</button></li>`;
            $('pagination').innerHTML = pageButton('<i class="bi bi-chevron-left"></i>', state.page - 1, { disabled: state.page === 1, aria: 'Previous page' })
                + Array.from({ length: pages }, (_, i) => pageButton(i + 1, i + 1, { active: i + 1 === state.page })).join('')
                + pageButton('<i class="bi bi-chevron-right"></i>', state.page + 1, { disabled: state.page === pages, aria: 'Next page' });
        }

        function render() { renderFilters(); renderRows(); }
        const refresh = () => { state.page = 1; render(); };

        // ---- Events ----
        // Academic Division and Alternative Learning are either/or: picking one clears the other
        $('filterAd').addEventListener('change', () => { if ($('filterAd').value) $('filterAl').value = ''; state.dept = 'All'; refresh(); });
        $('filterAl').addEventListener('change', () => { if ($('filterAl').value) $('filterAd').value = ''; state.dept = 'All'; refresh(); });
        $('filterScholarship').addEventListener('change', refresh);
        $('sort').addEventListener('change', renderRows);
        $('search').addEventListener('input', refresh);

        $('statusPills').addEventListener('click', e => { const b = e.target.closest('[data-status]'); if (b) { state.status = b.dataset.status; refresh(); } });
        $('deptPills').addEventListener('click', e => { const b = e.target.closest('[data-dept]'); if (b) { state.dept = b.dataset.dept; refresh(); } });
        $('chips').addEventListener('click', e => {
            if (e.target.id === 'clearAll') {
                ['filterAd', 'filterAl', 'filterScholarship'].forEach(id => $(id).value = '');
                Object.assign(state, { status: 'All', dept: 'All' });
                $('search').value = '';
                return refresh();
            }
            const key = e.target.dataset.chip;
            if (!key) return;
            if (key === 'dept') state.dept = 'All';
            else { $(key).value = ''; if (key !== 'filterScholarship') state.dept = 'All'; }
            refresh();
        });
        $('pagination').addEventListener('click', e => {
            const b = e.target.closest('[data-page]');
            if (b && !b.closest('.disabled')) { state.page = Number(b.dataset.page); renderRows(); }
        });

        // Open the drawer with a click or Enter on a row
        const openFromRow = e => { const tr = e.target.closest('tr[data-id]'); if (tr) openDrawer(APPLICANTS.find(a => a.id === tr.dataset.id)); };
        $('rows').addEventListener('click', openFromRow);
        $('rows').addEventListener('keydown', e => { if (e.key === 'Enter') openFromRow(e); });

        // ---- Export: the filtered rows (all pages) as CSV ----
        $('exportCsv').addEventListener('click', () => {
            const head = ['Applicant No.', 'Name', 'Division', 'Department', 'Program / Grade', 'Scholarship', 'Status', 'Date Applied'];
            const lines = visibleRows().map(a => [a.id, a.name, a.division, a.dept_label, a.program, a.scholarship || 'None', statusLabel(a), a.applied]);
            const csv = [head, ...lines].map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = 'iaris-applicants.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- Drawer ----
        const drawerEl = $('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

        let drawerApplicant = null;   // the applicant currently open, for the exam-result Save button

        function openDrawer(a) {
            drawerApplicant = a;
            const tone = STATUSES[a.status].tone, isSchool = a.division === 'Integrated School';
            const set = (field, value) => drawerEl.querySelectorAll(`[data-field="${field}"]`).forEach(el => el.textContent = value ?? '—');
            const [last, first] = a.name.split(', ');
            $('drawerInitials').textContent = (first[0] + last[0]).toUpperCase();
            $('drawerName').textContent = a.name;
            $('drawerSub').textContent = `${a.id} · ${a.division}`;
            $('drawerBadge').className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
            $('drawerBadge').textContent = statusLabel(a);

            // Integrated School applicants are children: their contact details are the guardian's
            $('emailLabel').textContent = isSchool ? 'Guardian Email' : 'Email';
            $('contactLabel').textContent = isSchool ? 'Guardian Contact' : 'Contact No.';
            $('deptLabel').textContent = DIVISIONS[a.division].dept;
            $('examLabel').textContent = examLabel(a);
            ['name', 'id', 'gender', 'email', 'contact', 'address', 'division', 'dept_label', 'program', 'pwd', 'ssen'].forEach(f => set(f, a[f]));
            set('dob', formatDate(a.dob, true));
            set('applied', formatDate(a.applied));
            set('scholarship', a.scholarship || 'None');
            set('exam', a.exam || 'Not yet scheduled');

            // Exam result: editable only for divisions backed by real records (not the
            // still-placeholder College of Law / iPACE) and only for users who can edit them
            const canEditThis = a.exam_status != null && CAN_EDIT_EXAM[a.division];
            $('examResultLabel').textContent = a.exam_status ? (EXAM_STATUSES[a.exam_status] ?? a.exam_status) : '—';
            $('examResultEdit').classList.toggle('d-none', !canEditThis);
            if (canEditThis) $('examResultSelect').value = a.exam_status;

            set('amount', money(a.amount));
            set('paid', formatDate(a.paid));
            set('payStatus', a.paid ? 'Confirmed' : 'Pending');

            // Timeline: five steps, filled in as far as the applicant has got
            const examDone = a.status !== 'Pending', paid = a.status === 'Paid', failed = a.status === 'Not Qualified';
            const resultIn = failed || paid || a.status === 'Passed';
            const steps = [
                ['bi-file-earmark-plus', 'Application Submitted', formatDate(a.applied), true, 'secondary'],
                ['bi-calendar-check', examLabel(a), a.exam || 'Not yet scheduled', examDone, 'warning'],
                [failed ? 'bi-x-circle' : 'bi-patch-check', 'Result', failed ? 'Not qualified' : resultIn ? 'Passed' : 'Waiting for the result', resultIn, failed ? 'secondary' : 'primary'],
                ['bi-cash-coin', 'Reservation Payment', paid ? `${formatDate(a.paid)} · ${money(a.amount)}` : 'Not yet paid', paid, 'info'],
                ['bi-check-circle', 'Reservation Confirmed', paid ? formatDate(a.paid) : 'Waiting for payment', paid, 'primary'],
            ];
            $('timeline').innerHTML = steps.map(([icon, title, date, done, color]) => `<li class="d-flex gap-3 pb-3">
                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 ${done ? `bg-${color} text-white` : 'bg-body-secondary text-body-secondary'}" style="width:32px;height:32px"><i class="bi ${icon}"></i></span>
                <div><div class="fw-bold small ${done ? '' : 'text-body-secondary'}">${esc(title)}</div><div class="small text-body-secondary">${esc(date)}</div></div></li>`).join('');

            // "Edit in records" opens this person on their Records page (ETEEAP has none yet)
            const records = DIVISIONS[a.division].records, link = $('drawerRecords');
            link.classList.toggle('disabled', !records);
            link.setAttribute('aria-disabled', !records);
            link.href = records ? `${records}?open=${encodeURIComponent(a.id)}` : '#';
            link.innerHTML = records ? '<i class="bi bi-pencil me-1"></i> Edit in records' : '<i class="bi bi-clock me-1"></i> Records page coming soon';

            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#paneInfo"]')).show();
            drawer.show();
        }

        // ---- Exam Result: save from the drawer, no full page reload ----
        $('examResultSave').addEventListener('click', async () => {
            if (!drawerApplicant) return;
            const btn = $('examResultSave'), select = $('examResultSelect');
            const original = btn.innerHTML;
            btn.disabled = true;
            select.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';

            try {
                const res = await fetch(`/applicants/${encodeURIComponent(drawerApplicant.id)}/exam-status`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ admission_test_status: select.value }),
                });
                if (!res.ok) throw new Error((await res.json().catch(() => null))?.message || 'Save failed');
                const data = await res.json();

                // Update this applicant in place (in the data, the open drawer, and the table)
                Object.assign(drawerApplicant, { exam_status: data.exam_status, status: data.status });
                $('examResultLabel').textContent = data.exam_label;
                const tone = STATUSES[data.status].tone;
                $('drawerBadge').className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
                $('drawerBadge').textContent = statusLabel(drawerApplicant);
                render();

                btn.innerHTML = '<i class="bi bi-check-lg"></i> Saved';
                setTimeout(() => { btn.innerHTML = original; }, 1500);
            } catch (err) {
                btn.innerHTML = original;
                alert(err.message || 'Could not save the exam result. Please try again.');
            } finally {
                btn.disabled = false;
                select.disabled = false;
            }
        });

        render();
    </script>
@endsection
