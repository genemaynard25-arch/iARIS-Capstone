@extends('layouts.app')

{{--
    Applicants for the iPACE Director (CollegeController::applicants). View only, as in the two mockups:
    a switch between iPACE and ETEEAP (?unit=eteeap), the unit's tiles, filters and table, and a drawer per applicant.
      iPACE:  delivery mode, year level, working student and employer
      ETEEAP: age, highest education, work experience, portfolio and competency assessment ("For Assessment" instead of "For Exam")
--}}

@php
    $isEteeap = $unit === 'ETEEAP';
    $middle = $isEteeap ? 'For Assessment' : 'For Exam';
    $switch = [
        'iPACE' => [url('/director/applicants'), 'Online and Blended'],
        'ETEEAP' => [url('/director/applicants?unit=eteeap'), 'Equivalency by experience'],
    ];
@endphp

@section('title', 'iARIS — Applicants')
@section('page-title', 'Applicants')
@section('page-subtitle', $unit . ' applicants for AY ' . \App\Support\ApplicantRecords::academicYear() . ($isEteeap ? ', with education, work experience, and portfolio assessment' : ', with delivery mode and working-student details') . ' · View only')

@include('partials.director-workspace', ['directorPage' => 'applicants', 'directorLabel' => 'Applicants'])

