@extends('layouts.app')

{{--
    Briefing: the Executive Office home page (ExecutiveController::dashboard).
    A short summary of the same placeholder numbers as Analytics ($units). Everything is worked out
    by the script from $units, so the Briefing always agrees with Analytics:
      - the summary sentence, the four tiles (with a 4-year sparkline each)
      - "Needs your attention": three cards, each opening a drawer with the details
      - "Units at a glance": a card per unit, each opening a drawer with its breakdown
    "Ask iARIS" opens the AI button with the question filled in.
--}}

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    // "Br. Edmundo" / "Dr. Teodosia": the honorific from the Executive Office list, then the first name
    $firstName = \Illuminate\Support\Str::before(auth()->user()->name, ' ');
    $listed = collect($executives)->first(fn ($p) => str_contains($p['name'], auth()->user()->name));
    $honorific = $listed && preg_match('/^(\S+\.)\s/', $listed['name'], $m) ? $m[1] . ' ' : '';
    $role = auth()->user()->roleInfo()[0];
    $weekLabel = now()->startOfWeek()->format('M j') . '–' . now()->endOfWeek()->format('M j, Y');
@endphp

@section('title', 'iARIS — Briefing')
@section('page-breadcrumb', 'Briefing')
@section('page-title', "{$greeting}, {$honorific}{$firstName}")
@section('page-subtitle', "{$role} · Admissions briefing for AY {$academicYear}")

@if (in_array(auth()->user()->role, ['president', 'chancellor']))
    @include('partials.president-workspace', ['presidentPage' => 'briefing', 'presidentLabel' => 'Briefing'])
    @section('page-actions')
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 no-print">
        <span class="badge rounded-pill bg-body-tertiary text-body-secondary border d-inline-flex align-items-center gap-1 px-3"><i class="bi bi-eye"></i> Read-only view</span>
        <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print briefing</button>
    </div>

    @endsection
@endif

