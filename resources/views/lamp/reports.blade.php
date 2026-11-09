@extends('layouts.app')

{{--
    LAMP Office "Analytics & Reports" (LampController::reports). Two tabs (#analytics, #reports):
      Analytics  four numbers, insights worked out from them, and four charts, from the real scholarship applicants
      Reports    what IATO sent: open one to read or download it (PDF, Excel or CSV); questions go to Messages
    Opened reports are remembered in this browser ("iaris.lampReportsRead"); the sidebar's red number follows it.
    The applicants are real; the list of reports IATO sent is a placeholder (nothing stores them yet).
--}}

@section('title', 'iARIS — Analytics & Reports')
@section('page-title', 'Analytics & Reports')
@section('page-subtitle', 'Scholar trends and insights, and the reports IATO sends you. Questions go to Messages.')

@section('content')
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <ul class="nav nav-pills gap-2 me-auto" role="tablist" id="tabs">
            <li class="nav-item" role="presentation"><button class="nav-link active fw-semibold" data-bs-toggle="pill" data-bs-target="#tabAnalytics" type="button" role="tab" data-hash="analytics"><i class="bi bi-graph-up me-1"></i> Analytics</button></li>
            <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" data-bs-toggle="pill" data-bs-target="#tabReports" type="button" role="tab" data-hash="reports"><i class="bi bi-file-earmark-text me-1"></i> Reports</button></li>
        </ul>
        <a href="{{ url('/lamp/messages') }}" class="btn btn-light border fw-semibold"><i class="bi bi-chat-dots me-1"></i> Messages</a>
    </div>

    <div class="tab-content">
        {{-- ========== ANALYTICS ========== --}}
        <div class="tab-pane fade show active" id="tabAnalytics" role="tabpanel">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" id="kpis"></div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-stars text-primary me-1"></i> Insights</h2>
                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border">Worked out from this page's figures</span>
                    </div>
                    <div class="row row-cols-1 row-cols-lg-2 g-3" id="insights"></div>
                    <p class="small text-body-secondary mt-3 mb-0">Insights describe past patterns and are not guarantees. Verify important figures before acting on them.</p>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                        <h2 class="fs-6 fw-bold mb-0">Scholarship applicants over four years</h2>
                        <p class="small text-body-secondary mb-3">Answered Yes on the application</p>
                        <div id="trend"></div>
                    </div></div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                        <h2 class="fs-6 fw-bold mb-0">Exam results</h2>
                        <p class="small text-body-secondary mb-3">Scholarship applicants · AY {{ $academicYear }}</p>
                        <div class="vstack gap-2" id="results"></div>
                    </div></div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                        <h2 class="fs-6 fw-bold mb-0">Scholarship applicants by unit</h2>
                        <p class="small text-body-secondary mb-3">AY {{ $academicYear }}</p>
                        <div class="vstack gap-2" id="units"></div>
                    </div></div>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100"><div class="card-body p-4">
                        <h2 class="fs-6 fw-bold mb-0">Your checks</h2>
                        <p class="small text-body-secondary mb-3">Applicants who passed</p>
                        <div class="vstack gap-2" id="checks"></div>
                        <p class="small text-body-secondary mt-3 mb-0" id="checksNote"></p>
                    </div></div>
                </div>
            </div>
        </div>

        {{-- ========== REPORTS ========== --}}
        <div class="tab-pane fade" id="tabReports" role="tabpanel">
            <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4" id="reportStats"></div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="search" placeholder="Search report title…" aria-label="Search reports">
                    </div>
                    <select class="form-select w-auto" id="typeFilter" aria-label="Report type">
                        <option value="">All report types</option>
                        @foreach (collect($reports)->pluck('type')->unique() as $type)
                            <option>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="pills"></div>
                <div class="list-group list-group-flush rounded-bottom-4" id="reportList"></div>
            </div>
        </div>
    </div>

    {{-- A report --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="reportDrawer" aria-labelledby="drawerTitle" style="--bs-offcanvas-width: 640px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerTitle"></h2>
                <div class="small text-white-50" id="drawerMeta"></div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3"><div class="small fw-bold text-uppercase text-body-secondary">Prepared by</div><div class="fw-semibold small">IATO · Renegado, Randolph</div></div>
                <div class="col-6 col-md-3"><div class="small fw-bold text-uppercase text-body-secondary">Date sent</div><div class="fw-semibold small" id="rDate"></div></div>
                <div class="col-6 col-md-3"><div class="small fw-bold text-uppercase text-body-secondary">AY</div><div class="fw-semibold small">{{ $academicYear }}</div></div>
                <div class="col-6 col-md-3"><div class="small fw-bold text-uppercase text-body-secondary">Report type</div><div class="fw-semibold small" id="rType"></div></div>
            </div>
            <p class="small text-body-secondary" id="rNote"></p>
            <div class="table-responsive border rounded-3 mb-2">
                <table class="table table-sm align-middle mb-0 small" id="rTable">
                    <thead class="table-light"><tr><th>Applicant</th><th>Applied for a scholarship</th><th>Exam result</th><th>LAMP check</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <p class="small text-body-secondary" id="rMore"></p>
            <div class="mb-4" id="rDownload"></div>
            <div class="border rounded-3 p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div><div class="fw-bold small">Question about this report?</div><div class="small text-body-secondary">Ask IATO in Messages. To check one applicant, open them in Scholars.</div></div>
                <a class="btn btn-primary fw-semibold" id="rAsk" href="#"><i class="bi bi-chat-dots me-1"></i> Ask IATO</a>
            </div>
        </div>
    </div>

    @include('toic.partials.kit')
@endsection

@section('scripts')
    @include('lamp.partials.check-script')
    <script>
        const REPORTS = @json($reports);
        const TREND = @json($trend);
        const ACADEMIC_YEAR = @json($academicYear);
        const KEY = 'iaris.lampReportsRead';
        const $ = id => document.getElementById(id);
        const esc = LAMP.esc;
        const fm = n => Number(n).toLocaleString('en-US');

        // ---------- Analytics: from the real scholarship applicants ----------
        const COLORS = { Pass: 'success', Reconsidered: 'info', Waitlisted: 'warning', Fail: 'danger', 'Not yet taken': 'secondary' };
        const bars = (rows, tone, max) => rows.length ? rows.map(([name, n, t]) => `<div class="d-flex align-items-center gap-3 small">
                <span class="text-truncate" style="width: 130px;" title="${esc(name)}">${esc(name)}</span>
                <div class="progress flex-grow-1" style="height: 12px;"><div class="progress-bar bg-${t || tone}" style="width: ${max ? n / max * 100 : 0}%"></div></div>
                <span class="fw-semibold text-end" style="width: 44px;">${fm(n)}</span></div>`).join('')
            : '<div class="small text-body-secondary">No scholarship applicants yet.</div>';

        function analytics() {
            const t = LAMP.totals(), people = LAMP.people;
            const prev = TREND[2][1], now = TREND[3][1] || t.apps;
            const change = prev ? (now - prev) / prev * 100 : null;
            const passPct = t.apps ? t.passed / t.apps * 100 : 0;

            $('kpis').innerHTML = [
                [`Scholarship applicants · AY ${ACADEMIC_YEAR}`, fm(t.apps), change === null ? 'No earlier AY imported' : `${change >= 0 ? '+' : '−'}${Math.abs(change).toFixed(1)}% vs last AY`, 'success'],
                ['Passed the exam', fm(t.passed), `${passPct.toFixed(1)}% of scholarship applicants`, 'info'],
                ['Confirmed scholars', fm(t.yes), 'Checked by LAMP', 'primary'],
                ['Waiting for LAMP', fm(t.wait), 'Passed, not yet checked', 'warning'],
            ].map(([label, value, note, tone]) => `<div class="col"><div class="card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div><div class="fs-3 fw-bold text-${tone}-emphasis">${value}</div><div class="small text-body-secondary">${note}</div></div></div></div>`).join('');

            // Insights, worked out from the same figures
            const notYet = people.filter(x => x.result === 'Not yet taken').length;
            const units = {};
            people.filter(x => x.result === 'Not yet taken').forEach(x => units[x.unit] = (units[x.unit] || 0) + 1);
            const topUnit = Object.entries(units).sort((a, b) => b[1] - a[1])[0];
            const insights = [];
            if (t.apps) {
                insights.push(['bi-arrow-up-right-circle', 'success', change === null ? `${fm(t.apps)} scholarship applicants this AY` : `Scholarship applicants are ${change >= 0 ? 'up' : 'down'} ${Math.abs(change).toFixed(1)}% from last AY`,
                    change === null ? 'Earlier school years are not in iARIS yet, so there is nothing to compare with.' : `${fm(now)} applicants answered Yes this AY against ${fm(prev)} last AY.`]);
                insights.push(['bi-person-check', 'info', `${passPct.toFixed(0)} in 100 scholarship applicants passed`, `${fm(t.passed)} of ${fm(t.apps)} passed the entrance exam (Pass or Reconsidered). Only these reach the LAMP Office for checking.`]);
                insights.push(['bi-hourglass-split', 'warning', t.wait ? `${fm(t.wait)} ${t.wait === 1 ? 'applicant is' : 'applicants are'} waiting for your check` : 'Everyone who passed has been checked', t.wait ? 'All of them passed. Checking them now gives IATO time to confirm before enrollment.' : 'IATO confirms every change you made.']);
                insights.push(['bi-clock', 'primary', `${fm(notYet)} ${notYet === 1 ? 'has' : 'have'} not taken the exam yet`, notYet ? `They will reach you once their results are in.${topUnit ? ` Most are ${topUnit[0]} applicants.` : ''}` : 'Every scholarship applicant has taken the exam.']);
            }
            $('insights').innerHTML = insights.length ? insights.map(([icon, tone, title, text]) => `<div class="col"><div class="d-flex gap-3"><i class="bi ${icon} text-${tone} fs-4"></i><div><div class="fw-bold">${title}</div><div class="small text-body-secondary">${text}</div></div></div></div>`).join('')
                : '<div class="col-12 small text-body-secondary">Insights appear once IATO imports applicants who answered Yes to the scholarship question.</div>';

            // Four years (earlier years show 0 until they are imported)
            const max = Math.max(...TREND.map(y => y[1]), 1);
            $('trend').innerHTML = '<div class="d-flex align-items-end gap-3" style="height: 170px;">' + TREND.map(([label, n], i) => `<div class="flex-fill text-center d-flex flex-column justify-content-end h-100">
                    <div class="small fw-bold">${fm(n)}</div><div class="rounded-top ${i === 3 ? 'bg-success' : 'bg-success-subtle'}" style="height: ${n / max * 130}px; min-height: 2px;"></div></div>`).join('')
                + '</div><div class="d-flex gap-3 small text-body-secondary mt-1">' + TREND.map(y => `<span class="flex-fill text-center">${y[0]}</span>`).join('') + '</div>'
                + (TREND.slice(0, 3).every(y => !y[1]) ? '<p class="small text-body-secondary mt-3 mb-0">Earlier school years show 0 until their applicants are imported.</p>' : '');

            const byResult = ['Pass', 'Reconsidered', 'Waitlisted', 'Fail', 'Not yet taken'].map(r => [r, people.filter(x => x.result === r).length, COLORS[r]]);
            $('results').innerHTML = bars(byResult, 'success', Math.max(t.apps, 1));
            const byUnit = Object.entries(people.reduce((o, x) => (o[x.unit] = (o[x.unit] || 0) + 1, o), {})).sort((a, b) => b[1] - a[1]);
            $('units').innerHTML = bars(byUnit, 'success', Math.max(...byUnit.map(u => u[1]), 1));
            $('checks').innerHTML = bars([['Confirmed scholar', t.yes], ['Waiting for LAMP', t.wait], ['Not a scholar', t.no]], 'primary', Math.max(t.passed, 1));
            $('checksNote').innerHTML = t.wait ? `Open <a href="{{ url('/lamp/scholars') }}">Scholars</a> to check the ${fm(t.wait)} who ${t.wait === 1 ? 'is' : 'are'} waiting. IATO confirms every change.` : 'Everyone who passed has been checked. IATO confirms every change.';
        }

        // ---------- Reports IATO sent ----------
        let read = @json($readReports), filter = 'all', current = null;
        try { read = JSON.parse(localStorage.getItem(KEY)) || read; } catch (e) {}
        const saveRead = () => { try { localStorage.setItem(KEY, JSON.stringify(read)); } catch (e) {} };
        const unread = () => REPORTS.filter(r => !read.includes(r.id)).length;
        function sidebarBadge() {
            document.querySelectorAll('[data-lamp-reports-badge]').forEach(e => { e.textContent = unread(); e.classList.toggle('d-none', !unread()); });
        }

        function drawReports() {
            const n = unread();
            $('reportStats').innerHTML = [['Reports', REPORTS.length, 'Sent by IATO this AY', 'success'], ['New', n, 'Not opened yet', 'danger'], ['Read', REPORTS.length - n, 'Already opened', 'primary']]
                .map(([label, value, note, tone]) => `<div class="col"><div class="card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div><div class="fs-3 fw-bold text-${tone}-emphasis">${value}</div><div class="small text-body-secondary">${note}</div></div></div></div>`).join('');
            $('pills').innerHTML = '<span class="small fw-bold text-body-secondary me-1">Show:</span>' + [['all', 'All'], ['new', 'New'], ['read', 'Read']].map(([k, label]) =>
                `<button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold ${filter === k ? 'btn-primary' : 'btn-outline-secondary'}" data-k="${k}">${label}</button>`).join('');
            $('pills').querySelectorAll('[data-k]').forEach(b => b.addEventListener('click', () => { filter = b.dataset.k; drawReports(); }));

            const q = $('search').value.trim().toLowerCase(), type = $('typeFilter').value;
            const list = REPORTS.filter(r => (filter === 'all' || (filter === 'new') !== read.includes(r.id)) && (!type || r.type === type) && (!q || r.title.toLowerCase().includes(q)));
            $('reportList').innerHTML = list.length ? list.map(r => {
                const isNew = !read.includes(r.id);
                return `<button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3" data-id="${r.id}">
                    <i class="bi bi-file-earmark-text fs-4 text-success"></i>
                    <div class="flex-grow-1 text-start"><div class="${isNew ? 'fw-bold' : 'fw-semibold'}">${esc(r.title)}</div>
                        <div class="small text-body-secondary">Sent by <b>Renegado, Randolph</b> (IATO) · ${esc(r.date)} · <span class="badge rounded-pill bg-${r.tone}-subtle text-${r.tone}-emphasis">${esc(r.type)}</span></div></div>
                    ${isNew ? '<span class="badge rounded-pill text-bg-danger">New</span>' : ''}
                    <span class="btn btn-sm btn-light border fw-semibold"><i class="bi bi-eye me-1"></i> Open</span></button>`;
            }).join('') : '<div class="text-center text-body-secondary small py-5">No reports match.</div>';
            $('reportList').querySelectorAll('[data-id]').forEach(b => b.addEventListener('click', () => openReport(b.dataset.id)));
            sidebarBadge();
        }

        const SHOWN = 10;
        const rows = () => LAMP.people.map(x => [x.name, 'Yes', x.result, LAMP.LB[x.lamp][0]]);
        function openReport(id) {
            const r = REPORTS.find(x => x.id === id);
            current = r;
            if (!read.includes(id)) { read.push(id); saveRead(); }
            $('drawerTitle').textContent = r.title;
            $('drawerMeta').textContent = `Sent by Renegado, Randolph (IATO) · ${r.date}`;
            $('rDate').textContent = r.date.split(' · ')[0];
            $('rType').textContent = r.type;
            $('rNote').textContent = r.note;
            $('rTable').querySelector('tbody').innerHTML = rows().slice(0, SHOWN).map(c => `<tr>${c.map(v => `<td>${esc(v)}</td>`).join('')}</tr>`).join('')
                || '<tr><td colspan="4" class="text-center text-body-secondary py-3">No scholarship applicants yet.</td></tr>';
            $('rMore').textContent = LAMP.people.length > SHOWN ? `Showing ${SHOWN} of ${fm(LAMP.people.length)} applicants. Download the report for all of them.` : '';
            $('rDownload').replaceChildren(downloadMenu(() => ({ title: r.title, sub: `Sent by Renegado, Randolph (IATO) · ${r.date}`, head: ['Applicant', 'Applied for a scholarship', 'Exam result', 'LAMP check'], rows: rows() }), false));
            $('rAsk').href = `{{ url('/lamp/messages') }}?new=1&subject=${encodeURIComponent('About: ' + r.title)}`;
            bootstrap.Offcanvas.getOrCreateInstance($('reportDrawer')).show();
            drawReports();
        }

        $('search').addEventListener('input', drawReports);
        $('typeFilter').addEventListener('change', drawReports);
        LAMP.onChange(analytics);
        analytics();
        drawReports();

        // Tabs follow the address: #analytics, #reports
        document.querySelectorAll('#tabs [data-hash]').forEach(b => b.addEventListener('shown.bs.tab', () => history.replaceState(null, '', '#' + b.dataset.hash)));
        if (location.hash === '#reports') bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-hash="reports"]')).show();
    </script>
@endsection
