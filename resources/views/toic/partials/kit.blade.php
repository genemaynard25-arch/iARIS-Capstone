{{--
    For TOIC's pages: a toast (toast('text')) and a Download menu (downloadMenu(() => ({title, sub, head, rows})))
    that saves a table as PDF (print window), Excel or CSV. Buttons go on the left of drawers; toasts at the bottom left.
--}}
<div class="toast-container position-fixed bottom-0 start-0 p-3">
    <div class="toast align-items-center border-0 text-bg-dark" id="toicToast" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="toicToastText"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
    function toast(text) {
        document.getElementById('toicToastText').textContent = text;
        bootstrap.Toast.getOrCreateInstance(document.getElementById('toicToast')).show();
    }
    function downloadTable(format, d) {
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const name = d.title.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '') || 'report';
        const sub = d.sub || 'iARIS · prepared for TOIC · no applicant data';
        const save = (blob, file) => { const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = file; a.click(); URL.revokeObjectURL(a.href); };
        const table = `<table border="1"><tr><th colspan="${d.head.length}" style="text-align:left">${esc(d.title)}</th></tr><tr><td colspan="${d.head.length}">${esc(sub)}</td></tr><tr>${d.head.map(h => `<th style="background:#EAF5F0">${esc(h)}</th>`).join('')}</tr>${d.rows.map(r => `<tr>${r.map(c => `<td>${esc(c)}</td>`).join('')}</tr>`).join('')}</table>`;
        if (format === 'csv') {
            const csv = [[d.title], [sub], [], d.head, ...d.rows].map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            save(new Blob(['﻿' + csv], { type: 'text/csv' }), name + '.csv');
            return toast('Downloaded as CSV.');
        }
        if (format === 'excel') {
            save(new Blob(['﻿<html><head><meta charset="utf-8"></head><body>' + table + '</body></html>'], { type: 'application/vnd.ms-excel' }), name + '.xls');
            return toast('Downloaded as Excel.');
        }
        const w = window.open('', '_blank');
        if (!w) return toast('Allow pop-ups to open the PDF.');
        w.document.write(`<!doctype html><title>${esc(d.title)}</title><style>body{font-family:sans-serif;padding:24px}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}</style>${table}`);
        w.document.close();
        w.print();
        toast('Print window opened. Choose "Save as PDF".');
    }
    // A "Download" button with PDF / Excel / CSV. Focus returns to the button afterwards.
    function downloadMenu(getData, small = true) {
        const wrap = document.createElement('div');
        wrap.className = 'dropdown d-inline-block';
        wrap.innerHTML = `<button type="button" class="btn ${small ? 'btn-sm' : ''} btn-light border fw-semibold text-nowrap dropdown-toggle" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false"><i class="bi bi-download me-1"></i> Download</button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><h6 class="dropdown-header">Download as</h6></li>
                <li><button type="button" class="dropdown-item" data-format="pdf"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</button></li>
                <li><button type="button" class="dropdown-item" data-format="excel"><i class="bi bi-file-earmark-excel text-success me-2"></i>Excel</button></li>
                <li><button type="button" class="dropdown-item" data-format="csv"><i class="bi bi-filetype-csv text-primary me-2"></i>CSV</button></li>
            </ul>`;
        wrap.querySelectorAll('[data-format]').forEach(b => b.addEventListener('click', () => { downloadTable(b.dataset.format, getData()); wrap.querySelector('.dropdown-toggle').focus(); }));
        return wrap;
    }
    // Reads a <table> into {head, rows}
    const tableData = el => ({ head: [...el.querySelectorAll('thead th')].map(x => x.textContent.trim()), rows: [...el.querySelectorAll('tbody tr')].map(tr => [...tr.children].map(td => td.textContent.trim())) });
</script>
