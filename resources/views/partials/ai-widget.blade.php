@php
    $sensitivePage = request()->is(
        'applicants*',
        'review-queue*',
        'submissions*',
        'records/*',
        'lamp/scholars*',
        'college/applicants*',
    );
@endphp

<div id="aiWidget">
    <button id="aiWidgetToggle" class="btn btn-primary rounded-circle shadow" type="button" aria-label="Open AI Assistant"
        style="position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; z-index: 2000;">
        <i class="bi bi-stars fs-5"></i>
    </button>

    <div id="aiWidgetPanel" class="d-none shadow-lg rounded-4 bg-white"
        style="position: fixed; bottom: 92px; right: 24px; width: 380px; height: 480px; min-width: 300px; min-height: 360px; z-index: 2000; display: flex; flex-direction: column; overflow: hidden;">

        <div id="aiHandleN" style="position:absolute; top:0; left:8px; right:8px; height:6px; cursor:ns-resize; z-index:2010;"></div>
        <div id="aiHandleS" style="position:absolute; bottom:0; left:8px; right:8px; height:6px; cursor:ns-resize; z-index:2010;"></div>
        <div id="aiHandleW" style="position:absolute; left:0; top:8px; bottom:8px; width:6px; cursor:ew-resize; z-index:2010;"></div>
        <div id="aiHandleE" style="position:absolute; right:0; top:8px; bottom:8px; width:6px; cursor:ew-resize; z-index:2010;"></div>
        <div id="aiHandleNW" style="position:absolute; top:0; left:0; width:12px; height:12px; cursor:nwse-resize; z-index:2011;"></div>
        <div id="aiHandleNE" style="position:absolute; top:0; right:0; width:12px; height:12px; cursor:nesw-resize; z-index:2011;"></div>
        <div id="aiHandleSW" style="position:absolute; bottom:0; left:0; width:12px; height:12px; cursor:nesw-resize; z-index:2011;"></div>
        <div id="aiHandleSE" style="position:absolute; bottom:0; right:0; width:12px; height:12px; cursor:nwse-resize; z-index:2011;"></div>

        <div class="bg-iaris text-white d-flex align-items-center justify-content-between px-3 py-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-stars"></i>
                <span class="fw-bold small">AI Assistant</span>
            </div>
            <button id="aiWidgetClose" type="button" class="btn-close btn-close-white btn-sm" aria-label="Close"></button>
        </div>

        {{-- Disclaimer Banner --}}
        <div class="small text-body-secondary px-3 py-2 border-bottom bg-body-tertiary" style="font-size: 0.75rem;">
            <i class="bi bi-shield-check me-1"></i>
            I can read and chart data on pages like Dashboard, Analytics, and Reports. For privacy, I don't read information on Applicants, Records, Review Queue, or Submissions pages.
        </div>

        <div id="aiWidgetMessages" class="flex-grow-1 overflow-auto p-3 small" style="background: #F5F7F6;"></div>
            <div class="d-flex flex-wrap gap-1 px-2 pt-2" style="background: #F5F7F6;">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" data-suggest="about" style="font-size: 0.72rem;">What can you do?</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" data-suggest="privacy" style="font-size: 0.72rem;">What don't you read?</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" data-suggest="summarize" style="font-size: 0.72rem;">Summarize this page</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" data-suggest="chart" style="font-size: 0.72rem;">Make a chart from this</button>
            </div>
        <form id="aiWidgetForm" class="d-flex gap-2 p-2 border-top bg-white">
            <input type="text" id="aiWidgetInput" class="form-control form-control-sm" placeholder="Ask about this page..." autocomplete="off" required>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send"></i></button>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
