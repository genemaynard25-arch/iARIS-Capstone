@extends('layouts.app')

{{--
    Reports for a Dean, Program Chair or the IS Principal (CollegeController::reports). As in the mockup, one page:
      - From IATO: open one to read or download it; questions go to Messages ("Ask IATO")
      - Generate a report: four ready-made reports from the same numbers as Analytics
      - Your reports: the ones you generated, each with Download (PDF, Excel or CSV)
    Looks like the admin Reports tab (public/css/analytics.css): section headings with a line under them, report cards
    with a coloured top edge and Preview / Generate, and a generation history table.
    FRONT END ONLY: reading a report and generating one stay until reload.
--}}

@php
    // key => [title, icon, colour, description]
    $templates = [
        'programs' => ["Applicants by {$word}", 'bi-layers', 'primary', "Pending, For Exam, and Paid for every {$word}."],
        'paid' => ['Paid reservations', 'bi-clipboard-check', 'info', 'Applicants who paid, ready for enrollment.'],
        'trend' => ['Four-year trend', 'bi-graph-up', 'danger', "Applicants, enrolled, and conversion since {$years[0]}."],
        'funnel' => ['Conversion funnel', 'bi-funnel', 'warning', 'Where applicants drop off this AY.'],
    ];
    if ($isChair) {
        $templates['programs'][3] = 'Pending, For Exam, and Paid for your program.';
    }
@endphp

@section('title', 'iARIS — Reports')
@section('page-title', 'Reports')
@section('page-subtitle', "Reports IATO sends you, and reports you generate for {$label}")
@unless ((auth()->user()->isDirector() || auth()->user()->isGradChair()))
@section('body-class', 'analytics-page')
@endunless
@push('styles')
    <link href="{{ asset('css/analytics.css') }}" rel="stylesheet">
@endpush

@if ((auth()->user()->isDirector() || auth()->user()->isGradChair()))
    @include('partials.director-workspace', ['directorPage' => 'reports', 'directorLabel' => 'Reports'])
@endif

