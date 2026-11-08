{{-- Reports page shared by the Executive Office (ExecutiveController) and the College offices
     (CollegeController): reports received from the IATO, and reports you generate yourself.
     Each controller passes its own data, plus $labels for the words that differ:
       office      page title, e.g. "College"                  subtitle  the text under the page title
       unit / all  "Unit" / "All Units", or "Program" / "All CITE programs"
       total       the name of the total row ("All units")     wide      "institution-wide" / "across CITE"
       role        your title in the reply thread              scope     [value, note] for the Insights report
       sharedTitle heading above $people in the report drawer (null = don't show them)
       locked      a program chair's program: the only scope they can pick (null = any)
       askUrl      (optional) the Messages page: questions about a report go there instead of a reply thread
       canGenerate (optional, default true) false = no "Generate a Report" tab (Vice Presidents and Vice Chancellors) --}}
@extends('layouts.app')

@section('title', 'iARIS — ' . $labels['office'] . ' Reports')
@section('page-title', 'Reports')
@section('page-subtitle', $labels['subtitle'])

@php
    // President: no reply thread; "Question about this report?" goes to Messages
    $ask = $labels['askUrl'] ?? null;
    // "Send a copy to": the IATO plus the other people in $people (not you)
    $recipients = collect($people)
        ->reject(fn ($person) => str_contains($person['name'], auth()->user()->name))
        ->map(fn ($person) => ['initials' => $person['initials'], 'name' => $person['name'], 'tone' => $person['tone']])
        ->prepend(['initials' => $sender['initials'], 'name' => "IATO · {$sender['name']}", 'tone' => 'primary'])
        ->values();
@endphp

@section('content')
    {{-- Two views: reports the IATO sent, and generating your own.
         Without the tabs (canGenerate false) only "Received from IATO" shows. --}}
    <ul class="nav nav-pills gap-2 mb-4 {{ ($labels['canGenerate'] ?? true) ? '' : 'd-none' }}" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" data-bs-toggle="pill" data-bs-target="#receivedView" type="button" role="tab">
                <i class="bi bi-inbox me-1"></i> Received from IATO <span class="badge rounded-pill bg-body-secondary text-body-secondary ms-1">{{ count($received) }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#generateView" type="button" role="tab">
                <i class="bi bi-stars me-1"></i> Generate a Report <span class="badge rounded-pill bg-body-secondary text-body-secondary ms-1" id="historyCount">{{ count($history) }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- ========== RECEIVED FROM IATO ========== --}}
        <div class="tab-pane fade show active" id="receivedView" role="tabpanel">
            {{-- Stat cards (filled in by the script, so they change as you read and reply) --}}
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
                @foreach ([['total', 'Total Reports', 'Received from IATO', 'primary'], ['new', 'Unread', 'Waiting for you', 'danger'], $ask ? ['read', 'Read', 'Opened', 'info'] : ['replied', 'Replied', 'You responded', 'info'], ['latest', 'Latest Report', '', 'dark']] as [$key, $label, $sub, $tone])
                    <div class="col">
                        <div class="card border-0 border-top border-3 border-{{ $tone }} shadow-sm rounded-4 h-100">
                            <div class="card-body">
                                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                                <div class="{{ $key === 'latest' ? 'fs-4' : 'fs-2' }} fw-bold" data-stat="{{ $key }}"></div>
                                <div class="small text-body-secondary" data-stat-sub="{{ $key }}">{{ $sub }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                {{-- Search and filters --}}
                <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
                    <div class="input-group" style="max-width: 280px;">
                        <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search report title…" aria-label="Search reports">
                    </div>
                    <select class="form-select w-auto" id="typeFilter" aria-label="Filter by report type">
                        <option value="">All Report Types</option>
                        @foreach (collect($received)->pluck('typeLabel')->unique() as $type)
                            <option>{{ $type }}</option>
                        @endforeach
                    </select>
                    <select class="form-select w-auto" id="yearFilter" aria-label="Filter by academic year">
                        <option value="">All Academic Years</option>
                        @foreach (collect($received)->pluck('ay')->unique() as $ay)
                            <option>{{ $ay }}</option>
                        @endforeach
                    </select>
                    <select class="form-select w-auto" id="sort" aria-label="Sort">
                        <option value="new">Sort: Newest First</option>
                        <option value="old">Sort: Oldest First</option>
                        <option value="unread">Sort: Unread First</option>
                    </select>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="statusPills">
                    <span class="small fw-bold text-body-secondary me-1">Status:</span>
                    @foreach ($ask ? ['' => 'All', 'new' => 'Unread', 'read' => 'Read'] : ['' => 'All', 'new' => 'Unread', 'replied' => 'Replied', 'read' => 'Read'] as $status => $label)
                        <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold {{ $status === '' ? 'btn-primary' : 'btn-outline-secondary' }}" data-status="{{ $status }}">{{ $label }}</button>
                    @endforeach
                </div>

                {{-- The list (drawn by the script) --}}
                <ul class="list-group list-group-flush" id="reportList"></ul>
                <div class="text-center text-body-secondary py-5 d-none" id="noResults">
                    <i class="bi bi-inbox fs-2 d-block mb-2"></i> No reports match your search or filters.
                </div>
                <div class="small text-body-secondary px-4 py-3 border-top" id="showingText"></div>
            </div>
        </div>

        {{-- ========== GENERATE A REPORT ========== --}}
        <div class="tab-pane fade" id="generateView" role="tabpanel">
            <h2 class="fs-5 fw-bold mb-1">Report templates</h2>
            <p class="small text-body-secondary mb-3">Generate a report from the data inside iARIS. Pick a template, set the options, and download it.</p>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xxl-3 g-3 mb-4">
                @foreach ($templates as $key => $template)
                    <div class="col">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="icon-circle rounded-3 bg-{{ $template['tone'] }}-subtle text-{{ $template['tone'] }}-emphasis d-flex align-items-center justify-content-center mb-3">
                                    <i class="bi {{ $template['icon'] }}"></i>
                                </div>
                                <h3 class="fs-6 fw-bold mb-1">{{ $template['name'] }}</h3>
                                <p class="small text-body-secondary mb-3">{{ $template['description'] }}</p>
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach ($template['tags'] as $tag)
                                        <span class="badge bg-body-secondary text-body-secondary">{{ $tag }}</span>
                                    @endforeach
                                </div>
                                <div class="d-flex gap-2 mt-auto">
                                    <button type="button" class="btn btn-sm btn-light border flex-fill" data-quick-preview="{{ $key }}"><i class="bi bi-eye me-1"></i> Preview</button>
                                    <button type="button" class="btn btn-sm btn-primary flex-fill" data-generate="{{ $key }}"><i class="bi bi-lightning-charge me-1"></i> Generate</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Reports you generated</h2>
                    <div class="small text-body-secondary">Your recent reports. Preview or download them again.</div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-nowrap">
                                <th class="ps-4 text-body-secondary fw-bold">Report</th>
                                <th class="text-body-secondary fw-bold">Scope</th>
                                <th class="text-body-secondary fw-bold">Format</th>
                                <th class="text-body-secondary fw-bold">Generated</th>
                                <th class="text-body-secondary fw-bold">Shared with</th>
                                <th class="pe-4"></th>
                            </tr>
                        </thead>
                        <tbody id="historyRows"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- The body of a report: highlights, table, note, AI summary. Copied into the drawer and the preview window. --}}
    <template id="reportBodyTemplate">
        <div>
            <div class="row row-cols-1 row-cols-sm-3 g-2 mb-3" data-part="highlights"></div>
            <div class="table-responsive border rounded-3 mb-2">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light" data-part="head"></thead>
                    <tbody data-part="rows"></tbody>
                </table>
            </div>
            <div class="small text-body-secondary mb-3" data-part="note"></div>
            <div class="small rounded-3 bg-info-subtle text-info-emphasis p-3 mb-3 d-none" data-part="ai">
                <div class="fw-bold mb-1"><i class="bi bi-stars me-1"></i> AI summary</div>
                <span data-part="aiText"></span>
            </div>
        </div>
    </template>

    {{-- Drawer: one received report (preview + reply thread) --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="reportDrawer" aria-labelledby="drawerTitle" style="--bs-offcanvas-width: 580px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerTitle"></h2>
                <div class="small text-white-50" id="drawerSub"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        @unless ($ask)
        <ul class="nav nav-underline px-4 border-bottom" role="tablist">
            <li class="nav-item"><button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#previewPane" type="button" role="tab" id="previewTab">Report Preview</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#threadPane" type="button" role="tab" id="threadTab">Reply Thread</button></li>
        </ul>
        @endunless
        <div class="offcanvas-body tab-content p-4">
            <div class="tab-pane fade show active" id="previewPane" role="tabpanel">
                <div id="drawerBody"></div>
                @if ($labels['sharedTitle'])
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $labels['sharedTitle'] }}</div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    @foreach ($people as $person)
                        <span class="badge rounded-pill bg-body-tertiary text-body border d-inline-flex align-items-center gap-1 fw-semibold py-1 ps-1 pe-2">
                            <span class="rounded-circle bg-{{ $person['tone'] }}-subtle text-{{ $person['tone'] }}-emphasis d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px;">{{ $person['initials'] }}</span>
                            {{ $person['name'] }}
                        </span>
                    @endforeach
                </div>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-light border" data-download="pdf"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> Download PDF</button>
                    <button type="button" class="btn btn-sm btn-light border" data-download="excel"><i class="bi bi-file-earmark-excel text-primary me-1"></i> Download Excel</button>
                    <button type="button" class="btn btn-sm btn-light border" data-download="print"><i class="bi bi-printer me-1"></i> Print</button>
                </div>
            </div>
            <div class="tab-pane fade" id="threadPane" role="tabpanel">
                <div class="d-flex flex-column gap-3" id="thread"></div>
            </div>
        </div>
        @if ($ask)
            {{-- President: questions go to Messages --}}
            {{-- Button on the left, so the floating AI button doesn't cover it --}}
            <div class="border-top p-3 d-flex flex-wrap align-items-center gap-3">
                <a class="btn btn-sm btn-primary fw-semibold" id="askLink" href="{{ $ask }}"><i class="bi bi-chat-dots me-1"></i> Ask IATO</a>
                <div><div class="fw-bold small">Question about this report?</div><div class="small text-body-secondary">Ask IATO in Messages.</div></div>
            </div>
        @else
        {{-- Reply box (always visible at the bottom) --}}
        <form class="border-top p-3" id="replyForm">
            <label for="replyText" class="visually-hidden">Reply to IATO</label>
            <textarea class="form-control mb-2" id="replyText" rows="2" placeholder="Type your reply to IATO… (Ctrl + Enter to send)"></textarea>
            {{-- Buttons on the left, so the floating AI button doesn't cover them --}}
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary fw-semibold"><i class="bi bi-send me-1"></i> Send Reply</button>
                <button type="button" class="btn btn-sm btn-light border" onclick="document.getElementById('replyText').value = ''">Clear</button>
            </div>
        </form>
        @endif
    </div>

    {{-- Generate a report: options --}}
    <div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4" id="generateForm">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h2 class="modal-title fs-5 fw-bold" id="generateTitle"></h2>
                        <div class="small text-body-secondary" id="generateSub"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="genYear" class="form-label small fw-bold text-uppercase">Academic Year</label>
                        <select class="form-select" id="genYear">
                            @foreach ($years as $i => $year)
                                <option value="{{ $i }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        <div class="form-text d-none" id="genYearHint">This report always uses the current academic year, {{ end($years) }}.</div>
                    </div>
                    <div class="mb-3">
                        <label for="genUnit" class="form-label small fw-bold text-uppercase">Scope</label>
                        <select class="form-select" id="genUnit">
                            {{-- A program chair can only pick their own program --}}
                            @unless ($labels['locked'])
                                <option value="">{{ $labels['all'] }}</option>
                            @endunless
                            @foreach ($units as $name => $unit)
                                <option>{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text d-none" id="genUnitHint">This report always covers {{ lcfirst($labels['total']) }}.</div>
                    </div>
                    <div class="form-check mb-2" id="genCompareRow">
                        <input class="form-check-input" type="checkbox" id="genCompare" checked>
                        <label class="form-check-label" for="genCompare">Compare with the previous academic year <span class="d-block small text-body-secondary">Adds year-over-year change.</span></label>
                    </div>
                    <div class="form-check mb-3" id="genAiRow">
                        <input class="form-check-input" type="checkbox" id="genAi">
                        <label class="form-check-label" for="genAi">Include an AI summary <span class="d-block small text-body-secondary">A short, descriptive summary of the figures. Not a forecast.</span></label>
                    </div>
                    <div class="mb-3">
                        <div class="form-label small fw-bold text-uppercase">Format</div>
                        <div class="btn-group w-100" role="group" aria-label="Format">
                            @foreach (['PDF', 'Excel', 'Print'] as $format)
                                <input type="radio" class="btn-check" name="genFormat" id="format{{ $format }}" value="{{ $format }}" @checked($format === 'PDF')>
                                <label class="btn btn-outline-primary" for="format{{ $format }}">{{ $format }}</label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <div class="form-label small fw-bold text-uppercase">Send a copy to (optional)</div>
                        <div class="d-flex flex-wrap gap-2" id="genTo">
                            @foreach ($recipients as $person)
                                <input type="checkbox" class="btn-check" id="to{{ $person['initials'] }}" value="{{ $person['initials'] }}" autocomplete="off">
                                <label class="btn btn-sm btn-outline-secondary rounded-pill" for="to{{ $person['initials'] }}">{{ $person['name'] }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-light border" id="genPreview"><i class="bi bi-eye me-1"></i> Preview</button>
                    <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-file-earmark-arrow-down me-1"></i> Generate</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Preview of a generated report --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5 fw-bold" id="previewTitle"></h2>
                        <div class="small text-body-secondary" id="previewMeta"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="previewBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary fw-semibold" id="previewDownload"><i class="bi bi-download me-1"></i> Download</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Small "done" message --}}
    <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3">
        <div class="toast align-items-center text-bg-dark border-0" id="reportToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle me-1"></i> <span id="reportToastText"></span></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const RECEIVED = @json($received);
        const YEARS = @json($years);
        const UNITS = @json($units);
        const INSIGHTS = @json($insights);
        const TEMPLATES = @json($templates);
        const HISTORY = @json($history);
        const RECIPIENTS = @json($recipients);
        const SENDER = @json($sender);
        const ME = @json(auth()->user()->name);
        const LABELS = @json($labels);
        // The scope a report starts with: '' (everything) or a program chair's own program
        const DEFAULT_UNIT = LABELS.locked || '';
        const NAMES = Object.keys(UNITS);
        const LAST = YEARS.length - 1;
        const TYPE_STYLE = { trends: ['bi-graph-up-arrow', 'primary'], units: ['bi-layers', 'info'], conv: ['bi-funnel', 'warning'], summary: ['bi-clipboard-data', 'secondary'] };
        const STATUS_STYLE = { new: ['New', 'danger'], read: ['Read', 'secondary'], replied: ['Replied', 'info'] };

        const $ = id => document.getElementById(id);
        const fmt = n => Math.round(n).toLocaleString('en-US');
        const pct = (a, b) => (b - a) / a * 100;
        const change = p => (p >= 0 ? '+' : '−') + Math.abs(p).toFixed(1) + '%';
        const formatDate = (iso, withTime = true) => {
            const d = new Date(iso.replace(' ', 'T'));
            const date = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            return withTime ? `${date} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}` : date;
        };
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('reportDrawer'));
        const generateModal = bootstrap.Modal.getOrCreateInstance($('generateModal'));
        const previewModal = bootstrap.Modal.getOrCreateInstance($('previewModal'));

        function toast(text) {
            $('reportToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance($('reportToast')).show();
        }

        // ---------- Shared: draw a report body, download it, print it ----------
        // report = { highlights: [[label, value, note]], head: [], rows: [[]], note, ai }
        function reportBody(report) {
            const body = $('reportBodyTemplate').content.firstElementChild.cloneNode(true);
            const part = name => body.querySelector(`[data-part="${name}"]`);
            part('highlights').replaceChildren(...report.highlights.map(([label, value, note]) => {
                const col = document.createElement('div');
                col.className = 'col';
                col.innerHTML = '<div class="rounded-3 bg-body-tertiary p-3 h-100"><div class="small fw-bold text-uppercase text-body-secondary"></div><div class="fs-5 fw-bold"></div><div class="small text-body-secondary"></div></div>';
                const [a, b, c] = col.firstElementChild.children;
                a.textContent = label; b.textContent = value; c.textContent = note;
                return col;
            }));
            const headRow = document.createElement('tr');
            report.head.forEach(text => { const th = document.createElement('th'); th.className = 'text-body-secondary'; th.textContent = text; headRow.appendChild(th); });
            part('head').replaceChildren(headRow);
            part('rows').replaceChildren(...report.rows.map((cells, i) => {
                const tr = document.createElement('tr');
                // The total row ("All units") is bold
                if (cells[0] === LABELS.total) tr.className = 'fw-bold table-light';
                cells.forEach(text => { const td = document.createElement('td'); td.textContent = text; tr.appendChild(td); });
                return tr;
            }));
            part('note').textContent = report.note;
            part('ai').classList.toggle('d-none', !report.ai);
            part('aiText').textContent = report.ai || '';
            return body;
        }

        // Excel: a .csv file, which Excel opens directly
        function downloadExcel(title, report) {
            const quote = v => `"${String(v).replace(/"/g, '""')}"`;
            const lines = [[title], [], report.head, ...report.rows].map(row => row.map(quote).join(','));
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv' }));
            // File names: plain letters, numbers and dashes only (e.g. Enrollment-Trends-Report-AY-2025-26.csv)
            link.download = title.replace(/[^A-Za-z0-9]+/g, '-').replace(/^-|-$/g, '') + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        }

        // PDF / Print: open the report on its own page and print it ("Save as PDF" for a PDF)
        function printReport(title, meta, report) {
            const win = window.open('', '_blank');
            if (!win) { toast('Allow pop-ups for this site to print or save as PDF.'); return; }
            win.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title></title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
                <body class="p-4"><h1 class="fs-4 fw-bold mb-1"></h1><p class="small text-body-secondary"></p><div id="body"></div>
                <p class="small text-body-secondary mt-4">iARIS · De La Salle Lipa · IATO Admissions Records and Information System</p></body></html>`);
            win.document.title = title;
            win.document.querySelector('h1').textContent = title;
            win.document.querySelector('p').textContent = meta;
            win.document.getElementById('body').appendChild(win.document.importNode(reportBody(report), true));
            win.document.close();
            // Print once Bootstrap's CSS has loaded
            win.onload = () => { win.focus(); win.print(); };
        }

        // ---------- RECEIVED FROM IATO ----------
        const filters = { search: '', type: '', year: '', sort: 'new', status: '' };
        let openReport = null;
        const ASK_URL = @json($ask);   // President: questions go to Messages

        function renderStats() {
            const count = status => RECEIVED.filter(r => r.status === status).length;
            const latest = [...RECEIVED].sort((a, b) => b.sent.localeCompare(a.sent))[0];
            document.querySelector('[data-stat="total"]').textContent = RECEIVED.length;
            document.querySelector('[data-stat="new"]').textContent = count('new');
            ['replied', 'read'].forEach(k => { const el = document.querySelector(`[data-stat="${k}"]`); if (el) el.textContent = count(k); });
            document.querySelector('[data-stat="latest"]').textContent = formatDate(latest.sent, false);
            document.querySelector('[data-stat-sub="latest"]').textContent = latest.typeLabel;
            // The sidebar badge
            const badge = $('reportsBadge');
            if (badge) { badge.textContent = count('new'); badge.classList.toggle('d-none', !count('new')); }
        }

        function renderList() {
            const list = RECEIVED.filter(r =>
                r.title.toLowerCase().includes(filters.search) &&
                (!filters.type || r.typeLabel === filters.type) &&
                (!filters.year || r.ay === filters.year) &&
                (!filters.status || r.status === filters.status)
            ).sort((a, b) =>
                filters.sort === 'old' ? a.sent.localeCompare(b.sent)
                    : filters.sort === 'unread' ? ((b.status === 'new') - (a.status === 'new')) || b.sent.localeCompare(a.sent)
                    : b.sent.localeCompare(a.sent));

            $('reportList').replaceChildren(...list.map(r => {
                const [icon, tone] = TYPE_STYLE[r.type];
                const [statusLabel, statusTone] = STATUS_STYLE[r.status];
                const li = document.createElement('li');
                li.className = `list-group-item list-group-item-action d-flex flex-wrap flex-md-nowrap align-items-center gap-3 px-4 py-3 ${r.status === 'new' ? 'bg-primary-subtle bg-opacity-25' : ''}`;
                li.setAttribute('role', 'button');
                li.innerHTML = `
                    <span class="rounded-circle ${r.status === 'new' ? 'bg-danger' : ''}" style="width: 8px; height: 8px; flex-shrink: 0;"></span>
                    <div class="icon-circle rounded-3 bg-${tone}-subtle text-${tone}-emphasis d-flex align-items-center justify-content-center"><i class="bi ${icon}"></i></div>
                    <div class="flex-grow-1" style="min-width: 0;">
                        <div class="${r.status === 'new' ? 'fw-bold' : 'fw-semibold'}" data-title></div>
                        <div class="small text-body-secondary">From <b class="text-body"></b> (${SENDER.role}) · <span data-date></span>
                            <span class="badge bg-body-secondary text-body-secondary ms-1" data-type></span></div>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <span class="badge bg-${statusTone}-subtle text-${statusTone}-emphasis">${statusLabel}</span>
                        <button type="button" class="btn btn-sm btn-light border" data-action="download" title="Download PDF"><i class="bi bi-download"></i></button>
                        ${ASK_URL ? '<button type="button" class="btn btn-sm btn-primary text-nowrap" data-action="open"><i class="bi bi-eye me-1"></i>Open</button>'
                            : `<button type="button" class="btn btn-sm btn-primary text-nowrap" data-action="reply"><i class="bi bi-reply me-1"></i>${r.status === 'replied' ? 'View Thread' : 'Reply'}</button>`}
                    </div>`;
                li.querySelector('[data-title]').textContent = r.title;
                li.querySelector('b').textContent = SENDER.name;
                li.querySelector('[data-date]').textContent = formatDate(r.sent);
                li.querySelector('[data-type]').textContent = r.typeLabel;
                li.querySelector('[data-action="download"]').setAttribute('aria-label', `Download ${r.title}`);
                li.addEventListener('click', e => {
                    const action = e.target.closest('[data-action]')?.dataset.action;
                    if (action === 'download') printReport(r.title, `From ${SENDER.name} · ${formatDate(r.sent)}`, r);
                    else openDrawer(r, action === 'reply' ? 'thread' : 'preview');
                });
                return li;
            }));
            $('noResults').classList.toggle('d-none', list.length > 0);
            $('showingText').textContent = `Showing ${list.length} of ${RECEIVED.length} reports`;
        }

        function renderThread(r) {
            $('thread').replaceChildren(...r.thread.map(message => {
                const mine = message.from === 'me';
                const bubble = document.createElement('div');
                bubble.className = `rounded-4 p-3 small ${mine ? 'bg-primary text-white align-self-end ms-5' : 'bg-body-tertiary border align-self-start me-5'}`;
                bubble.innerHTML = '<div class="fw-bold mb-1"></div><div style="white-space: pre-wrap;"></div><div class="mt-1 opacity-75"></div>';
                const [who, text, at] = bubble.children;
                who.textContent = mine ? `${ME} · ${LABELS.role}` : `${SENDER.name} · ${SENDER.role}`;
                text.textContent = message.text;
                at.textContent = message.at;
                return bubble;
            }));
        }

        function openDrawer(r, pane = 'preview') {
            openReport = r;
            if (r.status === 'new') r.status = 'read';
            $('drawerTitle').textContent = r.title;
            $('drawerSub').textContent = `From ${SENDER.name} · ${SENDER.role} · ${formatDate(r.sent)}`;
            $('drawerBody').replaceChildren(reportBody(r));
            renderThread(r);
            if (ASK_URL) $('askLink').href = `${ASK_URL}?new=1&subject=${encodeURIComponent('About: ' + r.title)}`;
            else bootstrap.Tab.getOrCreateInstance($(pane === 'thread' ? 'threadTab' : 'previewTab')).show();
            drawer.show();
            renderStats();
            renderList();
        }

        document.querySelectorAll('#reportDrawer [data-download]').forEach(b => b.addEventListener('click', () => {
            if (b.dataset.download === 'excel') downloadExcel(openReport.title, openReport);
            else printReport(openReport.title, `From ${SENDER.name} · ${formatDate(openReport.sent)}`, openReport);
        }));

        // Reply (kept in the page for now; the backend would save it and notify the IATO)
        $('replyForm')?.addEventListener('submit', e => {
            e.preventDefault();
            const text = $('replyText').value.trim();
            if (!text || !openReport) return;
            openReport.thread.push({ from: 'me', text, at: 'Just now' });
            openReport.status = 'replied';
            $('replyText').value = '';
            renderThread(openReport);
            bootstrap.Tab.getOrCreateInstance($('threadTab')).show();
            renderStats();
            renderList();
            toast('Reply sent to IATO');
        });
        $('replyText')?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) $('replyForm').requestSubmit();
        });

        $('search').addEventListener('input', e => { filters.search = e.target.value.trim().toLowerCase(); renderList(); });
        [['typeFilter', 'type'], ['yearFilter', 'year'], ['sort', 'sort']].forEach(([id, key]) =>
            $(id).addEventListener('change', e => { filters[key] = e.target.value; renderList(); }));
        const statusPills = document.querySelectorAll('#statusPills [data-status]');
        statusPills.forEach(pill => pill.addEventListener('click', () => {
            statusPills.forEach(p => { p.classList.toggle('btn-primary', p === pill); p.classList.toggle('btn-outline-secondary', p !== pill); });
            filters.status = pill.dataset.status;
            renderList();
        }));

        // ---------- GENERATE A REPORT ----------
        // Totals for one unit, or every unit added up ('' = All Units)
        const series = (unit, key, year) => unit ? UNITS[unit][key][year] : NAMES.reduce((t, n) => t + UNITS[n][key][year], 0);
        const single = (unit, key) => unit ? UNITS[unit][key] : NAMES.reduce((t, n) => t + UNITS[n][key], 0);
        const conversion = (unit, year) => series(unit, 'enrolled', year) / series(unit, 'applicants', year) * 100;

        // Build a report from a template and options: { template, year, unit, compare, ai }
        function build(o) {
            const t = TEMPLATES[o.template];
            const year = t.currentYearOnly ? LAST : o.year;
            const unit = t.allUnits ? '' : o.unit;
            const compare = t.alwaysCompare || (t.compare && o.compare);
            const hasPrevious = year > 0;
            const r = { highlights: [], head: [], rows: [], note: '', ai: '' };
            const vsLast = (key) => hasPrevious && compare ? change(pct(series(unit, key, year - 1), series(unit, key, year))) + ' vs last AY' : YEARS[year];
            const scopeName = unit || LABELS.total;

            if (o.template === 'trends') {
                r.head = compare ? ['Academic Year', 'Applicants', 'YoY', 'Enrolled', 'YoY', 'Conversion'] : ['Academic Year', 'Applicants', 'Enrolled', 'Conversion'];
                for (let i = 0; i <= year; i++) {
                    const a = series(unit, 'applicants', i), e = series(unit, 'enrolled', i);
                    const row = [YEARS[i], fmt(a)];
                    if (compare) row.push(i ? change(pct(series(unit, 'applicants', i - 1), a)) : '—');
                    row.push(fmt(e));
                    if (compare) row.push(i ? change(pct(series(unit, 'enrolled', i - 1), e)) : '—');
                    row.push(conversion(unit, i).toFixed(1) + '%');
                    r.rows.push(row);
                }
                r.highlights = [
                    ['Applicants', fmt(series(unit, 'applicants', year)), vsLast('applicants')],
                    ['Enrolled', fmt(series(unit, 'enrolled', year)), vsLast('enrolled')],
                    ['Applied → Enrolled', conversion(unit, year).toFixed(1) + '%', hasPrevious && compare ? `was ${conversion(unit, year - 1).toFixed(1)}% last AY` : YEARS[year]],
                ];
                r.note = 'Year-over-year change is calculated against the previous academic year.';
                if (hasPrevious) r.ai = `${scopeName} applicants ${change(pct(series(unit, 'applicants', year - 1), series(unit, 'applicants', year)))} and enrollment ${change(pct(series(unit, 'enrolled', year - 1), series(unit, 'enrolled', year)))} compared with the previous year. Applied-to-enrolled conversion moved from ${conversion(unit, year - 1).toFixed(1)}% to ${conversion(unit, year).toFixed(1)}%.`;
            }

            if (o.template === 'units') {
                const total = series('', 'applicants', year), previousTotal = hasPrevious ? series('', 'applicants', year - 1) : 0;
                r.head = hasPrevious ? [LABELS.unit, YEARS[year - 1], YEARS[year], 'Change', 'Share'] : [LABELS.unit, YEARS[year], 'Share'];
                let fastest = null;
                NAMES.forEach(n => {
                    const current = UNITS[n].applicants[year], share = (current / total * 100).toFixed(1) + '%';
                    if (hasPrevious) {
                        const growth = pct(UNITS[n].applicants[year - 1], current);
                        if (!fastest || growth > fastest[1]) fastest = [n, growth];
                        r.rows.push([n, fmt(UNITS[n].applicants[year - 1]), fmt(current), change(growth), share]);
                    } else {
                        r.rows.push([n, fmt(current), share]);
                    }
                });
                r.rows.push(hasPrevious ? [LABELS.total, fmt(previousTotal), fmt(total), change(pct(previousTotal, total)), '100%'] : [LABELS.total, fmt(total), '100%']);
                const largest = [...NAMES].sort((x, y) => UNITS[y].applicants[year] - UNITS[x].applicants[year])[0];
                const largestShare = (UNITS[largest].applicants[year] / total * 100).toFixed(1) + '%';
                r.highlights = [
                    ['Applicants', fmt(total), hasPrevious ? change(pct(previousTotal, total)) + ' vs last AY' : YEARS[year]],
                    ['Fastest growth', hasPrevious ? fastest[0] : '—', hasPrevious ? change(fastest[1]) : 'No prior year'],
                    ['Largest ' + LABELS.unit.toLowerCase(), largest, largestShare + ' of applicants'],
                ];
                r.note = hasPrevious ? 'Change is calculated against the previous academic year.' : `${YEARS[0]} is the first year on record, so there is no prior year to compare.`;
                if (hasPrevious) r.ai = `${fastest[0]} grew fastest (${change(fastest[1])}), and ${largest} remains the largest ${LABELS.unit.toLowerCase()} with ${largestShare} of all applicants.`;
            }

            if (o.template === 'funnel') {
                const stages = [['Applied', series(unit, 'applicants', LAST)], ['Took exam', single(unit, 'took')], ['Passed', single(unit, 'passed')],
                    ['Paid', single(unit, 'paid')], ['Enrolled', series(unit, 'enrolled', LAST)]];
                const applied = stages[0][1];
                let worst = null;
                r.head = ['Stage', 'Count', '% of applied', 'From previous step'];
                stages.forEach(([name, count], i) => {
                    const step = i ? count / stages[i - 1][1] * 100 : 100;
                    if (i && (!worst || step < worst[1])) worst = [`${stages[i - 1][0]} → ${name}`, step, stages[i - 1][1] - count];
                    r.rows.push([name, fmt(count), (count / applied * 100).toFixed(1) + '%', i ? step.toFixed(1) + '%' : '—']);
                });
                r.highlights = [
                    ['Applied', fmt(applied), YEARS[LAST]],
                    ['Paid', fmt(stages[3][1]), (stages[3][1] / applied * 100).toFixed(1) + '% of applied'],
                    ['Enrolled', fmt(stages[4][1]), (stages[4][1] / applied * 100).toFixed(1) + '% of applied'],
                ];
                r.note = 'Funnel stages are tracked for the current academic year only.';
                r.ai = `The largest drop is at ${worst[0]}, where only ${worst[1].toFixed(1)}% continue (${fmt(worst[2])} applicants lost).`;
            }

            if (o.template === 'summary') {
                r.head = compare ? [LABELS.unit, 'Applicants', 'vs last AY', 'Enrolled', 'Conversion'] : [LABELS.unit, 'Applicants', 'Enrolled', 'Conversion'];
                const row = (name, u) => {
                    const cells = [name, fmt(series(u, 'applicants', year))];
                    if (compare) cells.push(hasPrevious ? change(pct(series(u, 'applicants', year - 1), series(u, 'applicants', year))) : '—');
                    cells.push(fmt(series(u, 'enrolled', year)), conversion(u, year).toFixed(1) + '%');
                    return cells;
                };
                const shown = unit ? [unit] : NAMES;
                shown.forEach(n => r.rows.push(row(n, n)));
                if (!unit) r.rows.push(row(LABELS.total, ''));
                r.highlights = [
                    ['Applicants', fmt(series(unit, 'applicants', year)), vsLast('applicants')],
                    ['Enrolled', fmt(series(unit, 'enrolled', year)), vsLast('enrolled')],
                    ['Applied → Enrolled', conversion(unit, year).toFixed(1) + '%', scopeName],
                ];
                r.note = 'Conversion is enrolled students divided by applicants for the same academic year.';
                if (unit) {
                    r.ai = `${unit} had ${fmt(series(unit, 'applicants', year))} applicants${hasPrevious ? ` (${change(pct(series(unit, 'applicants', year - 1), series(unit, 'applicants', year)))} vs last AY)` : ''} and converted ${conversion(unit, year).toFixed(1)}% to enrollment, compared with ${conversion('', year).toFixed(1)}% ${LABELS.wide}.`;
                } else {
                    const best = [...NAMES].sort((x, y) => conversion(y, year) - conversion(x, year))[0];
                    r.ai = `${best} has the highest applied-to-enrolled conversion at ${conversion(best, year).toFixed(1)}%. Conversion ${LABELS.wide} is ${conversion('', year).toFixed(1)}%.`;
                }
            }

            if (o.template === 'insights') {
                r.head = ['Insight', 'What the data shows'];
                r.rows = INSIGHTS.map(i => [i.title, i.body]);
                r.highlights = [['Period', `${YEARS.length} years`, `${YEARS[0]} to ${YEARS[LAST]}`], ['Scope', ...LABELS.scope], ['Type', 'Descriptive', 'No forecasts']];
                r.note = 'Generated automatically from historical iARIS data. Decisions remain with university leadership.';
                return r;
            }

            if (!o.ai) r.ai = '';
            else if (!r.ai) r.ai = `${YEARS[0]} is the first year on record, so there is no earlier year to compare with.`;
            return r;
        }

        const titleOf = o => {
            const t = TEMPLATES[o.template];
            return `${t.name} — ${YEARS[t.currentYearOnly ? LAST : o.year]}${o.unit && !t.allUnits ? ' · ' + o.unit : ''}`;
        };
        const scopeOf = o => TEMPLATES[o.template].allUnits ? LABELS.all : (o.unit || LABELS.all);
        const metaOf = o => `${YEARS[TEMPLATES[o.template].currentYearOnly ? LAST : o.year]} · ${scopeOf(o)} · Generated by ${ME}`;

        let previewing = null;
        function showPreview(o) {
            previewing = o;
            $('previewTitle').textContent = titleOf(o);
            $('previewMeta').textContent = metaOf(o);
            $('previewBody').replaceChildren(reportBody(build(o)));
            previewModal.show();
        }
        $('previewDownload').addEventListener('click', () => download(previewing));

        // PDF and Print open the print window; Excel downloads a .csv
        function download(o) {
            if (o.format === 'Excel') downloadExcel(titleOf(o), build(o));
            else printReport(titleOf(o), metaOf(o), build(o));
        }

        // Template cards
        document.querySelectorAll('[data-quick-preview]').forEach(b => b.addEventListener('click', () =>
            showPreview({ template: b.dataset.quickPreview, year: LAST, unit: DEFAULT_UNIT, compare: true, ai: true, format: 'PDF' })));

        let generating = null;
        document.querySelectorAll('[data-generate]').forEach(b => b.addEventListener('click', () => {
            generating = b.dataset.generate;
            const t = TEMPLATES[generating];
            $('generateTitle').textContent = 'Generate: ' + t.name;
            $('generateSub').textContent = t.description;
            $('genYear').value = LAST;
            $('genYear').disabled = !!t.currentYearOnly;
            $('genYearHint').classList.toggle('d-none', !t.currentYearOnly);
            $('genUnit').value = DEFAULT_UNIT;
            $('genUnit').disabled = !!t.allUnits || !!LABELS.locked;
            $('genUnitHint').classList.toggle('d-none', !t.allUnits);
            $('genCompareRow').classList.toggle('d-none', !t.compare);
            // Starting values from Settings → Report Preferences
            const prefs = window.IARIS_SETTINGS?.reports || {};
            $('genCompare').checked = prefs.compare ?? true;
            $('genAiRow').classList.toggle('d-none', !t.ai);
            $('genAi').checked = prefs.ai ?? false;
            ($(`format${prefs.format}`) || $('formatPDF')).checked = true;
            document.querySelectorAll('#genTo input').forEach(box => box.checked = (prefs.to || []).includes(box.value));
            generateModal.show();
        }));

        const readOptions = () => ({
            template: generating,
            year: Number($('genYear').value),
            unit: $('genUnit').value,
            compare: $('genCompare').checked,
            ai: $('genAi').checked,
            format: document.querySelector('[name="genFormat"]:checked').value,
            to: [...document.querySelectorAll('#genTo input:checked')].map(box => box.value),
        });

        $('genPreview').addEventListener('click', () => { generateModal.hide(); showPreview(readOptions()); });
        $('generateForm').addEventListener('submit', e => {
            e.preventDefault();
            const o = readOptions();
            HISTORY.unshift({ template: o.template, year: o.year, unit: o.unit, compare: o.compare, ai: o.ai, format: o.format, to: o.to,
                at: new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).replace(/, (\d+:)/, ' · $1') });
            renderHistory();
            generateModal.hide();
            download(o);
            toast(`Report generated as ${o.format}` + (o.to.length ? ` · copy sent to ${o.to.length}` : ''));
        });

        // History table
        function renderHistory() {
            $('historyCount').textContent = HISTORY.length;
            if (!HISTORY.length) {
                $('historyRows').innerHTML = '<tr><td colspan="6" class="text-center text-body-secondary py-4">You have not generated any reports yet.</td></tr>';
                return;
            }
            const formatTone = { PDF: 'danger', Excel: 'primary', Print: 'secondary' };
            $('historyRows').replaceChildren(...HISTORY.map(h => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class="ps-4 fw-bold small"></td><td class="small"></td>
                    <td><span class="badge bg-${formatTone[h.format]}-subtle text-${formatTone[h.format]}-emphasis">${h.format}</span></td>
                    <td class="small text-body-secondary text-nowrap"></td><td class="small"></td>
                    <td class="pe-4 text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-light border" title="Preview" data-action="preview"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-sm btn-light border" title="Download" data-action="download"><i class="bi bi-download"></i></button>
                    </td>`;
                const cells = tr.children;
                cells[0].textContent = titleOf(h);
                cells[1].textContent = scopeOf(h);
                cells[3].textContent = h.at;
                if (h.to.length) {
                    h.to.forEach(initials => {
                        const person = RECIPIENTS.find(p => p.initials === initials);
                        const chip = document.createElement('span');
                        chip.className = `badge rounded-pill bg-${person?.tone || 'secondary'}-subtle text-${person?.tone || 'secondary'}-emphasis me-1`;
                        chip.textContent = initials;
                        chip.title = person?.name || initials;
                        cells[4].appendChild(chip);
                    });
                } else {
                    cells[4].innerHTML = '<span class="text-body-secondary">Only you</span>';
                }
                tr.querySelector('[data-action="preview"]').addEventListener('click', () => showPreview(h));
                tr.querySelector('[data-action="download"]').addEventListener('click', () => download(h));
                return tr;
            }));
        }

        // Received reports with 'build' (the College ones) are made from the same numbers as the Generate tab
        RECEIVED.filter(r => r.build).forEach(r => Object.assign(r, build(r.build), r.note ? { note: r.note } : {}));

        renderStats();
        renderList();
        renderHistory();

        // Coming from the dashboard's "Reports from IATO" card: open that report
        const openId = new URLSearchParams(location.search).get('open');
        const fromDashboard = RECEIVED.find(r => r.id === openId);
        if (fromDashboard) openDrawer(fromDashboard);
    </script>
@endsection