@section('content')
    @unless ($isEteeap)
        <p class="admin-preview-note mb-3"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>iPACE preview records · Applicant imports are not connected for this unit yet.</p>
    @endunless

    {{-- iPACE or ETEEAP --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="director-unit-tabs card-body d-flex flex-wrap align-items-center gap-2 px-4 py-3">
            <span class="small fw-bold text-body-secondary me-1">Program type:</span>
            @foreach ($switch as $name => [$href, $note])
                <a href="{{ $href }}" class="btn {{ $unit === $name ? 'btn-primary' : 'btn-outline-secondary' }} text-start px-3 py-2" @if ($unit === $name) aria-current="page" @endif>
                    <span class="d-block fw-bold small">{{ $name }}</span>
                    <span class="d-block small {{ $unit === $name ? 'text-white-50' : 'text-body-secondary' }}">{{ number_format($unitTotals[$name]) }} applicants · {{ $note }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="admin-stat-grid row row-cols-2 row-cols-xl-4 g-3 mb-4" id="tiles"></div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom rounded-top-4 d-flex flex-wrap align-items-center gap-2 p-3">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-body-tertiary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control bg-body-tertiary" id="search" placeholder="Search name or applicant number…" aria-label="Search">
            </div>
            <select class="form-select w-auto" id="programFilter" aria-label="Program">
                <option value="">All programs</option>
                @foreach ($unitPrograms as $code => $program)
                    <option value="{{ $code }}" @selected($selected === $code)>{{ $program['name'] }}</option>
                @endforeach
            </select>
            @if ($isEteeap)
                <select class="form-select w-auto" id="extraFilter" aria-label="Assessment status">
                    <option value="">All assessment status</option>
                    <option value="pending">Portfolio pending</option>
                    <option value="submitted">Portfolio submitted</option>
                    <option value="cleared">Assessment cleared</option>
                </select>
            @else
                <select class="form-select w-auto" id="extraFilter" aria-label="Delivery mode">
                    <option value="">All delivery modes</option>
                    <option>Online</option>
                    <option>Blended</option>
                </select>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2 border-bottom" id="statusPills" role="group" aria-label="Exam result"></div>
        <div class="director-category-pills d-flex flex-wrap align-items-center gap-2 border-bottom" id="categoryPills" role="group" aria-label="{{ $isEteeap ? 'Program' : 'Delivery mode' }}">
            <span class="small fw-semibold text-body-secondary">{{ $isEteeap ? 'Program' : 'Delivery Mode' }}:</span>
            <button class="btn btn-sm btn-primary" type="button" data-category="" aria-pressed="true">All</button>
            @foreach ($isEteeap ? array_keys($unitPrograms) : ['Online', 'Blended'] as $category)
                <button class="btn btn-sm btn-light border" type="button" data-category="{{ $category }}" aria-pressed="false">{{ $isEteeap ? (explode('-', $category, 2)[1] ?? $category) : $category }}</button>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Program</th>
                        @if ($isEteeap)
                            <th class="text-body-secondary fw-bold">Age</th>
                            <th class="text-body-secondary fw-bold">Highest educ.</th>
                            <th class="text-body-secondary fw-bold">Work experience</th>
                            <th class="text-body-secondary fw-bold">Portfolio / assessment</th>
                        @else
                            <th class="text-body-secondary fw-bold">Delivery mode</th>
                            <th class="text-body-secondary fw-bold">Year level</th>
                            <th class="text-body-secondary fw-bold">Working student</th>
                        @endif
                        <th scope="col">Exam Fee</th><th scope="col">Exam Result</th><th scope="col">Reservation</th>
                        <th class="pe-4 text-body-secondary fw-bold">{{ $isEteeap ? 'Updated' : 'Date Applied' }}</th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top rounded-bottom-4 small text-body-secondary px-4 py-3" id="footer"></div>
    </div>
    <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Records are managed by IATO. If something looks wrong, tell IATO in <a href="{{ $base }}/messages" class="fw-semibold text-decoration-none">Messages</a>.</p>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="applicantName" style="--bs-offcanvas-width: 560px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start p-4">
            <div>
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="applicantName"></h2>
                <div class="small text-white-50" id="applicantSub"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4" id="applicantBody"></div>
    </div>
@endsection

@section('scripts')
    <script>
        const UNIT = @json($unit), ETEEAP = UNIT === 'ETEEAP';
        const PROGRAMS = @json($unitPrograms);
        const ROWS = @json($rows);
        const STATUSES = ['Pending', @json($middle), 'Paid'];
        const TONES = { Pending: 'danger', 'For Exam': 'warning', 'For Assessment': 'warning', Passed: 'primary', 'Not Qualified': 'secondary', Paid: 'primary' };
        const ASSESS = { pending: ['Portfolio pending', 'warning'], submitted: ['Submitted', 'info'], cleared: ['Cleared', 'success'] };
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const longDate = d => new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const pill = (label, tone) => `<span class="badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis">${esc(label)}</span>`;
        // Short program tag: "BS Information Technology" -> BSIT
        const tag = code => code.split('-')[1];
        let status = 'All';
        ROWS.sort((a, b) => b.applied.localeCompare(a.applied));

        const RESULTS = {
            not_taken: ['Not yet taken', 'secondary'], passed: ['Pass', 'primary'], failed: ['Fail', 'danger'],
            waitlisted: ['Waitlisted', 'warning'], reconsidered: ['Reconsidered', 'info'],
            took_test: ['Awaiting result', 'warning'], qualified_other_degree: ['Qualified (other program)', 'info'], unknown: ['Not recorded', 'secondary'],
        };
        const examResult = r => r.exam_status || 'unknown';
        const reserved = r => !!(r.paid || r.status === 'Paid');
        const workYears = r => r.workYears == null ? '—' : r.workYears + ' years';
        $('tiles').innerHTML = [
            ['Total applicants', ROWS.length, 'All programs combined', 'primary'],
            ['Exam fee paid', ROWS.filter(r => r.exam_paid === true).length, 'Updates after online payment', 'primary'],
            ['Passed', ROWS.filter(r => ['passed', 'reconsidered', 'qualified_other_degree'].includes(examResult(r))).length, 'Pass or Reconsidered', 'purple'],
            ['Reservation paid', ROWS.filter(reserved).length, 'Ready for enrollment', 'info'],
        ].map(([label, n, note, tone]) => `<div class="col"><div class="admin-stat-card card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100"><div class="card-body">
            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div><div class="fs-3 fw-bold text-${tone}-emphasis">${fm(n)}</div><div class="small text-body-secondary">${note}</div></div></div></div>`).join('');

        function shown() {
            const q = $('search').value.trim().toLowerCase(), p = $('programFilter').value, extra = $('extraFilter').value;
            return ROWS.filter(r => (status === 'All' || examResult(r) === status) && (!p || r.program === p)
                && (!extra || (ETEEAP ? r.assess === extra : r.mode === extra))
                && (!q || r.name.toLowerCase().includes(q) || r.id.toLowerCase().includes(q)));
        }
        function draw() {
            $('statusPills').innerHTML = ['All', ...Object.keys(RESULTS).filter(key => !['took_test', 'qualified_other_degree', 'unknown'].includes(key) || ROWS.some(r => examResult(r) === key))].map(s => `<button type="button" class="btn btn-sm fw-semibold ${s === status ? 'btn-primary' : 'btn-outline-secondary'}" data-status="${s}" aria-pressed="${s === status}">${s === 'All' ? 'All' : RESULTS[s][0]}</button>`).join('');
            $('categoryPills').querySelectorAll('[data-category]').forEach(button => {
                const active = button.dataset.category === $(ETEEAP ? 'programFilter' : 'extraFilter').value;
                button.classList.toggle('btn-primary', active); button.classList.toggle('btn-light', !active); button.setAttribute('aria-pressed', active);
            });
            $('statusPills').querySelectorAll('[data-status]').forEach(b => b.addEventListener('click', () => { status = b.dataset.status; draw(); }));
            const rows = shown();
            $('rows').innerHTML = rows.length ? rows.map(r => `
                <tr role="button" tabindex="0" data-id="${r.id}">
                    <td class="ps-4"><div class="fw-semibold">${esc(r.name)}</div><div class="small text-body-secondary">${r.id}</div></td>
                    <td><span class="badge rounded-pill bg-body-tertiary text-body border" title="${esc(PROGRAMS[r.program].name)}">${tag(r.program)}</span></td>
                    ${ETEEAP
                        ? `<td class="small">${esc(r.age ?? '—')}</td><td class="small">${esc(r.education ?? '—')}</td><td class="small text-nowrap">${workYears(r)}</td><td>${pill(...ASSESS[r.assess])}</td>`
                        : `<td>${pill(r.mode, r.mode === 'Online' ? 'info' : 'secondary')}</td><td class="small">${esc(r.year)}</td><td class="small">${esc(r.working)}</td>`}
                    <td>${pill(r.exam_paid == null ? 'Not recorded' : r.exam_paid ? 'Exam fee paid' : 'Exam fee not paid', r.exam_paid == null ? 'secondary' : r.exam_paid ? 'primary' : 'danger')}</td>
                    <td>${pill(...(RESULTS[examResult(r)] || RESULTS.unknown))}</td>
                    <td>${pill(reserved(r) ? 'Reservation paid' : 'Not yet paid', reserved(r) ? 'info' : 'secondary')}</td>
                    <td class="pe-4 small text-body-secondary text-nowrap">${esc(ETEEAP ? r.updated : longDate(r.applied))}</td>
                </tr>`).join('')
                : `<tr><td colspan="${ETEEAP ? 10 : 9}" class="text-center text-body-secondary py-5">No applicants match these filters.</td></tr>`;
            $('rows').querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => openApplicant(tr.dataset.id));
                tr.addEventListener('keydown', e => { if (e.key === 'Enter') openApplicant(tr.dataset.id); });
            });
            $('footer').textContent = `Showing ${rows.length} of ${ROWS.length} applicant${ROWS.length === 1 ? '' : 's'}${ETEEAP ? '' : ' · Preview records'}`;
        }

        // ---- One applicant ----
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('applicantDrawer'));
        function openApplicant(id) {
            const r = ROWS.find(a => a.id === id);
            const stat = (label, value) => `<div class="col"><div class="rounded-3 bg-body-tertiary p-2 h-100"><div class="small text-body-secondary">${label}</div><div class="fw-bold small">${esc(value)}</div></div></div>`;
            const section = title => `<div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-1 mt-4 mb-2">${title}</div>`;
            const table = rows => `<table class="table table-sm small mb-0"><tbody>${rows.map(([l, v]) => `<tr><td class="text-body-secondary" style="width: 42%;">${l}</td><td class="fw-semibold">${esc(v || '—')}</td></tr>`).join('')}</tbody></table>`;
            $('applicantName').textContent = r.name;
            $('applicantSub').textContent = `${r.id} · ${PROGRAMS[r.program].name} · ${UNIT}`;
            let html = `<div class="row row-cols-2 row-cols-sm-4 g-2">${stat('Status', r.status)}${stat('Applied', longDate(r.applied))}${stat('Paid on', r.paid || '—')}${stat('Payment', r.paid ? 'Paid' : 'Pending')}</div>`
                + section('Personal information') + table([['Date of birth', r.dob], ...(ETEEAP ? [['Age', r.age + ' years old']] : []), ['Gender', r.gender], ['Email', r.email], ['Contact', r.phone], ['Address', r.address]]);
            if (ETEEAP) {
                html += section('Educational background') + table([['Highest educational attainment', r.education]])
                    + section('Employment history') + `<div class="vstack gap-2">${r.work.map(([title, company, period, years]) => `<div class="rounded-3 bg-body-tertiary p-2 small"><div class="fw-semibold">${esc(title)} · ${esc(company)}</div><div class="text-body-secondary">${esc(period)} · ${esc(years)}</div></div>`).join('')}</div>`
                    + `<div class="small mt-2">Total work experience: <b>${workYears(r)}</b> · Current: ${esc(r.employer || '—')}</div>`
                    + section('Portfolio and competency assessment') + table([['Portfolio', r.portfolio], ['Assessment', ASSESS[r.assess][0]]]);
            } else {
                html += section('Employment') + table([['Working student', r.working], ['Employer / position', r.employer]])
                    + section('Program') + table([['Program', PROGRAMS[r.program].name], ['Year level', r.year], ['Delivery mode', r.mode], ['Entrance exam', r.exam || 'Not yet scheduled']]);
            }
            html += '<p class="small text-body-secondary mt-4 mb-0"><i class="bi bi-lock me-1"></i>View only. IATO manages applicant records.</p>';
            $('applicantBody').innerHTML = html;
            drawer.show();
        }

        $('categoryPills').addEventListener('click', event => {
            const button = event.target.closest('[data-category]'); if (!button) return;
            $(ETEEAP ? 'programFilter' : 'extraFilter').value = button.dataset.category; draw();
        });

        $('search').addEventListener('input', draw);
        $('programFilter').addEventListener('change', draw);
        $('extraFilter').addEventListener('change', draw);
        draw();
    </script>
@endsection