@section('content')
    {{-- From IATO --}}
    <div class="{{ (auth()->user()->isDirector() || auth()->user()->isGradChair()) ? 'd-flex flex-wrap align-items-baseline gap-2 mb-2 director-report-heading' : 'ar-report-section mb-3' }}">
        <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-inbox text-primary me-1"></i> From IATO</h2>
        <span class="small text-body-secondary">Open a report to read or download it. Questions go to Messages.</span>
    </div>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="list-group list-group-flush rounded-4" id="received"></div>
    </div>

    {{-- Generate a report --}}
    <div class="ar-report-section mb-3">
        <h2 class="fs-6 fw-bold mb-0">Generate a report</h2>
        <p class="text-body-secondary mb-0">Uses iARIS data for {{ $label }} only, computed from your imported applicants.</p>
    </div>
    <div class="ar-report-grid row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($templates as $key => [$title, $icon, $tone, $text])
            <div class="col">
                <div class="ar-report-card card border-0 border-top border-3 border-{{ $tone === 'primary' ? 'primary' : $tone }} shadow-sm rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <span class="icon-circle rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis d-flex align-items-center justify-content-center"><i class="bi {{ $icon }}"></i></span>
                        <h3 class="fw-bold">{{ $title }}</h3>
                        <p class="small text-body-secondary flex-grow-1">{{ $text }}</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light fw-semibold flex-fill" data-preview="{{ $key }}"><i class="bi bi-eye me-1"></i> Preview</button>
                            <button type="button" class="btn btn-primary fw-semibold flex-fill" data-template="{{ $key }}"><i class="bi bi-lightning-charge me-1"></i> Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Your reports --}}
    <div class="ar-report-history card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
            <div><h2 class="fs-6 fw-bold mb-0">Report Generation History</h2><p class="text-body-secondary mb-0">The reports you generated, kept for 90 days</p></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Report name</th>
                        <th class="text-body-secondary fw-bold">Period</th>
                        <th class="text-body-secondary fw-bold">Generated</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="history"></tbody>
            </table>
        </div>
    </div>

    {{-- One report from IATO --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="reportDrawer" aria-labelledby="reportTitle" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start p-4">
            <div>
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="reportTitle"></h2>
                <div class="small text-white-50" id="reportSub"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-1 mb-2">Report</div>
            <p class="small rounded-3 bg-body-tertiary p-3 mb-3" id="reportMessage"></p>
            {{-- Buttons on the left, so the floating AI button doesn't cover them --}}
            <div class="mb-4" id="reportDownload"></div>
            <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-1 mb-2">Question about this report?</div>
            <p class="small text-body-secondary">Ask IATO in Messages. The report's name is filled in for you.</p>
            <a class="btn btn-primary fw-semibold" id="reportAsk" href="#"><i class="bi bi-chat-dots me-1"></i> Ask IATO</a>
        </div>
    </div>

    {{-- Preview of a report you can generate --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="previewDrawer" aria-labelledby="previewTitle" style="--bs-offcanvas-width: 600px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start p-4">
            <div>
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="previewTitle"></h2>
                <div class="small text-white-50" id="previewSub"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="table-responsive border rounded-3 mb-3"><table class="table table-sm align-middle mb-0 small" id="previewTable"></table></div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary fw-semibold" id="previewGenerate"><i class="bi bi-lightning-charge me-1"></i> Generate</button>
                <span id="previewDownload"></span>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="reportToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="reportToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const PROGRAMS = @json($programs);
        const YEARS = @json($years);
        const LABEL = @json($label);
        const RECEIVED = @json($received);
        const TEMPLATES = @json($templates);
        // [applicant no., name, program, paid on]
        const PAID_ROWS = @json($paidRows);
        // {template, period, at, isNew}
        const HISTORY = @json($history);
        const MESSAGES = @json("{$base}/messages");
        const WORD = @json($word);
        const CODES = Object.keys(PROGRAMS);
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const toast = text => { $('reportToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('reportToast')).show(); };
        const now = () => { const d = new Date(); return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`; };

        // ---- Build a report's table: {title, sub, head, rows} ----
        function build(kind, title) {
            const sum = key => [0, 1, 2, 3].map(i => CODES.reduce((s, c) => s + PROGRAMS[c][key][i], 0));
            const stage = i => CODES.reduce((s, c) => s + PROGRAMS[c].funnel[i], 0);
            const sub = `${LABEL} · ${YEARS[3]} · Generated ${now()}`;
            if (kind === 'paid') {
                return { title, sub, head: ['Applicant no.', 'Name', WORD[0].toUpperCase() + WORD.slice(1), 'Paid on'], rows: PAID_ROWS.length ? PAID_ROWS : [['—', 'No paid applicants yet', '', '']] };
            }
            if (kind === 'trend') {
                const a = sum('applicants'), e = sum('enrolled');
                return { title, sub, head: ['Academic year', 'Applicants', 'Enrolled', 'Conversion'], rows: YEARS.map((y, i) => [y, fm(a[i]), fm(e[i]), (e[i] / a[i] * 100).toFixed(1) + '%']) };
            }
            if (kind === 'funnel') {
                const applied = stage(0);
                return { title, sub, head: ['Step', 'Applicants', 'Share of applied'],
                    rows: ['Applied', 'Took exam', 'Passed', 'Paid', 'Enrolled'].map((s, i) => [s, fm(stage(i)), (stage(i) / applied * 100).toFixed(1) + '%']) };
            }
            // With units (iPACE Director): a Unit column
            const units = CODES.some(c => PROGRAMS[c].unit);
            return { title, sub, head: [...(units ? ['Unit'] : []), WORD[0].toUpperCase() + WORD.slice(1), 'Pending', 'For Exam', 'Paid', 'Total'],
                rows: CODES.map(c => { const s = PROGRAMS[c].status; return [...(units ? [PROGRAMS[c].unit] : []), PROGRAMS[c].name, s[0], s[1], s[2], s[0] + s[1] + s[2]]; }) };
        }

        // ---- Download as PDF (print window), Excel or CSV ----
        function download(format, d) {
            const name = d.title.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'report';
            const save = (blob, file) => { const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = file; a.click(); URL.revokeObjectURL(a.href); };
            const table = `<table border="1"><tr><th colspan="${d.head.length}" style="text-align:left">${esc(d.title)}</th></tr><tr><td colspan="${d.head.length}">${esc(d.sub)}</td></tr><tr>${d.head.map(h => `<th style="background:#EAF5F0">${esc(h)}</th>`).join('')}</tr>${d.rows.map(r => `<tr>${r.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`).join('')}</table>`;
            if (format === 'csv') {
                const csv = [[d.title], [d.sub], [], d.head, ...d.rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
                save(new Blob(['﻿' + csv], { type: 'text/csv' }), name + '.csv');
                return toast('Downloaded as CSV.');
            }
            if (format === 'excel') {
                save(new Blob(['﻿<html><head><meta charset="utf-8"></head><body>' + table + '</body></html>'], { type: 'application/vnd.ms-excel' }), name + '.xls');
                return toast('Downloaded as Excel.');
            }
            const w = window.open('', '_blank');
            if (!w) return toast('Allow pop-ups to open the PDF.');
            w.document.write(`<!doctype html><title>${esc(d.title)}</title><style>body{font-family:sans-serif;padding:24px}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}</style>${table}`);
            w.document.close();
            w.print();
            toast('Print window opened. Choose "Save as PDF".');
        }
        // A "Download" button with PDF / Excel / CSV
        function downloadMenu(getData) {
            const wrap = document.createElement('div');
            wrap.className = 'dropdown d-inline-block';
            wrap.innerHTML = `<button type="button" class="btn btn-sm btn-light border fw-semibold text-nowrap dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"><i class="bi bi-download me-1"></i> Download</button>
                <ul class="dropdown-menu shadow-sm">
                    <li><h6 class="dropdown-header">Download as</h6></li>
                    <li><button type="button" class="dropdown-item" data-format="pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF <span class="small text-body-secondary">· print, then Save as PDF</span></button></li>
                    <li><button type="button" class="dropdown-item" data-format="excel"><i class="bi bi-file-earmark-excel text-success me-2"></i>Excel</button></li>
                    <li><button type="button" class="dropdown-item" data-format="csv"><i class="bi bi-filetype-csv text-primary me-2"></i>CSV</button></li>
                </ul>`;
            // Afterwards focus goes back to the button, so Escape still closes the drawer
            wrap.querySelectorAll('[data-format]').forEach(b => b.addEventListener('click', () => {
                download(b.dataset.format, getData());
                wrap.querySelector('.dropdown-toggle').focus();
            }));
            return wrap;
        }

        // ---- From IATO ----
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('reportDrawer'));
        function drawReceived() {
            $('received').innerHTML = RECEIVED.map((r, i) => `
                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3" data-report="${i}">
                    <span class="rounded-3 bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-file-earmark-text"></i></span>
                    <span class="flex-grow-1"><span class="d-block small ${r.status === 'new' ? 'fw-bold' : 'fw-semibold'}">${esc(r.title)}</span><span class="d-block small text-body-secondary">From ${esc(r.by)} (${esc(r.role)}) · ${esc(r.at)}</span></span>
                    <span class="badge rounded-pill ${r.status === 'new' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-tertiary text-body-secondary'}">${r.status === 'new' ? 'New' : 'Read'}</span>
                </button>`).join('');
            $('received').querySelectorAll('[data-report]').forEach(b => b.addEventListener('click', () => openReport(+b.dataset.report)));
            // The sidebar's red badge counts unread reports
            const unread = RECEIVED.filter(r => r.status === 'new').length, badge = $('reportsBadge');
            if (badge) { badge.textContent = unread; badge.classList.toggle('d-none', !unread); }
        }
        function openReport(i) {
            const r = RECEIVED[i];
            r.status = 'read';
            $('reportTitle').textContent = r.title;
            $('reportSub').textContent = `From ${r.by} · ${r.at}`;
            $('reportMessage').textContent = r.message;
            $('reportDownload').replaceChildren(downloadMenu(() => build(r.kind, r.title)));
            $('reportAsk').href = `${MESSAGES}?new=1&subject=${encodeURIComponent('About: ' + r.title)}`;
            drawer.show();
            drawReceived();
        }

        // ---- Your reports ----
        function drawHistory() {
            $('history').replaceChildren(...HISTORY.map(h => {
                const tr = document.createElement('tr');
                if (h.isNew) tr.className = 'table-success';
                tr.innerHTML = `<td class="ps-4"><div class="fw-semibold">${esc(TEMPLATES[h.template][0])}</div><div class="small text-body-secondary">${esc(LABEL)}</div></td><td class="small">${esc(h.period)}</td><td class="small text-body-secondary text-nowrap">${esc(h.at)}</td><td class="pe-4 text-end"></td>`;
                tr.lastElementChild.appendChild(downloadMenu(() => build(h.template, TEMPLATES[h.template][0])));
                return tr;
            }));
        }
        function generate(key) {
            HISTORY.forEach(h => h.isNew = false);
            HISTORY.unshift({ template: key, period: YEARS[3], at: now(), isNew: true });
            drawHistory();
            toast(`${TEMPLATES[key][0]} is ready. Click Download to pick PDF, Excel, or CSV. (Demo only — not saved yet.)`);
        }
        document.querySelectorAll('[data-template]').forEach(b => b.addEventListener('click', () => generate(b.dataset.template)));

        // ---- Preview: the report's table before you generate it ----
        const previewDrawer = bootstrap.Offcanvas.getOrCreateInstance($('previewDrawer'));
        document.querySelectorAll('[data-preview]').forEach(b => b.addEventListener('click', () => {
            const key = b.dataset.preview, d = build(key, TEMPLATES[key][0]);
            $('previewTitle').textContent = d.title;
            $('previewSub').textContent = d.sub;
            $('previewTable').innerHTML = `<thead class="table-light"><tr>${d.head.map(h => `<th>${esc(h)}</th>`).join('')}</tr></thead><tbody>${d.rows.map(r => `<tr>${r.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`).join('')}</tbody>`;
            $('previewGenerate').onclick = () => { previewDrawer.hide(); generate(key); };
            $('previewDownload').replaceChildren(downloadMenu(() => build(key, TEMPLATES[key][0])));
            previewDrawer.show();
        }));

        drawReceived();
        drawHistory();
    </script>
@endsection
