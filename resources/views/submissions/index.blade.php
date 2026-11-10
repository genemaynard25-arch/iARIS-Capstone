@extends('layouts.app')

{{--
    My Submissions (SubmissionsController): the staff member's edits, new records, removals and imports,
    and what the Super Admin did with them. LAMP requests (conversations they started from Scholarships)
    show here too. Everything below the "How to submit" card is drawn by the script.
    FRONT END ONLY: Fix and resubmit / Withdraw change this page until reload.
--}}

@section('title', 'iARIS — My Submissions')
@section('page-title', 'My Submissions')
@section('page-subtitle', 'Edits, new records, removals, and imports you sent to the Review Queue')

@include('partials.admin-workspace', ['workspacePage' => 'submissions-page', 'workspaceGroup' => 'Staff', 'workspaceLabel' => 'My Work · My Submissions'])

@section('content')
    <p class="admin-preview-note mb-3"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Preview submissions · Fix, resubmit, and withdraw actions stay on this page until reload. The review workflow is not connected yet.</p>
    {{-- Four workflow tiles from the staff submissions mockup. --}}
    <div class="staff-submission-flow">
        @foreach ([
            ['1', 'You submit', 'Edits, new applicants, removals, imports', 'submit'],
            ['2', 'Super Admin reviews', 'Only you and the Super Admin see it until then', 'review'],
            ['bi-check-lg', 'Approved', 'Visible to every user', 'approved'],
            ['bi-arrow-counterclockwise', 'Sent back', 'Read the reason, fix, and resubmit', 'sent'],
        ] as [$mark, $title, $text, $state])
            <div class="staff-flow-tile staff-flow-{{ $state }}">
                <span class="staff-flow-mark">@if (str_starts_with($mark, 'bi-'))<i class="bi {{ $mark }}" aria-hidden="true"></i>@else{{ $mark }}@endif</span>
                <strong>{{ $title }}</strong><small>{{ $text }}</small>
            </div>
            @if ($loop->index < 2)<i class="bi bi-arrow-right staff-flow-arrow" aria-hidden="true"></i>@endif
        @endforeach
    </div>

    {{-- Always-visible guidance matches the supplied staff mockup. --}}
    <div class="staff-submission-help card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4" id="submissionHelp">
            <h2 class="fs-6 fw-bold mb-1"><i class="bi bi-question-circle me-1 text-primary"></i> How to submit</h2>
            <p class="small text-body-secondary mb-3">There is no separate form. Every change you make in iARIS is sent here automatically when you press <b>Submit for review</b>.</p>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3">
                @foreach ([
                    ['bi-pencil', 'info', 'Edit a record', 'Open a records page, press the pen icon on a row (or <b>Edit Record</b> in its details), change the fields, then <b>Submit for review</b>.', '/records/college', 'Open College records'],
                    ['bi-person-plus', 'primary', 'Add an applicant', 'Press <b>Add Applicant</b>, choose the division, fill in the form, then <b>Submit for review</b>.', '/applicants', 'Add applicant'],
                    ['bi-trash', 'danger', 'Remove an applicant', 'Open the applicant, press <b>Remove</b>, choose a reason, then <b>Request removal</b>. The record stays until approved.', '/records/is', 'Open IS records'],
                    ['bi-cloud-arrow-up', 'purple', 'Import a file', 'Upload an Excel or CSV file, check the preview, then press <b>Submit for review</b> on the last step.', '/import', 'Import data'],
                ] as [$icon, $tone, $title, $text, $url, $link])
                    <div class="col">
                        <div class="border rounded-4 p-3 h-100 d-flex flex-column">
                            <span class="rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis d-flex align-items-center justify-content-center mb-2" style="width: 36px; height: 36px;"><i class="bi {{ $icon }}"></i></span>
                            <div class="fw-bold">{{ $title }}</div>
                            <div class="small text-body-secondary mb-3">{!! $text !!}</div>
                            <a href="{{ url($url) }}" class="btn btn-sm btn-light border fw-semibold mt-auto align-self-start">{{ $link }} <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="staff-lamp-help small rounded-3 px-3 py-2 mt-3">
                <i class="bi bi-chat-dots me-1"></i> Messages you send to the LAMP Office from Scholarships also show here, under <b>LAMP requests</b>. They don't need approval because they don't change any data.
            </div>
        </div>
    </div>

    {{-- Counts; clicking one filters the list --}}
    <div class="admin-stat-grid row row-cols-2 row-cols-xl-4 g-3 mb-4" id="statCards"></div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="d-flex flex-wrap gap-2 me-auto" id="statusPills" role="group" aria-label="Filter by status"></div>
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search applicant, file, or RQ number…" aria-label="Search submissions">
            </div>
        </div>
        <div class="list-group list-group-flush rounded-bottom-4" id="submissionList"></div>
    </div>

    {{-- Fix and resubmit --}}
    <div class="modal fade" id="fixModal" tabindex="-1" aria-labelledby="fixTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="fixForm" novalidate>
                <div class="modal-header border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="fixTitle">Fix and resubmit</h3>
                        <p class="small text-body-secondary mb-0" id="fixSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger small py-2 px-3">
                        <div class="fw-bold" id="fixBy"></div>
                        <div id="fixReason"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle small">
                            <thead><tr class="text-body-secondary"><th class="fw-semibold">Field</th><th class="fw-semibold">Current</th><th class="fw-semibold">Your new value</th></tr></thead>
                            <tbody id="fixRows"></tbody>
                        </table>
                    </div>
                    <label for="fixNote" class="form-label small fw-bold text-uppercase text-body-secondary">Note for the Super Admin</label>
                    <textarea class="form-control" id="fixNote" rows="3" required placeholder="e.g. Checked the receipt again. The correct amount is ₱1,500.00."></textarea>
                    <div class="invalid-feedback">Add a short note about what you fixed.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-send me-1"></i> Resubmit for review</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Withdraw --}}
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-body p-4">
                    <h3 class="fs-5 fw-bold mb-1" id="withdrawTitle">Withdraw this submission?</h3>
                    <p class="small text-body-secondary mb-2" id="withdrawSubtitle"></p>
                    <p class="small mb-0">It leaves the Review Queue and the record stays as it is now. You can submit the change again later.</p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Keep it</button>
                    <button type="button" class="btn btn-danger fw-semibold" id="withdrawConfirm"><i class="bi bi-arrow-counterclockwise me-1"></i> Withdraw</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="submissionToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="submissionToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const $ = id => document.getElementById(id);

        // Review Queue items, newest first, plus the LAMP conversations you started
        const ITEMS = @json($items).sort((a, b) => b.at.localeCompare(a.at));
        @json($lampRequests).forEach(t => ITEMS.push({
            id: t.id, type: 'lamp', title: 'Asked LAMP to check', subject: t.name, ref: t.reason, unit: 'Scholarships',
            when: t.messages[t.messages.length - 1].at, status: 'lt_' + t.status, thread: t,
        }));

        // status => [label, Bootstrap colour, icon]
        const STATUS = {
            waiting: ['Waiting for review', 'warning', 'bi-hourglass-split'],
            sent: ['Sent back', 'danger', 'bi-arrow-counterclockwise'],
            approved: ['Approved', 'success', 'bi-check-circle-fill'],
            withdrawn: ['Withdrawn', 'secondary', 'bi-arrow-counterclockwise'],
            resubmitted: ['Resubmitted', 'secondary', 'bi-send'],
            lt_waiting: ['Waiting for LAMP', 'warning', 'bi-hourglass-split'],
            lt_replied: ['LAMP replied', 'info', 'bi-reply-fill'],
            lt_resolved: ['Resolved', 'success', 'bi-check-circle-fill'],
        };
        // type => [icon, colour]
        const TYPES = { edit: ['bi-pencil', 'primary'], add: ['bi-person-plus', 'info'], remove: ['bi-trash', 'danger'], import: ['bi-cloud-arrow-up', 'warning'], lamp: ['bi-chat-dots', 'info'] };

        let filter = 'all';
        const open = new Set(ITEMS.filter(x => x.status === 'sent').map(x => x.id));   // sent back starts open
        let current = null;           // the item in the Fix / Withdraw pop-up
        let nextReviewNumber = 413;   // made-up Review Queue numbers

        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };
        const badge = status => {
            const [label, tone, icon] = STATUS[status];
            const b = el('span', `staff-status-${status} badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`);
            b.innerHTML = `<i class="bi ${icon} me-1"></i>`;
            b.append(label);
            return b;
        };
        const toast = text => {
            $('submissionToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance($('submissionToast')).show();
        };
        const nowLabel = () => {
            const d = new Date();
            return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
        };
        const count = status => ITEMS.filter(x => x.status === status).length;
        const matchesFilter = x => filter === 'all' || x.status === filter || (filter === 'lamp' && x.type === 'lamp')
            || (filter === 'withdrawn' && x.status === 'resubmitted');

        function draw() {
            // Stat cards
            $('statCards').replaceChildren(...[
                ['waiting', 'Waiting', 'warning', 'Only you and the Super Admin see these'],
                ['sent', 'Sent back', 'danger', 'Needs your fix'],
                ['approved', 'Approved', 'success', 'Visible to every user'],
                ['withdrawn', 'Withdrawn', 'secondary', 'Taken back by you'],
            ].map(([key, label, tone, sub]) => {
                const col = el('div', 'col');
                const card = el('button', `admin-stat-card card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100 w-100 text-start ${filter === key ? 'bg-body-tertiary' : ''}`);
                card.type = 'button';
                card.setAttribute('aria-pressed', filter === key);
                card.innerHTML = `<div class="card-body"><div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2"></div><div class="fs-3 fw-bold text-${tone}-emphasis"></div><div class="small text-body-secondary"></div></div>`;
                const [l, v, s] = card.querySelectorAll('.card-body > div');
                l.textContent = label; v.textContent = count(key); s.textContent = sub;
                card.addEventListener('click', () => { filter = key; draw(); });
                col.appendChild(card);
                return col;
            }));

            // Pills
            $('statusPills').replaceChildren(...[
                ['all', 'All', ITEMS.length], ['waiting', 'Waiting', count('waiting')], ['sent', 'Sent back', count('sent')],
                ['approved', 'Approved', count('approved')], ['withdrawn', 'Withdrawn', count('withdrawn') + count('resubmitted')],
                ['lamp', 'LAMP requests', ITEMS.filter(x => x.type === 'lamp').length],
            ].map(([key, label, n]) => {
                const pill = el('button', `btn btn-sm rounded-pill px-3 fw-semibold ${filter === key ? 'btn-primary' : 'btn-outline-secondary'}`);
                pill.type = 'button';
                pill.setAttribute('aria-pressed', filter === key);
                pill.append(label, el('span', 'ms-2 opacity-75', String(n)));
                pill.addEventListener('click', () => { filter = key; draw(); });
                return pill;
            }));

            // The list
            const q = $('search').value.trim().toLowerCase();
            const list = ITEMS.filter(x => matchesFilter(x) && (!q || [x.id, x.subject, x.ref, x.title].join(' ').toLowerCase().includes(q)));
            $('submissionList').replaceChildren(...(list.length ? list.map(item) : [el('div', 'text-center text-body-secondary py-5', 'No submissions here.')]));

            // The amber number on the sidebar: sent back to you
            const nav = $('nav-count-my-submissions');
            if (nav) { nav.textContent = count('sent'); nav.classList.toggle('d-none', count('sent') === 0); }
        }

        // One submission: a header row that opens and closes its details
        function item(x) {
            const wrap = el('div', `staff-submission-item list-group-item p-0 ${x.status === 'sent' ? 'staff-needs-fix' : ''}`);
            const head = el('button', 'staff-submission-row btn w-100 text-start border-0 rounded-0');
            head.type = 'button';
            head.setAttribute('aria-expanded', open.has(x.id));
            const [icon, tone] = TYPES[x.type] || TYPES.edit;
            const ic = el('span', `staff-submission-icon staff-icon-${x.type} rounded-3 bg-${tone}-subtle text-${tone}-emphasis d-flex align-items-center justify-content-center flex-shrink-0`);
            ic.style.width = ic.style.height = '40px';
            ic.innerHTML = `<i class="bi ${icon}"></i>`;
            const text = el('div', 'flex-grow-1');
            text.classList.add('staff-submission-title');
            const title = el('div', 'fw-bold', x.title);
            title.appendChild(el('span', 'small fw-normal text-body-secondary font-monospace ms-2', x.id));
            text.append(title, el('div', 'small text-body-secondary', `${x.subject} · ${x.ref} · ${x.unit}`));
            const when = el('span', 'staff-submission-when small text-body-secondary text-nowrap', x.when);
            const chev = el('i', `bi bi-chevron-${open.has(x.id) ? 'down' : 'right'} text-body-secondary`);
            when.appendChild(chev);
            head.append(ic, text, badge(x.status), when);
            head.addEventListener('click', () => { open.has(x.id) ? open.delete(x.id) : open.add(x.id); draw(); });
            wrap.appendChild(head);
            if (!open.has(x.id)) return wrap;

            const body = el('div', 'staff-submission-body');
            if (x.status === 'sent') {
                const r = el('div', 'staff-submission-reason alert alert-danger small');
                const reasonText = el('div');
                reasonText.append(el('div', 'fw-bold', x.reviewed || 'Sent back by the Super Admin'), el('div', '', x.reason || ''));
                const reasonIcon = el('i', 'bi bi-chat-left-text');
                reasonIcon.setAttribute('aria-hidden', 'true');
                r.append(reasonIcon, reasonText);
                body.appendChild(r);
            }
            if (x.status === 'approved') body.appendChild(el('div', 'small rounded-3 px-3 py-2 mb-2 bg-success-subtle text-success-emphasis fw-semibold', `${x.reviewed || 'Approved'}. It is now visible to every user.`));
            if (x.status === 'resubmitted') body.appendChild(el('div', 'small rounded-3 px-3 py-2 mb-2 bg-body-tertiary', `Fixed and resubmitted as ${x.resubmittedAs}.`));
            if (x.status === 'withdrawn') body.appendChild(el('div', 'small rounded-3 px-3 py-2 mb-2 bg-body-tertiary', `You withdrew this on ${x.withdrawnAt}. The record was not changed.`));
            if (x.note) {
                const n = el('div', 'staff-submission-note small mb-2');
                n.append(el('span', 'text-body-secondary', 'Your note: '), x.note);
                body.appendChild(n);
            }
            if (x.summary) {
                const sum = el('div', 'd-flex flex-wrap gap-2 small mb-2');
                [['Rows in file', x.summary.total], ['New applicants', x.summary.new], ['Updated', x.summary.updated], ['Rows with errors', x.summary.errors]].forEach(([l, v], i) => {
                    const c = el('div', 'rounded-3 bg-body-tertiary border px-3 py-2');
                    c.append(el('div', 'text-body-secondary', l), el('div', `fw-bold ${i === 3 && v ? 'text-danger' : ''}`, String(v)));
                    sum.appendChild(c);
                });
                body.appendChild(sum);
            }
            if (x.changes?.length) {
                const tbl = el('table', 'table table-sm small mb-2');
                tbl.style.maxWidth = '600px';
                tbl.innerHTML = '<thead><tr class="text-body-secondary"><th class="fw-semibold">Field</th><th class="fw-semibold">Before</th><th class="fw-semibold">Your change</th></tr></thead>';
                const tb = el('tbody');
                x.changes.forEach(([f, before, after]) => {
                    const tr = el('tr');
                    tr.append(el('td', '', f), el('td', 'staff-change-before text-body-secondary', before), el('td', 'staff-change-after fw-semibold', after));
                    tb.appendChild(tr);
                });
                tbl.appendChild(tb);
                body.appendChild(el('div', 'table-responsive')).appendChild(tbl);
            }
            if (x.type === 'lamp') {
                const thread = el('div', 'vstack gap-2 mb-2');
                x.thread.messages.forEach(m => {
                    const b = el('div', `rounded-3 px-3 py-2 small ${m.from === 'iato' ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}`);
                    b.style.maxWidth = '92%';
                    const who = el('div', 'fw-bold', m.name);
                    who.appendChild(el('span', 'fw-normal text-body-secondary', ` · ${m.role} · ${m.at}`));
                    b.append(who, el('div', '', m.text));
                    thread.appendChild(b);
                });
                body.appendChild(thread);
                const link = el('a', `btn btn-sm fw-semibold ${x.status === 'lt_replied' ? 'btn-primary' : 'btn-light border'}`);
                link.href = '{{ url('/records/scholars') }}';
                link.innerHTML = '<i class="bi bi-award me-1"></i>';
                link.append(x.status === 'lt_replied' ? 'Read and reply in Scholarships' : 'Open in Scholarships');
                body.appendChild(link);
            }

            // Actions
            const acts = el('div', 'd-flex flex-wrap gap-2');
            if (x.status === 'sent' && x.changes) {
                const fix = el('button', 'btn btn-sm btn-primary fw-semibold');
                fix.type = 'button';
                fix.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Fix and resubmit';
                fix.addEventListener('click', () => openFix(x));
                acts.appendChild(fix);
            }
            if (x.status === 'waiting') {
                const wd = el('button', 'btn btn-sm btn-outline-danger fw-semibold');
                wd.type = 'button';
                wd.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i> Withdraw';
                wd.addEventListener('click', () => openWithdraw(x));
                acts.appendChild(wd);
            }
            if (acts.children.length) body.appendChild(acts);
            wrap.appendChild(body);
            return wrap;
        }

        // ---- Fix and resubmit: change the values, add a note, and it goes back to the queue as a new RQ ----
        const fixModal = bootstrap.Modal.getOrCreateInstance($('fixModal'));
        function openFix(x) {
            current = x;
            $('fixSubtitle').textContent = `${x.id} · ${x.subject} · ${x.ref}`;
            $('fixBy').textContent = x.reviewed || 'Sent back';
            $('fixReason').textContent = x.reason || '';
            $('fixRows').replaceChildren(...x.changes.map(([f, before, after], i) => {
                const tr = el('tr');
                const input = el('input', 'form-control form-control-sm');
                input.value = after;
                input.dataset.i = i;
                input.setAttribute('aria-label', `New value for ${f}`);
                const td = el('td');
                td.appendChild(input);
                tr.append(el('td', '', f), el('td', 'text-body-secondary', before), td);
                return tr;
            }));
            $('fixForm').reset();
            $('fixForm').classList.remove('was-validated');
            fixModal.show();
        }
        $('fixForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return $('fixNote').focus();
            const x = current;
            const id = 'RQ-' + String(nextReviewNumber++).padStart(4, '0');
            const changes = x.changes.map(([f, before, after], i) => [f, before, $('fixRows').querySelector(`[data-i="${i}"]`).value.trim() || after]);
            ITEMS.unshift({ ...x, id, title: `${x.title} (resubmitted)`, when: nowLabel(), status: 'waiting', note: $('fixNote').value.trim(), changes, reviewed: null, reason: null });
            Object.assign(x, { status: 'resubmitted', resubmittedAs: id });
            open.delete(x.id);
            open.add(id);
            filter = 'all';
            fixModal.hide();
            draw();
            toast(`Resubmitted as ${id}. The Super Admin will review it again. (Demo only — not saved yet.)`);
        });

        // ---- Withdraw ----
        const withdrawModal = bootstrap.Modal.getOrCreateInstance($('withdrawModal'));
        function openWithdraw(x) {
            current = x;
            $('withdrawSubtitle').textContent = `${x.id} · ${x.title} · ${x.subject}`;
            withdrawModal.show();
        }
        $('withdrawConfirm').addEventListener('click', () => {
            Object.assign(current, { status: 'withdrawn', withdrawnAt: nowLabel() });
            withdrawModal.hide();
            draw();
            toast(`${current.id} withdrawn. The record was not changed. (Demo only — not saved yet.)`);
        });

        $('search').addEventListener('input', draw);
        draw();
    </script>
@endsection
