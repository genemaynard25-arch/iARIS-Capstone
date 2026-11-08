@extends('layouts.app')

{{--
    Analytics for a Dean, Program Chair or the IS Principal (CollegeController::analytics). As in the mockup
    ($word is "program", or "level" for the Integrated School):
    four tiles, then four tabs — Overview (trend and funnel), Programs (sortable table),
    Compare years, AI insights. Everything is worked out by the script from $programs,
    the same numbers as the Briefing. Tabs follow the address: #overview, #programs, #years, #insights.
--}}

@section('title', 'iARIS — Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', "{$label} admissions and enrollment, with AI-generated insights")

@section('content')
    {{-- Same id as the Executive Analytics, so "Export PDF" prints every tab (iaris.css section 9) --}}
    <div id="executiveReport">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2"><i class="bi bi-eye me-1"></i> Read-only view</span>
            <button type="button" class="btn btn-primary fw-semibold ms-sm-auto no-print" onclick="window.print()"><i class="bi bi-file-earmark-pdf me-1"></i> Export PDF</button>
        </div>

        <ul class="nav nav-pills gap-2 mb-3 no-print" role="tablist" id="analyticsTabs">
            <li class="nav-item" role="presentation"><button class="nav-link active fw-semibold" data-bs-toggle="pill" data-bs-target="#tabOverview" type="button" role="tab" data-hash="overview"><i class="bi bi-graph-up me-1"></i> Overview <span class="small fw-normal opacity-75 d-none d-md-inline">· trend and funnel</span></button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabPrograms" type="button" role="tab" data-hash="programs"><i class="bi bi-layers me-1"></i> {{ ucfirst($word) }}s</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabYears" type="button" role="tab" data-hash="years"><i class="bi bi-calendar-range me-1"></i> Compare years</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabInsights" type="button" role="tab" data-hash="insights"><i class="bi bi-stars me-1"></i> AI insights</button></li>
        </ul>

        {{-- Four tiles, above every tab --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" id="kpis"></div>

        <div class="tab-content">
            {{-- Overview: applicants vs enrolled, and the funnel --}}
            <div class="tab-pane fade show active" id="tabOverview" role="tabpanel">
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
    </div>
@endsection

@section('scripts')
    <script>
        const PROGRAMS = @json($programs);
        const YEARS = @json($years);
        const CODES = Object.keys(PROGRAMS);
        const WORD = @json($word);
        const $ = id => document.getElementById(id);
        const fm = n => Math.round(n).toLocaleString('en-US');
        const pc = (a, b) => (b - a) / a * 100;
        const signed = (v, digits = 1, suffix = '%') => `${v >= 0 ? '+' : '−'}${Math.abs(v).toFixed(digits)}${suffix}`;
        const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // ---- Totals, worked out from the programs (same as the Briefing) ----
        const sumBy = key => [0, 1, 2, 3].map(i => CODES.reduce((s, c) => s + PROGRAMS[c][key][i], 0));
        const app = sumBy('applicants'), enr = sumBy('enrolled');
        const stage = i => CODES.reduce((s, c) => s + PROGRAMS[c].funnel[i], 0);
        const took = stage(1), passed = stage(2), paidNow = stage(3);
        const paidPrev = CODES.reduce((s, c) => s + PROGRAMS[c].paidLastYear, 0);
        const conv = app.map((a, i) => enr[i] / a * 100);
        const dConv = +conv[3].toFixed(1) - +conv[2].toFixed(1);

        // ---- Four tiles ----
        $('kpis').innerHTML = [
            ['Total applicants', fm(app[3]), pc(app[2], app[3]), '%', 'primary'],
            ['Paid reservations', fm(paidNow), pc(paidPrev, paidNow), '%', 'info'],
            ['Enrolled', fm(enr[3]), pc(enr[2], enr[3]), '%', 'dark'],
            ['Applied → Enrolled', conv[3].toFixed(1) + '%', dConv, ' pts', 'warning'],
        ].map(([label, value, d, suffix, tone]) => `
            <div class="col">
                <div class="card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div>
                        <div class="fs-2 fw-bold">${value}</div>
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
                    <div class="flex-grow-1 bg-body-tertiary rounded" style="height: 18px;"><div class="rounded" style="height: 18px; width: ${n / app[3] * 100}%; background: ${shades[i]};"></div></div>
                    <span class="fw-bold text-end text-nowrap" style="width: 96px;">${fm(n)} · ${(n / app[3] * 100).toFixed(1)}%</span>
                </div>`).join('');
        })();

        // ---- Programs: sortable table ----
        // Settings → Display → "Program shown first on Analytics" keeps that program at the top
        const first = window.IARIS_SETTINGS?.display?.unit || '';
        const PR = CODES.map(c => { const p = PROGRAMS[c], [P, E, D] = p.status, T = P + E + D; return { name: p.name, P, E, D, T, R: D / T * 100, S: T / app[3] * 100 }; });
        const columns = [['name', WORD[0].toUpperCase() + WORD.slice(1)], ['P', 'Pending'], ['E', 'For Exam'], ['D', 'Paid'], ['T', 'Total'], ['R', 'Paid rate'], ['S', 'Share']];
        let sortKey = 'T', sortDir = -1;
        function drawPrograms() {
            // sortDir: 1 = ascending, -1 = descending. The "shown first" program stays on top.
            const sorted = PR.slice().sort((a, b) => (b.name === first) - (a.name === first)
                || sortDir * (sortKey === 'name' ? a.name.localeCompare(b.name) : a[sortKey] - b[sortKey]));
            $('programTable').innerHTML = `<thead class="table-light"><tr>${columns.map(([k, l], i) => `<th class="${i ? 'text-end' : 'ps-4'} text-body-secondary fw-bold" role="button" data-sort="${k}" aria-sort="${sortKey === k ? (sortDir < 0 ? 'descending' : 'ascending') : 'none'}">${l}${sortKey === k ? (sortDir < 0 ? ' ↓' : ' ↑') : ''}</th>`).join('')}</tr></thead>`
                + `<tbody>${sorted.map(r => `<tr class="${r.name === first ? 'table-success' : ''}"><td class="ps-4 fw-semibold">${esc(r.name)}${r.name === first ? ' <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis ms-1" title="Settings → Display">Shown first</span>' : ''}</td><td class="text-end">${fm(r.P)}</td><td class="text-end">${fm(r.E)}</td><td class="text-end">${fm(r.D)}</td><td class="text-end fw-bold">${fm(r.T)}</td><td class="text-end">${r.R.toFixed(1)}%</td><td class="text-end pe-4">${r.S.toFixed(1)}%</td></tr>`).join('')}</tbody>`;
            $('programTable').querySelectorAll('[data-sort]').forEach(th => th.addEventListener('click', () => {
                // A new column starts A→Z for names, largest first for numbers
                if (sortKey === th.dataset.sort) sortDir = -sortDir; else { sortKey = th.dataset.sort; sortDir = sortKey === 'name' ? 1 : -1; }
                drawPrograms();
            }));
        }
        drawPrograms();

        // ---- Compare years ----
        $('yearTable').innerHTML = `<thead class="table-light"><tr><th class="ps-4 text-body-secondary fw-bold">Academic year</th><th class="text-end text-body-secondary fw-bold">Applicants</th><th class="text-end text-body-secondary fw-bold">Change</th><th class="text-end text-body-secondary fw-bold">Enrolled</th><th class="text-end pe-4 text-body-secondary fw-bold">Conversion</th></tr></thead>`
            + `<tbody>${YEARS.map((y, i) => `<tr><td class="ps-4 fw-semibold">${y}</td><td class="text-end">${fm(app[i])}</td><td class="text-end">${i ? `<span class="badge rounded-pill ${app[i] >= app[i - 1] ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis'}">${signed(pc(app[i - 1], app[i]))}</span>` : '—'}</td><td class="text-end">${fm(enr[i])}</td><td class="text-end pe-4">${conv[i].toFixed(1)}%</td></tr>`).join('')}</tbody>`;

        // ---- AI insights: four cards worked out from the numbers (descriptive, not forecasts) ----
        const byTotal = PR.slice().sort((a, b) => b.T - a.T), byRate = PR.slice().sort((a, b) => b.R - a.R);
        const g4 = pc(app[0], app[3]);
        const INSIGHTS = [
            ['success', 'bi-graph-up-arrow', `Applicants ${g4 >= 0 ? 'up' : 'down'} ${Math.abs(g4).toFixed(1)}% in three years`, `From ${fm(app[0])} in ${YEARS[0]} to ${fm(app[3])} now.`],
            ...(PR.length > 1 ? [
                ['warning', 'bi-trophy', `${byTotal[0].name} is your largest ${WORD}`, `${fm(byTotal[0].T)} applicants, ${byTotal[0].S.toFixed(1)}% of your total.`],
                ['success', 'bi-check-circle', `${byRate[0].name} converts best`, `${byRate[0].R.toFixed(1)}% of its applicants have paid.`],
                ['danger', 'bi-exclamation-triangle', `${byRate.at(-1).name} has the lowest paid rate`, `${byRate.at(-1).R.toFixed(1)}% paid so far, with ${fm(byRate.at(-1).P)} still Pending.`],
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

        // Tabs follow the address (#overview, #programs, #years, #insights)
        document.querySelectorAll('#analyticsTabs [data-hash]').forEach(b => b.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + b.dataset.hash)));
        const tab = document.querySelector(`#analyticsTabs [data-hash="${location.hash.slice(1)}"]`);
        if (tab) bootstrap.Tab.getOrCreateInstance(tab).show();
    </script>
@endsection
