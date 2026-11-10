@extends('layouts.app')

{{--
    LAMP Office Scholars (LampController::scholars): every applicant who answered Yes to the scholarship question,
    with their entrance exam result. LAMP checks each one who passed (Confirm scholar / Not a scholar), and IATO
    confirms the change. Filter by unit, exam result and LAMP check; "Download list" saves the filtered list.
    ?open=APP-2026-0383 opens that applicant. Applicants and LAMP checks are database-backed.
--}}

@php
    $stats = [
        ['key' => 'apps', 'label' => 'Scholarship applicants', 'sub' => 'Answered Yes on the application', 'tone' => 'success'],
        ['key' => 'passed', 'label' => 'Passed the exam', 'sub' => 'Pass or Reconsidered', 'tone' => 'info'],
        ['key' => 'yes', 'label' => 'Confirmed scholars', 'sub' => 'Checked by LAMP', 'tone' => 'primary'],
        ['key' => 'wait', 'label' => 'Waiting for LAMP', 'sub' => 'Passed, not yet checked', 'tone' => 'warning'],
    ];
@endphp

@section('title', 'iARIS — LAMP Office · Scholars')
@section('page-breadcrumb', 'Scholars')
@section('page-title', 'Scholarship applicants')
@section('page-subtitle', 'Applicants who answered Yes to the scholarship question, with their entrance exam result. Check each one who passed.')

@section('content')
    <div class="row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] }}-emphasis" data-stat="{{ $stat['key'] }}">0</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-secondary d-flex gap-2 small rounded-4 mb-4" role="note">
        <i class="bi bi-info-circle"></i>
        <span>IATO only shares <b>whether an applicant applied for a scholarship</b> and <b>their exam result</b>. The scholarship type and documents stay with the LAMP Office. When you confirm or decline, IATO confirms the change.</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search name or applicant number…" aria-label="Search">
            </div>
            <select class="form-select w-auto" id="unitFilter" aria-label="Unit"><option value="">All units</option></select>
            <select class="form-select w-auto" id="resultFilter" aria-label="Exam result">
                <option value="">All exam results</option>
                @foreach (['Pass', 'Reconsidered', 'Waitlisted', 'Fail', 'Not yet taken'] as $result)
                    <option>{{ $result }}</option>
                @endforeach
            </select>
            <div class="ms-auto" id="download"></div>
        </div>

        {{-- LAMP check pills --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="pills" role="tablist" aria-label="LAMP check"></div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                        <th class="text-body-secondary fw-bold">Unit</th>
                        <th class="text-body-secondary fw-bold">Program / Grade</th>
                        <th class="text-body-secondary fw-bold">Exam result</th>
                        <th class="text-body-secondary fw-bold">LAMP check</th>
                        <th class="text-body-secondary fw-bold">Date applied</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white rounded-bottom-4 small text-body-secondary px-4 py-3" id="footer"></div>
    </div>

    @include('lamp.partials.check')
@endsection

@section('scripts')
    @include('lamp.partials.check-script')
    <script>
        const $ = id => document.getElementById(id);
        const PILLS = [['all', 'All'], ['wait', 'Waiting for LAMP'], ['yes', 'Confirmed scholar'], ['no', 'Not a scholar'], ['notyet', 'Not eligible yet'], ['fail', 'Did not pass']];
        let pill = 'all';

        // The units that are in the list
        [...new Set(LAMP.people.map(x => x.unit))].forEach(u => $('unitFilter').insertAdjacentHTML('beforeend', `<option>${LAMP.esc(u)}</option>`));

        // The filters without the LAMP check pills (the pill counts follow them)
        const base = () => {
            const q = $('search').value.trim().toLowerCase(), unit = $('unitFilter').value, result = $('resultFilter').value;
            return LAMP.people.filter(x => (!q || x.name.toLowerCase().includes(q) || x.id.toLowerCase().includes(q)) && (!unit || x.unit === unit) && (!result || x.result === result));
        };

        function render() {
            const t = LAMP.totals();
            document.querySelectorAll('[data-stat]').forEach(e => e.textContent = t[e.dataset.stat].toLocaleString('en-US'));

            const list = base();
            const count = k => k === 'all' ? list.length : list.filter(x => x.lamp === k).length;
            $('pills').innerHTML = '<span class="small fw-bold text-body-secondary me-1">LAMP check:</span>' + PILLS.map(([k, label]) =>
                `<button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold ${pill === k ? 'btn-primary' : 'btn-outline-secondary'}" data-k="${k}">${label}<span class="ms-2 opacity-75">${count(k)}</span></button>`).join('');
            $('pills').querySelectorAll('[data-k]').forEach(b => b.addEventListener('click', () => { pill = b.dataset.k; render(); }));

            const rows = list.filter(x => pill === 'all' || x.lamp === pill);
            $('rows').innerHTML = rows.length ? rows.map(x => `<tr data-i="${LAMP.people.indexOf(x)}" style="cursor: pointer;">
                    <td class="ps-4">${LAMP.nameCell(x)}</td>
                    <td>${LAMP.esc(x.unit)}</td>
                    <td class="small">${LAMP.esc(x.program)}</td>
                    <td>${LAMP.result(x)}</td>
                    <td>${LAMP.label(x)}</td>
                    <td class="small text-body-secondary text-nowrap">${LAMP.esc(x.applied)}</td>
                    <td class="pe-4 text-end text-nowrap">${x.lamp === 'wait'
                        ? '<button type="button" class="btn btn-sm btn-primary fw-semibold me-1" data-a="yes">Confirm scholar</button><button type="button" class="btn btn-sm btn-light border fw-semibold" data-a="no">Not a scholar</button>'
                        : '<button type="button" class="btn btn-sm btn-light border fw-semibold" data-a="view">View</button>'}</td>
                </tr>`).join('')
                : `<tr><td colspan="7" class="text-center text-body-secondary py-5">${LAMP.people.length ? 'No applicants match these filters.' : 'No scholarship applicants yet. They appear here once IATO imports applicants who answered Yes to the scholarship question.'}</td></tr>`;
            $('rows').querySelectorAll('tr[data-i]').forEach(tr => tr.addEventListener('click', e => {
                const x = LAMP.people[tr.dataset.i], a = e.target.closest('[data-a]')?.dataset.a;
                if (a === 'yes' || a === 'no') return LAMP.act(x, a);
                LAMP.open(x);
            }));
            $('footer').textContent = `Showing ${rows.length} of ${LAMP.people.length} scholarship applicants`;
        }

        ['search', 'unitFilter', 'resultFilter'].forEach(id => $(id).addEventListener('input', render));
        $('download').appendChild(downloadMenu(() => ({
            title: 'Scholarship applicants',
            sub: 'AY {{ $academicYear }} · LAMP Office',
            head: ['Applicant no.', 'Name', 'Sex', 'Unit', 'Program / Grade', 'Exam result', 'LAMP check', 'Date applied'],
            rows: base().filter(x => pill === 'all' || x.lamp === pill).map(x => [x.id, x.name, x.sex, x.unit, x.program, x.result, LAMP.LB[x.lamp][0], x.applied]),
        }), false));
        LAMP.onChange(render);
        render();

        // ?open=APP-2026-0383 opens that applicant
        const linked = LAMP.people.find(x => x.id === new URLSearchParams(location.search).get('open'));
        if (linked) LAMP.open(linked);
    </script>
@endsection
