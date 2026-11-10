@extends('layouts.app')

{{--
    Briefing: the home page of a Dean, a Program Chair or the IS Principal (CollegeController::dashboard).
    $word is what the groups are called ("program", or "level" for the Integrated School); $whole is what you see
    ("your college", "your school", "iPACE and ETEEAP"). The iPACE Director's programs each have a 'unit' (iPACE or ETEEAP):
    they are grouped by unit, and $profiles adds the Learner profile.
    Same layout as the Executive Briefing, for the programs in $programs. Everything is worked out
    by the script from $programs, so the Briefing always agrees with Analytics:
      - the summary sentence, the four tiles (with a 4-year sparkline each)
      - "Needs your attention": three cards, each opening a drawer with the details
      - "Programs at a glance": a card per program, each opening a drawer with its breakdown
    "Ask iARIS" opens the AI button with the question filled in.
--}}

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $weekLabel = now()->startOfWeek()->format('M j') . '–' . now()->endOfWeek()->format('M j, Y');
@endphp

@section('title', 'iARIS — Briefing')
@section('page-breadcrumb', 'Briefing')
@section('page-title', "{$greeting}, {$greetName}")
@section('page-subtitle', ($isChair ? "{$label} · " : '') . $college['subtitle'] . ' · AY ' . \App\Support\ApplicantRecords::academicYear())

@if ((auth()->user()->isDirector() || auth()->user()->isGradChair() || auth()->user()->role === 'dean'))
    @include('partials.director-workspace', ['directorPage' => 'dashboard', 'directorLabel' => 'Briefing'])
@endif

@if ((auth()->user()->isDirector() || auth()->user()->isGradChair() || auth()->user()->role === 'dean'))
@section('page-actions')
    <div class="d-flex flex-wrap justify-content-end gap-2 no-print">
        <span class="badge rounded-pill bg-body-tertiary text-body-secondary border d-inline-flex align-items-center gap-1 px-3"><i class="bi bi-eye"></i> Read-only view</span>
        <button type="button" class="btn btn-sm btn-primary fw-semibold" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print briefing</button>
    </div>
@endsection
@endif

