{{--
    Registrars only: "Ask IATO about this record" at the top of a record's drawer (records are view only for them).
    If they already asked about this record, the box shows the conversation instead.
    FRONT END ONLY: new questions stay on this page until reload. TODO (backend): save them (see App\Support\RegistrarQuestions).
    Needs from the page: openDrawer() calling window.onApplicantDrawer(applicant), and <div id="askIatoBox"> in the drawer.
--}}
@php
    $askMe = [
        'name' => \App\Http\Controllers\RegistrarController::lastFirst(auth()->user()->name),
        'role' => auth()->user()->assigned_level === 'is' ? 'IS Registrar' : 'College Registrar',
    ];
    $askQuestions = collect(\App\Support\RegistrarQuestions::sample($askMe))->keyBy('id');
@endphp

<div class="modal fade" id="askIatoModal" tabindex="-1" aria-labelledby="askIatoTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="askIatoForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="askIatoTitle">Ask IATO about this record</h3>
                    <p class="small text-white-50 mb-0" id="askIatoSubtitle"></p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label for="askIatoTopic" class="form-label small fw-bold text-uppercase text-body-secondary">Topic</label>
                <select class="form-select" id="askIatoTopic" required>
                    <option value="">Choose one</option>
                    <option>Payment</option>
                    <option>Personal details</option>
                    <option>Program or year level</option>
                    <option>Admission status</option>
                    <option>Other</option>
                </select>
                <div class="invalid-feedback">Choose a topic.</div>
                <label for="askIatoMessage" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Question</label>
                <textarea class="form-control" id="askIatoMessage" rows="3" required placeholder="e.g. The payment date is set, but I can't see the OR number. Can you confirm it?"></textarea>
                <div class="invalid-feedback">Write your question.</div>
                <div class="small text-body-secondary mt-3"><i class="bi bi-info-circle me-1"></i> IATO sees this on the record and answers here. Questions don't change the record. Only IATO can edit it.</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send to IATO</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 start-0 p-3">
    <div class="toast align-items-center border-0 text-bg-dark" id="askIatoToast" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="askIatoToastText"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    // Runs after the page's own script and Bootstrap have loaded
    document.addEventListener('DOMContentLoaded', () => {
        const $ = id => document.getElementById(id);
        const QUESTIONS = @json((object) $askQuestions->all());   // applicant number => question
        const ME = @json($askMe);
        const STATUS = { waiting: ['Waiting for IATO', 'warning', 'bi-hourglass-split'], answered: ['IATO answered', 'info', 'bi-reply-fill'], resolved: ['Resolved', 'success', 'bi-check-circle-fill'] };
        const askModal = bootstrap.Modal.getOrCreateInstance($('askIatoModal'));
        let current = null, asking = false;
        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };
        const toast = text => { $('askIatoToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('askIatoToast')).show(); };
        const nowLabel = () => { const d = new Date(); return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`; };

        function drawBox() {
            const box = $('askIatoBox');
            box.replaceChildren();
            if (!current) return;
            const q = QUESTIONS[current.id];
            const card = el('div', 'border rounded-4 p-3 mb-4 no-print');
            box.appendChild(card);

            if (!q) {
                card.append(el('div', 'fw-bold', 'Something wrong in this record?'),
                    el('p', 'small text-body-secondary mb-2', "You can view records but not edit them. Ask IATO and they'll answer here."));
                const ask = el('button', 'btn btn-sm btn-primary fw-semibold');
                ask.type = 'button';
                ask.innerHTML = '<i class="bi bi-question-circle me-1"></i> Ask IATO about this record';
                ask.addEventListener('click', openAsk);
                card.appendChild(ask);
                return;
            }

            const [label, tone, icon] = STATUS[q.status];
            const head = el('div', 'd-flex flex-wrap justify-content-between align-items-center gap-2');
            const b = el('span', `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`);
            b.innerHTML = `<i class="bi ${icon} me-1"></i>`;
            b.append(label);
            head.append(el('div', 'fw-bold', 'Your question to IATO'), b);
            card.append(head, el('div', 'small text-body-secondary mb-2', `Topic: ${q.topic}`));
            const thread = el('div', 'vstack gap-2');
            q.messages.forEach(m => {
                const mine = m.from === 'reg';
                const bubble = el('div', `rounded-3 px-3 py-2 small ${mine ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}`);
                bubble.style.maxWidth = '92%';
                const who = el('div', 'fw-bold', m.name);
                who.appendChild(el('span', 'fw-normal text-body-secondary', ` · ${m.role} · ${m.at}`));
                bubble.append(who, el('div', '', m.text));
                thread.appendChild(bubble);
            });
            card.appendChild(thread);
            if (q.status === 'resolved') return;

            const reply = el('textarea', 'form-control mt-3');
            reply.rows = 2;
            reply.placeholder = 'Write a follow-up…';
            reply.setAttribute('aria-label', 'Follow-up to IATO');
            const actions = el('div', 'd-flex flex-wrap gap-2 mt-2');
            const send = el('button', 'btn btn-sm btn-primary fw-semibold');
            send.type = 'button';
            send.innerHTML = '<i class="bi bi-reply me-1"></i> Send';
            send.addEventListener('click', () => {
                const text = reply.value.trim();
                reply.classList.toggle('is-invalid', !text);
                if (!text) return reply.focus();
                q.messages.push({ from: 'reg', name: ME.name, role: ME.role, at: nowLabel(), text });
                q.status = 'waiting';
                drawBox();
                $('applicantDrawer').focus();
                toast('Sent to IATO. (Demo only — not saved yet.)');
            });
            actions.appendChild(send);
            if (q.status === 'answered') {
                const done = el('button', 'btn btn-sm btn-light border fw-semibold');
                done.type = 'button';
                done.innerHTML = '<i class="bi bi-check-lg me-1"></i> Mark resolved';
                done.addEventListener('click', () => { q.status = 'resolved'; drawBox(); $('applicantDrawer').focus(); toast('Marked as resolved. (Demo only — not saved yet.)'); });
                actions.appendChild(done);
            }
            card.append(reply, actions);
        }

        function openAsk() {
            $('askIatoSubtitle').textContent = `${current.name} · ${current.id} · ${current.program_label}`;
            $('askIatoForm').reset();
            $('askIatoForm').classList.remove('was-validated');
            asking = true;
            bootstrap.Offcanvas.getInstance($('applicantDrawer'))?.hide();
            askModal.show();
        }
        $('askIatoModal').addEventListener('shown.bs.modal', () => $('askIatoTopic').focus());
        $('askIatoModal').addEventListener('hidden.bs.modal', () => {
            asking = false;
            if (current) bootstrap.Offcanvas.getOrCreateInstance($('applicantDrawer')).show();
        });
        $('askIatoForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return e.target.querySelector(':invalid')?.focus();
            QUESTIONS[current.id] = {
                id: current.id, name: current.name, topic: $('askIatoTopic').value, status: 'waiting',
                messages: [{ from: 'reg', name: ME.name, role: ME.role, at: nowLabel(), text: $('askIatoMessage').value.trim() }],
            };
            drawBox();
            askModal.hide();
            toast('Sent to IATO. You can follow it in Questions to IATO. (Demo only — not saved yet.)');
        });

        // The page's openDrawer() calls this
        window.onApplicantDrawer = a => { current = a; drawBox(); };
        $('applicantDrawer').addEventListener('hidden.bs.offcanvas', () => { if (!asking) current = null; });
    });
</script>
