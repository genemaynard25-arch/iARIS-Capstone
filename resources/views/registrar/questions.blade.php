@extends('layouts.app')

{{--
    Questions to IATO: what the Registrar asked about records (here with "New question", or with
    "Ask IATO about this record" in a record's drawer) and IATO's answers.
    FRONT END ONLY: new questions, follow-ups and "Mark resolved" stay until reload.
--}}

@section('title', 'iARIS — Questions to IATO')
@section('page-title', 'Questions to IATO')
@section('page-subtitle', 'Ask IATO about a record. Questions never change the record; only IATO can edit it.')

@section('content')
    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="d-flex flex-wrap gap-2 me-auto" id="pills" role="group" aria-label="Filter by status"></div>
            <button type="button" class="btn btn-primary fw-semibold" id="newQuestion"><i class="bi bi-plus-lg me-1"></i> New question</button>
        </div>
        <div class="vstack gap-3 p-4" id="list"></div>
    </div>

    {{-- New question --}}
    <div class="modal fade" id="newModal" tabindex="-1" aria-labelledby="newTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="newForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="newTitle">New question to IATO</h3>
                        <p class="small text-white-50 mb-0">Pick the record you're asking about.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label for="newRecord" class="form-label small fw-bold text-uppercase text-body-secondary">Record</label>
                    <select class="form-select" id="newRecord" required>
                        <option value="">Choose a record</option>
                        @foreach (collect($records)->groupBy('unit') as $unit => $list)
                            <optgroup label="{{ $unit }}">
                                @foreach ($list as $r)
                                    <option value="{{ $r['id'] }}">{{ $r['name'] }} · {{ $r['id'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="newRecordError">Choose a record.</div>
                    <label for="newTopic" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Topic</label>
                    <select class="form-select" id="newTopic" required>
                        <option value="">Choose one</option>
                        <option>Payment</option>
                        <option>Personal details</option>
                        <option>Program or year level</option>
                        <option>Admission status</option>
                        <option>Other</option>
                    </select>
                    <div class="invalid-feedback">Choose a topic.</div>
                    <label for="newMessage" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Question</label>
                    <textarea class="form-control" id="newMessage" rows="3" required placeholder="Write your question for IATO…"></textarea>
                    <div class="invalid-feedback">Write your question.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send to IATO</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="questionToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="questionToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const QUESTIONS = @json($questions);
        const ME = @json($me);
        const RECORDS = @json($records);
        const STATUS = { waiting: ['Waiting for IATO', 'warning', 'bi-hourglass-split'], answered: ['IATO answered', 'info', 'bi-reply-fill'], resolved: ['Resolved', 'success', 'bi-check-circle-fill'] };
        const $ = id => document.getElementById(id);
        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };
        const badge = status => {
            const [label, tone, icon] = STATUS[status];
            const b = el('span', `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`);
            b.innerHTML = `<i class="bi ${icon} me-1"></i>`;
            b.append(label);
            return b;
        };
        const toast = text => { $('questionToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('questionToast')).show(); };
        const nowLabel = () => { const d = new Date(); return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`; };
        let filter = 'open';   // Open = not resolved

        function draw() {
            const n = s => QUESTIONS.filter(q => q.status === s).length;
            $('pills').replaceChildren(...[['open', 'Open', QUESTIONS.length - n('resolved')], ['answered', 'IATO answered', n('answered')], ['waiting', 'Waiting for IATO', n('waiting')], ['resolved', 'Resolved', n('resolved')]].map(([key, label, count]) => {
                const b = el('button', `btn btn-sm rounded-pill px-3 fw-semibold ${filter === key ? 'btn-primary' : 'btn-outline-secondary'}`);
                b.type = 'button';
                b.append(label, el('span', 'ms-2 opacity-75', String(count)));
                b.addEventListener('click', () => { filter = key; draw(); });
                return b;
            }));
            const nav = $('navAnsweredBadge');
            if (nav) { nav.textContent = n('answered'); nav.classList.toggle('d-none', !n('answered')); }

            // IATO's answers first
            const list = QUESTIONS.filter(q => filter === 'open' ? q.status !== 'resolved' : q.status === filter)
                .sort((a, b) => (a.status === 'answered' ? 0 : 1) - (b.status === 'answered' ? 0 : 1));
            $('list').replaceChildren(...(list.length ? list.map(card) : [el('div', 'text-center text-body-secondary py-5', 'Nothing here.')]));
        }

        function card(q) {
            const box = el('div', `border rounded-4 p-3 ${q.status === 'answered' ? 'border-info' : ''}`);
            const head = el('div', 'd-flex flex-wrap align-items-start justify-content-between gap-2 mb-2');
            const who = el('div');
            who.append(el('div', 'fw-bold', q.name), el('div', 'small text-body-secondary', `${q.id} · ${q.unit} · ${q.program} · Topic: ${q.topic}`));
            head.append(who, badge(q.status));
            box.appendChild(head);

            const thread = el('div', 'vstack gap-2 mb-2');
            q.messages.forEach(m => {
                const mine = m.from === 'reg';
                const b = el('div', `rounded-3 px-3 py-2 small ${mine ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}`);
                b.style.maxWidth = '92%';
                const w = el('div', 'fw-bold', m.name);
                w.appendChild(el('span', 'fw-normal text-body-secondary', ` · ${m.role} · ${m.at}`));
                b.append(w, el('div', '', m.text));
                thread.appendChild(b);
            });
            box.appendChild(thread);

            const actions = el('div', 'd-flex flex-wrap gap-2 mt-2');
            const open = el('a', 'btn btn-sm btn-light border fw-semibold', 'Open record');
            open.href = q.url;
            actions.appendChild(open);
            if (q.status !== 'resolved') {
                const reply = el('textarea', 'form-control mt-2');
                reply.rows = 2;
                reply.placeholder = 'Write a follow-up…';
                reply.setAttribute('aria-label', 'Follow-up to IATO');
                box.appendChild(reply);
                const send = el('button', 'btn btn-sm btn-primary fw-semibold');
                send.type = 'button';
                send.innerHTML = '<i class="bi bi-reply me-1"></i> Send';
                send.addEventListener('click', () => {
                    const text = reply.value.trim();
                    reply.classList.toggle('is-invalid', !text);
                    if (!text) return reply.focus();
                    q.messages.push({ from: 'reg', name: ME.name, role: ME.role, at: nowLabel(), text });
                    q.status = 'waiting';
                    draw();
                    toast('Sent to IATO. (Demo only — not saved yet.)');
                });
                actions.prepend(send);
                if (q.status === 'answered') {
                    const done = el('button', 'btn btn-sm btn-light border fw-semibold');
                    done.type = 'button';
                    done.innerHTML = '<i class="bi bi-check-lg me-1"></i> Mark resolved';
                    done.addEventListener('click', () => { q.status = 'resolved'; draw(); toast('Marked as resolved. (Demo only — not saved yet.)'); });
                    actions.insertBefore(done, open);
                }
            }
            box.appendChild(actions);
            return box;
        }

        // ---- New question ----
        const newModal = bootstrap.Modal.getOrCreateInstance($('newModal'));
        $('newQuestion').addEventListener('click', () => {
            $('newForm').reset();
            $('newForm').classList.remove('was-validated');
            $('newRecord').setCustomValidity('');
            $('newRecordError').textContent = 'Choose a record.';
            newModal.show();
        });
        $('newRecord').addEventListener('change', e => e.target.setCustomValidity(''));
        $('newForm').addEventListener('submit', e => {
            e.preventDefault();
            const id = $('newRecord').value;
            // One open question per record: add a follow-up to it instead
            const open = QUESTIONS.find(q => q.id === id && q.status !== 'resolved');
            $('newRecord').setCustomValidity(open ? 'open' : '');
            $('newRecordError').textContent = open ? 'There is already an open question on this record. Add a follow-up there.' : 'Choose a record.';
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return e.target.querySelector(':invalid')?.focus();
            const r = RECORDS.find(x => x.id === id);
            const old = QUESTIONS.findIndex(q => q.id === id);
            if (old > -1) QUESTIONS.splice(old, 1);   // a resolved one is replaced
            QUESTIONS.unshift({
                id, name: r.name, unit: r.unit, program: r.program, url: r.url, topic: $('newTopic').value, status: 'waiting',
                messages: [{ from: 'reg', name: ME.name, role: ME.role, at: nowLabel(), text: $('newMessage').value.trim() }],
            });
            newModal.hide();
            filter = 'open';
            draw();
            toast('Sent to IATO. (Demo only — not saved yet.)');
        });

        draw();
    </script>
@endsection
