{{--
    "Ask LAMP to check" on the Scholarships page: IATO's side of the conversations with the LAMP Office
    about scholars whose records don't match. LAMP answers on its own Scholars page ("Requests from IATO").
    Both pages start from the same sample conversations (App\Support\LampRequests).

    FRONT END ONLY: new messages stay on this page until reload, and LAMP doesn't see them yet.
    TODO (backend): a table of conversations and messages, and routes to send / reply / resolve.
    Needs from the page: SCHOLARS, and openDrawer() calling window.onScholarDrawer(scholar).
--}}

{{-- Ask LAMP to check --}}
<div class="modal fade" id="askLampModal" tabindex="-1" aria-labelledby="askLampTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="askLampForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="askLampTitle">Ask LAMP to check</h3>
                    <p class="small text-white-50 mb-0" id="askLampSubtitle"></p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-3">
                    <div class="col-6"><div class="bg-body-tertiary rounded-3 px-3 py-2 h-100"><div class="small fw-bold text-uppercase text-body-secondary">IATO record</div><div class="fw-bold" id="askLampIato"></div></div></div>
                    <div class="col-6"><div class="bg-body-tertiary rounded-3 px-3 py-2 h-100"><div class="small fw-bold text-uppercase text-body-secondary">LAMP record</div><div class="fw-bold" id="askLampLamp"></div></div></div>
                </div>
                <label for="askLampReason" class="form-label small fw-bold text-uppercase text-body-secondary">What's wrong</label>
                <select class="form-select" id="askLampReason" required>
                    <option value="">Choose one</option>
                    <option>Scholarship type doesn't match</option>
                    <option>Not in LAMP records</option>
                    <option>Scholarship status differs</option>
                    <option>Other</option>
                </select>
                <div class="invalid-feedback">Choose one.</div>
                <label for="askLampMessage" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Message to LAMP Office</label>
                <textarea class="form-control" id="askLampMessage" rows="3" required placeholder="e.g. Her folder has a DLSL Scholarship award letter dated May 20. Can you check your records?"></textarea>
                <div class="invalid-feedback">Add a short message so LAMP knows what to check.</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send to LAMP Office</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 start-0 p-3">
    <div class="toast align-items-center border-0 text-bg-dark" id="askLampToast" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="askLampToastText"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    // Runs after the page's own script and Bootstrap have loaded
    document.addEventListener('DOMContentLoaded', () => {
        const $ = id => document.getElementById(id);
        const THREADS = @json((object) $lampThreads);   // applicant number => conversation
        const CAN_MESSAGE = @json($canMessageLamp);
        const ME = @json($me);
        // status => [label, Bootstrap colour, icon]
        const STATUS = {
            waiting: ['Waiting for LAMP', 'warning', 'bi-hourglass-split'],
            replied: ['LAMP replied', 'info', 'bi-reply-fill'],
            resolved: ['Resolved', 'primary', 'bi-check-circle-fill'],
        };
        const askModal = bootstrap.Modal.getOrCreateInstance($('askLampModal'));
        let current = null;   // the scholar in the drawer
        let asking = false;   // the Ask pop-up is open (the drawer is hidden behind it)

        const toast = text => {
            $('askLampToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance($('askLampToast')).show();
        };
        // e.g. "Nov 8, 2026 · 9:20 AM"
        const nowLabel = () => {
            const d = new Date();
            return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
        };
        const badge = status => {
            const [label, tone, icon] = STATUS[status];
            const el = document.createElement('span');
            el.className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
            el.innerHTML = `<i class="bi ${icon} me-1"></i>`;
            el.append(label);
            return el;
        };
        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };

        // ---- Table tags and the notice's numbers ----
        function drawTags() {
            document.querySelectorAll('#scholarRows .scholar-row').forEach(row => {
                const t = THREADS[SCHOLARS[row.dataset.index].id];
                const cell = row.querySelector('[data-thread-tag]');
                cell.replaceChildren();
                if (t) {
                    const b = badge(t.status);
                    b.classList.add('mt-1');
                    cell.appendChild(b);
                }
            });
            const all = Object.values(THREADS);
            const waiting = all.filter(t => t.status === 'waiting').length;
            const replied = all.filter(t => t.status === 'replied').length;
            const counts = $('lampThreadCounts');
            if (counts) counts.textContent = `${waiting} waiting for LAMP · ${replied} LAMP ${replied === 1 ? 'reply' : 'replies'} to read.`;
        }

        // ---- The box under the LAMP cross-check in the drawer ----
        function drawBox() {
            const box = $('lampAsk');
            box.replaceChildren();
            const s = current;
            if (!s) return;
            const t = THREADS[s.id];

            // Records match and nobody asked: nothing to show
            if (!t && s.lamp === 'Match') return;

            const card = el('div', 'border rounded-4 p-3 mt-3');
            box.appendChild(card);

            if (!t) {
                card.appendChild(el('div', 'fw-bold', 'Tell the LAMP Office'));
                card.appendChild(el('p', 'small text-body-secondary mb-2', 'Send this mismatch to LAMP. They see it under Requests from IATO and can confirm, correct, or reply.'));
                if (CAN_MESSAGE) {
                    const ask = el('button', 'btn btn-sm btn-primary fw-semibold');
                    ask.type = 'button';
                    ask.innerHTML = '<i class="bi bi-send me-1"></i> Ask LAMP to check';
                    ask.addEventListener('click', openAsk);
                    card.appendChild(ask);
                } else {
                    card.appendChild(el('div', 'small text-body-secondary fst-italic', 'Only IATO can message the LAMP Office.'));
                }
                return;
            }

            // The conversation
            const head = el('div', 'd-flex flex-wrap align-items-center justify-content-between gap-2 mb-1');
            head.append(el('div', 'fw-bold', 'Conversation with LAMP Office'), badge(t.status));
            card.append(head, el('div', 'small text-body-secondary mb-2', `Reason: ${t.reason}`));

            const thread = el('div', 'vstack gap-2');
            t.messages.forEach(m => {
                const iato = m.from === 'iato';
                const bubble = el('div', `rounded-3 px-3 py-2 small ${iato ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}`);
                bubble.style.maxWidth = '92%';
                const who = el('div', 'fw-bold', m.name);
                who.appendChild(el('span', 'fw-normal text-body-secondary', ` · ${m.role} · ${m.at}`));
                bubble.append(who, el('div', '', m.text));
                thread.appendChild(bubble);
            });
            if (t.outcome) thread.appendChild(el('div', 'small rounded-3 px-3 py-2 bg-success-subtle text-success-emphasis fw-semibold', `✓ ${t.outcome}`));
            card.appendChild(thread);

            if (t.status === 'resolved' || !CAN_MESSAGE) return;

            // Reply / Mark resolved
            const reply = el('textarea', 'form-control mt-3 mb-1');
            reply.rows = 2;
            reply.placeholder = 'Write a reply to LAMP…';
            reply.setAttribute('aria-label', 'Reply to LAMP');
            const feedback = el('div', 'invalid-feedback mb-1', 'Type a reply first.');
            reply.addEventListener('input', () => reply.classList.remove('is-invalid'));
            const actions = el('div', 'd-flex flex-wrap gap-2 mt-2');
            const send = el('button', 'btn btn-sm btn-primary fw-semibold');
            send.type = 'button';
            send.innerHTML = '<i class="bi bi-reply me-1"></i> Send reply';
            send.addEventListener('click', () => {
                const text = reply.value.trim();
                reply.classList.toggle('is-invalid', !text);
                if (!text) return reply.focus();
                t.messages.push({ from: 'iato', name: ME.name, role: ME.role, at: nowLabel(), text });
                t.status = 'waiting';
                redraw();
                toast('Reply sent to the LAMP Office. (Demo only — not saved yet.)');
            });
            const done = el('button', 'btn btn-sm btn-light border fw-semibold');
            done.type = 'button';
            done.innerHTML = '<i class="bi bi-check-lg me-1"></i> Mark resolved';
            done.addEventListener('click', () => {
                t.status = 'resolved';
                t.outcome = `Marked resolved by ${ME.name}`;
                redraw();
                toast('Marked as resolved. (Demo only — not saved yet.)');
            });
            actions.append(send, done);
            card.append(reply, feedback, actions);
        }

        // Redraw, and keep keyboard focus in the drawer (the clicked button was just replaced)
        const redraw = () => { drawTags(); drawBox(); $('applicantDrawer').focus(); };

        // ---- The Ask LAMP pop-up ----
        function openAsk() {
            const s = current;
            $('askLampSubtitle').textContent = `${s.name} · ${s.id} · ${s.program}`;
            $('askLampIato').textContent = s.type;
            $('askLampLamp').textContent = s.lamp_record;
            $('askLampForm').reset();
            $('askLampForm').classList.remove('was-validated');
            $('askLampReason').value = s.lamp === 'Not Found' ? 'Not in LAMP records' : "Scholarship type doesn't match";
            // Hide the drawer first so the two don't stack; the pop-up reopens it when closed
            asking = true;
            bootstrap.Offcanvas.getInstance($('applicantDrawer'))?.hide();
            askModal.show();
        }
        $('askLampModal').addEventListener('shown.bs.modal', () => $('askLampMessage').focus());
        $('askLampModal').addEventListener('hidden.bs.modal', () => {
            asking = false;
            if (current) bootstrap.Offcanvas.getOrCreateInstance($('applicantDrawer')).show();
        });

        $('askLampForm').addEventListener('submit', e => {
            e.preventDefault();
            const form = e.target;
            form.classList.add('was-validated');
            if (!form.checkValidity()) return form.querySelector(':invalid')?.focus();
            const s = current;
            THREADS[s.id] = {
                id: s.id, name: s.name, program: s.program, iato: s.type, lamp: s.lamp_record,
                reason: $('askLampReason').value, status: 'waiting',
                messages: [{ from: 'iato', name: ME.name, role: ME.role, at: nowLabel(), text: $('askLampMessage').value.trim() }],
            };
            redraw();
            askModal.hide();
            toast('Sent to the LAMP Office. They see it under Requests from IATO. (Demo only — not saved yet.)');
        });

        // The page's openDrawer() calls this
        window.onScholarDrawer = s => { current = s; drawBox(); };
        $('applicantDrawer').addEventListener('hidden.bs.offcanvas', () => {
            // Keep "current" while the Ask pop-up is open, so the drawer can come back to it
            if (!asking) current = null;
        });

        drawTags();
    });
</script>
