@extends('layouts.app')

{{--
    Issues (ToicController::issuesPage): every problem reported to TOIC, from "Report a problem" and system checks.
    TODO: the Issues mockup is still to come. Until then: a simple list, and each issue's history in a drawer.
    The list is kept in this browser (partials/issues-store).
--}}

@section('title', 'iARIS — Issues')
@section('page-title', 'Issues')
@section('page-subtitle', 'Problems reported by users and found by system checks')

@section('content')
    @include('toic.partials.privacy')

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light"><tr><th class="ps-4 text-body-secondary">Issue</th><th class="text-body-secondary">Area</th><th class="text-body-secondary">Severity</th><th class="text-body-secondary">Status</th><th class="pe-4 text-body-secondary">Reported</th></tr></thead>
                <tbody id="issues"></tbody>
            </table>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="issueDrawer" aria-labelledby="issueTitle" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start p-4">
            <div><h2 class="offcanvas-title fs-5 fw-bold mb-1" id="issueTitle"></h2><div class="small text-white-50" id="issueSub"></div></div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4" id="issueBody"></div>
    </div>
@endsection

@section('scripts')
    <script>
        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const STATUS = { new: ['New', 'danger'], investigating: ['Investigating', 'warning'], fixed: ['Fixed', 'success'], closed: ['Closed', 'secondary'] };
        const SEVERITY = { Critical: 'danger', High: 'warning', Normal: 'secondary' };
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('issueDrawer'));

        $('issues').innerHTML = IARIS_ISSUES.all().map(x => `<tr role="button" tabindex="0" data-id="${x.id}">
            <td class="ps-4"><div class="fw-semibold">${esc(x.title)}</div><div class="text-body-secondary">${x.id} · ${esc(x.src)}</div></td>
            <td>${esc(x.svc)}</td>
            <td><span class="badge rounded-pill bg-${SEVERITY[x.sev] || 'secondary'}-subtle text-${SEVERITY[x.sev] || 'secondary'}-emphasis">${esc(x.sev)}</span></td>
            <td><span class="badge rounded-pill bg-${STATUS[x.st][1]}-subtle text-${STATUS[x.st][1]}-emphasis">${STATUS[x.st][0]}</span></td>
            <td class="pe-4 text-nowrap text-body-secondary">${esc(x.at)}</td></tr>`).join('');

        function open(id) {
            const x = IARIS_ISSUES.get(id);
            if (!x) return;
            $('issueTitle').textContent = x.title;
            $('issueSub').textContent = `${x.id} · ${x.svc} · ${STATUS[x.st][0]}`;
            $('issueBody').innerHTML = `<div class="small text-body-secondary mb-3">${esc(x.src)} · Page: ${esc(x.page)}</div>`
                + x.tl.map(m => `<div class="rounded-3 p-3 mb-2 small ${m.toic ? 'bg-primary-subtle' : 'bg-body-tertiary'}"><div class="fw-semibold">${esc(m.who)} <span class="fw-normal text-body-secondary">· ${esc(m.role)} · ${esc(m.at)}</span></div><div>${esc(m.text)}</div></div>`).join('')
                + (x.fix ? `<div class="alert alert-success small mt-3 mb-0"><b>Fix:</b> ${esc(x.fix)}</div>` : '');
            drawer.show();
        }
        $('issues').querySelectorAll('tr[data-id]').forEach(tr => {
            tr.addEventListener('click', () => open(tr.dataset.id));
            tr.addEventListener('keydown', e => { if (e.key === 'Enter') open(tr.dataset.id); });
        });
        // System Overview links here with #INC-0034
        if (location.hash) open(decodeURIComponent(location.hash.slice(1)));
    </script>
@endsection