@section('content')
    @unless ((auth()->user()->isDirector() || auth()->user()->isGradChair() || auth()->user()->role === 'dean'))
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
                <div class="small fw-bold text-uppercase tracking-wide text-iaris-pale mb-2"><i class="bi bi-stars me-1"></i> This week's briefing · {{ $label }} · {{ $weekLabel }}</div>
                <h2 class="fs-5 fw-bold mb-3" id="briefHeadline"></h2>
                <div class="d-flex flex-wrap gap-2 mb-3" id="briefPoints"></div>
                <div class="small text-white-50">Counts reflect imported applicant records where available. You see {{ $isChair ? 'your program' : $whole }} only. Summaries describe the data; decisions stay with you.</div>
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
        <span class="small text-body-secondary">Click a card for the full breakdown</span>
    </div>
    <div class="row row-cols-1 row-cols-lg-3 g-3 mb-4" id="attention"></div>

    {{-- Learner profile: one card per unit, counts only (iPACE Director) --}}
    @if ($profiles)
        <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
            <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-person-vcard text-primary me-1"></i> Learner profile</h2>
            <span class="small text-body-secondary">Counts only · {{ $years[3] }}</span>
        </div>
        <div class="row row-cols-1 row-cols-lg-2 g-3 mb-4">
            @foreach ($profiles as $unitName => [$title, $groups, $note])
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-4">
                            <h3 class="fs-6 fw-bold mb-3">{{ $unitName }} <span class="small fw-normal text-body-secondary">· {{ $title }}</span></h3>
                            @foreach ($groups as [$group, $parts])
                                @php($sum = array_sum(array_column($parts, 1)))
                                <div class="small fw-semibold mb-1">{{ $group }}</div>
                                <div class="mb-3">
                                    @foreach ($parts as $i => [$partLabel, $count])
                                        <div class="d-flex align-items-center gap-2 small mb-1">
                                            <span style="width: 170px;">{{ $partLabel }}</span>
                                            <div class="flex-grow-1 bg-body-tertiary rounded" style="height: 10px;"><div class="rounded" style="height: 10px; width: {{ ($sum ? round($count / $sum * 100, 1) : 0) }}%; background: {{ ['#00674F', '#4DAB8E', '#2558D9', '#B96000'][$i % 4] }};"></div></div>
                                            <span class="fw-bold text-end text-nowrap" style="width: 84px;">{{ number_format($count) }} · {{ ($sum ? round($count / $sum * 100) : 0) }}%</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            <p class="small text-body-secondary mb-0">{{ $note }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Programs (or levels) at a glance --}}
    <div class="d-flex flex-wrap align-items-baseline gap-2 mb-2">
        <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-layers text-primary me-1"></i> {{ ucfirst($word) }}s at a glance</h2>
        <span class="small text-body-secondary">Click a {{ $word }} for its breakdown</span>
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-2" id="programCards"></div>
    <div class="d-flex flex-wrap gap-3 small text-body-secondary mb-4">
        <span><i class="bi bi-square-fill me-1" style="color: #E8A0A0;"></i>Pending</span>
        <span><i class="bi bi-square-fill me-1" style="color: #F0C27B;"></i>For Exam / Assessment</span>
        <span><i class="bi bi-square-fill me-1" style="color: #2558D9;"></i>Paid</span>
    </div>

    <div class="row g-4">
        {{-- Reports from IATO --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Reports from IATO</h2>
                    <a href="{{ $base }}/reports" class="small fw-bold text-decoration-none">All reports →</a>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @foreach ($reports as $r)
                        <a href="{{ $base }}/reports" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
                            <span class="rounded-3 bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi bi-file-earmark-text"></i></span>
                            <div class="flex-grow-1">
                                <div class="{{ $r['status'] === 'new' ? 'fw-bold' : 'fw-semibold' }} small">{{ $r['title'] }}</div>
                                <div class="small text-body-secondary">From {{ $r['by'] }} · {{ \Illuminate\Support\Str::before($r['at'], ' ·') }}</div>
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
                    <p class="small text-body-secondary">Quick answers about {{ $label }}. It never changes anything.</p>
                    <div class="vstack gap-2">
                        @foreach ([['bi-graph-up-arrow', 'Summarize enrollment trends'], ['bi-trophy', "Which {$word} is largest?"], ['bi-funnel', 'What happened to conversion?']] as [$icon, $question])
                            <button type="button" class="btn btn-light border text-start fw-semibold small" data-ask="{{ $question }}"><i class="bi {{ $icon }} text-primary me-2"></i>{{ $question }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details drawer (a "Needs your attention" card or a program) --}}
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
            <a class="btn btn-primary fw-semibold" id="detailGo" href="{{ $base }}/analytics"></a>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const PROGRAMS = @json($programs);
        const YEARS = @json($years);
        const LABEL = @json($label);
        const ANALYTICS = @json("{$base}/analytics");
        const APPLICANTS = @json("{$base}/applicants");
        // "program" or "level"; "college" or "school"
        const WORD = @json($word), WHOLE = @json($whole);
        // The units (iPACE and ETEEAP for the iPACE Director; none for a college)
        const UNITS = [...new Set(Object.values(PROGRAMS).map(p => p.unit).filter(Boolean))];
        // A program's name, with its unit when there are units ("BS Nursing · ETEEAP")
        const nameOf = c => PROGRAMS[c].unit ? `${PROGRAMS[c].name} · ${PROGRAMS[c].unit}` : PROGRAMS[c].name;
        const CODES = Object.keys(PROGRAMS);
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const pc = (a, b) => a > 0 ? (b - a) / Math.max(1, a) * 100 : 0;
        const signed = (v, digits = 1, suffix = '%') => `${v >= 0 ? '+' : '−'}${Math.abs(v).toFixed(digits)}${suffix}`;
        const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // ---- Totals, worked out from the programs ----
        // funnel: Applied, Took exam, Passed, Paid, Enrolled. status: Pending, For Exam, Paid.
        const sumBy = key => [0, 1, 2, 3].map(i => CODES.reduce((s, c) => s + PROGRAMS[c][key][i], 0));
        const app = sumBy('applicants'), enr = sumBy('enrolled');
        const stage = i => CODES.reduce((s, c) => s + PROGRAMS[c].funnel[i], 0);
        const took = stage(1), passed = stage(2), paidNow = stage(3);
        const paidPrev = CODES.reduce((s, c) => s + PROGRAMS[c].paidLastYear, 0);
        // Paid reservations for the two years before last are not in the data yet: estimated for the sparkline only
        const paid = [Math.round(paidPrev * 0.86), Math.round(paidPrev * 0.93), paidPrev, paidNow];
        const conv = app.map((a, i) => enr[i] / Math.max(1, a) * 100);
        const dApp = pc(app[2], app[3]), dPaid = pc(paidPrev, paidNow), dEnr = pc(enr[2], enr[3]);
        const dConv = +conv[3].toFixed(1) - +conv[2].toFixed(1);
        // Programs, largest first; t = applicants this AY
        const rows = CODES.map(c => { const p = PROGRAMS[c], t = p.applicants[3]; return { c, name: nameOf(c), s: p.status, t, pending: p.status[0] / Math.max(1, t) * 100, paidRate: p.status[2] / Math.max(1, t) * 100 }; })
            .sort((a, b) => b.t - a.t);

        // ---- Summary ----
        $('briefBig').textContent = fm(app[3]);
        $('briefHeadline').innerHTML = `Applicants are ${dApp >= 0 ? 'up' : 'down'} <b>${Math.abs(dApp).toFixed(1)}%</b> to ${fm(app[3])}, and paid reservations ${dPaid >= 0 ? 'rose' : 'fell'} <b>${Math.abs(dPaid).toFixed(1)}%</b>. Conversion to enrollment is <b>${conv[3].toFixed(1)}%</b> (${signed(dConv, 1, '')} pts).`;
        $('briefPoints').innerHTML = [
            ['bi-trophy', CODES.length > 1 ? `${esc(rows[0].name)} is your largest ${WORD} (${fm(rows[0].t)})` : `${fm(rows[0].t)} applicants this AY`],
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
        const table = (head, body, highlight = -1) => `<div class="table-responsive"><table class="table table-sm small mb-0"><thead><tr>${head.map((h, i) => `<th class="${i ? 'text-end' : ''} text-body-secondary fw-semibold">${h}</th>`).join('')}</tr></thead><tbody>${body.map((r, k) => `<tr class="${k === highlight ? 'table-warning' : ''}">${r.map((c, i) => `<td class="${i ? 'text-end' : 'fw-semibold'}">${c}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
        const bars = (items, format, color) => {
            const max = Math.max(...items.map(r => Math.abs(r[1]))) || 1;
            return items.map(([label, v]) => `<div class="d-flex align-items-center gap-2 small mb-1"><span class="text-truncate" style="width: 150px;" title="${esc(label)}">${esc(label)}</span><div class="flex-grow-1 bg-body-tertiary rounded" style="height: 10px;"><div class="rounded" style="height: 10px; width: ${Math.max(3, Math.abs(v) / max * 100)}%; background: ${color};"></div></div><span class="fw-bold text-end" style="width: 56px;">${format(v)}</span></div>`).join('');
        };
        // Pending / For Exam / Paid as one stacked bar
        const statusBar = s => {
            const t = s[0] + s[1] + s[2];
            return `<div class="progress-stacked" style="height: 8px;">${[[s[0], '#E8A0A0', 'Pending'], [s[1], '#F0C27B', 'For Exam'], [s[2], '#2558D9', 'Paid']].map(([n, c, l]) => `<div class="progress" style="width: ${n / Math.max(1, t) * 100}%; height: 8px;" title="${l}: ${fm(n)}"><div class="progress-bar" style="background: ${c};"></div></div>`).join('')}</div>`;
        };
        // A 4-year line, labelled
        function line(values, color, label) {
            const min = Math.min(...values) * 0.97, max = Math.max(...values) * 1.02;
            const X = i => 40 + i * 130, Y = v => 130 - (v - min) / ((max - min) || 1) * 100;
            return `<svg viewBox="0 0 470 160" width="100%" role="img" aria-label="Four-year trend">${YEARS.map((y, i) => `<text x="${X(i)}" y="155" text-anchor="middle" font-size="11" fill="#6B7C77">${y}</text>`).join('')}`
                + `<polyline points="${values.map((v, i) => `${X(i)},${Y(v).toFixed(1)}`).join(' ')}" fill="none" stroke="${color}" stroke-width="2"/>`
                + values.map((v, i) => `<circle cx="${X(i)}" cy="${Y(v).toFixed(1)}" r="4" fill="#fff" stroke="${color}" stroke-width="2"/><text x="${X(i)}" y="${(Y(v) - 9).toFixed(1)}" text-anchor="middle" font-size="11" font-weight="700" fill="${color}">${label(v)}</text>`).join('') + '</svg>';
        }
        // go: [link text, url] for the button at the bottom of the drawer
        function openDrawer(title, sub, html, go = ['Open in Analytics', ANALYTICS + '#overview']) {
            $('detailGo').innerHTML = `${go[0]} <i class="bi bi-arrow-right ms-1"></i>`;
            $('detailGo').href = go[1];
            $('detailTitle').textContent = title;
            $('detailSub').textContent = sub;
            $('detailBody').innerHTML = html + `<p class="small text-body-secondary mt-4 mb-0"><i class="bi bi-info-circle me-1"></i>Counts reflect imported applicant records where available. You see ${CODES.length > 1 ? WHOLE : 'your program'} only.</p>`;
            drawer.show();
        }

        // ---- Needs your attention ----
        const stages = [['Applied', app[3]], ['Took exam', took], ['Passed', passed], ['Paid', paidNow], ['Enrolled', enr[3]]];
        const lost = stages.slice(1).map((st, k) => [`${stages[k][0]} → ${st[0]}`, stages[k][1] - st[1], (stages[k][1] - st[1]) / Math.max(1, stages[k][1]) * 100]);
        const biggest = lost.reduce((a, b) => b[2] > a[2] ? b : a);
        const byPending = rows.slice().sort((a, b) => b.pending - a.pending);
        const ATTENTION = [
            {
                tone: dConv < 0 ? 'danger' : 'success', icon: 'bi-funnel',
                title: `Conversion ${dConv < 0 ? 'slipped' : 'improved'} to ${conv[3].toFixed(1)}%`,
                text: `${dConv >= 0 ? 'Up' : 'Down'} ${Math.abs(dConv).toFixed(1)} pts from ${conv[2].toFixed(1)}%. Applicants ${signed(dApp)}, enrolled ${signed(dEnr)}.`,
                open() {
                    openDrawer(this.title, `Applied → enrolled · ${LABEL} · ${signed(dConv, 1, '')} pts vs last AY`,
                        stats([['This AY', conv[3].toFixed(1) + '%'], ['Last AY', conv[2].toFixed(1) + '%'], ['Applicants', signed(dApp), dApp >= 0 ? 'success' : 'danger'], ['Enrolled', signed(dEnr), dEnr >= 0 ? 'success' : 'danger']])
                        + section('What happened') + facts([
                            `Applicants went from ${fm(app[2])} to ${fm(app[3])} (${signed(dApp)}), and enrolled from ${fm(enr[2])} to ${fm(enr[3])} (${signed(dEnr)}).`,
                            `Over four years conversion went ${conv.map(c => c.toFixed(1) + '%').join(' → ')}.`,
                            `${fm(app[3] - enr[3])} of this year's applicants have not enrolled yet. Enrollment is still open.`])
                        + section('Conversion over four years') + line(conv, '#B96000', v => v.toFixed(1) + '%')
                        + section('Year by year') + table(['Year', 'Applicants', 'Enrolled', 'Conversion'], YEARS.map((y, k) => [y, fm(app[k]), fm(enr[k]), conv[k].toFixed(1) + '%']))
                        // With units: conversion per unit, last AY and this AY
                        + (UNITS.length > 1 ? section('By unit') + table(['Unit', YEARS[2], YEARS[3]], UNITS.map(u => {
                            const codes = CODES.filter(c => PROGRAMS[c].unit === u), sum = (k, i) => codes.reduce((x, c) => x + PROGRAMS[c][k][i], 0);
                            return [u, (sum('enrolled', 2) / Math.max(1, sum('applicants', 2)) * 100).toFixed(1) + '%', (sum('enrolled', 3) / Math.max(1, sum('applicants', 3)) * 100).toFixed(1) + '%'];
                        })) : ''));
                },
            },
            {
                tone: 'warning', icon: 'bi-hourglass-split',
                title: `${fm(passed - paidNow)} exam passers haven't paid`,
                text: `${biggest[0]} is your biggest drop: ${fm(biggest[1])} applicants (${biggest[2].toFixed(1)}%).`,
                open() {
                    const rates = rows.map(r => [r.name, r.paidRate]).sort((a, b) => a[1] - b[1]);
                    openDrawer(this.title, `${YEARS[3]} · ${((passed - paidNow) / Math.max(1, passed) * 100).toFixed(1)}% of ${fm(passed)} who passed`,
                        stats([['Passed', fm(passed)], ['Paid', fm(paidNow)], ['Not yet paid', fm(passed - paidNow), 'danger'], ['Pass → paid', (paidNow / Math.max(1, passed) * 100).toFixed(1) + '%']])
                        + section('What happened') + facts([
                            `<b>${biggest[0]}</b> is where you lose the most applicants: ${fm(biggest[1])} (${biggest[2].toFixed(1)}%).`,
                            ...(rates.length > 1 ? [`<b>${esc(rates[0][0])}</b> has the lowest paid rate (${rates[0][1].toFixed(1)}% of its applicants). <b>${esc(rates.at(-1)[0])}</b> has the highest (${rates.at(-1)[1].toFixed(1)}%).`] : []),
                            'Paying the reservation fee is what turns a passer into a likely enrollee.'])
                        + section('Where applicants drop off') + table(['Step', 'Lost', 'Drop'], lost.map(r => [r[0], fm(r[1]), r[2].toFixed(1) + '%']), lost.indexOf(biggest))
                        + section(`Paid rate by ${WORD}`) + bars(rates, v => v.toFixed(1) + '%', '#2558D9'));
                },
            },
            {
                tone: 'danger', icon: 'bi-clock',
                title: `${byPending[0].name}: ${byPending[0].pending.toFixed(0)}% still Pending`,
                text: `${fm(byPending[0].s[0])} of ${fm(byPending[0].t)} applicants haven't moved past Pending${CODES.length > 1 ? `, the highest share among your ${WORD}s` : ''}.`,
                open() {
                    const top = byPending[0], allPending = rows.reduce((s, r) => s + r.s[0], 0);
                    openDrawer(this.title, `Applicants still at Pending · ${YEARS[3]}`,
                        stats([['Pending', fm(top.s[0]), 'danger'], ['For Exam', fm(top.s[1])], ['Paid', fm(top.s[2])], ['Total', fm(top.t)]])
                        + section('What happened') + facts([
                            `<b>${esc(top.name)}</b> has ${top.pending.toFixed(1)}% of its applicants still Pending${CODES.length > 1 ? `, the highest among your ${WORD}s` : ''}.`,
                            ...(CODES.length > 1 ? [`Across all your ${WORD}s, ${fm(allPending)} applicants are still Pending (${(allPending / Math.max(1, app[3]) * 100).toFixed(1)}%).`] : []),
                            'Pending means the application is in but no exam is scheduled yet. IATO schedules exams; ask them if a ' + WORD + ' needs more slots.'])
                        + section(`Pending share by ${WORD}`) + bars(byPending.map(r => [r.name, r.pending]), v => v.toFixed(1) + '%', '#E8A0A0')
                        + section(`All ${WORD}s`) + table([WORD[0].toUpperCase() + WORD.slice(1), 'Pending', 'For Exam', 'Paid'], rows.map(r => [esc(r.name), fm(r.s[0]), fm(r.s[1]), fm(r.s[2])])),
                        ['Open in Analytics', ANALYTICS + '#programs']);
                },
            },
        ];
        // Settings → Briefing: which "Needs your attention" cards and programs to show (saved in this browser)
        const PREFS = window.IARIS_SETTINGS?.briefing || {};
        ATTENTION.forEach((a, i) => a.hidden = PREFS[['conversion', 'dropoff', 'pending'][i]] === false);
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

        // ---- Programs at a glance ----
        // With units, each unit gets a heading above its programs
        $('programCards').innerHTML = CODES.filter(c => PREFS['unit:' + PROGRAMS[c].name] !== false).map((c, i, shown) => {
            const p = PROGRAMS[c], s = p.status, t = p.applicants[3];
            const heading = p.unit && p.unit !== PROGRAMS[shown[i - 1]]?.unit ? `<div class="col-12 w-100"><div class="fw-bold text-body-secondary border-bottom pb-1">${esc(p.unit)}</div></div>` : '';
            return heading + `
            <div class="col">
                <button type="button" class="card border-0 shadow-sm rounded-4 h-100 w-100 text-start" data-program="${c}" aria-label="Open ${esc(p.name)}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2"><div><div class="fw-bold">${esc(p.name)}</div><div class="small text-body-secondary">${(t / Math.max(1, app[3]) * 100).toFixed(1)}% of your applicants</div></div><span class="small fw-semibold text-primary text-nowrap">Details →</span></div>
                        <div class="fs-4 fw-bold my-1">${fm(t)}</div>
                        <div class="mb-2">${statusBar(s)}</div>
                        <div class="d-flex justify-content-between small text-body-secondary"><span>Pending <b class="text-body">${fm(s[0])}</b></span><span>For Exam <b class="text-body">${fm(s[1])}</b></span><span>Paid <b class="text-body">${fm(s[2])}</b></span></div>
                    </div>
                </button>
            </div>`;
        }).join('');
        document.querySelectorAll('[data-program]').forEach(b => b.addEventListener('click', () => {
            const c = b.dataset.program, p = PROGRAMS[c], s = p.status, t = p.applicants[3];
            const rank = rows.findIndex(r => r.c === c) + 1;
            openDrawer(p.name, `${p.unit || LABEL} · ${(t / Math.max(1, app[3]) * 100).toFixed(1)}% of your applicants · ${YEARS[3]}`,
                stats([['Applicants', fm(t)], ['Pending', fm(s[0])], ['For Exam', fm(s[1])], ['Paid', fm(s[2])]])
                + section('Status') + statusBar(s)
                + `<div class="d-flex flex-wrap gap-3 small text-body-secondary mt-2"><span><i class="bi bi-square-fill me-1" style="color:#E8A0A0"></i>Pending ${(s[0] / Math.max(1, t) * 100).toFixed(1)}%</span><span><i class="bi bi-square-fill me-1" style="color:#F0C27B"></i>For Exam ${(s[1] / Math.max(1, t) * 100).toFixed(1)}%</span><span><i class="bi bi-square-fill me-1" style="color:#2558D9"></i>Paid ${(s[2] / Math.max(1, t) * 100).toFixed(1)}%</span></div>`
                + (CODES.length > 1 ? section(`Compared with your other ${WORD}s`) + facts([
                    `Rank <b>#${rank}</b> of ${CODES.length} by applicants.`,
                    `Paid rate <b>${(s[2] / Math.max(1, t) * 100).toFixed(1)}%</b> vs ${(paidNow / Math.max(1, app[3]) * 100).toFixed(1)}% for ${WHOLE.startsWith('your ') ? 'your whole ' + WHOLE.slice(5) : 'all of ' + WHOLE}.`]) : '')
                + section('Applicants over four years') + line(p.applicants, '#00674F', fm),
                // The iPACE Director's Applicants page shows one unit at a time
                ['See applicants', `${APPLICANTS}?${p.unit === 'ETEEAP' ? 'unit=eteeap&' : ''}program=${encodeURIComponent(c)}`]);
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
