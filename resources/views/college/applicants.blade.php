@extends('layouts.app')

@section('title', 'iARIS — College Applicants')
@section('page-title', $heading)
@section('page-subtitle', $description)

@php
    // Status colours (Bootstrap names): Pending red, For Exam orange, Paid blue
    $tones = ['Pending' => 'danger', 'For Exam' => 'warning', 'Paid' => 'info'];

    $stats = [
        ['label' => 'Total Applicants', 'value' => array_sum($status), 'sub' => 'All statuses', 'tone' => 'primary'],
        ['label' => 'Pending', 'value' => $status['Pending'], 'sub' => 'Awaiting action', 'tone' => 'danger'],
        ['label' => 'For Exam', 'value' => $status['For Exam'], 'sub' => 'Scheduled / waiting', 'tone' => 'warning'],
        ['label' => 'Paid', 'value' => $status['Paid'], 'sub' => 'Reservation confirmed', 'tone' => 'info'],
    ];
@endphp

@section('content')
    {{-- Stat cards --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] === 'primary' ? 'body' : $stat['tone'] . '-emphasis' }}">{{ number_format($stat['value']) }}</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        {{-- Search, program, sort, export --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search name or applicant number…" aria-label="Search applicants">
            </div>
            @if ($isChair)
                {{-- A program chair only sees their own program --}}
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                    <i class="bi bi-lock-fill me-1"></i> {{ $scopeName }}
                </span>
            @else
                {{-- Choosing a program reloads the page for it (?program=BSCS), like the dashboard's pills --}}
                <select class="form-select w-auto" id="programFilter" aria-label="Program">
                    <option value="">All Programs</option>
                    @foreach ($programs as $code => $program)
                        <option value="{{ $code }}" @selected($selected === $code)>{{ $program['name'] }}</option>
                    @endforeach
                </select>
            @endif
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="newest">Sort: Newest First</option>
                <option value="az">Sort: Name A–Z</option>
                <option value="za">Sort: Name Z–A</option>
                <option value="status">Sort: Status</option>
            </select>
            <button type="button" class="btn btn-light border fw-semibold" id="exportButton" title="Download the filtered list as CSV">
                <i class="bi bi-download me-1"></i> Export
            </button>
        </div>

        {{-- Status pills, with each status's total --}}
        <div class="d-flex flex-wrap gap-2 px-4 py-3 border-bottom" id="statusPills">
            @foreach (['All' => array_sum($status)] + $status as $label => $count)
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold {{ $loop->first ? 'btn-primary' : 'btn-outline-secondary' }}" data-status="{{ $label }}">
                    {{ $label }} <span class="opacity-75 ms-1">{{ number_format($count) }}</span>
                </button>
            @endforeach
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2 ms-sm-auto align-self-center">
                <i class="bi bi-eye me-1"></i> Read-only view
            </span>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-nowrap">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Program</th>
                        <th class="text-body-secondary fw-bold">Year Level</th>
                        <th class="text-body-secondary fw-bold">Status</th>
                        <th class="text-body-secondary fw-bold">Date Applied</th>
                        <th class="text-body-secondary fw-bold">Updated</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="applicantRows">
                    @foreach ($rows as $i => $a)
                        <tr class="applicant-row" style="cursor: pointer;" tabindex="0"
                            data-index="{{ $i }}" data-status="{{ $a['status'] }}" data-name="{{ $a['name'] }}" data-applied="{{ $a['applied'] }}"
                            data-search="{{ strtolower($a['name'] . ' ' . $a['id']) }}">
                            <td class="ps-4">
                                <div class="fw-semibold text-nowrap">{{ $a['name'] }}</div>
                                <div class="small text-body-secondary">{{ $a['id'] }}</div>
                            </td>
                            <td class="text-nowrap">
                                <span class="badge bg-info-subtle text-info-emphasis">{{ $a['program'] }}</span>
                                <span class="small text-body-secondary">{{ str_replace('BS ', '', $programs[$a['program']]['name']) }}</span>
                            </td>
                            <td class="text-nowrap">1st Year</td>
                            <td><span class="badge rounded-pill bg-{{ $tones[$a['status']] }}-subtle text-{{ $tones[$a['status']] }}-emphasis">{{ $a['status'] }}</span></td>
                            <td class="small text-body-secondary text-nowrap">{{ \Illuminate\Support\Carbon::parse($a['applied'])->format('M j, Y') }}</td>
                            <td class="small text-body-secondary text-nowrap">{{ $a['updated'] }}</td>
                            <td class="pe-4 text-end"><span class="btn btn-sm btn-light border"><i class="bi bi-chevron-right"></i></span></td>
                        </tr>
                    @endforeach
                    <tr id="noResults" class="d-none">
                        <td colspan="7" class="text-center text-body-secondary py-5">
                            <i class="bi bi-folder2-open fs-3 d-block mb-2"></i>
                            No applicants match your search or filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer: count + pagination --}}
        <div class="card-footer bg-white rounded-bottom-4 d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3">
            <span class="small text-body-secondary" id="showingText"></span>
            <nav aria-label="Applicant pages">
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>

    {{-- Profile drawer (Bootstrap offcanvas), read-only --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="drawerName" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="rounded-4 bg-white bg-opacity-25 border border-white border-opacity-25 fs-3 fw-bold font-brand d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 72px; height: 72px;" id="drawerInitials"></div>
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerName"></h2>
                <div class="small text-white-50 mb-2" id="drawerSub"></div>
                <span class="badge rounded-pill" id="drawerBadge"></span>
            </div>
            <button type="button" class="btn-close btn-close-white no-print" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <ul class="nav nav-underline px-4 border-bottom no-print" role="tablist">
            <li class="nav-item"><button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabInfo" type="button" role="tab">Personal Info</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabApplication" type="button" role="tab">Application</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabTimeline" type="button" role="tab">Timeline</button></li>
        </ul>

        <div class="offcanvas-body p-4">
            <div class="tab-content">
                {{-- Personal info --}}
                <div class="tab-pane fade show active" id="tabInfo" role="tabpanel">
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Personal Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Full Name</div><div class="fw-semibold" data-field="name"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Applicant No.</div><div class="fw-semibold" data-field="id"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date of Birth</div><div class="fw-semibold" data-field="dob"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Gender</div><div class="fw-semibold" data-field="gender"></div></div>
                    </div>
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Contact Details</div>
                    <div class="row g-3">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Email</div><div class="fw-semibold text-break" data-field="email"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Contact No.</div><div class="fw-semibold" data-field="phone"></div></div>
                        <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">Address</div><div class="fw-semibold" data-field="address"></div></div>
                    </div>
                </div>

                {{-- Application --}}
                <div class="tab-pane fade" id="tabApplication" role="tabpanel">
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Program Details</div>
                    <div class="row g-3 mb-4">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">College</div><div class="fw-semibold">CITE</div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Year Level</div><div class="fw-semibold">1st Year</div></div>
                        <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">Program</div><div class="fw-semibold" data-field="programName"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date Applied</div><div class="fw-semibold" data-field="appliedLabel"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Exam Schedule</div><div class="fw-semibold" data-field="exam"></div></div>
                    </div>
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Payment &amp; Status</div>
                    <div class="rounded-3 border bg-body-tertiary p-3">
                        <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary fw-semibold">Reservation Payment</span><span class="fw-bold" data-field="amount"></span></div>
                        <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary fw-semibold">Date of Payment</span><span class="fw-bold" data-field="paid"></span></div>
                        <div class="d-flex justify-content-between small"><span class="text-body-secondary fw-semibold">Payment Status</span><span class="fw-bold" data-field="payStatus"></span></div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="tab-pane fade" id="tabTimeline" role="tabpanel">
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Application Timeline</div>
                    <ol class="list-unstyled mb-0" id="timeline"></ol>
                </div>
            </div>

            <div class="small text-body-secondary bg-body-tertiary rounded-3 p-3 mt-4 d-flex gap-2">
                <i class="bi bi-lock-fill"></i>
                <span>This record is read-only. To correct any detail, please contact the IATO.</span>
            </div>
        </div>

        {{-- Buttons on the left, so the AI button in the corner doesn't cover them --}}
        <div class="d-flex gap-2 border-top p-3 no-print">
            <button type="button" class="btn btn-light border px-4" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
            <button type="button" class="btn btn-light border px-4" data-bs-dismiss="offcanvas"><i class="bi bi-x-lg me-1"></i> Close</button>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const APPLICANTS = @json($rows);
        const PROGRAMS = @json($programs);
        const TONES = @json($tones);
        const PAGE_SIZE = 10;
        const STATUS_ORDER = ['Pending', 'For Exam', 'Paid'];
        const state = { status: 'All', search: '', sort: 'newest', page: 1 };
        let currentMatches = [];   // every row that passes the filters (all pages), used by Export

        const tbody = document.getElementById('applicantRows');
        const rows = [...tbody.querySelectorAll('.applicant-row')];
        const dateLabel = iso => new Date(iso + 'T00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

        // ---- Filtering, sorting and paging (in the browser) ----
        function render() {
            const matches = rows.filter(r =>
                (state.status === 'All' || r.dataset.status === state.status) &&
                r.dataset.search.includes(state.search)
            );
            const compare = {
                newest: (a, b) => b.dataset.applied.localeCompare(a.dataset.applied),
                az: (a, b) => a.dataset.name.localeCompare(b.dataset.name),
                za: (a, b) => b.dataset.name.localeCompare(a.dataset.name),
                // Pending first, then For Exam, then Paid; newest first within each
                status: (a, b) => STATUS_ORDER.indexOf(a.dataset.status) - STATUS_ORDER.indexOf(b.dataset.status) || b.dataset.applied.localeCompare(a.dataset.applied),
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
                ? `Showing ${start + 1}–${start + pageRows.length} of ${matches.length} applicant${matches.length === 1 ? '' : 's'}`
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

        // Status pills
        document.querySelectorAll('#statusPills button').forEach(pill => pill.addEventListener('click', () => {
            document.querySelectorAll('#statusPills button').forEach(p => {
                p.classList.toggle('btn-primary', p === pill);
                p.classList.toggle('btn-outline-secondary', p !== pill);
            });
            state.status = pill.dataset.status;
            state.page = 1;
            render();
        }));

        // Search and sort
        document.getElementById('search').addEventListener('input', e => { state.search = e.target.value.trim().toLowerCase(); state.page = 1; render(); });
        document.getElementById('sort').addEventListener('change', e => { state.sort = e.target.value; render(); });

        // Program (deans only): reload the page for that program
        document.getElementById('programFilter')?.addEventListener('change', e => {
            window.location = '{{ route('college.applicants') }}' + (e.target.value ? '?program=' + e.target.value : '');
        });

        // ---- Export: the filtered rows (all pages) as a CSV file ----
        document.getElementById('exportButton').addEventListener('click', () => {
            const header = ['Applicant No.', 'Name', 'Program', 'Year Level', 'Status', 'Date Applied', 'Updated'];
            const lines = currentMatches.map(row => {
                const a = APPLICANTS[row.dataset.index];
                return [a.id, a.name, PROGRAMS[a.program].name, '1st Year', a.status, dateLabel(a.applied), a.updated];
            });
            // Quote every value so commas in names don't break columns
            const csv = [header, ...lines].map(cols => cols.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            // ﻿ tells Excel the file is UTF-8
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = 'cite-applicants{{ $selected ? '-' . strtolower($selected) : '' }}.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- Profile drawer ----
        const drawerEl = document.getElementById('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

        function openDrawer(a) {
            const tone = TONES[a.status];
            // Initials: first letter of the last name and of the first name ("Cruz, Angelo" -> CA)
            document.getElementById('drawerInitials').textContent = a.name.split(',').map(part => part.trim()[0]).join('');
            document.getElementById('drawerName').textContent = a.name;
            document.getElementById('drawerSub').textContent = `${a.id} · ${a.program}`;
            const badge = document.getElementById('drawerBadge');
            badge.textContent = a.status;
            badge.className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;

            // Every element with data-field="x" shows that value
            const values = {
                ...a,
                programName: PROGRAMS[a.program].name,
                appliedLabel: dateLabel(a.applied),
                exam: a.exam ?? 'Not yet scheduled',
                amount: a.paid ? '₱2,000.00' : '—',
                paid: a.paid ?? '—',
                payStatus: a.paid ? 'Confirmed' : 'Pending',
            };
            drawerEl.querySelectorAll('[data-field]').forEach(el => { el.textContent = values[el.dataset.field]; });

            renderTimeline(a);
            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#tabInfo"]')).show();
            drawer.show();
        }

        // Steps the applicant has reached are in colour; later ones are greyed out
        function renderTimeline(a) {
            const steps = [
                { title: 'Application Submitted', date: dateLabel(a.applied), icon: 'bi-file-earmark-plus', tone: 'secondary', done: true },
                { title: a.exam ? 'Entrance Exam Scheduled' : 'Entrance Exam Not Yet Scheduled', date: a.exam ?? 'Waiting for a schedule', icon: 'bi-calendar-check', tone: 'warning', done: !!a.exam },
                { title: 'Reservation Payment', date: a.paid ? `${a.paid} · ₱2,000.00` : 'Not yet paid', icon: 'bi-cash-coin', tone: 'info', done: !!a.paid },
                { title: 'Reservation Confirmed', date: a.paid ? 'Ready for enrollment' : 'Pending payment', icon: 'bi-check-circle', tone: 'primary', done: !!a.paid },
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
            const open = () => openDrawer(APPLICANTS[row.dataset.index]);
            row.addEventListener('click', open);
            row.addEventListener('keydown', e => { if (e.key === 'Enter') open(); });
        });

        render();
    </script>
@endsection
