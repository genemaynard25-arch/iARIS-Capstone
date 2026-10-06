@extends('layouts.app')

{{--
    This view is used by four pages, picked by $scope:
    'all' = /applicants, 'college' = /records/college, 'is' = /records/is, 'law' = /records/law.
    $page holds what's different between them ($defaults fills in anything a page leaves out):
      extra        the one extra column: which field, its heading, and whether it goes
                   before the second column ('first') or after it
      labels       headings / "All ..." filter text for the two filter columns
                   (null on Applicants, where they follow the College / IS tab).
                   'programAll' => null hides the second filter.
      statuses     which statuses get a stat card and a pill
      statusLabels a different name to show for a status
      programField the drawer's label for program_label
      drawerExtras more fields shown only in the drawer
      groupBadge   show the first column as a small tag
--}}
@php
    $defaults = [
        'extra' => null, 'labels' => null, 'groupBadge' => false,
        'statuses' => ['Pending', 'For Exam', 'Paid', 'Enrolled'], 'statusLabels' => [],
        'programField' => 'Program / Track / Strand', 'examField' => 'Exam Schedule', 'drawerExtras' => [],
    ];
    $page = array_merge($defaults, [
        'all' => [
            'title' => 'All Applicants', 'subtitle' => "Academic Year {$academicYear} · {$periodLabel}",
            'total' => 'Total Applicants', 'totalSub' => 'College + IS combined', 'noun' => 'applicant',
        ],
        'college' => [
            'title' => 'College Records', 'subtitle' => "All college applicant and enrolled student records for AY {$academicYear}",
            'total' => 'Total College Students', 'totalSub' => 'All colleges combined', 'noun' => 'student',
            'extra' => ['key' => 'year', 'label' => 'Year Level', 'first' => false],
            'labels' => ['group' => 'College', 'groupAll' => 'All Colleges', 'program' => 'Program', 'programAll' => 'All Programs'],
        ],
        'is' => [
            'title' => 'Integrated School Records', 'subtitle' => "All Integrated School applicant and enrolled student records for AY {$academicYear}",
            'total' => 'Total IS Students', 'totalSub' => 'All sub-levels combined', 'noun' => 'student',
            'extra' => ['key' => 'level', 'label' => 'Grade / Level', 'first' => true],
            'labels' => ['group' => 'Sub-Level', 'groupAll' => 'All Sub-Levels', 'program' => 'Strand / Track', 'programAll' => 'All Strands / Tracks'],
        ],
        // Law: the first column is the program (JD / LLM / JSD), the second is the undergraduate degree
        'law' => [
            'title' => 'College of Law Records', 'subtitle' => "Law program applicant records for AY {$academicYear}",
            'total' => 'Total Applicants', 'totalSub' => 'All law programs', 'noun' => 'applicant',
            'extra' => ['key' => 'bar', 'label' => 'Bar Exam', 'first' => false],
            'labels' => ['group' => 'Program', 'groupAll' => 'All Programs', 'program' => 'Undergraduate Degree', 'programAll' => null],
            'groupBadge' => true,
            'statuses' => ['Pending', 'For Exam', 'Paid'],
            'statusLabels' => ['For Exam' => 'For Exam / Interview'],
            'programField' => 'Program', 'examField' => 'Exam / Interview Schedule',
            'drawerExtras' => [['key' => 'program', 'label' => 'Undergraduate / Prior Degree']],
        ],
    ][$scope]);
    $extra = $page['extra'];
@endphp

@section('title', 'iARIS — ' . $page['title'])
@section('page-title', $page['title'])
@section('page-subtitle', $page['subtitle'])

