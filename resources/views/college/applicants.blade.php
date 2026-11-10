@extends('layouts.app')

{{--
    Applicants for a Dean, Program Chair or the IS Principal (CollegeController::applicants). View only, as in the mockup
    ($word is "program", or "level" for the Integrated School):
      - four tiles: every applicant this AY by status (the programs' totals, same as the Briefing)
      - a table of sample records with search, a program filter and status pills
      - click a row for the applicant's details
    ?program=BSCS picks a program first (the Briefing's "See applicants").
    Graduate Programs: programs have a 'level' (Master's or Doctoral: a Level column and filter), applicants have an
    'undergrad' degree (a column), and "For Exam" is shown as $middle ("For Exam / Interview").
    "Download list" saves the rows you see as a CSV file.
    Looks like the admin Records pages (partials/admin-workspace): summary cards, a toolbar, status pills with counts,
    a Scholarship column, and 10 applicants a page.
--}}

@section('title', 'iARIS — Applicants')
@section('page-title', 'Applicants')
@section('page-subtitle', "{$label} applicants for AY " . \App\Support\ApplicantRecords::academicYear() . ' · View only')

@php
    // "Principal · Integrated School", "Dean · CITE": the office in the breadcrumb
    $office = auth()->user()->roleInfo();
    $hasLevels = collect($programs)->contains(fn ($p) => isset($p['level']));
    $hasDegrees = collect($rows)->contains(fn ($r) => isset($r['undergrad']));
    $hasBar = collect($rows)->contains(fn ($r) => isset($r['bar']));
@endphp

@include('partials.admin-workspace', ['workspacePage' => 'records-page college-records', 'workspaceGroup' => $office[0] . ' · ' . $office[1], 'workspaceLabel' => 'Applicants'])

@push('styles')
    <link href="{{ asset('css/records.css') }}" rel="stylesheet">
@endpush

@section('content')
    {{-- Every applicant this AY, by status --}}
    <div class="admin-stat-grid row row-cols-2 row-cols-xl-4 g-3 mb-4" id="tiles"></div>

    <div class="records-table-card card border-0 shadow-sm rounded-4">
        {{-- Search, program, Download list --}}
        <div class="d-flex flex-wrap align-items-center gap-2 p-3">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-body-tertiary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control bg-body-tertiary" id="search" placeholder="Search name or applicant number…" aria-label="Search">
            </div>
            @if (count($programs) > 1)
                <select class="form-select w-auto" id="programFilter" aria-label="{{ ucfirst($word) }}">
                    <option value="">All {{ $word }}s</option>
                    @foreach ($programs as $code => $program)
                        <option value="{{ $code }}" @selected($selected === $code)>{{ $program['name'] }}</option>
                    @endforeach
                </select>
            @endif
            @if ($hasLevels)
                <select class="form-select w-auto" id="levelFilter" aria-label="Level">
                    <option value="">All levels</option>
                    @foreach (collect($programs)->pluck('level')->unique() as $level)
                        <option>{{ $level }}</option>
                    @endforeach
                </select>
            @endif
            <button type="button" class="btn btn-light border fw-semibold ms-auto" id="downloadButton"><i class="bi bi-download me-1"></i> Download list</button>
        </div>

        {{-- Status, with how many in each --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-top border-bottom" id="statusBar">
            <span class="small fw-bold text-body-secondary me-1">Status:</span>
            <div class="d-flex flex-wrap gap-2" id="statusPills" role="group" aria-label="Status"></div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">{{ ucfirst($word) }}</th>
                        @if ($hasLevels)
                            <th class="text-body-secondary fw-bold">Level</th>
                        @endif
                        @if ($hasDegrees)
                            <th class="text-body-secondary fw-bold">Undergraduate degree</th>
                        @endif
                        @if ($hasBar)
                            <th class="text-body-secondary fw-bold">Bar exam passer</th>
                        @endif
                        <th class="text-body-secondary fw-bold">Scholarship</th>
                        <th class="text-body-secondary fw-bold">Status</th>
                        <th class="text-body-secondary fw-bold">Date applied</th>
                        <th class="text-body-secondary fw-bold">Exam</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top rounded-bottom-4 d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3">
            <span class="small text-body-secondary" id="footer"></span>
            <nav aria-label="Applicant pages"><ul class="pagination pagination-sm mb-0" id="pager"></ul></nav>
        </div>
    </div>
    <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Records are managed by IATO. If something looks wrong, tell IATO in <a href="{{ $base }}/messages" class="fw-semibold text-decoration-none">Messages</a>.</p>

    {{-- One applicant --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="applicantName" style="--bs-offcanvas-width: 540px;">
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
    <script src="{{ asset('js/pagination.js') }}"></script>
    <script>
        const PROGRAMS = @json($programs);
        const ROWS = @json($rows);
        // The unit: "CITE", "Integrated School" (a program chair's unit is their college)
        const COLLEGE = @json($isChair ? $college['code'] : $label);
        const WORD = @json($word);
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const longDate = d => new Date(d + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const STATUSES = ['Pending', 'For Exam', 'Paid'];
        // What "For Exam" is called here, and the extra columns (Graduate Programs)
        const MIDDLE = @json($middle), HAS_LEVELS = @json($hasLevels), HAS_DEGREES = @json($hasDegrees), HAS_BAR = @json($hasBar);
        const labelOf = s => s === 'For Exam' ? MIDDLE : s;
        const TONES = { Pending: 'danger', 'For Exam': 'warning', Paid: 'primary' };
        let status = 'All', page = 1;
        const PER_PAGE = 10;
        ROWS.sort((a, b) => b.applied.localeCompare(a.applied));

        // How many applicants (all of them, not only the samples) in the chosen programs
        const totals = code => {
            const codes = code ? [code] : Object.keys(PROGRAMS);
            const t = STATUSES.map((s, i) => codes.reduce((sum, c) => sum + PROGRAMS[c].status[i], 0));
            return { All: t[0] + t[1] + t[2], Pending: t[0], 'For Exam': t[1], Paid: t[2] };
        };
        const program = () => $('programFilter')?.value || '';

        // ---- Tiles (every applicant in your programs) ----
        (() => {
            const t = totals('');
            $('tiles').innerHTML = [['Applicants', t.All, 'primary'], ['Pending', t.Pending, 'danger'], [MIDDLE === 'For Exam' ? 'For Exam / Assessment' : MIDDLE, t['For Exam'], 'warning'], ['Paid', t.Paid, 'info']]
                .map(([label, n, tone]) => `
                <div class="col">
                    <div class="admin-stat-card card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100">
                        <div class="card-body">
                            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div>
                            <div class="fs-3 fw-bold text-${tone}-emphasis">${fm(n)}</div>
                            <div class="small text-body-secondary">${t.All ? (n / t.All * 100).toFixed(1) : '0.0'}% of applicants</div>
                        </div>
                    </div>
                </div>`).join('');
        })();

        // ---- Table ----
        // Every filter except the status pills (the pill counts follow them)
        function filtered() {
            const q = $('search').value.trim().toLowerCase(), p = program();
            const level = $('levelFilter')?.value || '';
            return ROWS.filter(r => (!p || r.program === p) && (!level || PROGRAMS[r.program].level === level)
                && (!q || r.name.toLowerCase().includes(q) || r.id.toLowerCase().includes(q)));
        }
        const shown = () => filtered().filter(r => status === 'All' || r.status === status);
        function draw() {
            const base = filtered();
            const count = s => s === 'All' ? base.length : base.filter(r => r.status === s).length;
            $('statusPills').innerHTML = ['All', ...STATUSES].map(s => `<button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold ${s === status ? 'btn-primary' : 'btn-outline-secondary'}" data-status="${s}" aria-pressed="${s === status}">${labelOf(s)} <span class="ms-1 opacity-75">${count(s)}</span></button>`).join('');
            $('statusPills').querySelectorAll('[data-status]').forEach(b => b.addEventListener('click', () => { status = b.dataset.status; page = 1; draw(); }));
            const all = shown(), pages = Math.max(1, Math.ceil(all.length / PER_PAGE));
            page = Math.min(page, pages);
            const rows = all.slice((page - 1) * PER_PAGE, page * PER_PAGE);
            $('rows').innerHTML = rows.length ? rows.map(r => `
                <tr role="button" tabindex="0" data-id="${r.id}">
                    <td class="ps-4"><div class="fw-semibold">${esc(r.name)}</div><div class="small text-body-secondary font-monospace">${r.id}</div></td>
                    <td><span class="badge border text-body fw-semibold bg-white">${esc(PROGRAMS[r.program].name)}</span></td>
                    ${HAS_LEVELS ? `<td class="small">${esc(PROGRAMS[r.program].level)}</td>` : ''}
                    ${HAS_DEGREES ? `<td class="small">${esc(r.undergrad || '—')}</td>` : ''}
                    ${HAS_BAR ? `<td class="small">${esc(r.bar || '—')}</td>` : ''}
                    <td>${r.scholar ? '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-award me-1"></i>Scholar</span>' : '<span class="text-body-tertiary">—</span>'}</td>
                    <td><span class="badge rounded-pill bg-${TONES[r.status]}-subtle text-${TONES[r.status]}-emphasis">${labelOf(r.status)}</span></td>
                    <td class="small text-nowrap text-body-secondary">${longDate(r.applied)}</td>
                    <td class="small">${esc(r.exam || 'Not yet scheduled')}</td>
                    <td class="pe-4 text-end text-body-tertiary"><i class="bi bi-chevron-right"></i></td>
                </tr>`).join('')
                : '<tr><td colspan="10" class="text-center text-body-secondary py-5">No applicants match these filters.</td></tr>';
            $('rows').querySelectorAll('tr[data-id]').forEach(tr => {
                tr.addEventListener('click', () => openApplicant(tr.dataset.id));
                tr.addEventListener('keydown', e => { if (e.key === 'Enter') openApplicant(tr.dataset.id); });
            });
            $('footer').textContent = all.length ? `Showing ${(page - 1) * PER_PAGE + 1}–${(page - 1) * PER_PAGE + rows.length} of ${fm(all.length)} applicants` : 'Showing 0 applicants';
            // Pager: previous, 1 2 3 …, next
            const item = (label, target, { active = false, disabled = false } = {}) => `<li class="page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${target}" ${disabled ? 'disabled' : ''} ${active ? 'aria-current="page"' : ''}>${label}</button></li>`;
            $('pager').innerHTML = pages > 1 ? item('<i class="bi bi-chevron-left"></i>', page - 1, { disabled: page === 1 })
                + IARIS_PAGINATION.pages(page, pages).map(p => p === null
                    ? '<li class="page-item disabled" aria-hidden="true"><span class="page-link">…</span></li>'
                    : item(p, p, { active: p === page })).join('')
                + item('<i class="bi bi-chevron-right"></i>', page + 1, { disabled: page === pages }) : '';
            $('pager').querySelectorAll('[data-page]').forEach(b => b.addEventListener('click', () => { page = +b.dataset.page; draw(); }));
        }

        // ---- One applicant ----
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('applicantDrawer'));
        function openApplicant(id) {
            const r = ROWS.find(a => a.id === id);
            const stat = (label, value) => `<div class="col"><div class="rounded-3 bg-body-tertiary p-2 h-100"><div class="small text-body-secondary">${label}</div><div class="fw-bold small">${esc(value)}</div></div></div>`;
            const section = title => `<div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-1 mt-4 mb-2">${title}</div>`;
            const table = rows => `<table class="table table-sm small mb-0"><tbody>${rows.map(([l, v]) => `<tr><td class="text-body-secondary" style="width: 42%;">${l}</td><td class="fw-semibold">${esc(v || '—')}</td></tr>`).join('')}</tbody></table>`;
            $('applicantName').textContent = r.name;
            $('applicantSub').textContent = `${r.id} · ${PROGRAMS[r.program].name}`;
            $('applicantBody').innerHTML = `<div class="row row-cols-2 row-cols-sm-4 g-2">${stat('Status', labelOf(r.status))}${stat('Applied', longDate(r.applied))}${stat('Paid on', r.paid || '—')}${stat('Payment', r.paid ? 'Paid' : 'Pending')}</div>`
                + section('Application') + table([['Unit', COLLEGE], [WORD[0].toUpperCase() + WORD.slice(1), PROGRAMS[r.program].name], ['Year / level', r.year || '1st Year'], ...(r.undergrad ? [['Undergraduate / graduate degree', r.undergrad]] : []), ...(r.bar ? [['Bar exam passer', r.bar]] : []), ['Entrance exam / interview', r.exam || 'Not yet scheduled']])
                + section('Applicant') + table([['Date of birth', r.dob], ['Gender', r.gender], ['Email', r.email], ['Contact', r.phone], ['Address', r.address]])
                + '<p class="small text-body-secondary mt-4 mb-0"><i class="bi bi-lock me-1"></i>View only. IATO manages applicant records.</p>';
            drawer.show();
        }

        // ---- Download the rows you see ----
        $('downloadButton').addEventListener('click', () => {
            const head = ['Applicant No.', 'Name', 'Unit', WORD[0].toUpperCase() + WORD.slice(1), 'Scholarship', 'Status', 'Date applied', 'Exam', 'Paid on'];
            const csv = [head, ...shown().map(r => [r.id, r.name, COLLEGE, PROGRAMS[r.program].name, r.scholar ? 'Scholar' : '—', labelOf(r.status), longDate(r.applied), r.exam || 'Not yet scheduled', r.paid || '—'])]
                .map(cols => cols.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = `${COLLEGE.toLowerCase().replace(/\s+/g, '-')}-applicants${program() ? '-' + program().toLowerCase() : ''}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        });

        const redraw = () => { page = 1; draw(); };
        $('search').addEventListener('input', redraw);
        $('programFilter')?.addEventListener('change', redraw);
        $('levelFilter')?.addEventListener('change', redraw);
        draw();
    </script>
@endsection