@section('content')
    @unless (in_array(auth()->user()->role, ['president', 'chancellor']))
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 no-print">
        <span class="badge rounded-pill bg-body-tertiary text-body-secondary border d-inline-flex align-items-center gap-1 px-3"><i class="bi bi-eye"></i> Read-only view</span>
        <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print briefing</button>
    </div>

    @endunless

    {{-- This week's summary --}}
    <section class="card border-0 shadow-sm rounded-4 bg-iaris text-white mb-4" aria-label="Summary">
        {{-- Text on the left, the big number on the right (stacked on phones) --}}
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center gap-4">
            <div class="flex-grow-1">
                <div class="small fw-bold text-uppercase tracking-wide text-iaris-pale mb-2"><i class="bi bi-stars me-1"></i> This week's briefing · {{ $weekLabel }}</div>
                <h2 class="fs-5 fw-bold mb-3" id="briefHeadline"></h2>
                <div class="d-flex flex-wrap gap-2 mb-3" id="briefPoints"></div>
                <div class="small text-white-50">Placeholder data until the numbers come from iARIS. Summaries describe the data; decisions stay with university leadership.</div>
            </div>
            <div class="text-md-center px-md-3 flex-shrink-0">
                <div class="display-5 fw-bold font-brand" id="briefBig"></div>
                <div class="small text-white-50">applicants this AY</div>
            </div>
        </div>
    </section>

    {{-- Four tiles, each with a 4-year sparkline --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" id="kpis"></div>

    {{-- Needs your attention --}}
    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
        <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-bell text-warning me-1"></i> Needs your attention</h2>
        <span class="small text-body-secondary">Click a card to see the details</span>
    </div>
    <div class="row row-cols-1 row-cols-lg-3 g-3 mb-4" id="attention"></div>

    {{-- Units at a glance --}}
    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
        <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-building text-primary me-1"></i> Units at a glance</h2>
        <span class="small text-body-secondary">Click a unit for its breakdown</span>
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-2" id="unitCards"></div>
    <div class="d-flex flex-wrap gap-3 small text-body-secondary mb-4">
        <span><i class="bi bi-square-fill me-1" style="color: #E8A0A0;"></i>Not yet tested</span>
        <span><i class="bi bi-square-fill me-1" style="color: #F0C27B;"></i>Tested, not paid</span>
        <span><i class="bi bi-square-fill me-1" style="color: #2558D9;"></i>Paid</span>
    </div>

    <div class="row g-4">
        {{-- Reports from IATO --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Reports from IATO</h2>
                    <a href="{{ url('/executive/reports') }}" class="small fw-bold text-decoration-none">All reports →</a>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @foreach ($reports as $r)
                        <a href="{{ url('/executive/reports') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
                            <span class="rounded-3 bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-file-earmark-text"></i></span>
                            <div class="flex-grow-1">
                                <div class="{{ $r['status'] === 'new' ? 'fw-bold' : 'fw-semibold' }} small">{{ $r['title'] }}</div>
                                <div class="small text-body-secondary">From Renegado, Randolph · {{ \Illuminate\Support\Carbon::parse($r['sent'])->format('M j, Y') }}</div>
                            </div>
                            <span class="badge rounded-pill {{ $r['status'] === 'new' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-tertiary text-body-secondary' }}">{{ $r['status'] === 'new' ? 'New' : 'Read' }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ask iARIS: opens the AI button with the question --}}
        <div class="col-12 col-xl-5 no-print">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <h2 class="fs-6 fw-bold mb-1"><i class="bi bi-stars text-primary me-1"></i> Ask iARIS</h2>
                    <p class="small text-body-secondary">Get a quick answer about the numbers. It reads institution-level data only and never changes anything.</p>
                    <div class="vstack gap-2">
                        @foreach ([['bi-graph-up-arrow', 'Summarize enrollment trends'], ['bi-arrow-up-right', 'Which unit is growing fastest?'], ['bi-funnel', 'What happened to conversion?']] as [$icon, $question])
                            <button type="button" class="btn btn-light border text-start fw-semibold small" data-ask="{{ $question }}"><i class="bi {{ $icon }} text-primary me-2"></i>{{ $question }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details drawer (a "Needs your attention" card or a unit) --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="detailDrawer" aria-labelledby="detailTitle" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start p-4">
            <div>
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="detailTitle"></h2>
                <div class="small text-white-50" id="detailSub"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4" id="detailBody"></div>
        <div class="border-top p-3">
            <a class="btn btn-primary fw-semibold" id="detailGo" href="{{ url('/executive/analytics') }}">Open in Analytics <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const UNITS = @json($units);
        const YEARS = @json($years);
        const PAID_BY_YEAR = @json($paidByYear);
        const ANALYTICS = @json(url('/executive/analytics'));
        const NAMES = Object.keys(UNITS);
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const pc = (a, b) => (b - a) / a * 100;
        const signed = (v, digits = 1, suffix = '%') => `${v >= 0 ? '+' : '−'}${Math.abs(v).toFixed(digits)}${suffix}`;
        const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // ---- Totals, worked out from the units ----
        const sumBy = key => [0, 1, 2, 3].map(i => NAMES.reduce((s, u) => s + UNITS[u][key][i], 0));
        const app = sumBy('applicants'), enr = sumBy('enrolled'), paid = PAID_BY_YEAR;
        const total = key => NAMES.reduce((s, u) => s + UNITS[u][key], 0);
        const took = total('took'), passed = total('passed'), paidNow = total('paid'), paidPrev = total('paidPrevious');
        const conv = app.map((a, i) => enr[i] / a * 100);
        const dApp = pc(app[2], app[3]), dPaid = pc(paidPrev, paidNow), dEnr = pc(enr[2], enr[3]);
        const dConv = +conv[3].toFixed(1) - +conv[2].toFixed(1);
        const growth = NAMES.map(u => [u, pc(UNITS[u].applicants[2], UNITS[u].applicants[3])]).sort((a, b) => b[1] - a[1]);

        // ---- Summary ----
        $('briefBig').textContent = fm(app[3]);
        $('briefHeadline').innerHTML = `Applicants are up <b>${dApp.toFixed(1)}%</b> to ${fm(app[3])}, and paid reservations rose <b>${dPaid.toFixed(1)}%</b>. Conversion to enrollment ${dConv < 0 ? 'slipped' : 'rose'} to <b>${conv[3].toFixed(1)}%</b>.`;
        $('briefPoints').innerHTML = [
            ['bi-graph-up-arrow', `${esc(growth[0][0])} grew fastest (+${growth[0][1].toFixed(1)}%)`],
            ['bi-person-check', `${fm(enr[3])} enrolled so far`],
            ['bi-hourglass-split', `${fm(passed - paidNow)} exam passers not yet paid`],
        ].map(([icon, text]) => `<span class="badge rounded-pill bg-white bg-opacity-10 border border-white border-opacity-25 fw-semibold py-2 px-3"><i class="bi ${icon} me-1"></i>${text}</span>`).join('');

        // ---- Four tiles with sparklines ----
        function spark(values, color) {
            const min = Math.min(...values), max = Math.max(...values);
            const X = i => 4 + i * (232 / 3), Y = v => 40 - (v - min) / ((max - min) || 1) * 32;
            const pts = values.map((v, i) => `${X(i).toFixed(1)},${Y(v).toFixed(1)}`).join(' ');
            return `<svg viewBox="0 0 240 46" width="100%" height="46" preserveAspectRatio="none" aria-hidden="true"><polyline points="4,46 ${pts} 236,46" fill="${color}14" stroke="none"/><polyline points="${pts}" fill="none" stroke="${color}" stroke-width="2"/><circle cx="${X(3)}" cy="${Y(values[3])}" r="3.5" fill="${color}"/></svg>`;
        }
        $('kpis').innerHTML = [
            ['Total applicants', fm(app[3]), signed(dApp) + ' vs last AY', dApp, app, '#00674F'],
            ['Paid reservations', fm(paidNow), signed(dPaid) + ' vs last AY', dPaid, paid, '#2558D9'],
            ['Enrolled', fm(enr[3]), signed(dEnr) + ' vs last AY', dEnr, enr, '#7C3AED'],
            ['Applied → Enrolled', conv[3].toFixed(1) + '%', signed(dConv, 1, '') + ' pts vs last AY', dConv, conv, '#B96000'],
        ].map(([label, value, change, d, series, color]) => `
            <div class="col">
                <a href="${ANALYTICS}" class="card border-0 shadow-sm rounded-4 h-100 text-decoration-none text-body" title="Open in Analytics">
                    <div class="card-body pb-2">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label} <i class="bi bi-box-arrow-up-right ms-1"></i></div>
                        <div class="fs-3 fw-bold">${value}</div>
                        <span class="small fw-semibold ${d >= 0 ? 'text-success' : 'text-danger'}"><i class="bi bi-arrow-${d >= 0 ? 'up' : 'down'}"></i> ${change}</span>
                        <div class="mt-2">${spark(series, color)}</div>
                        <div class="d-flex justify-content-between small text-body-secondary"><span>${YEARS[0]}</span><span>${YEARS[3]}</span></div>
                    </div>
                </a>
            </div>`).join('');

        // ---- Drawer helpers ----
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('detailDrawer'));
        const stats = items => `<div class="row row-cols-2 row-cols-sm-4 g-2 mb-3">${items.map(([l, v, tone]) => `<div class="col"><div class="rounded-3 bg-body-tertiary p-2 h-100"><div class="small text-body-secondary">${l}</div><div class="fw-bold ${tone ? 'text-' + tone : ''}">${v}</div></div></div>`).join('')}</div>`;
        const section = title => `<div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-1 mt-4 mb-2">${title}</div>`;
        const facts = items => `<ul class="small ps-3 mb-0">${items.map(x => `<li class="mb-1">${x}</li>`).join('')}</ul>`;
        const table = (head, rows) => `<div class="table-responsive"><table class="table table-sm small mb-0"><thead><tr>${head.map((h, i) => `<th class="${i ? 'text-end' : ''} text-body-secondary fw-semibold">${h}</th>`).join('')}</tr></thead><tbody>${rows.map(r => `<tr>${r.map((c, i) => `<td class="${i ? 'text-end' : 'fw-semibold'}">${c}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
        const bars = (rows, format, color) => {
            const max = Math.max(...rows.map(r => Math.abs(r[1]))) || 1;
            return rows.map(([label, v]) => `<div class="d-flex align-items-center gap-2 small mb-1"><span style="width: 90px;">${esc(label)}</span><div class="flex-grow-1 bg-body-tertiary rounded" style="height: 10px;"><div class="rounded" style="height: 10px; width: ${Math.max(3, Math.abs(v) / max * 100)}%; background: ${color};"></div></div><span class="fw-bold text-end" style="width: 56px;">${format(v)}</span></div>`).join('');
        };
        // A 4-year line, labelled (values in the series' own unit)
        function line(series, color, label) {
            return series.map(([name, values, c]) => {
                const min = Math.min(...values) * 0.95, max = Math.max(...values) * 1.05;
                return { name, values, c, min, max };
            }).reduce((svg, s, k, all) => {
                const min = Math.min(...all.map(x => x.min)), max = Math.max(...all.map(x => x.max));
                const X = i => 40 + i * 130, Y = v => 130 - (v - min) / ((max - min) || 1) * 100;
                return svg + `<polyline points="${s.values.map((v, i) => `${X(i)},${Y(v).toFixed(1)}`).join(' ')}" fill="none" stroke="${s.c}" stroke-width="2"/>`
                    + s.values.map((v, i) => `<circle cx="${X(i)}" cy="${Y(v).toFixed(1)}" r="4" fill="#fff" stroke="${s.c}" stroke-width="2"/><text x="${X(i)}" y="${(Y(v) - 9).toFixed(1)}" text-anchor="middle" font-size="11" font-weight="700" fill="${s.c}">${label(v)}</text>`).join('');
            }, `<svg viewBox="0 0 470 160" width="100%" role="img" aria-label="Four-year trend">${YEARS.map((y, i) => `<text x="${40 + i * 130}" y="155" text-anchor="middle" font-size="11" fill="#6B7C77">${y}</text>`).join('')}`) + '</svg>';
        }
        // go: where "Open in Analytics" lands (#overview, or #unit=… for one unit)
        function openDrawer(title, sub, html, go = '#overview') {
            $('detailGo').href = ANALYTICS + go;
            $('detailTitle').textContent = title;
            $('detailSub').textContent = sub;
            $('detailBody').innerHTML = html + '<p class="small text-body-secondary mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>These describe the data; decisions stay with university leadership.</p>';
            drawer.show();
        }

        // ---- Needs your attention ----
        const unpaid = passed - paidNow;
        const ATTENTION = [
            {
                tone: dConv < 0 ? 'danger' : 'success', icon: 'bi-funnel',
                title: `Conversion ${dConv < 0 ? 'slipped' : 'rose'} to ${conv[3].toFixed(1)}%`,
                text: `${dConv < 0 ? 'Down' : 'Up'} ${Math.abs(dConv).toFixed(1)} pts from ${conv[2].toFixed(1)}%. Applicants grew faster than enrollment.`,
                open() {
                    const rows = NAMES.map(u => { const d = UNITS[u], c0 = d.enrolled[2] / d.applicants[2] * 100, c1 = d.enrolled[3] / d.applicants[3] * 100; return [u, c0, c1, +c1.toFixed(1) - +c0.toFixed(1)]; }).sort((a, b) => a[3] - b[3]);
                    openDrawer(this.title, `Applied → enrolled · ${YEARS[3]} vs ${YEARS[2]} · ${signed(dConv, 1, '')} pts`,
                        stats([['This AY', conv[3].toFixed(1) + '%'], ['Last AY', conv[2].toFixed(1) + '%'], ['Applicants', signed(dApp), 'success'], ['Enrolled', signed(dEnr), 'success']])
                        + section('What happened') + facts([
                            `Applicants grew <b>${dApp.toFixed(1)}%</b> (${fm(app[2])} → ${fm(app[3])}), but enrolled grew <b>${dEnr.toFixed(1)}%</b> (${fm(enr[2])} → ${fm(enr[3])}).`,
                            `Over four years: ${conv.map(c => c.toFixed(1) + '%').join(' → ')}.`,
                            `The biggest change is in <b>${esc(rows[0][0])}</b> (${rows[0][1].toFixed(1)}% → ${rows[0][2].toFixed(1)}%).`,
                            `${fm(app[3] - enr[3])} of this year's ${fm(app[3])} applicants have not enrolled yet. Enrollment is still open, so this can still move.`])
                        + section('Conversion over four years') + line([['Conversion', conv, '#B96000']], '#B96000', v => v.toFixed(1) + '%')
                        + section('By unit · change in points') + table(['Unit', YEARS[2], YEARS[3], 'Change'], rows.map(r => [esc(r[0]), r[1].toFixed(1) + '%', r[2].toFixed(1) + '%', signed(r[3], 1, '')])));
                },
            },
            {
                tone: 'warning', icon: 'bi-hourglass-split',
                title: `${fm(unpaid)} exam passers haven't paid`,
                text: `${(unpaid / passed * 100).toFixed(1)}% of those who passed haven't reserved a slot, the biggest drop in the funnel.`,
                open() {
                    const stages = [['Applied', app[3]], ['Took exam', took], ['Passed', passed], ['Paid', paidNow], ['Enrolled', enr[3]]];
                    const lost = stages.slice(1).map((st, k) => [`${stages[k][0]} → ${st[0]}`, stages[k][1] - st[1], (stages[k][1] - st[1]) / stages[k][1] * 100]);
                    const rows = NAMES.map(u => { const d = UNITS[u]; return [u, d.passed, d.paid, d.passed - d.paid, (d.passed - d.paid) / d.passed * 100]; }).sort((a, b) => b[4] - a[4]);
                    openDrawer(this.title, `${YEARS[3]} · ${(unpaid / passed * 100).toFixed(1)}% of ${fm(passed)} applicants who passed`,
                        stats([['Passed', fm(passed)], ['Paid', fm(paidNow)], ['Not yet paid', fm(unpaid), 'danger'], ['Pass → paid', (paidNow / passed * 100).toFixed(1) + '%']])
                        + section('What happened') + facts([
                            `<b>${esc(rows[0][0])}</b> has the highest share of unpaid passers (${rows[0][4].toFixed(1)}%). <b>${esc(rows.at(-1)[0])}</b> has the lowest (${rows.at(-1)[4].toFixed(1)}%).`,
                            `Paying the reservation fee is the step that turns a passer into a likely enrollee.`])
                        + section('Where applicants drop off') + table(['Step', 'Lost', 'Drop'], lost.map(r => [r[0], fm(r[1]), r[2].toFixed(1) + '%']))
                        + section('Unpaid passers by unit') + bars(rows.map(r => [UNITS[r[0]].short, r[4]]), v => v.toFixed(1) + '%', '#F0A44B')
                        + '<div class="mt-2"></div>' + table(['Unit', 'Passed', 'Paid', 'Not yet paid'], rows.map(r => [esc(r[0]), fm(r[1]), fm(r[2]), fm(r[3])])));
                },
            },
            {
                tone: 'success', icon: 'bi-graph-up-arrow',
                title: `${growth[0][0]} up ${growth[0][1].toFixed(1)}%`,
                text: `${growth[1][0]} follows at +${growth[1][1].toFixed(1)}%. Adult-learner programs are growing fastest.`,
                open() {
                    const rows = NAMES.map(u => { const a = UNITS[u].applicants; return [u, a[2], a[3], pc(a[2], a[3]), pc(a[0], a[3])]; }).sort((a, b) => b[3] - a[3]);
                    const top = rows[0][0];
                    openDrawer(this.title, `Growth by unit · ${YEARS[3]} vs ${YEARS[2]}`,
                        stats([[`${UNITS[top].short} now`, fm(rows[0][2])], ['Last AY', fm(rows[0][1])], [`Since ${YEARS[0]}`, signed(rows[0][4], 0), 'success'], ['All units', signed(dApp), 'success']])
                        + section('What happened') + facts([
                            `<b>${esc(top)}</b> grew fastest (${signed(rows[0][3])}), followed by <b>${esc(rows[1][0])}</b> (${signed(rows[1][3])}).`,
                            `Slowest growth: <b>${esc(rows.at(-1)[0])}</b> (${signed(rows.at(-1)[3])}).`])
                        + section('Growth vs last AY') + bars(rows.map(r => [UNITS[r[0]].short, r[3]]), v => signed(v), '#00674F')
                        + section(`${esc(top)} over four years`) + line([[top, UNITS[top].applicants, '#00674F']], '#00674F', fm)
                        + section('All units') + table(['Unit', YEARS[2], YEARS[3], '3-year'], rows.map(r => [esc(r[0]), fm(r[1]), fm(r[2]), signed(r[4], 0)])),
                        '#unit=' + encodeURIComponent(top));
                },
            },
        ];
        // Settings → Briefing: which "Needs your attention" cards and units to show (saved in this browser)
        const PREFS = window.IARIS_SETTINGS?.briefing || {};
        ATTENTION.forEach((a, i) => a.hidden = PREFS[['conversion', 'dropoff', 'growth'][i]] === false);
        $('attention').innerHTML = ATTENTION.map((a, i) => a.hidden ? '' : `
            <div class="col">
                <button type="button" class="card border-0 border-start border-4 border-${a.tone} shadow-sm rounded-4 h-100 w-100 text-start" data-attention="${i}">
                    <div class="card-body d-flex gap-3">
                        <span class="rounded-circle bg-${a.tone}-subtle text-${a.tone}-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi ${a.icon}"></i></span>
                        <div><div class="fw-bold">${esc(a.title)}</div><div class="small text-body-secondary">${esc(a.text)}</div><div class="small fw-semibold text-primary mt-1">See the details →</div></div>
                    </div>
                </button>
            </div>`).join('');
        document.querySelectorAll('[data-attention]').forEach(b => b.addEventListener('click', () => ATTENTION[b.dataset.attention].open()));

        // ---- Units at a glance ----
        $('unitCards').innerHTML = NAMES.filter(u => PREFS['unit:' + u] !== false).map(u => {
            const d = UNITS[u], g = pc(d.applicants[2], d.applicants[3]), now = d.applicants[3];
            const parts = [[now - d.took, '#E8A0A0', 'Not yet tested'], [d.took - d.paid, '#F0C27B', 'Tested, not paid'], [d.paid, '#2558D9', 'Paid']];
            return `
            <div class="col">
                <button type="button" class="card border-0 shadow-sm rounded-4 h-100 w-100 text-start" data-unit="${esc(u)}" aria-label="Open ${esc(u)} breakdown">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2"><div><div class="fw-bold">${esc(u)}</div><div class="small text-body-secondary">${(now / app[3] * 100).toFixed(1)}% of all applicants</div></div><span class="small fw-semibold text-primary">Details →</span></div>
                        <div class="fs-4 fw-bold my-1">${fm(now)} <span class="fs-6 ${g >= 0 ? 'text-success' : 'text-danger'}">${signed(g)}</span></div>
                        <div class="progress-stacked mb-2" style="height: 8px;">${parts.map(([n, c, l]) => `<div class="progress" style="width: ${n / now * 100}%; height: 8px;" title="${l}: ${fm(n)}"><div class="progress-bar" style="background: ${c};"></div></div>`).join('')}</div>
                        <div class="d-flex justify-content-between small text-body-secondary"><span>Paid <b class="text-body">${fm(d.paid)}</b></span><span>Enrolled <b class="text-body">${fm(d.enrolled[3])}</b></span><span>Conv. <b class="text-body">${(d.enrolled[3] / now * 100).toFixed(1)}%</b></span></div>
                    </div>
                </button>
            </div>`;
        }).join('');
        document.querySelectorAll('[data-unit]').forEach(b => b.addEventListener('click', () => {
            const u = b.dataset.unit, d = UNITS[u], now = d.applicants[3];
            openDrawer(u, `${YEARS[3]} · ${(now / app[3] * 100).toFixed(1)}% of all applicants · ${signed(pc(d.applicants[2], now))} vs last AY`,
                stats([['Applicants', fm(now)], ['Paid', fm(d.paid)], ['Enrolled', fm(d.enrolled[3])], ['Conversion', (d.enrolled[3] / now * 100).toFixed(1) + '%']])
                + section('Four-year trend') + line([['Applicants', d.applicants, '#00674F'], ['Enrolled', d.enrolled, '#2558D9']], '', fm)
                + '<div class="d-flex gap-3 small text-body-secondary"><span><i class="bi bi-square-fill me-1" style="color:#00674F"></i>Applicants</span><span><i class="bi bi-square-fill me-1" style="color:#2558D9"></i>Enrolled</span></div>'
                + section('Funnel this AY') + table(['Stage', 'Count', '% of applied'], [['Applied', now], ['Took exam', d.took], ['Passed', d.passed], ['Paid', d.paid], ['Enrolled', d.enrolled[3]]].map(([s, n]) => [s, fm(n), (n / now * 100).toFixed(1) + '%'])),
                '#unit=' + encodeURIComponent(u));
        }));

        // ---- Ask iARIS: open the AI button and ask ----
        document.querySelectorAll('[data-ask]').forEach(b => b.addEventListener('click', () => {
            const panel = $('aiWidgetPanel');
            if (!panel) return;
            panel.classList.remove('d-none');
            $('aiWidgetInput').value = b.dataset.ask;
            $('aiWidgetForm').requestSubmit();
        }));
    </script>
@endsection