@php
    // Bootstrap colour for each status (used for badges, stat cards and pills)
    $statusTones = ['Pending' => 'danger', 'For Exam' => 'warning', 'Paid' => 'info', 'Enrolled' => 'primary'];

    // Add display-ready fields to each placeholder applicant
    $rows = collect($applicants)->map(fn ($a) => $a + [
        'name' => "{$a['last']}, {$a['first']} {$a['mi']}.",
        // A controller can send its own 'group' / 'unit_label' (Law does); ?? skips the rest then.
        // IS Records groups by sub-level (Preschool, Grade School, ...); the Applicants IS tab by grade
        'group' => $a['group'] ?? ($a['unit'] === 'college' ? $a['college'] : ($a['sub_level'] ?? $a['level'])),
        'unit_label' => $a['unit_label'] ?? ($a['unit'] === 'college' ? $a['college'] : implode(' · ', array_filter(['Integrated School', $a['sub_level'] ?? null, $a['level']]))),
        'applied_label' => \Illuminate\Support\Carbon::parse($a['applied'])->format('M j, Y'),
        'updated_label' => \Illuminate\Support\Carbon::parse($a['updated'])->format('M j, g:i A'),
        'dob_label' => \Illuminate\Support\Carbon::parse($a['dob'])->format('F j, Y'),
        'paid_label' => $a['paid'] ? \Illuminate\Support\Carbon::parse($a['paid'])->format('M j, Y') : null,
        'amount_label' => $a['amount'] ? '₱' . number_format($a['amount'], 2) : null,
        'tone' => $statusTones[$a['status']],
        'status_label' => $page['statusLabels'][$a['status']] ?? $a['status'],
        'program_label' => $a['program'],
    ]);

    $stats = [
        ['label' => $page['total'], 'value' => $rows->count(), 'sub' => $page['totalSub'], 'tone' => 'primary'],
    ];
    // Then one card per status this page uses
    $statusSubs = ['Pending' => 'Awaiting action', 'For Exam' => 'Scheduled / waiting', 'Paid' => 'Reservation confirmed', 'Enrolled' => 'Active enrollees'];
    foreach ($page['statuses'] as $status) {
        $stats[] = [
            'label' => $page['statusLabels'][$status] ?? $status,
            'value' => $rows->where('status', $status)->count(),
            'sub' => $statusSubs[$status],
            'tone' => $statusTones[$status],
        ];
    }
@endphp

