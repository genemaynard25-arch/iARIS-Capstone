{{--
    Script for the Reports tab (templates, Preview / Generate, history) and, when the user also has
    Analytics, the two report builders: "Generate from this view" and the "Add to report" tray.
    Front end only: nothing is saved yet, rows added to the history disappear on reload.
--}}
<script>
    const REPORTS = @json(collect($reportData['sections'])->pluck('reports')->flatten(1)->keyBy('key'));
    const CURRENT_USER = @json(auth()->user()->name);

    const reportModalEl = document.getElementById('reportModal');
    const reportModal = bootstrap.Modal.getOrCreateInstance(reportModalEl);
    let currentKey = null;

    // Open the modal. "preview" shows the sample table; "generate" skips straight to the settings.
    function openReport(key, mode) {
        const report = REPORTS[key];
        currentKey = key;
        document.getElementById('reportTitle').textContent = (mode === 'generate' ? 'Generate: ' : '') + report.title;
        document.getElementById('reportSubtitle').textContent = `Recipient: ${report.recipient} · ` +
            (mode === 'preview' ? 'Sample data — configure settings below' : 'Configure before generating');
        document.getElementById('reportRecipient').value = report.recipient;
        document.querySelectorAll('[data-preview]').forEach(p => p.classList.toggle('d-none', mode !== 'preview' || p.dataset.preview !== key));
        reportModal.show();
    }

    document.querySelectorAll('[data-open-report]').forEach(btn =>
        btn.addEventListener('click', () => openReport(btn.dataset.openReport, btn.dataset.mode)));

    // Eye button in the history table re-opens that report's preview
    document.getElementById('historyRows').addEventListener('click', e => {
        const view = e.target.closest('[data-view-report]');
        if (view && view.dataset.viewReport) openReport(view.dataset.viewReport, 'preview');
    });

    function nowStamp() {
        const now = new Date();
        return now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' +
            now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    // Add a row to the top of "Report Generation History". key = the template it came from ('' for a custom report).
    function addHistory({ key = '', name, meta, recipient, period, format }) {
        const row = document.getElementById('historyRowTemplate').content.firstElementChild.cloneNode(true);
        row.dataset.recipient = recipient;
        row.querySelector('[data-cell="name"]').textContent = name;
        row.querySelector('[data-cell="meta"]').textContent = meta;
        row.querySelector('[data-cell="recipient"]').textContent = recipient;
        row.querySelector('[data-cell="period"]').textContent = period.replace('Academic Year', 'AY');
        row.querySelector('[data-cell="format"]').textContent = format === 'Print' ? 'PRINT' : format;
        row.querySelector('[data-cell="by"]').textContent = CURRENT_USER;
        row.querySelector('[data-cell="at"]').textContent = nowStamp();
        const eye = row.querySelector('[data-view-report]');
        eye.dataset.viewReport = key;
        if (!key) { eye.disabled = true; eye.title = 'No sample preview for custom reports yet'; }
        document.getElementById('historyRows').prepend(row);
        applyRecipientFilter();
    }

    function showToast(text) {
        document.getElementById('reportToastText').textContent = text;
        bootstrap.Toast.getOrCreateInstance(document.getElementById('reportToast')).show();
    }

    function generatedMessage(name, format, recipient) {
        const sent = recipient && recipient !== 'Keep for myself' ? ` · "sent" to ${recipient}` : '';
        return `"${name}" added to the history${sent}. Real ${format === 'Print' ? 'printing' : format + ' files'} will come once the backend is connected.`;
    }

    // PDF downloads a real file (ReportController::pdf). XLSX/Print aren't built yet - still a
    // history row + toast saying so, same as before.
    document.getElementById('generateButton').addEventListener('click', () => {
        const report = REPORTS[currentKey];
        const format = document.querySelector('input[name="reportFormat"]:checked').value;
        const period = document.getElementById('reportPeriod').value;

        addHistory({ key: currentKey, name: report.title, meta: format === 'PDF' ? 'Downloaded just now' : 'Sample only — not saved',
            recipient: report.recipient, period, format });
        reportModal.hide();

        if (format === 'PDF') {
            window.location = `{{ url('/analytics/reports') }}/${currentKey}/pdf`;
            showToast(`"${report.title}" downloading as a PDF now.`);
        } else {
            showToast(generatedMessage(report.title, format));
        }
    });

    // Recipient filter for the history table
    function applyRecipientFilter() {
        const wanted = document.getElementById('recipientFilter').value;
        let shown = 0;
        document.querySelectorAll('#historyRows tr[data-recipient]').forEach(row => {
            const match = !wanted || row.dataset.recipient === wanted;
            row.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        document.getElementById('historyEmpty').classList.toggle('d-none', shown > 0);
    }
    document.getElementById('recipientFilter').addEventListener('change', applyRecipientFilter);

    // ======== Report builders (only on the page when the user has Analytics too) ========
    if (document.getElementById('reportTray')) {
        const $ = id => document.getElementById(id);
        const checkedFormat = name => document.querySelector(`input[name="${name}"]:checked`).value;
        const recipientLabel = to => to === 'Keep for myself' ? 'IATO (self)' : to;

        // After building a report: show the Reports tab so the new history row is visible
        function finish(name, format, to) {
            bootstrap.Tab.getOrCreateInstance($('tab-reports')).show();
            showToast(generatedMessage(name, format, to));
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

        $('viewGenerate').addEventListener('click', () => {
            const sections = [...document.querySelectorAll('[name="viewSection"]:checked')];
            if (!sections.length) { $('viewMessage').classList.remove('d-none'); return; }
            const v = currentView(), format = checkedFormat('viewFormat'), to = $('viewTo').value;
            const name = $('viewTitle').value.trim() || 'Analytics Report';
            addHistory({ name, meta: `${sections.length} section${sections.length > 1 ? 's' : ''} · ${v.department} · ${v.range}`,
                recipient: recipientLabel(to), period: v.year, format });
            bootstrap.Modal.getOrCreateInstance($('viewReportModal')).hide();
            finish(name, format, to);
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

        $('trayGenerate').addEventListener('click', () => {
            if (!tray.length) return;
            const v = currentView(), format = checkedFormat('trayFormat'), to = $('trayTo').value;
            const name = $('trayTitle').value.trim() || 'Custom Admissions Report';
            addHistory({ name, meta: `${tray.length} chart${tray.length > 1 ? 's' : ''} · ${v.department}`,
                recipient: recipientLabel(to), period: v.year, format });
            bootstrap.Modal.getOrCreateInstance($('trayReportModal')).hide();
            tray = [];
            renderTray();
            finish(name, format, to);
        });
    }
</script>
