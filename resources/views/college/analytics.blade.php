@extends('layouts.app')

{{--
    Analytics for a Dean, Program Chair or the IS Principal (CollegeController::analytics). As in the mockup
    ($word is "program", or "level" for the Integrated School):
    four tiles, then four tabs — Overview (trend and funnel), Programs (sortable table),
    Compare years, AI insights. Everything is worked out by the script from $programs,
    the same numbers as the Briefing. Tabs follow the address: #overview, #programs, #years, #insights.
    Looks like the admin Analytics & Reports page (public/css/analytics.css): tab bar with a "Showing" chip, four tiles,
    and the Applicant field counts ($fieldCounts).
--}}

@section('title', 'iARIS — Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', "{$label} admissions and enrollment, with AI-generated insights")
@unless (auth()->user()->isDirector())
@section('body-class', 'analytics-page')
@endunless
@push('styles')
    <link href="{{ asset('css/analytics.css') }}" rel="stylesheet">
@endpush

{{-- Like the admin page's "Generate from this view": the one action, top right --}}
@section('page-actions')
    @if (auth()->user()->isDirector())
        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2"><i class="bi bi-eye me-1"></i> Read-only view</span>
    @endif
    <button type="button" class="ar-generate-view btn btn-primary fw-semibold no-print" onclick="window.print()"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> Export PDF</button>
@endsection

@if (auth()->user()->isDirector())
    @include('partials.director-workspace', ['directorPage' => 'analytics', 'directorLabel' => 'Analytics'])
@endif

@section('content')
    {{-- Same id as the Executive Analytics, so "Export PDF" prints every tab (iaris.css section 9) --}}
    <div id="executiveReport">
        <div class="ar-tab-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
        <ul class="ar-tabs nav nav-pills gap-1" role="tablist" id="analyticsTabs">
            <li class="nav-item" role="presentation"><button class="nav-link active fw-semibold" data-bs-toggle="pill" data-bs-target="#tabOverview" type="button" role="tab" data-hash="overview"><i class="bi bi-graph-up me-1"></i> Overview <span class="small fw-normal opacity-75 d-none d-md-inline">· trend and funnel</span></button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabPrograms" type="button" role="tab" data-hash="programs"><i class="bi bi-layers me-1"></i> {{ ucfirst($word) }}s</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabYears" type="button" role="tab" data-hash="years"><i class="bi bi-calendar-range me-1"></i> Compare years</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabInsights" type="button" role="tab" data-hash="insights"><i class="bi bi-stars me-1"></i> AI insights</button></li>
        </ul>
        <span class="ar-scope-chip badge rounded-pill text-wrap text-start"><i class="bi bi-funnel me-1"></i> Showing: <span class="text-body">{{ $label }} · AY {{ \App\Support\ApplicantRecords::academicYear() }} · View only</span></span>
        </div>

        {{-- Four tiles, above every tab --}}
        <div class="ar-stat-grid row row-cols-2 row-cols-xl-4 g-3 mb-4" id="kpis"></div>

        <div class="tab-content">
            {{-- Overview: applicants vs enrolled, and the funnel --}}
            <div class="tab-pane fade show active" id="tabOverview" role="tabpanel">
                @include('college.partials.field-counts')
                <div class="row g-4">
                    <div class="col-12 col-xl-7">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-3">Applicants vs Enrolled · four years</h2>
                                <div id="trend"></div>
                                <div class="d-flex gap-3 small text-body-secondary mt-2">
                                    <span><i class="bi bi-square-fill me-1" style="color: #00674F;"></i>Applicants</span>
                                    <span><i class="bi bi-square-fill me-1" style="color: #2558D9;"></i>Enrolled</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-5">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body p-4">
                                <h2 class="fs-6 fw-bold mb-3">Conversion funnel · {{ $years[3] }}</h2>
                                <div class="vstack gap-2" id="funnel"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Programs: click a column to sort --}}
            <div class="tab-pane fade" id="tabPrograms" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 rounded-top-4 d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 pt-4 pb-2">
                        <h2 class="fs-6 fw-bold mb-0">{{ ucfirst($word) }}s · {{ $years[3] }}</h2>
                        <span class="small text-body-secondary">Click a column to sort</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small text-nowrap" id="programTable"></table>
                    </div>
                </div>
            </div>

            {{-- Compare years --}}
            <div class="tab-pane fade" id="tabYears" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 rounded-top-4 px-4 pt-4 pb-2"><h2 class="fs-6 fw-bold mb-0">Year by year</h2></div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 small" id="yearTable"></table>
                    </div>
                </div>
            </div>

            {{-- AI insights --}}
            <div class="tab-pane fade" id="tabInsights" role="tabpanel">
                <div class="row row-cols-1 row-cols-lg-2 g-3" id="insights"></div>
                <p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Generated automatically from iARIS data for {{ $label }}. They describe past patterns and are not guarantees.</p>
            </div>
        </div>
            @if (auth()->user()->isDirector())
            <section class="card border-0 shadow-sm rounded-4 mt-4" aria-labelledby="directorComparisonTitle">
                <div class="director-comparison-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h2 class="fs-6 fw-bold mb-0" id="directorComparisonTitle">Current vs previous SY</h2>
                    <span class="small text-body-secondary">{{ $schoolYearComparison['currentLabel'] }} against {{ $schoolYearComparison['previousLabel'] }}</span>
                </div>
                <div class="director-comparison-body" id="cvp">
                    <div class="director-comparison-chart"><canvas id="directorYearComparison" role="img" aria-label="Current versus previous school year: Applicants, Reservation paid, and Enrolled"></canvas></div>
                </div>
            </section>
        @endif
    </div>