@section('content')
    {{-- Stat cards --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-{{ count($stats) }} g-3 mb-4">
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
        {{-- Tabs (the Records pages only have one unit, so no tabs there) --}}
        @if ($scope === 'all')
        <div class="card-header bg-white border-bottom rounded-top-4 px-4 pt-3 pb-0">
            <nav class="nav nav-underline" role="tablist">
                <button type="button" class="nav-link active fw-bold d-flex align-items-center gap-2 pb-3" data-unit="college" role="tab" aria-selected="true">
                    <i class="bi bi-mortarboard"></i> College
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $rows->where('unit', 'college')->count() }}</span>
                </button>
                <button type="button" class="nav-link fw-bold d-flex align-items-center gap-2 pb-3" data-unit="is" role="tab" aria-selected="false">
                    <i class="bi bi-building"></i> Integrated School
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $rows->where('unit', 'is')->count() }}</span>
                </button>
            </nav>
        </div>
        @endif

        {{-- Search, filters, sort --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search by name or app number…" aria-label="Search applicants">
            </div>
            <select class="form-select w-auto" id="groupFilter" aria-label="Filter by college or level"></select>
            <select class="form-select w-auto" id="programFilter" aria-label="Filter by program or strand"></select>
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="newest">Sort: Newest First</option>
                <option value="oldest">Sort: Oldest First</option>
                <option value="az">Sort: Name A–Z</option>
                <option value="za">Sort: Name Z–A</option>
            </select>
            <button type="button" class="btn btn-light border fw-semibold" id="exportButton" title="Download the filtered list as CSV">
                <i class="bi bi-download me-1"></i> Export
            </button>
            {{-- TODO: needs an "add applicant" form + backend. Disabled for now. --}}
            <button type="button" class="btn btn-primary fw-semibold ms-lg-auto" disabled title="Coming soon">
                <i class="bi bi-plus-lg me-1"></i> Add Applicant
            </button>
        </div>

        {{-- Status pills --}}
        <div class="d-flex flex-wrap gap-2 px-4 py-3 border-bottom" id="statusPills">
            @foreach (['All', ...$page['statuses']] as $status)
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold {{ $loop->first ? 'btn-primary' : 'btn-outline-secondary' }}" data-status="{{ $status }}">{{ $page['statusLabels'][$status] ?? $status }}</button>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    {{-- Headings may wrap onto two lines so long ones ("Undergraduate Degree") don't widen the table --}}
                    <tr class="small text-uppercase">
                        <th class="ps-4" style="width: 1%;"><input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all on this page"></th>
                        <th class="text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold" id="groupHeading">College</th>
                        @if ($extra && $extra['first'])
                            <th class="text-body-secondary fw-bold">{{ $extra['label'] }}</th>
                        @endif
                        <th class="text-body-secondary fw-bold" id="programHeading">Program</th>
                        @if ($extra && ! $extra['first'])
                            <th class="text-body-secondary fw-bold">{{ $extra['label'] }}</th>
                        @endif
                        <th class="text-body-secondary fw-bold">Status</th>
                        <th class="text-body-secondary fw-bold">Date Applied</th>
                        <th class="text-body-secondary fw-bold">Updated</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="applicantRows">
                    @foreach ($rows as $i => $a)
                        <tr class="applicant-row" style="cursor: pointer;" tabindex="0"
                            data-index="{{ $i }}" data-unit="{{ $a['unit'] }}" data-status="{{ $a['status'] }}"
                            data-group="{{ $a['group'] }}" data-program="{{ $a['program'] }}"
                            data-search="{{ strtolower($a['name'] . ' ' . $a['id']) }}"
                            data-applied="{{ $a['applied'] }}" data-name="{{ $a['name'] }}">
                            <td class="ps-4"><input type="checkbox" class="form-check-input row-check" aria-label="Select {{ $a['name'] }}"></td>
                            <td>
                                <div class="fw-semibold text-nowrap">{{ $a['name'] }}</div>
                                <div class="small text-body-secondary">{{ $a['id'] }}</div>
                            </td>
                            <td>
                                @if ($page['groupBadge'])
                                    <span class="badge bg-dark-subtle text-dark-emphasis">{{ $a['group'] }}</span>
                                @else
                                    {{ $a['group'] }}
                                @endif
                            </td>
                            @if ($extra && $extra['first'])
                                <td>{{ $a[$extra['key']] }}</td>
                            @endif
                            <td>{{ $a['program'] }}</td>
                            @if ($extra && ! $extra['first'])
                                <td>{{ $a[$extra['key']] }}</td>
                            @endif
                            <td><span class="badge rounded-pill text-wrap bg-{{ $a['tone'] }}-subtle text-{{ $a['tone'] }}-emphasis">{{ $a['status_label'] }}</span></td>
                            <td class="small text-body-secondary text-nowrap">{{ $a['applied_label'] }}</td>
                            <td class="small text-body-secondary text-nowrap">{{ $a['updated_label'] }}</td>
                            <td class="pe-4 text-end"><span class="btn btn-sm btn-light border"><i class="bi bi-chevron-right"></i></span></td>
                        </tr>
                    @endforeach
                    <tr id="noResults" class="d-none">
                        <td colspan="{{ $extra ? 9 : 8 }}" class="text-center text-body-secondary py-5">
                            <i class="bi bi-search fs-3 d-block mb-2"></i>
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

    {{-- Profile drawer (Bootstrap offcanvas) --}}
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

            {{-- Application --}}
            <div class="tab-pane fade" id="tabApplication" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Program Details</div>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">College / Unit</div><div class="fw-semibold" data-field="unit_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Academic Year</div><div class="fw-semibold">{{ $academicYear }}</div></div>
                    @if ($extra)
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">{{ $extra['label'] }}</div><div class="fw-semibold" data-field="{{ $extra['key'] }}"></div></div>
                    @endif
                    <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">{{ $page['programField'] }}</div><div class="fw-semibold" data-field="program_label"></div></div>
                    @foreach ($page['drawerExtras'] as $field)
                        <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">{{ $field['label'] }}</div><div class="fw-semibold" data-field="{{ $field['key'] }}"></div></div>
                    @endforeach
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date Applied</div><div class="fw-semibold" data-field="applied_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">{{ $page['examField'] }}</div><div class="fw-semibold" data-field="exam"></div></div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Payment &amp; Status</div>
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
        const APPLICANTS = @json($rows->values());
        const PAGE_SIZE = 10;
        const STATUS_ORDER = ['Pending', 'For Exam', 'Paid', 'Enrolled'];

        const SCOPE = @json($scope);
        const PAGE = @json($page);
        // Sub-levels go in school order, not A–Z
        const GROUP_ORDER = @json($groupOrder ?? []);
        const state = { unit: { is: 'is', law: 'law' }[SCOPE] ?? 'college', status: 'All', group: '', program: '', search: '', sort: 'newest', page: 1 };
        let currentMatches = [];   // every row that passes the filters (all pages), used by Export

        const tbody = document.getElementById('applicantRows');
        const rows = [...tbody.querySelectorAll('.applicant-row')];
        const groupFilter = document.getElementById('groupFilter');
        const programFilter = document.getElementById('programFilter');

        // ---- Filtering, sorting and paging (all in the browser, on the placeholder data) ----

        // '—' (nothing to filter by) is left out of the options
        function fillSelect(select, allLabel, values, order = []) {
            const byOrder = (a, b) => order.indexOf(a) - order.indexOf(b);
            const byName = (a, b) => a.localeCompare(b, undefined, { numeric: true });
            const options = [...new Set(values)].filter(v => v !== '—').sort(order.length ? byOrder : byName);
            select.replaceChildren(new Option(allLabel, ''), ...options.map(v => new Option(v, v)));
        }

        // Headings and filter text: fixed on the Records pages, by tab on Applicants
        function currentLabels() {
            if (PAGE.labels) return PAGE.labels;
            return state.unit === 'college'
                ? { group: 'College', groupAll: 'All Colleges', program: 'Program', programAll: 'All Programs' }
                : { group: 'Level', groupAll: 'All Levels', program: 'Strand / Track', programAll: 'All Strands / Tracks' };
        }

        function setUpFiltersForTab() {
            const inTab = rows.filter(r => r.dataset.unit === state.unit);
            const labels = currentLabels();
            fillSelect(groupFilter, labels.groupAll, inTab.map(r => r.dataset.group), GROUP_ORDER);
            if (labels.programAll) fillSelect(programFilter, labels.programAll, inTab.map(r => r.dataset.program));
            programFilter.classList.toggle('d-none', !labels.programAll);
            document.getElementById('groupHeading').textContent = labels.group;
            document.getElementById('programHeading').textContent = labels.program;
            state.group = state.program = '';
        }

        function render() {
            const matches = rows.filter(r =>
                r.dataset.unit === state.unit &&
                (state.status === 'All' || r.dataset.status === state.status) &&
                (!state.group || r.dataset.group === state.group) &&
                (!state.program || r.dataset.program === state.program) &&
                r.dataset.search.includes(state.search)
            );

            const compare = {
                newest: (a, b) => b.dataset.applied.localeCompare(a.dataset.applied),
                oldest: (a, b) => a.dataset.applied.localeCompare(b.dataset.applied),
                az: (a, b) => a.dataset.name.localeCompare(b.dataset.name),
                za: (a, b) => b.dataset.name.localeCompare(a.dataset.name),
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
                ? `Showing ${start + 1}–${start + pageRows.length} of ${matches.length} ${PAGE.noun}${matches.length === 1 ? '' : 's'}`
                : '';
            renderPagination(pages);

            document.getElementById('selectAll').checked = false;
            rows.forEach(r => { r.querySelector('.row-check').checked = false; });
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

        // Tabs
        document.querySelectorAll('[data-unit]').forEach(tab => tab.addEventListener('click', () => {
            if (!tab.matches('button')) return;
            document.querySelectorAll('button[data-unit]').forEach(t => {
                t.classList.toggle('active', t === tab);
                t.setAttribute('aria-selected', t === tab);
            });
            state.unit = tab.dataset.unit;
            state.page = 1;
            setUpFiltersForTab();
            render();
        }));

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

        // Search, filters, sort
        document.getElementById('search').addEventListener('input', e => { state.search = e.target.value.trim().toLowerCase(); state.page = 1; render(); });
        groupFilter.addEventListener('change', e => { state.group = e.target.value; state.page = 1; render(); });
        programFilter.addEventListener('change', e => { state.program = e.target.value; state.page = 1; render(); });
        document.getElementById('sort').addEventListener('change', e => { state.sort = e.target.value; render(); });

        // Checkboxes (select all = the rows visible on this page)
        document.getElementById('selectAll').addEventListener('change', e => {
            rows.filter(r => !r.classList.contains('d-none')).forEach(r => { r.querySelector('.row-check').checked = e.target.checked; });
        });

        // ---- Export: the filtered rows (all pages, not just this one) as a CSV file ----
        document.getElementById('exportButton').addEventListener('click', () => {
            const labels = currentLabels();
            // Same columns, in the same order, as the table
            const extra = PAGE.extra;
            const header = ['App No.', 'Name', labels.group];
            if (extra && extra.first) header.push(extra.label);
            header.push(labels.program);
            if (extra && !extra.first) header.push(extra.label);
            header.push('Status', 'Date Applied', 'Updated');

            const lines = currentMatches.map(row => {
                const a = APPLICANTS[row.dataset.index];
                const cols = [a.id, a.name, a.group];
                if (extra && extra.first) cols.push(a[extra.key]);
                cols.push(a.program);
                if (extra && !extra.first) cols.push(a[extra.key]);
                cols.push(a.status_label, a.applied_label, a.updated_label);
                return cols;
            });
            // Wrap every value in quotes (and double any quotes inside) so commas in names don't break columns
            const csv = [header, ...lines].map(cols => cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            // \uFEFF at the start tells Excel the file is UTF-8 (for ñ, – and ₱)
            link.href = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv' }));
            link.download = SCOPE === 'all' ? `applicants-${state.unit}.csv` : `${SCOPE}-records.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- Profile drawer ----

        const drawerEl = document.getElementById('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

        function openDrawer(a) {
            document.getElementById('drawerInitials').textContent = (a.first[0] + a.last[0]).toUpperCase();
            document.getElementById('drawerName').textContent = a.name;
            document.getElementById('drawerSub').textContent = `${a.id} · ${a.program_label}`;
            const badge = document.getElementById('drawerBadge');
            badge.textContent = a.status_label;
            badge.className = `badge rounded-pill bg-${a.tone}-subtle text-${a.tone}-emphasis`;

            // Every element with data-field="x" shows a[x] ("—" if empty)
            drawerEl.querySelectorAll('[data-field]').forEach(el => {
                const value = a[el.dataset.field];
                el.textContent = value ?? (el.dataset.field === 'exam' ? 'Not yet scheduled' : '—');
            });
            document.getElementById('drawerPayStatus').textContent = a.paid ? 'Confirmed' : 'Pending';

            renderTimeline(a);
            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#tabInfo"]')).show();
            drawer.show();
        }

        // Steps up to the applicant's current status are "done"; later ones are greyed out
        function renderTimeline(a) {
            const reached = STATUS_ORDER.indexOf(a.status);
            const steps = [
                { title: 'Application Submitted', date: a.applied_label, icon: 'bi-file-earmark-plus', tone: 'secondary', done: true },
                { title: 'Entrance Exam Scheduled', date: a.exam ?? 'Not yet scheduled', icon: 'bi-calendar-check', tone: 'warning', done: reached >= 1, note: a.exam ? 'Venue: DLSL Main Campus — Testing Center' : null },
                { title: 'Reservation Payment', date: a.paid ? `${a.paid_label} · ${a.amount_label}` : 'Not yet paid', icon: 'bi-cash-coin', tone: 'info', done: reached >= 2 },
                { title: 'Enrolled', date: reached >= 3 ? a.updated_label : 'Not yet enrolled', icon: 'bi-check-circle', tone: 'primary', done: reached >= 3 },
            ];
            // Law applicants have their documents checked first, an exam or interview, and "admission confirmed" instead of enrolled
            if (SCOPE === 'law') {
                steps.splice(1, 0, { title: 'Documents Verified', date: reached >= 1 ? 'Transcript, IDs and credentials reviewed' : 'Not yet verified', icon: 'bi-file-earmark-check', tone: 'dark', done: reached >= 1 });
                steps[2].title = 'Entrance Exam / Interview Scheduled';
                steps[4] = { ...steps[4], title: 'Admission Confirmed', date: reached >= 3 ? a.updated_label : 'Pending' };
            }
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
                if (step.note && step.done) {
                    const note = document.createElement('div');
                    note.className = 'small text-body-secondary bg-body-tertiary rounded-2 px-2 py-1 mt-1';
                    note.textContent = step.note;
                    li.lastElementChild.appendChild(note);
                }
                list.appendChild(li);
            });
        }

        rows.forEach(row => {
            const open = () => openDrawer(APPLICANTS[row.dataset.index]);
            row.addEventListener('click', e => { if (!e.target.closest('.row-check')) open(); });
            row.addEventListener('keydown', e => { if (e.key === 'Enter') open(); });
        });

        setUpFiltersForTab();
        render();
    </script>
@endsection
