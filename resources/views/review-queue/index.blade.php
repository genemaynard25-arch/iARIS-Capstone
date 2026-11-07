@extends('layouts.app')

{{--
    Review Queue (ReviewQueueController): changes wait here until the Super Admin approves them.
    FRONT END ONLY: the items are placeholders and Approve / Send back change this page until reload.
    Everything is drawn by the script from $items.
--}}

@section('title', 'iARIS — Review Queue')
@section('page-title', 'Review Queue')
@section('page-subtitle', 'Check, correct, and approve changes before other users can see them')

@php
    // Source type => [label, icon, Bootstrap colour]
    $types = [
        'edit' => ['Staff edit', 'bi-pencil', 'primary'],
        'import' => ['Batch import', 'bi-cloud-arrow-up', 'info'],
        'lamp' => ['LAMP Office', 'bi-award', 'warning'],
        'self' => ['Applicant update', 'bi-person-gear', 'secondary'],
    ];
@endphp

@section('content')
    {{-- How data moves --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 d-flex flex-wrap align-items-center gap-2 small">
            @foreach ([
                ['bi-inbox', 'Staff edits, imports, LAMP updates, applicant updates'],
                ['bi-person-check', 'You review and correct'],
                ['bi-eye', 'Approved data becomes visible to Deans, Registrar, LAMP, and leadership'],
            ] as [$icon, $text])
                <span class="d-flex align-items-center gap-2 rounded-pill bg-body-tertiary border px-3 py-2 fw-semibold"><i class="bi {{ $icon }} text-primary"></i> {{ $text }}</span>
                @unless ($loop->last)<i class="bi bi-arrow-right text-body-secondary"></i>@endunless
            @endforeach
        </div>
    </div>

    {{-- Stat cards (filled in by the script) --}}
    <div class="row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ([
            ['waiting', 'Waiting for review', 'warning', 'Not visible to other users yet'],
            ['approvedToday', 'Approved today', 'primary', 'Published to other users'],
            ['sent', 'Sent back', 'danger', 'Waiting for the submitter'],
            ['oldest', 'Oldest waiting', 'info', ''],
        ] as [$key, $label, $tone, $sub])
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $tone }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                        <div class="fs-3 fw-bold text-{{ $tone }}-emphasis" data-stat="{{ $key }}">0</div>
                        <div class="small text-body-secondary" data-stat-sub="{{ $key }}">{{ $sub }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search applicant, file, or ID…" aria-label="Search the queue">
            </div>
            <select class="form-select w-auto" id="typeFilter" aria-label="Filter by source">
                <option value="">All sources</option>
                @foreach ($types as $key => [$label])
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="old">Sort: Oldest first</option>
                <option value="new">Sort: Newest first</option>
            </select>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="statusPills" role="group" aria-label="Filter by status"></div>

        {{-- Bulk approve: shown when waiting items are ticked --}}
        <div class="d-none align-items-center gap-2 px-4 py-2 bg-primary-subtle border-bottom" id="bulkBar">
            <span class="fw-bold text-primary-emphasis me-2" id="bulkLabel"></span>
            <button type="button" class="btn btn-sm btn-primary fw-semibold" id="bulkApprove"><i class="bi bi-check2-all me-1"></i> Approve &amp; publish</button>
            <button type="button" class="btn btn-sm btn-light border ms-auto" id="bulkClear"><i class="bi bi-x-lg me-1"></i> Clear</button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr class="small text-uppercase text-nowrap">
                        <th class="ps-4" style="width: 1%;"><input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all waiting items"></th>
                        <th class="text-body-secondary fw-bold">Change</th>
                        <th class="text-body-secondary fw-bold">Unit</th>
                        <th class="text-body-secondary fw-bold">Submitted by</th>
                        <th class="text-body-secondary fw-bold">Size</th>
                        <th class="text-body-secondary fw-bold">Submitted</th>
                        <th class="text-body-secondary fw-bold">Status</th>
                        <th class="pe-4"></th>
                    </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3 border-top small text-body-secondary">
            <span id="footerText"></span>
            <span><i class="bi bi-info-circle me-1"></i> Front end only for now: approvals and send-backs aren't saved yet.</span>
        </div>
    </div>

    {{-- Drawer --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="itemDrawer" aria-labelledby="drawerTitle" style="--bs-offcanvas-width: 560px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <span class="icon-circle rounded-3 bg-white bg-opacity-25 fs-5 d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi" id="drawerIcon"></i></span>
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerTitle"></h2>
                <div class="small text-white-50 mb-2" id="drawerSub"></div>
                <span class="badge rounded-pill" id="drawerBadge"></span>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4" id="drawerBody"></div>
        {{-- Buttons on the left: the AI button sits in the bottom-right corner --}}
        <div class="d-flex flex-wrap gap-2 border-top p-3" id="drawerActions">
            <button type="button" class="btn btn-primary fw-semibold" id="approveButton"><i class="bi bi-check2-circle me-1"></i> Approve &amp; publish</button>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#sendBackModal"><i class="bi bi-arrow-counterclockwise me-1"></i> Send back</button>
        </div>
    </div>

    {{-- Send back: needs a reason --}}
    <div class="modal fade" id="sendBackModal" tabindex="-1" aria-labelledby="sendBackTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4" id="sendBackForm" novalidate>
                <div class="modal-body p-4">
                    <h3 class="fs-5 fw-bold mb-1" id="sendBackTitle">Send back for correction</h3>
                    <p class="small text-body-secondary">The submitter will see your reason and can resubmit.</p>
                    <label for="sendBackReason" class="form-label small fw-bold text-uppercase">Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="sendBackReason" rows="3" required></textarea>
                    <div class="invalid-feedback">Please write a reason so they know what to fix.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold">Send back</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="queueToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="queueToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const ITEMS = @json($items);
        const TYPES = @json($types);
        const STATUS = { waiting: ['Waiting', 'warning', 'bi-hourglass-split'], approved: ['Approved', 'primary', 'bi-check-circle'], sent: ['Sent back', 'danger', 'bi-arrow-counterclockwise'] };
        const state = { status: 'waiting', selected: new Set(), open: null, approvedToday: 0 };

        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        // "2026-06-12 10:14" -> "Jun 12, 2026 · 10:14 AM"
        const when = at => { const d = new Date(at.replace(' ', 'T'));
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }); };
        const size = q => q.type === 'import' ? `${q.summary.total} rows` : `${q.changes.length} field${q.changes.length > 1 ? 's' : ''}`;
        const drawer = bootstrap.Offcanvas.getOrCreateInstance($('itemDrawer'));
        const toast = text => { $('queueToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('queueToast')).show(); };
        const nowLabel = () => 'Just now';

        // ---- Stat cards and status pills ----
        function renderStats() {
            const waiting = ITEMS.filter(q => q.status === 'waiting');
            const oldest = waiting.map(q => q.at).sort()[0];
            const days = oldest ? Math.floor((Date.now() - new Date(oldest.replace(' ', 'T'))) / 864e5) : null;
            document.querySelector('[data-stat="waiting"]').textContent = waiting.length;
            document.querySelector('[data-stat="approvedToday"]').textContent = state.approvedToday;
            document.querySelector('[data-stat="sent"]').textContent = ITEMS.filter(q => q.status === 'sent').length;
            document.querySelector('[data-stat="oldest"]').textContent = days === null ? '—' : days === 0 ? 'Today' : `${days} day${days > 1 ? 's' : ''}`;
            document.querySelector('[data-stat-sub="oldest"]').textContent = oldest ? `Since ${when(oldest)}` : 'Queue is clear';

            const counts = { waiting: waiting.length, sent: ITEMS.filter(q => q.status === 'sent').length, approved: ITEMS.filter(q => q.status === 'approved').length, all: ITEMS.length };
            $('statusPills').innerHTML = [['waiting', 'Waiting'], ['sent', 'Sent back'], ['approved', 'Approved'], ['all', 'All']].map(([k, label]) =>
                `<button type="button" class="btn btn-sm rounded-pill fw-semibold ${k === state.status ? 'btn-primary' : 'btn-light border'}" data-status="${k}" aria-pressed="${k === state.status}">${label} <span class="badge rounded-pill ${k === state.status ? 'bg-white text-primary' : 'bg-body-secondary text-body'} ms-1">${counts[k]}</span></button>`).join('');
        }

        // ---- Table ----
        function renderRows() {
            const q = $('search').value.trim().toLowerCase(), type = $('typeFilter').value, sort = $('sort').value;
            const list = ITEMS.filter(x => (state.status === 'all' || x.status === state.status) && (!type || x.type === type)
                && (!q || [x.subject, x.ref, x.id, x.title, x.by].join(' ').toLowerCase().includes(q)))
                .sort((a, b) => sort === 'new' ? b.at.localeCompare(a.at) : a.at.localeCompare(b.at));

            $('rows').innerHTML = list.length ? list.map(x => {
                const [label, icon, tone] = TYPES[x.type], [stLabel, stTone, stIcon] = STATUS[x.status];
                return `<tr role="button" tabindex="0" data-id="${x.id}">
                    <td class="ps-4">${x.status === 'waiting' ? `<input type="checkbox" class="form-check-input row-check" data-id="${x.id}" aria-label="Select ${esc(x.id)}" ${state.selected.has(x.id) ? 'checked' : ''}>` : ''}</td>
                    <td><div class="d-flex align-items-center gap-3">
                        <span class="icon-circle rounded-3 bg-${tone}-subtle text-${tone}-emphasis d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi ${icon}"></i></span>
                        <div><div class="fw-semibold">${esc(x.title)} · ${esc(x.subject)}</div><div class="small text-body-secondary">${esc(x.id)} · ${esc(label)} · ${esc(x.ref)}</div></div></div></td>
                    <td class="small">${esc(x.unit)}</td>
                    <td><div class="small fw-semibold">${esc(x.by)}</div><div class="small text-body-secondary">${esc(x.role)}</div></td>
                    <td class="small text-nowrap">${size(x)}${x.type === 'import' && x.summary.errors ? ` <span class="text-danger">· ${x.summary.errors} errors</span>` : ''}</td>
                    <td class="small text-body-secondary text-nowrap">${when(x.at)}</td>
                    <td><span class="badge rounded-pill bg-${stTone}-subtle text-${stTone}-emphasis"><i class="bi ${stIcon} me-1"></i>${stLabel}</span></td>
                    <td class="pe-4 text-end"><i class="bi bi-chevron-right text-body-secondary"></i></td></tr>`;
            }).join('') : `<tr><td colspan="8" class="text-center text-body-secondary py-5"><i class="bi bi-check2-circle fs-3 d-block mb-2 text-primary"></i>${state.status === 'waiting' ? 'Nothing is waiting for review. Everything other users see is approved.' : 'No items match these filters.'}</td></tr>`;
            $('footerText').textContent = `Showing ${list.length} of ${ITEMS.length} items`;

            const waitingShown = list.filter(x => x.status === 'waiting');
            $('selectAll').disabled = !waitingShown.length;
            $('selectAll').checked = waitingShown.length > 0 && waitingShown.every(x => state.selected.has(x.id));
            renderBulk();
        }

        function renderBulk() {
            $('bulkBar').classList.toggle('d-none', !state.selected.size);
            $('bulkBar').classList.toggle('d-flex', state.selected.size > 0);
            $('bulkLabel').textContent = `${state.selected.size} selected`;
        }

        function render() { renderStats(); renderRows(); }

        // ---- Approve / send back ----
        function approve(ids) {
            let skipped = 0;
            ids.forEach(id => {
                const q = ITEMS.find(x => x.id === id);
                if (q.type === 'import') skipped += q.errors.length;   // rows with errors aren't published
                q.status = 'approved';
                q.reviewed = `Approved by you · ${nowLabel()}`;
                state.selected.delete(id);
            });
            state.approvedToday += ids.length;
            render();
            toast(`${ids.length} item${ids.length > 1 ? 's' : ''} approved on this page`
                + (skipped ? ` · ${skipped} import row${skipped > 1 ? 's' : ''} with errors would be skipped` : '')
                + '. Not saved yet: needs the backend.');
        }

        // ---- Drawer ----
        function openItem(id) {
            const q = ITEMS.find(x => x.id === id);
            state.open = q;
            const [label, icon, tone] = TYPES[q.type], [stLabel, stTone] = STATUS[q.status];
            $('drawerIcon').className = `bi ${icon}`;
            $('drawerTitle').textContent = `${q.title} · ${q.subject}`;
            $('drawerSub').textContent = `${q.id} · ${label} · ${q.ref}`;
            $('drawerBadge').className = `badge rounded-pill bg-${stTone}-subtle text-${stTone}-emphasis`;
            $('drawerBadge').textContent = stLabel;

            // Who can see it
            const visibility = q.status === 'approved'
                ? ['primary', 'bi-eye', `<b>Published.</b> ${esc(q.reviewed)}. Deans, the Registrar, LAMP, and leadership now see this data.`]
                : q.status === 'sent'
                    ? ['danger', 'bi-arrow-counterclockwise', `<b>Sent back.</b> ${esc(q.reviewed)}. Reason: ${esc(q.reason)}`]
                    : ['warning', 'bi-eye-slash', '<b>Not visible to other users yet.</b> Approve it to publish, or send it back with a reason.'];
            let html = `<div class="alert alert-${visibility[0]} small d-flex gap-2"><i class="bi ${visibility[1]} mt-1"></i><div>${visibility[2]}</div></div>
                <dl class="row g-2 small mb-3">
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Submitted by</dt><dd class="fw-bold mb-0">${esc(q.by)} · ${esc(q.role)}</dd></div>
                    <div class="col-6"><dt class="text-body-secondary fw-semibold">Submitted</dt><dd class="fw-bold mb-0">${when(q.at)}</dd></div>
                    <div class="col-12"><dt class="text-body-secondary fw-semibold">Unit</dt><dd class="fw-bold mb-0">${esc(q.unit)}</dd></div>
                    ${q.note ? `<div class="col-12"><dt class="text-body-secondary fw-semibold">Note from submitter</dt><dd class="mb-0 fst-italic">“${esc(q.note)}”</dd></div>` : ''}
                </dl>`;

            if (q.type === 'import') {
                const s = q.summary;
                html += `<div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Import summary</div>
                    <div class="row row-cols-4 g-2 mb-3 text-center">${[['Rows', s.total, 'body'], ['New', s.new, 'primary'], ['Updated', s.updated, 'info'], ['Errors', s.errors, s.errors ? 'danger' : 'body']].map(([l, v, t]) =>
                        `<div class="col"><div class="rounded-3 bg-body-tertiary p-2"><div class="fs-5 fw-bold text-${t === 'body' ? 'body' : t + '-emphasis'}">${v}</div><div class="small text-body-secondary">${l}</div></div></div>`).join('')}</div>
                    <div class="small fw-bold mb-2">Sample rows</div>
                    <table class="table table-sm small mb-3"><tbody>${q.sample.map(r => `<tr><td>${esc(r[0])}</td><td class="text-body-secondary">${esc(r[1])}</td><td class="text-end"><span class="badge bg-body-tertiary text-body border">${esc(r[2])}</span></td></tr>`).join('')}</tbody></table>`
                    + (q.errors.length ? `<div class="small fw-bold mb-2 text-danger">Rows with errors (skipped when approved)</div>
                    <table class="table table-sm small mb-0"><thead><tr class="text-uppercase text-body-secondary"><th>Row</th><th>Applicant</th><th>Field</th><th>Value</th><th>Problem</th></tr></thead><tbody>${q.errors.map(e =>
                        `<tr><td>${e[0]}</td><td>${esc(e[1])}</td><td>${esc(e[2])}</td><td class="font-monospace">${esc(e[3])}</td><td class="text-danger">${esc(e[4])}</td></tr>`).join('')}</tbody></table>` : '');
            } else {
                html += `<div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Before / after</div>
                    <div class="border rounded-3 overflow-hidden"><table class="table table-sm small mb-0"><thead class="table-light"><tr class="text-uppercase"><th class="ps-3 text-body-secondary">Field</th><th class="text-body-secondary">Before</th><th class="pe-3 text-body-secondary">After</th></tr></thead>
                    <tbody>${q.changes.map(c => `<tr><td class="ps-3 fw-semibold">${esc(c[0])}</td><td class="text-body-secondary text-decoration-line-through">${esc(c[1])}</td><td class="pe-3 fw-bold text-primary-emphasis">${esc(c[2])}</td></tr>`).join('')}</tbody></table></div>`;
            }
            $('drawerBody').innerHTML = html;
            $('drawerActions').classList.toggle('d-none', q.status !== 'waiting');
            drawer.show();
        }

        // ---- Events ----
        $('statusPills').addEventListener('click', e => { const b = e.target.closest('[data-status]'); if (b) { state.status = b.dataset.status; state.selected.clear(); render(); } });
        ['search', 'typeFilter', 'sort'].forEach(id => $(id).addEventListener(id === 'search' ? 'input' : 'change', renderRows));
        $('rows').addEventListener('click', e => {
            if (e.target.closest('.row-check')) return;
            const tr = e.target.closest('tr[data-id]');
            if (tr) openItem(tr.dataset.id);
        });
        $('rows').addEventListener('keydown', e => { const tr = e.target.closest('tr[data-id]'); if (tr && e.key === 'Enter') openItem(tr.dataset.id); });
        $('rows').addEventListener('change', e => {
            if (!e.target.matches('.row-check')) return;
            e.target.checked ? state.selected.add(e.target.dataset.id) : state.selected.delete(e.target.dataset.id);
            renderRows();
        });
        $('selectAll').addEventListener('change', e => {
            document.querySelectorAll('#rows .row-check').forEach(box => e.target.checked ? state.selected.add(box.dataset.id) : state.selected.delete(box.dataset.id));
            renderRows();
        });
        $('bulkClear').addEventListener('click', () => { state.selected.clear(); renderRows(); });
        $('bulkApprove').addEventListener('click', () => approve([...state.selected]));
        $('approveButton').addEventListener('click', () => { drawer.hide(); approve([state.open.id]); });

        $('sendBackModal').addEventListener('show.bs.modal', () => { $('sendBackForm').reset(); $('sendBackForm').classList.remove('was-validated'); drawer.hide(); });
        $('sendBackForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return;
            const q = state.open;
            q.status = 'sent';
            q.reason = $('sendBackReason').value.trim();
            q.reviewed = `Sent back by you · ${nowLabel()}`;
            state.selected.delete(q.id);
            bootstrap.Modal.getOrCreateInstance($('sendBackModal')).hide();
            render();
            toast(`${q.id} sent back to ${q.by} on this page. Not saved yet: needs the backend.`);
        });

        render();
    </script>
@endsection
