@extends('layouts.app')

{{--
    Registrar Reports: four ready-made reports built in the browser from the Registrar's records.
    CSV downloads a file; PDF opens a printable page (Save as PDF). The "Your reports" list is
    front end only: new ones stay until reload. TODO (backend): save generated reports.
--}}

@php
    // key => [title, icon, colour, description]
    $templates = [
        'paid' => ['Paid reservations list', 'bi-clipboard-check', 'info', 'Every applicant who paid, with payment date. For enrollment.'],
        'status' => ['Applicants by status', 'bi-bar-chart', 'primary', 'Pending, For Exam, Passed and Paid counts by unit and department (or level).'],
        'daily' => ['Daily payments summary', 'bi-calendar-day', 'warning', 'Reservations paid per day, with totals.'],
        'program' => ['Applicants by program / grade', 'bi-layers', 'secondary', 'Counts per program or grade, by unit.'],
    ];
    $periods = ["AY {$academicYear} (to date)", 'Last 7 days', 'This month', 'Last month'];
@endphp

@section('title', 'iARIS — Registrar Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Generate reports for ' . (count($units) > 1 ? implode(', ', array_slice($units, 0, -1)) . ', and ' . last($units) : $units[0]) . ' from the data in iARIS')

@section('content')
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($templates as $key => [$title, $icon, $tone, $text])
            <div class="col">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body d-flex flex-column gap-2">
                        <span class="rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;"><i class="bi {{ $icon }}"></i></span>
                        <div class="fw-bold">{{ $title }}</div>
                        <div class="small text-body-secondary flex-grow-1">{{ $text }}</div>
                        <button type="button" class="btn btn-sm btn-primary fw-semibold align-self-start" data-template="{{ $key }}"><i class="bi bi-lightning-charge me-1"></i> Generate</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
            <h2 class="fs-6 fw-bold mb-0">Your reports</h2>
            <span class="small text-body-secondary">Kept for 90 days</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Report</th>
                        <th class="text-body-secondary fw-bold">Scope</th>
                        <th class="text-body-secondary fw-bold">Period</th>
                        <th class="text-body-secondary fw-bold">Format</th>
                        <th class="text-body-secondary fw-bold">Generated</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="history"></tbody>
            </table>
        </div>
    </div>

    {{-- Generate --}}
    <div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="generateForm">
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="generateTitle">Generate report</h3>
                        <p class="small text-white-50 mb-0" id="generateSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label for="genScope" class="form-label small fw-bold text-uppercase text-body-secondary">Scope</label>
                    <select class="form-select mb-3" id="genScope">
                        @if (count($units) > 1)
                            <option>All units</option>
                        @endif
                        @foreach ($units as $unit)
                            <option>{{ $unit }}</option>
                        @endforeach
                    </select>
                    <label for="genPeriod" class="form-label small fw-bold text-uppercase text-body-secondary">Period</label>
                    <select class="form-select mb-3" id="genPeriod">
                        @foreach ($periods as $period)
                            <option>{{ $period }}</option>
                        @endforeach
                    </select>
                    <label for="genFormat" class="form-label small fw-bold text-uppercase text-body-secondary">Format</label>
                    <select class="form-select" id="genFormat">
                        <option>Excel (CSV)</option>
                        <option>PDF</option>
                    </select>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-lightning-charge me-1"></i> Generate</button>
                </div>
            </form>
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
        const ROWS = @json($rows);
        const TEMPLATES = @json($templates);
        // [title, scope, period, format, generated, new?]
        const HISTORY = @json($history);
        const $ = id => document.getElementById(id);
        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };
        const toast = text => { $('reportToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('reportToast')).show(); };
        let template = null;   // key of the report being generated

        // ---- Build a report: [title, header, rows] ----
        function inPeriod(dateText, period) {
            if (period.startsWith('AY')) return true;
            if (!dateText) return false;
            const d = new Date(dateText), today = new Date();
            if (Number.isNaN(d.getTime())) return false;
            d.setHours(0, 0, 0, 0); today.setHours(0, 0, 0, 0);
            if (d > today) return false;
            if (period === 'Last 7 days') {
                const start = new Date(today); start.setDate(start.getDate() - 6);
                return d >= start;
            }
            const month = period === 'This month' ? today.getMonth() : (today.getMonth() + 11) % 12;
            const year = period === 'Last month' && today.getMonth() === 0 ? today.getFullYear() - 1 : today.getFullYear();
            return d.getMonth() === month && d.getFullYear() === year;
        }
        function build(key, scope, period) {
            const rows = ROWS.filter(r => scope === 'All units' || r.unit === scope);
            const paid = rows.filter(r => (r.reservation_paid || r.status === 'Paid') && inPeriod(r.paid, period));
            const applied = rows.filter(r => inPeriod(r.applied, period));
            const countBy = (list, keyOf) => Object.entries(list.reduce((m, r) => { const k = keyOf(r); m[k] = (m[k] || 0) + 1; return m; }, {}));
            if (key === 'paid') {
                // No Amount column: that's the cashier's record, not the Registrar's.
                return [['Applicant No.', 'Name', 'Unit', 'Department', 'Program', 'Paid on'],
                    paid.map(r => [r.id, r.name, r.unit, r.dept, r.program, r.paid_label])];
            }
            if (key === 'status') {
                const groups = [...new Set(applied.map(r => `${r.unit}|${r.dept}`))].sort();
                return [['Unit', 'Department', 'Pending', 'For Exam', 'Passed', 'Paid', 'Total'],
                    groups.map(g => {
                        const [unit, dept] = g.split('|'), mine = applied.filter(r => r.unit === unit && r.dept === dept);
                        const n = s => mine.filter(r => r.status === s).length;
                        return [unit, dept, n('Pending'), n('For Exam'), n('Passed'), n('Paid'), mine.length];
                    })];
            }
            if (key === 'daily') {
                // No Total amount column: that's the cashier's record, not the Registrar's.
                const days = [...new Set(paid.map(r => r.paid))].sort();
                return [['Date', 'Reservations paid'],
                    days.map(day => {
                        const mine = paid.filter(r => r.paid === day);
                        return [mine[0].paid_label, mine.length];
                    })];
            }
            return [['Unit', 'Program', 'Applicants'],
                countBy(applied, r => `${r.unit}|${r.program}`).sort().map(([k, n]) => [...k.split('|'), n])];
        }

        // ---- Download: CSV file, or a printable page for PDF ----
        function download(h) {
            const key = Object.keys(TEMPLATES).find(k => TEMPLATES[k][0] === h[0]);
            const [header, rows] = build(key, h[1], h[2]);
            if (h[3] === 'PDF') {
                const w = window.open('', '_blank');
                if (!w) return toast('Allow pop-ups to open the PDF.');
                const esc = v => String(v).replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
                w.document.write(`<!doctype html><title>${esc(h[0])}</title><style>body{font-family:sans-serif;padding:24px}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}th{background:#f2f5f4}</style>`
                    + `<h2>${esc(h[0])}</h2><p>${esc(h[1])} · ${esc(h[2])} · Generated ${esc(h[4])}</p>`
                    + `<table><tr>${header.map(c => `<th>${esc(c)}</th>`).join('')}</tr>${rows.map(r => `<tr>${r.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`).join('') || `<tr><td colspan="${header.length}">No data for this period.</td></tr>`}</table>`);
                w.document.close();
                w.print();
                return;
            }
            const csv = [header, ...rows].map(cols => cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = h[0].toLowerCase().replace(/[^a-z]+/g, '_') + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
            toast(`Downloaded ${rows.length} row${rows.length === 1 ? '' : 's'}.`);
        }

        function drawHistory() {
            $('history').replaceChildren(...HISTORY.map(h => {
                const tr = el('tr', h[5] ? 'table-success' : '');
                const title = el('td', 'ps-4 fw-semibold', h[0]);
                const format = el('td');
                format.appendChild(el('span', 'badge rounded-pill bg-body-tertiary text-body border', h[3] === 'PDF' ? 'PDF' : 'CSV'));
                const btnCell = el('td', 'pe-4 text-end');
                const btn = el('button', 'btn btn-sm btn-light border fw-semibold text-nowrap');
                btn.type = 'button';
                btn.innerHTML = '<i class="bi bi-download me-1"></i> Download';
                btn.addEventListener('click', () => download(h));
                btnCell.appendChild(btn);
                tr.append(title, el('td', '', h[1]), el('td', 'small', h[2]), format, el('td', 'small text-body-secondary text-nowrap', h[4]), btnCell);
                return tr;
            }));
        }

        const modal = bootstrap.Modal.getOrCreateInstance($('generateModal'));
        document.querySelectorAll('[data-template]').forEach(b => b.addEventListener('click', () => {
            template = b.dataset.template;
            $('generateSubtitle').textContent = TEMPLATES[template][0];
            modal.show();
        }));
        $('generateForm').addEventListener('submit', e => {
            e.preventDefault();
            const d = new Date();
            const when = `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
            HISTORY.forEach(h => h[5] = false);
            HISTORY.unshift([TEMPLATES[template][0], $('genScope').value, $('genPeriod').value, $('genFormat').value, when, true]);
            modal.hide();
            drawHistory();
            toast(`${TEMPLATES[template][0]} is ready to download. (Kept on this page until reload.)`);
        });

        drawHistory();
    </script>
@endsection
