@extends('layouts.app')

{{-- Ready for Enrollment: applicants who paid the reservation fee (status Paid), drawn and filtered by the script --}}

@section('title', 'iARIS — Ready for Enrollment')
@section('page-title', 'Ready for Enrollment')
@section('page-subtitle', 'Applicants who paid the reservation fee and can now be enrolled')

@section('content')
    <div class="row row-cols-2 row-cols-xl-4 g-3 mb-4" id="statCards"></div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom no-print">
            <div class="input-group" style="max-width: 280px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search name or applicant number…" aria-label="Search">
            </div>
            <select class="form-select w-auto" id="unitFilter" aria-label="Unit">
                <option value="">All units</option>
                @foreach ($units as $unit)
                    <option>{{ $unit }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto d-none" id="deptFilter" aria-label="Department"></select>
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="pay">Latest payment first</option>
                <option value="az">Name A–Z</option>
            </select>
            <div class="ms-lg-auto d-flex gap-2">
                <button type="button" class="btn btn-light border fw-semibold" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
                <button type="button" class="btn btn-primary fw-semibold" id="download"><i class="bi bi-download me-1"></i> Download list (CSV)</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Unit</th>
                        <th class="text-body-secondary fw-bold">Department</th>
                        <th class="text-body-secondary fw-bold">Program</th>
                        <th class="text-body-secondary fw-bold">Paid on</th>
                        <th class="text-body-secondary fw-bold">Amount</th>
                        <th class="text-body-secondary fw-bold">Applied</th>
                        <th class="pe-4 no-print"></th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white rounded-bottom-4 small text-body-secondary px-4 py-3" id="footer"></div>
    </div>
@endsection

@section('scripts')
    <script>
        const ROWS = @json($rows);
        const UNITS = @json($units);
        const $ = id => document.getElementById(id);
        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };

        // The department list follows the chosen unit
        function fillDepartments() {
            const unit = $('unitFilter').value;
            const dept = $('deptFilter');
            dept.classList.toggle('d-none', !unit);
            const depts = [...new Set(ROWS.filter(r => r.unit === unit).map(r => r.dept))].sort();
            dept.replaceChildren(new Option(unit === 'College' ? 'All colleges' : 'All programs', ''), ...depts.map(d => new Option(d)));
        }

        function filtered() {
            const q = $('search').value.trim().toLowerCase(), unit = $('unitFilter').value, dept = $('deptFilter').value;
            return ROWS.filter(r => (!unit || r.unit === unit) && (!dept || r.dept === dept)
                    && (!q || r.name.toLowerCase().includes(q) || r.id.toLowerCase().includes(q)))
                .sort((a, b) => $('sort').value === 'az' ? a.name.localeCompare(b.name) : String(b.paid).localeCompare(String(a.paid)));
        }

        function draw() {
            const list = filtered(), unit = $('unitFilter').value, dept = $('deptFilter').value;

            // Cards: this view, then the paid count of each unit
            $('statCards').replaceChildren(...[
                ['Ready for enrollment', list.length, 'info', dept || unit || 'All your units'],
                ...UNITS.slice(0, 3).map(u => [u, ROWS.filter(r => r.unit === u).length, 'primary', 'Paid reservations']),
            ].map(([label, value, tone, sub]) => {
                const col = el('div', 'col');
                const card = el('div', `card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100`);
                const body = el('div', 'card-body');
                body.append(el('div', 'small fw-bold text-uppercase tracking-wide text-body-secondary mb-2', label),
                    el('div', `fs-3 fw-bold ${tone === 'info' ? 'text-info-emphasis' : ''}`, value.toLocaleString()),
                    el('div', 'small text-body-secondary', sub));
                card.appendChild(body);
                col.appendChild(card);
                return col;
            }));

            $('rows').replaceChildren(...(list.length ? list.map(r => {
                const tr = el('tr');
                const who = el('td', 'ps-4');
                who.append(el('div', 'fw-semibold text-nowrap', r.name), el('div', 'small text-body-secondary', r.id));
                const deptCell = el('td');
                deptCell.appendChild(el('span', 'badge rounded-pill bg-body-tertiary text-body border', r.dept));
                const view = el('td', 'pe-4 text-end no-print');
                const a = el('a', 'btn btn-sm btn-light border fw-semibold text-nowrap', 'View record');
                a.href = r.url;
                view.appendChild(a);
                tr.append(who, el('td', 'text-nowrap', r.unit), deptCell, el('td', 'small', r.program),
                    el('td', 'text-nowrap', r.paid_label), el('td', 'fw-bold text-info-emphasis text-nowrap', r.amount),
                    el('td', 'small text-body-secondary text-nowrap', r.applied_label), view);
                return tr;
            }) : [(() => {
                const tr = el('tr');
                const td = el('td', 'text-center text-body-secondary py-5', 'No paid applicants match these filters.');
                td.colSpan = 8;
                tr.appendChild(td);
                return tr;
            })()]));
            $('footer').textContent = `Showing ${list.length} paid applicant${list.length === 1 ? '' : 's'}. The download includes every paid applicant in this view.`;
        }

        $('unitFilter').addEventListener('change', () => { fillDepartments(); draw(); });
        ['deptFilter', 'sort'].forEach(id => $(id).addEventListener('change', draw));
        $('search').addEventListener('input', draw);

        $('download').addEventListener('click', () => {
            const list = filtered();
            const header = ['Applicant No.', 'Name', 'Unit', 'Department', 'Program', 'Paid on', 'Amount', 'Date applied'];
            const csv = [header, ...list.map(r => [r.id, r.name, r.unit, r.dept, r.program, r.paid_label, r.amount, r.applied_label])]
                .map(cols => cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = 'ready_for_enrollment_AY{{ $academicYear }}.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        draw();
    </script>
@endsection