@endsection

@section('scripts')
    @if (auth()->user()->isDirector())
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            const yearComparison = @json($schoolYearComparison);
            const comparisonMetrics = ['Applicants', 'Reservation paid', 'Enrolled'];
            const comparisonLabels = comparisonMetrics.map((label, i) => {
                const previous = yearComparison.previous[i], current = yearComparison.current[i];
                if (previous === null || current === null || (previous === 0 && current > 0)) return [label, '(comparison unavailable)'];
                const change = previous > 0 ? (current - previous) / previous * 100 : 0;
                return `${label} (${change >= 0 ? '+' : ''}${change.toFixed(1)}%)`;
            });
            new Chart(document.getElementById('directorYearComparison'), {
                type: 'bar',
                data: { labels: comparisonLabels, datasets: [
                    { label: yearComparison.previousLabel, data: yearComparison.previous, backgroundColor: '#A8D5C5', borderRadius: 4, maxBarThickness: 30 },
                    { label: yearComparison.currentLabel, data: yearComparison.current, backgroundColor: '#00674F', borderRadius: 4, maxBarThickness: 30 },
                ] },
                plugins: [{
                    id: 'comparisonValues',
                    afterDatasetsDraw(chart) {
                        const { ctx } = chart;
                        ctx.save();
                        ctx.textAlign = 'center';
                        chart.data.datasets.forEach((dataset, index) => {
                            if (!chart.isDatasetVisible(index)) return;
                            ctx.fillStyle = index === 0 ? '#6B7C77' : '#1A2B26';
                            ctx.font = `${index === 0 ? '400' : '700'} 11px Inter, sans-serif`;
                            chart.getDatasetMeta(index).data.forEach((bar, i) => {
                                if (dataset.data[i] !== null) ctx.fillText(dataset.data[i].toLocaleString('en-US'), bar.x, bar.y - 6);
                            });
                        });
                        ctx.restore();
                    },
                }],
                options: {
                    responsive: true, maintainAspectRatio: false, layout: { padding: { top: 18 } },
                    plugins: { legend: { position: 'bottom', align: 'start', labels: { boxWidth: 9, boxHeight: 9, color: '#6B7C77', padding: 18 } } },
                    scales: {
                        x: { grid: { display: false }, border: { color: '#DDE7E4' }, ticks: { color: '#4A5B56', font: { size: 12 }, autoSkip: false, maxRotation: 0 } },
                        y: { beginAtZero: true, grace: '15%', display: false },
                    },
                },
            });
        </script>
    @endif
    <script>
        const PROGRAMS = @json($programs);
        const YEARS = @json($years);
        const CODES = Object.keys(PROGRAMS);
        const WORD = @json($word);
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const pc = (a, b) => a > 0 ? (b - a) / Math.max(1, a) * 100 : 0;
        const signed = (v, digits = 1, suffix = '%') => `${v >= 0 ? '+' : '−'}${Math.abs(v).toFixed(digits)}${suffix}`;
        const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // ---- Totals, worked out from the programs (same as the Briefing) ----
        const sumBy = key => [0, 1, 2, 3].map(i => CODES.reduce((s, c) => s + PROGRAMS[c][key][i], 0));
        const app = sumBy('applicants'), enr = sumBy('enrolled');
        const stage = i => CODES.reduce((s, c) => s + PROGRAMS[c].funnel[i], 0);
        const took = stage(1), passed = stage(2), paidNow = stage(3);
        const paidPrev = CODES.reduce((s, c) => s + PROGRAMS[c].paidLastYear, 0);
        const conv = app.map((a, i) => enr[i] / Math.max(1, a) * 100);
        const dConv = +conv[3].toFixed(1) - +conv[2].toFixed(1);

        // ---- Four tiles ----
        $('kpis').innerHTML = [
            ['Total applicants', fm(app[3]), pc(app[2], app[3]), '%', 'primary'],
            ['Paid reservations', fm(paidNow), pc(paidPrev, paidNow), '%', 'info'],
            ['Enrolled', fm(enr[3]), pc(enr[2], enr[3]), '%', 'primary'],
            ['Applied → Enrolled', conv[3].toFixed(1) + '%', dConv, ' pts', 'warning'],
        ].map(([label, value, d, suffix, tone]) => `
            <div class="col">
                <div class="ar-stat-card card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-body-secondary mb-2">${label}</div>
                        <div class="fs-3 fw-bold mb-1">${value}</div>
                        <span class="small fw-bold ${d >= 0 ? 'text-success' : 'text-danger'}">${signed(d, 1, suffix)} vs last AY</span>
                    </div>
                </div>
            </div>`).join('');

        // ---- Overview: applicants vs enrolled (one scale), and the funnel ----
        (() => {
            const max = Math.max(...app) * 1.12, X = i => 60 + i * 150, Y = v => 200 - v / max * 170;
            const series = (values, color) => `<polyline points="${values.map((v, i) => `${X(i)},${Y(v).toFixed(1)}`).join(' ')}" fill="none" stroke="${color}" stroke-width="2.5"/>`
                + values.map((v, i) => `<circle cx="${X(i)}" cy="${Y(v).toFixed(1)}" r="4.5" fill="#fff" stroke="${color}" stroke-width="2.2"><title>${YEARS[i]}: ${fm(v)}</title></circle><text x="${X(i)}" y="${(Y(v) - 10).toFixed(1)}" text-anchor="middle" font-size="11.5" font-weight="700" fill="${color}">${fm(v)}</text>`).join('');
            // Settings → Display → "Default trend chart": Line (default) or Bar
            const bar = (values, color, dx) => values.map((v, i) => `<rect x="${X(i) + dx}" y="${Y(v).toFixed(1)}" width="34" height="${(200 - Y(v)).toFixed(1)}" rx="4" fill="${color}"><title>${YEARS[i]}: ${fm(v)}</title></rect><text x="${X(i) + dx + 17}" y="${(Y(v) - 6).toFixed(1)}" text-anchor="middle" font-size="11.5" font-weight="700" fill="${color}">${fm(v)}</text>`).join('');
            const asBars = window.IARIS_SETTINGS?.display?.chart === 'bar';
            $('trend').innerHTML = `<svg viewBox="0 0 560 230" width="100%" role="img" aria-label="Applicants and enrolled over four years">`
                + [0, .25, .5, .75, 1].map(f => `<line x1="40" x2="540" y1="${200 - f * 170}" y2="${200 - f * 170}" stroke="#EEF3F1"/>`).join('')
                + YEARS.map((y, i) => `<text x="${X(i)}" y="222" text-anchor="middle" font-size="11.5" fill="#6B7C77">${y}</text>`).join('')
                + (asBars ? bar(app, '#00674F', -36) + bar(enr, '#2558D9', 2) : series(app, '#00674F') + series(enr, '#2558D9')) + '</svg>';

            const stages = [['Applied', app[3]], ['Took exam', took], ['Passed', passed], ['Paid', paidNow], ['Enrolled', enr[3]]];
            const shades = ['#00674F', '#137A60', '#2E9C7C', '#4DAB8E', '#7DC4AA'];
            $('funnel').innerHTML = stages.map(([name, n], i) => `
                <div class="d-flex align-items-center gap-2 small">
                    <span class="fw-semibold" style="width: 76px;">${name}</span>
                    <div class="flex-grow-1 bg-body-tertiary rounded" style="height: 18px;"><div class="rounded" style="height: 18px; width: ${n / Math.max(1, app[3]) * 100}%; background: ${shades[i]};"></div></div>
                    <span class="fw-bold text-end text-nowrap" style="width: 96px;">${fm(n)} · ${(n / Math.max(1, app[3]) * 100).toFixed(1)}%</span>
                </div>`).join('');
        })();

        // ---- Programs: sortable table ----
        // Settings → Display → "Program shown first on Analytics" keeps that program at the top
        const first = window.IARIS_SETTINGS?.display?.unit || '';
        const PR = CODES.map(c => { const p = PROGRAMS[c], [P, E, D] = p.status, T = P + E + D; return { name: p.name, unit: p.unit || '', P, E, D, T, R: D / T * 100, S: T / Math.max(1, app[3]) * 100 }; });
        // The iPACE Director's programs have a unit (iPACE or ETEEAP): add a Unit column
        const hasUnits = PR.some(r => r.unit);
        const columns = [['name', WORD[0].toUpperCase() + WORD.slice(1)], ...(hasUnits ? [['unit', 'Unit']] : []), ['P', 'Pending'], ['E', 'For Exam'], ['D', 'Paid'], ['T', 'Total'], ['R', 'Paid rate'], ['S', 'Share']];
        let sortKey = 'T', sortDir = -1;
        function drawPrograms() {
            // sortDir: 1 = ascending, -1 = descending. The "shown first" program stays on top.
            const sorted = PR.slice().sort((a, b) => (b.name === first) - (a.name === first)
                || sortDir * (typeof a[sortKey] === 'string' ? a[sortKey].localeCompare(b[sortKey]) : a[sortKey] - b[sortKey]));
            $('programTable').innerHTML = `<thead class="table-light"><tr>${columns.map(([k, l], i) => `<th class="${i === 0 ? 'ps-4' : k === 'unit' ? '' : 'text-end'} text-body-secondary fw-bold" role="button" data-sort="${k}" aria-sort="${sortKey === k ? (sortDir < 0 ? 'descending' : 'ascending') : 'none'}">${l}${sortKey === k ? (sortDir < 0 ? ' ↓' : ' ↑') : ''}</th>`).join('')}</tr></thead>`
                + `<tbody>${sorted.map(r => `<tr class="${r.name === first ? 'table-success' : ''}"><td class="ps-4 fw-semibold">${esc(r.name)}${r.name === first ? ' <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis ms-1" title="Settings → Display">Shown first</span>' : ''}</td>${hasUnits ? `<td>${esc(r.unit)}</td>` : ''}<td class="text-end">${fm(r.P)}</td><td class="text-end">${fm(r.E)}</td><td class="text-end">${fm(r.D)}</td><td class="text-end fw-bold">${fm(r.T)}</td><td class="text-end">${r.R.toFixed(1)}%</td><td class="text-end pe-4">${r.S.toFixed(1)}%</td></tr>`).join('')}</tbody>`;
            $('programTable').querySelectorAll('[data-sort]').forEach(th => th.addEventListener('click', () => {
                // A new column starts A→Z for names, largest first for numbers
                if (sortKey === th.dataset.sort) sortDir = -sortDir; else { sortKey = th.dataset.sort; sortDir = ['name', 'unit'].includes(sortKey) ? 1 : -1; }
                drawPrograms();
            }));
        }
        drawPrograms();

        // ---- Compare years ----
        $('yearTable').innerHTML = `<thead class="table-light"><tr><th class="ps-4 text-body-secondary fw-bold">Academic year</th><th class="text-end text-body-secondary fw-bold">Applicants</th><th class="text-end text-body-secondary fw-bold">Change</th><th class="text-end text-body-secondary fw-bold">Enrolled</th><th class="text-end pe-4 text-body-secondary fw-bold">Conversion</th></tr></thead>`
            + `<tbody>${YEARS.map((y, i) => `<tr><td class="ps-4 fw-semibold">${y}</td><td class="text-end">${fm(app[i])}</td><td class="text-end">${i ? `<span class="badge rounded-pill ${app[i] >= app[i - 1] ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${signed(pc(app[i - 1], app[i]))}</span>` : '—'}</td><td class="text-end">${fm(enr[i])}</td><td class="text-end pe-4">${conv[i].toFixed(1)}%</td></tr>`).join('')}</tbody>`;

        // ---- AI insights: four cards worked out from the numbers (descriptive, not forecasts) ----
        const byTotal = PR.slice().sort((a, b) => b.T - a.T), byRate = PR.slice().sort((a, b) => b.R - a.R);
        const unitOf = r => r.unit ? ` (${r.unit})` : '';
        const g4 = pc(app[0], app[3]);
        const INSIGHTS = [
            ['success', 'bi-graph-up-arrow', `Applicants ${g4 >= 0 ? 'up' : 'down'} ${Math.abs(g4).toFixed(1)}% in three years`, `From ${fm(app[0])} in ${YEARS[0]} to ${fm(app[3])} now.`],
            ...(PR.length > 1 ? [
                ['warning', 'bi-trophy', `${byTotal[0].name}${unitOf(byTotal[0])} is your largest ${WORD}`, `${fm(byTotal[0].T)} applicants, ${byTotal[0].S.toFixed(1)}% of your total.`],
                ['success', 'bi-check-circle', `${byRate[0].name}${unitOf(byRate[0])} converts best`, `${byRate[0].R.toFixed(1)}% of its applicants have paid.`],
                ['danger', 'bi-exclamation-triangle', `${byRate.at(-1).name}${unitOf(byRate.at(-1))} has the lowest paid rate`, `${byRate.at(-1).R.toFixed(1)}% paid so far, with ${fm(byRate.at(-1).P)} still Pending.`],
            ] : [
                ['success', 'bi-check-circle', `${PR[0].R.toFixed(1)}% of applicants have paid`, `${fm(PR[0].D)} of ${fm(PR[0].T)}, with ${fm(PR[0].P)} still Pending.`],
                [dConv < 0 ? 'danger' : 'success', 'bi-funnel', `Conversion ${dConv < 0 ? 'slipped' : 'improved'} to ${conv[3].toFixed(1)}%`, `${signed(dConv, 1, '')} pts from ${conv[2].toFixed(1)}% last AY.`],
            ]),
        ];
        $('insights').innerHTML = INSIGHTS.map(([tone, icon, title, text]) => `
            <div class="col">
                <div class="card border-0 border-start border-4 border-${tone} shadow-sm rounded-4 h-100">
                    <div class="card-body d-flex gap-3">
                        <span class="rounded-circle bg-${tone}-subtle text-${tone}-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi ${icon}"></i></span>
                        <div><div class="fw-bold">${esc(title)}</div><div class="small text-body-secondary">${esc(text)}</div></div>
                    </div>
                </div>
            </div>`).join('');

        // ---- Applicant field counts: Cards / Table, and CSV ----
        document.querySelectorAll('[data-field-view]').forEach(b => b.addEventListener('click', () => {
            const cards = b.dataset.fieldView === 'cards';
            $('fieldCountCards').classList.toggle('d-none', !cards);
            $('fieldCountTable').classList.toggle('d-none', cards);
            document.querySelectorAll('[data-field-view]').forEach(x => { x.classList.toggle('active', x === b); x.setAttribute('aria-pressed', x === b); });
        }));
        $('exportFieldCounts').addEventListener('click', () => {
            const rows = [...document.querySelectorAll('#fieldCountTable tbody tr')].map(tr => [tr.children[0].textContent.trim(), tr.children[1].textContent.trim()]);
            const csv = [['Field', 'Current school year'], ...rows].map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv' }));
            link.download = 'iaris-applicant-field-counts.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // Tabs follow the address (#overview, #programs, #years, #insights)
        document.querySelectorAll('#analyticsTabs [data-hash]').forEach(b => b.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + b.dataset.hash)));
        const tab = document.querySelector(`#analyticsTabs [data-hash="${location.hash.slice(1)}"]`);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    </script>
@endsection
