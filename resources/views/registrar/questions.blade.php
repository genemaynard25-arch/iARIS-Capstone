@extends('layouts.app')

{{--
    Questions to IATO: what the Registrar asked about records (from "Ask IATO about this record" in a
    record's drawer) and IATO's answers. Built without a mockup, in the same style as the LAMP conversations.
    FRONT END ONLY: follow-ups and "Mark resolved" stay until reload.
--}}

@section('title', 'iARIS — Questions to IATO')
@section('page-title', 'Questions to IATO')
@section('page-subtitle', 'Your questions about records you can view but not change, and IATO\'s answers')

@section('content')
    <div class="alert alert-info d-flex align-items-center gap-2 rounded-4 small mb-4">
        <i class="bi bi-info-circle"></i>
        <span>To ask a new question, open the record and use <b>Ask IATO about this record</b>. Questions don't change the record. Only IATO can edit it.</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap gap-2 px-4 py-3 border-bottom" id="pills" role="group" aria-label="Filter by status"></div>
        <div class="vstack gap-3 p-4" id="list"></div>
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
        let filter = '';

        function draw() {
            const n = s => QUESTIONS.filter(q => q.status === s).length;
            $('pills').replaceChildren(...[['', 'All', QUESTIONS.length], ['answered', 'IATO answered', n('answered')], ['waiting', 'Waiting for IATO', n('waiting')], ['resolved', 'Resolved', n('resolved')]].map(([key, label, count]) => {
                const b = el('button', `btn btn-sm rounded-pill px-3 fw-semibold ${filter === key ? 'btn-primary' : 'btn-outline-secondary'}`);
                b.type = 'button';
                b.append(label, el('span', 'ms-2 opacity-75', String(count)));
                b.addEventListener('click', () => { filter = key; draw(); });
                return b;
            }));
            const nav = $('navAnsweredBadge');
            if (nav) { nav.textContent = n('answered'); nav.classList.toggle('d-none', !n('answered')); }

            const list = QUESTIONS.filter(q => !filter || q.status === filter);
            $('list').replaceChildren(...(list.length ? list.map(card) : [el('div', 'text-center text-body-secondary py-5', 'No questions here.')]));
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

        draw();
    </script>
@endsection
