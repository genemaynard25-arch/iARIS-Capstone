{{--
    Script for the Reports tab (templates, Preview / Generate, history) and, when the user also has
    Analytics, the two report builders: "Generate from this view" and the "Add to report" tray.
    Downloads and snapshot history are persisted through authenticated report endpoints.
--}}
<script>
    const REPORTS = @json(collect($reportData['sections'])->pluck('reports')->flatten(1)->keyBy('key'));

    const reportModalEl = document.getElementById('reportModal');
    const reportModal = bootstrap.Modal.getOrCreateInstance(reportModalEl);
    let currentKey = null;

    // Open the modal. "preview" shows the imported table; "generate" skips straight to the settings.
    function openReport(key, mode) {
        const report = REPORTS[key];
        currentKey = key;
        document.getElementById('reportTitle').textContent = (mode === 'generate' ? 'Generate: ' : '') + report.title;
        document.getElementById('reportSubtitle').textContent = `Recipient: ${report.recipient} · ` +
            (mode === 'preview' ? 'Imported data — configure settings below' : 'Configure before generating');
        document.getElementById('reportRecipient').value = report.recipient;
        document.querySelectorAll('[data-preview]').forEach(p => p.classList.toggle('d-none', mode !== 'preview' || p.dataset.preview !== key));
        updateReportPeriod();
        reportModal.show();
    }

    let previewVersion = 0;
    async function updateReportPeriod() {
        if (!currentKey) return;
        const period = document.getElementById('reportPeriod').value;
        const version = ++previewVersion;
        const year = typeof currentView === 'function' ? currentView().year : new URLSearchParams(location.search).get('year');
        let report;
        try {
            const response = await fetch(`{{ url('/analytics/reports') }}/${encodeURIComponent(currentKey)}/preview?${new URLSearchParams({period, ...(year ? {year} : {})})}`, {headers:{Accept:'application/json'}});
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Unable to preview report.');
            if (version !== previewVersion) return;
            report = result.report;
        } catch(error) { showToast(error.message); return; }
        if (!report) return;
        document.getElementById('reportSubtitle').textContent = `${period} · Recipient: ${report.recipient}`;
        const preview = [...document.querySelectorAll('[data-preview]')].find(node => node.dataset.preview === currentKey);
        const note = preview?.querySelector('[data-report-note]');
        if (note) note.textContent = report.note || '';
        const tbody = preview?.querySelector('tbody');
        if (!tbody) return;
        tbody.replaceChildren(...report.rows.map(row => {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td'); td.className = 'text-nowrap';
                if (Array.isArray(cell)) { const badge = document.createElement('span'); badge.className = `badge rounded-pill bg-${cell[1]}-subtle text-${cell[1]}-emphasis`; badge.textContent = cell[0]; td.append(badge); }
                else td.textContent = cell;
                tr.append(td);
            });
            return tr;
        }));
        if (!report.rows.length) {
            const tr = tbody.insertRow(), td = tr.insertCell(); td.colSpan = report.columns.length;
            td.className = 'text-body-secondary text-center py-3'; td.textContent = report.note || 'No records match this period.';
        }
    }
    document.getElementById('reportPeriod').addEventListener('change', updateReportPeriod);

    document.querySelectorAll('[data-open-report]').forEach(btn =>
        btn.addEventListener('click', () => openReport(btn.dataset.openReport, btn.dataset.mode)));

    // Eye button in the history table re-opens that report's preview
    document.getElementById('historyRows').addEventListener('click', e => {
        const view = e.target.closest('[data-view-report]');
        if (view && view.dataset.viewReport) openReport(view.dataset.viewReport, 'preview');
    });

    // Add a row to the top of "Report Generation History". key = the template it came from ('' for a custom report).
    function addHistory({ report: key = '', name, meta, recipient, period, format, by, at, download }) {
        const row = document.getElementById('historyRowTemplate').content.firstElementChild.cloneNode(true);
        row.dataset.recipient = recipient;
        row.querySelector('[data-cell="name"]').textContent = name;
        row.querySelector('[data-cell="meta"]').textContent = meta;
        row.querySelector('[data-cell="recipient"]').textContent = recipient;
        row.querySelector('[data-cell="period"]').textContent = period.replace('Academic Year', 'AY');
        row.querySelector('[data-cell="format"]').textContent = format === 'Print' ? 'PRINT' : format;
        row.querySelector('[data-cell="by"]').textContent = by;
        row.querySelector('[data-cell="at"]').textContent = at;
        const eye = row.querySelector('[data-view-report]');
        const link = document.createElement('a'); link.className = eye.className;
        link.href = download + '?preview=1'; link.innerHTML = '<i class="bi bi-eye"></i>'; link.title = 'View saved snapshot';
        eye.replaceWith(link);
        const savedDownload = row.querySelector('[data-download-report]');
        savedDownload.href = download; savedDownload.classList.remove('d-none');
        document.getElementById('historyRows').prepend(row);
        applyRecipientFilter();
    }

    function showToast(text) {
        document.getElementById('reportToastText').textContent = text;
        bootstrap.Toast.getOrCreateInstance(document.getElementById('reportToast')).show();
    }

    async function generateSaved(payload, button) {
        if (button.disabled) return false;
        button.disabled = true;
        try {
            const response = await fetch(@json(route('reports.generate')), {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(payload),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Report generation failed.');
            addHistory(result.history);
            showToast('Report saved. Your ' + payload.format + ' download is starting.');
            window.location = result.url;
            return true;
        } catch (error) { showToast(error.message); return false; }
        finally { button.disabled = false; }
    }

    document.getElementById('generateButton').addEventListener('click', async event => {
        const format = document.querySelector('input[name="reportFormat"]:checked').value;
        const period = document.getElementById('reportPeriod').value;
        const year = typeof currentView === 'function' ? currentView().year : new URLSearchParams(location.search).get('year');
        if (await generateSaved({ key: currentKey, format, period, ...(year ? {year} : {}) }, event.currentTarget)) reportModal.hide();
    });

    // Recipient filter for the history table
    function applyRecipientFilter() {
        const filter = document.getElementById('recipientFilter');
        const wanted = filter.value;
        const recipients = [...new Set([...document.querySelectorAll('#historyRows tr[data-recipient]')].map(row => row.dataset.recipient).filter(Boolean))];
        for (const recipient of recipients) {
            if (![...filter.options].some(option => option.value === recipient)) filter.add(new Option(recipient, recipient));
        }
        let shown = 0;
        document.querySelectorAll('#historyRows tr[data-recipient]').forEach(row => {
            const match = !wanted || row.dataset.recipient === wanted;
            row.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        document.getElementById('historyEmpty').classList.toggle('d-none', shown > 0);
    }
    document.getElementById('recipientFilter').addEventListener('change', applyRecipientFilter);
    applyRecipientFilter();

    // ======== Report builders (only on the page when the user has Analytics too) ========
    if (document.getElementById('reportTray')) {
        const $ = id => document.getElementById(id);
        const checkedFormat = name => document.querySelector(`input[name="${name}"]:checked`).value;
        const recipientLabel = to => to === 'Keep for myself' ? 'IATO (self)' : to;

        function customSnapshot(names) {
            const scope = currentView(), engine = window.IARIS_ANALYTICS_FILTERS;
            const selected = engine.select(FILTER_DATA, scope), totals = engine.totals(selected);
            const metrics = ['applicants', 'took', 'passed', 'reserved', 'enrolled'];
            const grouped = (dimension, fields = metrics) => {
                const groups = {};
                selected.forEach(row => { const key = row[dimension] || 'Not recorded'; groups[key] ||= Object.fromEntries(fields.map(f=>[f,0])); fields.forEach(f=>groups[key][f]+=Number(row[f]||0)); });
                return Object.entries(groups).sort(([a],[b])=>a.localeCompare(b)).map(([label, counts])=>[label,...fields.map(f=>String(counts[f]))]);
            };
            return names.map(title => {
                const lower = title.toLowerCase(); let columns, rows;
                if (lower.includes('project')) {
                    columns=['Department','Selected AY enrolled','Previous AY enrolled','Projected next AY (estimate)'];
                    rows=grouped('department').map(([department])=>{const current=selected.filter(r=>r.department===department).reduce((n,r)=>n+r.enrolled,0);const before=engine.previousYearTotals(FILTER_DATA,{...scope,department})?.enrolled || 0;return [department,String(current),String(before),before>0?String(Math.round(current*current/before)):'Prior-year enrollment unavailable'];});
                } else if (lower.includes('insight')) {
                    columns=['Insight','Content']; rows=[['Scope','Cached institution-wide insights; not filtered to the selected department.'], ...[...document.querySelectorAll('.ar-insight-card')].map(card=>[card.querySelector('h3')?.textContent.trim() || 'Insight', card.querySelector('p')?.textContent.trim() || ''])];
                } else if (lower.includes('field count')) {
                    columns=['Field','Count']; rows=['male','female','scholars','paidExam','reserved','noaIssued','pwd','ssen'].map(f=>[f,String(totals[f])]);
                } else if (lower.includes('funnel') || lower.includes('key figure')) {
                    columns=['Metric','Count']; rows=metrics.map(f=>[f,String(totals[f])]);
                } else {
                    const dimension=lower.includes('location')?'location':lower.includes('trend')?'date':'department';
                    columns=[dimension,'Applicants','Took exam','Passed','Reserved','Completed applications']; rows=grouped(dimension);
                }
                return {title, columns, rows};
            });
        }

        async function finish(name, format, to, names, button) {
            const v=currentView();
            const saved=await generateSaved({title:name,format,recipient:recipientLabel(to),period:`${v.range} · ${v.department}`,year:v.year,tables:customSnapshot(names)},button);
            if(saved) bootstrap.Tab.getOrCreateInstance($('tab-reports')).show();
            return saved;
        }

        // Fill the "Date range · Department · Academic year" summary at the top of a builder
        function fillScope(prefix) {
            const v = currentView();
            $(prefix + 'Range').textContent = v.range;
            $(prefix + 'Dept').textContent = v.department;
            $(prefix + 'Year').textContent = v.year;
            return v;
        }

        // ---- "Generate from this view" ----
        $('viewReportModal').addEventListener('show.bs.modal', () => {
            const v = fillScope('view');
            $('viewTitle').value = `${v.department === 'All Departments' ? 'Admissions' : v.department} Analytics Report — ${v.year}`;
            document.querySelectorAll('[name="viewSection"]').forEach(c => c.checked = true);
            $('viewMessage').classList.add('d-none');
        });

        $('viewGenerate').addEventListener('click', async event => {
            const sections = [...document.querySelectorAll('[name="viewSection"]:checked')];
            if (!sections.length) { $('viewMessage').classList.remove('d-none'); return; }
            const v = currentView(), format = checkedFormat('viewFormat'), to = $('viewTo').value;
            const name = $('viewTitle').value.trim() || 'Analytics Report';
            if (await finish(name,format,to,sections.map(s=>s.value),event.currentTarget)) bootstrap.Modal.getOrCreateInstance($('viewReportModal')).hide();
        });

        // ---- "Add to report" tray: the charts you picked, in order ----
        let tray = [];

        function renderTray() {
            $('trayCount').textContent = tray.length;
            $('trayNames').textContent = tray.join(' · ');
            $('reportTray').classList.toggle('show', tray.length > 0);
            document.querySelectorAll('[data-add-report]').forEach(btn => {
                const added = tray.includes(btn.dataset.addReport);
                btn.classList.toggle('btn-primary', added);
                btn.classList.toggle('btn-light', !added);
                btn.setAttribute('aria-pressed', added);
                btn.innerHTML = added ? '<i class="bi bi-check-lg me-1"></i> Added' : '<i class="bi bi-plus-lg me-1"></i> Add to report';
            });
        }

        document.querySelectorAll('[data-add-report]').forEach(btn => btn.addEventListener('click', () => {
            const name = btn.dataset.addReport;
            tray = tray.includes(name) ? tray.filter(n => n !== name) : [...tray, name];
            renderTray();
        }));
        $('trayClear').addEventListener('click', () => { tray = []; renderTray(); });

        // The list inside "Build report": move up / move down / remove
        function drawTrayList() {
            const list = $('trayList');
            list.replaceChildren(...tray.map((name, i) => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex align-items-center gap-2';
                li.innerHTML = `<span class="badge rounded-pill text-bg-primary">${i + 1}</span><span class="flex-grow-1 fw-semibold"></span>
                    <button type="button" class="btn btn-sm btn-light border" data-move="-1" aria-label="Move up" ${i ? '' : 'disabled'}><i class="bi bi-arrow-up"></i></button>
                    <button type="button" class="btn btn-sm btn-light border" data-move="1" aria-label="Move down" ${i < tray.length - 1 ? '' : 'disabled'}><i class="bi bi-arrow-down"></i></button>
                    <button type="button" class="btn btn-sm btn-light border" data-remove aria-label="Remove"><i class="bi bi-x-lg"></i></button>`;
                li.children[1].textContent = name;
                li.dataset.index = i;
                return li;
            }));
            $('trayEmpty').classList.toggle('d-none', tray.length > 0);
        }

        $('trayList').addEventListener('click', e => {
            const btn = e.target.closest('button');
            if (!btn) return;
            const i = Number(btn.closest('li').dataset.index);
            if (btn.hasAttribute('data-remove')) tray.splice(i, 1);
            else { const j = i + Number(btn.dataset.move); [tray[i], tray[j]] = [tray[j], tray[i]]; }
            drawTrayList();
            renderTray();
        });

        $('trayReportModal').addEventListener('show.bs.modal', () => {
            const v = fillScope('tray');
            $('trayTitle').value = `Custom Admissions Report — ${v.year}`;
            drawTrayList();
        });

        $('trayGenerate').addEventListener('click', async event => {
            if (!tray.length) return;
            const v = currentView(), format = checkedFormat('trayFormat'), to = $('trayTo').value;
            const name = $('trayTitle').value.trim() || 'Custom Admissions Report';
            if (await finish(name,format,to,[...tray],event.currentTarget)) {
                bootstrap.Modal.getOrCreateInstance($('trayReportModal')).hide(); tray=[]; renderTray();
            }
        });
    }
</script>
