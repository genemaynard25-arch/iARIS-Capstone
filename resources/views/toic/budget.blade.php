@extends('layouts.app')

{{--
    Budget & Maintenance (ToicController::budget): estimated costs, who shoulders each, the maintenance plan,
    and what IATO and TOIC still have to agree on. "Shouldered by" choices are kept in this browser for now.
--}}

@php
    $plan = [
        ['Monthly · security updates', 'Third Saturday, 10 PM to 12 AM. Read-only during the window.'],
        ['Quarterly · security review', 'Access roles, MFA, failed sign-ins, and certificates, with the IATO Super Admin.'],
        ['Twice a year · restore test', 'Restore a backup on a test server to prove it works.'],
        ['Yearly · before admissions season', 'Load test for the March to April peak, when about 42% of applications arrive.'],
    ];
    $agree = [
        ['Who pays for hosting and storage', 'Server space on the DLSL server and growth as records add up each year.'],
        ['Who handles fixes and new features', 'Bug fixes, new roles or reports, and how requests are approved.'],
        ['Response time when iARIS is down', 'Proposed: within 2 hours on school days, especially during admissions season.'],
        ['AI assistant usage limits', 'The AI service is billed by use. A monthly cap keeps the cost predictable.'],
    ];
@endphp

@section('title', 'iARIS — Budget & Maintenance')
@section('page-title', 'Budget & Maintenance')
@section('page-subtitle', 'Estimated costs to run and maintain iARIS, and which office shoulders each one. For planning with IATO.')

@section('content')
    <div class="d-flex justify-content-end mb-3" id="budgetDownload"></div>
    @include('toic.partials.privacy')

    {{-- Totals (worked out by the script) --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" id="totals"></div>

    {{-- Cost items --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 rounded-top-4 d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 pt-4 pb-2">
            <h2 class="fs-6 fw-bold mb-0">Cost items</h2>
            <span class="small text-body-secondary">Estimates in Philippine pesos · change "Shouldered by" as the offices agree</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 small">
                <thead class="table-light"><tr><th class="ps-4 text-body-secondary">Item</th><th class="text-body-secondary">What it covers</th><th class="text-end text-body-secondary">Monthly</th><th class="text-end text-body-secondary">Yearly</th><th class="pe-4 text-body-secondary">Shouldered by</th></tr></thead>
                <tbody id="items"></tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 px-4 pt-4 pb-2"><h2 class="fs-6 fw-bold mb-0">Maintenance plan</h2></div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @foreach ($plan as [$when, $what])
                        <div class="list-group-item d-flex gap-3 px-4 py-3"><i class="bi bi-calendar-check text-primary"></i><div class="small"><div class="fw-semibold">{{ $when }}</div><div class="text-body-secondary">{{ $what }}</div></div></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Things to agree on</h2>
                    <span class="small text-body-secondary">Between IATO and TOIC</span>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @foreach ($agree as [$title, $text])
                        <div class="list-group-item d-flex gap-3 px-4 py-3"><i class="bi bi-question-circle text-warning"></i><div class="small"><div class="fw-semibold">{{ $title }}</div><div class="text-body-secondary">{{ $text }}</div></div></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('toic.partials.kit')
@endsection

@section('scripts')
    <script>
        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const peso = n => '₱' + Math.round(n).toLocaleString('en-US');
        // [item, what it covers, monthly, shouldered by]
        const ITEMS = @json($items);
        const WHO = ['IATO', 'TOIC', 'Shared', 'To be agreed'];
        const KEY = 'iaris.toic.budget';
        try { (JSON.parse(localStorage.getItem(KEY)) || []).forEach((who, i) => { if (ITEMS[i] && WHO.includes(who)) ITEMS[i][3] = who; }); } catch (e) {}

        function draw() {
            const month = ITEMS.reduce((s, x) => s + x[2], 0);
            // Each office pays its own items plus half of the shared ones
            const shared = ITEMS.filter(x => x[3] === 'Shared').reduce((s, x) => s + x[2], 0) / 2;
            const by = who => ITEMS.filter(x => x[3] === who).reduce((s, x) => s + x[2], 0);
            $('totals').innerHTML = [
                ['Total per year', peso(month * 12), `${peso(month)} a month`, 'success'],
                ['IATO shoulders', peso((by('IATO') + shared) * 12), 'a year, incl. half of shared', 'primary'],
                ['TOIC shoulders', peso((by('TOIC') + shared) * 12), 'a year, incl. half of shared', 'info'],
                ['Still to agree', peso(by('To be agreed') * 12), `${ITEMS.filter(x => x[3] === 'To be agreed').length} items`, 'warning'],
            ].map(([label, value, note, tone]) => `<div class="col"><div class="card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div><div class="fs-4 fw-bold">${value}</div><div class="small text-body-secondary">${note}</div></div></div></div>`).join('');
            $('items').innerHTML = ITEMS.map(([item, what, m, who], i) => `<tr>
                <td class="ps-4 fw-semibold">${esc(item)}</td><td class="text-body-secondary">${esc(what)}</td><td class="text-end">${peso(m)}</td><td class="text-end">${peso(m * 12)}</td>
                <td class="pe-4"><select class="form-select form-select-sm" data-item="${i}" aria-label="Who shoulders ${esc(item)}">${WHO.map(w => `<option ${w === who ? 'selected' : ''}>${w}</option>`).join('')}</select></td></tr>`).join('')
                + `<tr class="table-light fw-bold"><td class="ps-4" colspan="2">Total</td><td class="text-end">${peso(month)}</td><td class="text-end">${peso(month * 12)}</td><td class="pe-4"></td></tr>`;
            $('items').querySelectorAll('[data-item]').forEach(sel => sel.addEventListener('change', () => {
                ITEMS[+sel.dataset.item][3] = sel.value;
                try { localStorage.setItem(KEY, JSON.stringify(ITEMS.map(x => x[3]))); } catch (e) {}
                draw();
                toast(`${ITEMS[+sel.dataset.item][0]}: ${sel.value}. (Demo only — saved in this browser.)`);
            }));
        }
        draw();

        $('budgetDownload').appendChild(downloadMenu(() => ({
            title: 'iARIS Budget and Maintenance Estimate', sub: 'Estimates in Philippine pesos · for planning between IATO and TOIC',
            head: ['Item', 'What it covers', 'Monthly', 'Yearly', 'Shouldered by'], rows: ITEMS.map(([a, b, m, w]) => [a, b, peso(m), peso(m * 12), w]),
        }), false));
    </script>
@endsection