(function () {
    const toggle = document.getElementById('aiWidgetToggle');
    const panel = document.getElementById('aiWidgetPanel');
    const closeBtn = document.getElementById('aiWidgetClose');
    const form = document.getElementById('aiWidgetForm');
    const input = document.getElementById('aiWidgetInput');
    const messages = document.getElementById('aiWidgetMessages');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const SENSITIVE_PAGE = @json($sensitivePage);
    const CANNED_REPLIES = {
        about: "I can answer questions about the page you're viewing and build charts (bar, line, or pie) from the data shown, when it helps. I'm here to help you read your admissions data faster, not to replace your own judgment on decisions.",
        privacy: "For privacy, I don't read or send what's shown on the Applicants, Records, Review Queue, or Submissions pages as those contain individual student data. I still work on those pages for general questions, I just can't see what's on screen there. On pages like Dashboard, Analytics, and Reports, I can read the page and help with it directly.",
    };

    toggle.addEventListener('click', () => panel.classList.toggle('d-none'));
    closeBtn.addEventListener('click', () => panel.classList.add('d-none'));

    // ---- Bootstrap modal compatibility ----
    // Reparent the panel into an open modal so Bootstrap's focus-trap treats
    // it as part of the modal (otherwise typing into it gets blocked).
    document.addEventListener('shown.bs.modal', (e) => {
        panel._homeParent = panel.parentNode;
        panel._homeNext = panel.nextSibling;
        e.target.appendChild(panel);
    });

    document.addEventListener('hidden.bs.modal', () => {
        if (!panel._homeParent) return;
        if (panel._homeNext) {
            panel._homeParent.insertBefore(panel, panel._homeNext);
        } else {
            panel._homeParent.appendChild(panel);
        }
        panel._homeParent = null;
        panel._homeNext = null;
    });

    // ---- Resizing from any edge or corner ----
    let resizing = null;
    let startX, startY, startW, startH, startTop, startLeft;
    const MIN_W = 300, MIN_H = 360;

    function beginResize(dir) {
        return function (e) {
            resizing = dir;
            startX = e.clientX;
            startY = e.clientY;
            const rect = panel.getBoundingClientRect();
            startW = rect.width;
            startH = rect.height;
            startTop = rect.top;
            startLeft = rect.left;
            panel.style.top = startTop + 'px';
            panel.style.left = startLeft + 'px';
            panel.style.right = 'auto';
            panel.style.bottom = 'auto';
            panel.style.width = startW + 'px';
            panel.style.height = startH + 'px';
            document.body.style.userSelect = 'none';
            e.preventDefault();
        };
    }

    function doResize(e) {
        if (!resizing) return;
        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        if (resizing.includes('e')) panel.style.width = Math.max(MIN_W, startW + dx) + 'px';
        if (resizing.includes('s')) panel.style.height = Math.max(MIN_H, startH + dy) + 'px';
        if (resizing.includes('w')) {
            const newW = Math.max(MIN_W, startW - dx);
            panel.style.width = newW + 'px';
            panel.style.left = (startLeft + (startW - newW)) + 'px';
        }
        if (resizing.includes('n')) {
            const newH = Math.max(MIN_H, startH - dy);
            panel.style.height = newH + 'px';
            panel.style.top = (startTop + (startH - newH)) + 'px';
        }
    }

    function endResize() {
        resizing = null;
        document.body.style.userSelect = '';
    }

    ['N', 'S', 'E', 'W', 'NE', 'NW', 'SE', 'SW'].forEach(dir => {
        const handle = document.getElementById('aiHandle' + dir);
        handle.addEventListener('mousedown', beginResize(dir.toLowerCase()));
    });
    document.addEventListener('mousemove', doResize);
    document.addEventListener('mouseup', endResize);

    // ---- Lightweight, safe markdown rendering for AI replies ----
    // Escapes HTML first (so the AI can never inject markup), then turns
    // **bold** and "* " bullet lines into real formatting.
    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatAiText(text) {
        const lines = escapeHtml(text).split('\n');
        let html = '';
        let inList = false;

        lines.forEach(line => {
            const bulletMatch = line.match(/^\s*[*-]\s+(.*)/);
            if (bulletMatch) {
                if (!inList) { html += '<ul class="mb-1 ps-3">'; inList = true; }
                html += `<li>${bulletMatch[1]}</li>`;
            } else {
                if (inList) { html += '</ul>'; inList = false; }
                html += line.trim() === '' ? '<div class="mb-1"></div>' : `<div>${line}</div>`;
            }
        });
        if (inList) html += '</ul>';

        return html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    }

    // ---- Chat ----
    function addMessage(text, who) {
        const row = document.createElement('div');
        row.className = 'mb-2 ' + (who === 'user' ? 'text-end' : 'text-start');
        const bubble = document.createElement('div');
        bubble.className = 'd-inline-block px-3 py-2 rounded-3 ' + (who === 'user' ? 'bg-primary text-white' : 'bg-white border');
        bubble.style.maxWidth = '90%';
        bubble.style.textAlign = 'left';
        bubble.textContent = text;
        row.appendChild(bubble);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    function addChart(spec) {
        const wrap = document.createElement('div');
        wrap.className = 'mb-2';
        wrap.style.background = '#fff';
        wrap.style.border = '1px solid #dee2e6';
        wrap.style.borderRadius = '0.5rem';
        wrap.style.padding = '10px';

        const canvasBox = document.createElement('div');
        canvasBox.style.height = '240px';
        canvasBox.style.position = 'relative';
        const canvas = document.createElement('canvas');
        canvasBox.appendChild(canvas);
        wrap.appendChild(canvasBox);

        const downloadLink = document.createElement('a');
        downloadLink.href = '#';
        downloadLink.className = 'small d-inline-flex align-items-center gap-1 mt-2 text-decoration-none';
        downloadLink.innerHTML = '<i class="bi bi-download"></i> Download chart';
        wrap.appendChild(downloadLink);

        messages.appendChild(wrap);
        messages.scrollTop = messages.scrollHeight;

        const isPie = spec.type === 'pie';
        const brand = '#00795A';
        // Pie slices need distinct hues; a single-series bar/line should stay
        // one consistent colour so it doesn't visually read as "categories".
        const pieColors = ['#00795A', '#2a78d6', '#eb6834', '#8C918E', '#7FC4AB', '#C27BA0'];

        // Draws the value above each bar so numbers are readable at a glance,
        // without needing to hover for the tooltip.
        const valueLabels = {
            id: 'valueLabels',
            afterDatasetsDraw(chart) {
                if (isPie) return;
                const { ctx } = chart;
                ctx.save();
                ctx.font = '600 11px Inter, system-ui, sans-serif';
                ctx.fillStyle = '#1A2B26';
                ctx.textAlign = 'center';
                chart.data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);
                    meta.data.forEach((bar, idx) => {
                        const value = dataset.data[idx];
                        ctx.fillText(Number(value).toLocaleString(), bar.x, bar.y - 6);
                    });
                });
                ctx.restore();
            },
        };

        new Chart(canvas, {
            type: isPie ? 'pie' : (spec.type === 'line' ? 'line' : 'bar'),
            data: {
                labels: spec.labels || [],
                datasets: [{
                    label: spec.title || '',
                    data: spec.values || [],
                    backgroundColor: isPie ? pieColors : brand,
                    borderColor: spec.type === 'line' ? brand : (isPie ? '#fff' : brand),
                    borderWidth: isPie ? 2 : 1,
                    borderRadius: isPie ? 0 : 4,
                    fill: spec.type === 'line' ? false : undefined,
                    tension: 0,
                }],
            },
            options: {
                maintainAspectRatio: false,
                layout: { padding: { top: 20 } },
                plugins: {
                    legend: { display: isPie, position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } },
                    title: { display: !!spec.title, text: spec.title || '', font: { size: 12, weight: '600' }, padding: { bottom: 10 } },
                    tooltip: { padding: 8, backgroundColor: '#1A2B26' },
                },
                scales: isPie ? {} : {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: '#E7ECEA' }, ticks: { font: { size: 10 } } },
                },
            },
            plugins: [valueLabels],
        });

        downloadLink.addEventListener('click', (e) => {
            e.preventDefault();
            // Chart.js canvases are transparent by default; paint a white
            // backing behind it first so the downloaded PNG isn't see-through.
            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = canvas.width;
            exportCanvas.height = canvas.height;
            const ctx = exportCanvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
            ctx.drawImage(canvas, 0, 0);

            const link = document.createElement('a');
            link.download = (spec.title || 'chart').replace(/[^a-z0-9]+/gi, '_').toLowerCase() + '.png';
            link.href = exportCanvas.toDataURL('image/png');
            link.click();
        });
    }

    function pageContext() {
        if (SENSITIVE_PAGE) return '';   // don't send individual student/applicant data off-site
        const main = document.querySelector('main');
        const text = main ? main.innerText : document.body.innerText;
        return text.replace(/\s+/g, ' ').trim().slice(0, 4000);
    }
    
    // Handler for clicking on the suggestions
    document.querySelectorAll('[data-suggest]').forEach(btn => {
    btn.addEventListener('click', () => {
        const key = btn.dataset.suggest;
        if (CANNED_REPLIES[key]) {
            addMessage(btn.textContent, 'user');
            addMessage(CANNED_REPLIES[key], 'ai');
            return;
        }
        input.value = btn.textContent;
        form.requestSubmit();
        });
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const question = input.value.trim();
        if (!question) return;
        addMessage(question, 'user');
        input.value = '';
        const thinking = addMessage('Thinking…', 'ai');

        try {
            const res = await fetch('/ai-widget/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: question, context: pageContext() }),
            });
            const data = await res.json();
            let reply = data.reply || 'Sorry, something went wrong.';

            let chartSpec = null;
            const match = reply.match(/CHART:\s*(\{[\s\S]*\})\s*$/);
            if (match) {
                try { chartSpec = JSON.parse(match[1]); } catch (err) { chartSpec = null; }
                reply = reply.replace(match[0], '').trim();
            }

            thinking.innerHTML = formatAiText(reply);
            thinking.parentElement.style.textAlign = 'left';
            if (chartSpec) addChart(chartSpec);
        } catch (err) {
            thinking.textContent = 'Sorry, something went wrong reaching the AI assistant.';
        }
    });
})();
</script>