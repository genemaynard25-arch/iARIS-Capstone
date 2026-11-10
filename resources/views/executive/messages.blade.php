@extends('layouts.app')

{{--
    Messages with IATO: the Executive Office's conversations (ExecutiveController::messages), and a dean's
    (CollegeController::messages). $base is that office's pages, for the links at the bottom.
    List on the left, the open conversation on the right. ?new=1&subject=… opens "New message" filled in
    (the Reports page's "Ask IATO" uses it).
    A thread with 'check' => [id, name, unit, iato, lamp, url] is a scholar check IATO asked the LAMP Office
    (LampOffice::threads): it shows both records, and LAMP can confirm IATO's record or correct it ($correctTypes).
    FRONT END ONLY: new messages, replies and "Mark resolved" stay until reload.
--}}

@section('title', 'iARIS — Messages')
@section('page-breadcrumb', 'Messages')
@section('page-title', 'Messages with IATO')
@section('page-subtitle', "Every conversation with IATO in one place: your questions, IATO's replies.")

@if (in_array(auth()->user()->role, ['president', 'chancellor']))
    @include('partials.president-workspace', ['presidentPage' => 'messages', 'presidentLabel' => 'Messages'])
    @section('page-actions')
        <button type="button" class="btn btn-primary fw-semibold" id="newButton"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i> New message</button>
    @endsection
@endif

@if ((auth()->user()->isDirector() || auth()->user()->isGradChair() || auth()->user()->role === 'dean'))
    @include('partials.director-workspace', ['directorPage' => 'messages', 'directorLabel' => 'Messages'])
    @section('page-actions')
        <button type="button" class="btn btn-primary fw-semibold" id="newButton"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i> New message</button>
    @endsection
@endif

@section('content')
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2 me-auto" id="tabs" role="group" aria-label="Filter conversations"></div>
        @unless ((in_array(auth()->user()->role, ['president', 'chancellor']) || auth()->user()->isDirector() || auth()->user()->isGradChair() || auth()->user()->role === 'dean'))
        <button type="button" class="btn btn-primary fw-semibold" id="newButton"><i class="bi bi-pencil-square me-1"></i> New message</button>
        @endunless
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="list-group list-group-flush rounded-4" id="list" aria-label="Conversations"></div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100" id="view"></div>
        </div>
    </div>

    {{-- $note (optional): [text, link label] shown instead of "Other ways to get answers" (TOIC) --}}
    @isset($note)
    <div class="small text-body-secondary d-flex flex-wrap gap-3">
        <span><i class="bi bi-person-lock me-1"></i>{{ $note[0] }}</span>
        <a href="{{ $base }}" class="text-decoration-none fw-semibold"><i class="bi {{ $note[2] ?? 'bi-speedometer2' }} me-1"></i> {{ $note[1] }}</a>
    </div>
    @else
    <div class="small text-body-secondary d-flex flex-wrap gap-3">
        <span>Other ways to get answers:</span>
        <a href="{{ ($base ?? url('/executive')) . '/reports' }}" class="text-decoration-none fw-semibold"><i class="bi bi-file-earmark-text me-1"></i> Ask about a report</a>
        <a href="{{ $base ?? url('/executive') }}" class="text-decoration-none fw-semibold"><i class="bi bi-stars me-1"></i> Ask iARIS on your Briefing</a>
    </div>
    @endisset

    {{-- New message --}}
    <div class="modal fade" id="newModal" tabindex="-1" aria-labelledby="newTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="newForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="newTitle">New message to IATO</h3>
                        <p class="small text-white-50 mb-0">IATO usually answers within one working day.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label for="newTopic" class="form-label small fw-bold text-uppercase text-body-secondary">Topic</label>
                    <select class="form-select" id="newTopic" required>
                        <option value="">Choose one</option>
                        <option>Question about the data</option>
                        <option>Request a report</option>
                        <option>Something looks wrong</option>
                        <option>Access or account</option>
                        <option>Other</option>
                    </select>
                    <div class="invalid-feedback">Choose a topic.</div>
                    <label for="newSubject" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Subject</label>
                    <input type="text" class="form-control" id="newSubject" maxlength="80" required placeholder="e.g. Exam results for June 6">
                    <div class="invalid-feedback">Write a short subject.</div>
                    <label for="newMessage" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Message</label>
                    <textarea class="form-control" id="newMessage" rows="4" required placeholder="Tell IATO what you need."></textarea>
                    <div class="invalid-feedback">Write your message.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send</button>
                </div>
            </form>
        </div>
    </div>

    {{-- LAMP: correct the IATO record (scholar checks only) --}}
    @isset($correctTypes)
    <div class="modal fade" id="fixModal" tabindex="-1" aria-labelledby="fixTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="fixForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="fixTitle">Correct the IATO record</h3>
                        <p class="small text-white-50 mb-0" id="fixSub">—</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label for="fixType" class="form-label small fw-bold text-uppercase text-body-secondary">Correct scholarship type</label>
                    <select class="form-select" id="fixType" required>
                        <option value="">Choose one</option>
                        @foreach ($correctTypes as $type)
                            <option>{{ $type }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">Choose the correct type.</div>
                    <label for="fixNote" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Note for IATO <span class="fw-normal text-lowercase">(optional)</span></label>
                    <textarea class="form-control" id="fixNote" rows="3" placeholder="e.g. Already on our scholarship list for this AY."></textarea>
                    <p class="small text-body-secondary mt-3 mb-0">The correction goes to the IATO Review Queue. IATO sees it after the Super Admin approves.</p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send correction</button>
                </div>
            </form>
        </div>
    </div>
    @endisset

    <div class="toast-container position-fixed bottom-0 start-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="messageToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="messageToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const THREADS = @json($threads);
        const ME = { name: @json(auth()->user()->name), role: @json(auth()->user()->roleInfo()[0]) };
        // status => [label, Bootstrap colour, icon]
        const STATUS = { answered: ['IATO answered', 'info', 'bi-reply-fill'], waiting: ['Waiting for IATO', 'warning', 'bi-hourglass-split'], resolved: ['Resolved', 'success', 'bi-check-circle-fill'] };
        const $ = id => document.getElementById(id);
        const el = (tag, className, text) => {
            const e = document.createElement(tag);
            if (className) e.className = className;
            if (text !== undefined) e.textContent = text;
            return e;
        };
        // A scholar check IATO asked needs LAMP's answer (status 'answered')
        const badge = t => {
            const [label, tone, icon] = t.check && t.status === 'answered' ? ['Needs your answer', 'warning', 'bi-exclamation-circle-fill'] : STATUS[t.status];
            const b = el('span', `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`);
            b.innerHTML = `<i class="bi ${icon} me-1"></i>`;
            b.append(label);
            return b;
        };
        const toast = text => { $('messageToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('messageToast')).show(); };
        const nowLabel = () => { const d = new Date(); return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`; };
        let tab = 'all', current = null;

        function draw() {
            const n = s => THREADS.filter(t => t.status === s).length;
            $('tabs').replaceChildren(...[['all', 'All', THREADS.length], ['answered', 'Needs you', n('answered')], ['waiting', 'Waiting for IATO', n('waiting')], ['resolved', 'Resolved', n('resolved')]].map(([key, label, count]) => {
                const b = el('button', `btn btn-sm rounded-pill px-3 fw-semibold ${tab === key ? 'btn-primary' : 'btn-outline-secondary'}`);
                b.type = 'button';
                b.append(label, el('span', 'ms-2 opacity-75', String(count)));
                b.addEventListener('click', () => { tab = key; draw(); });
                return b;
            }));
            const nav = $('messagesBadge');
            if (nav) { nav.textContent = n('answered'); nav.classList.toggle('d-none', !n('answered')); }

            // The list: IATO's answers first
            const list = THREADS.filter(t => tab === 'all' || t.status === tab)
                .sort((a, b) => (a.status === 'answered' ? 0 : 1) - (b.status === 'answered' ? 0 : 1));
            $('list').replaceChildren(...(list.length ? list.map(t => {
                const last = t.messages.at(-1);
                const item = el('button', `list-group-item list-group-item-action px-3 py-3 ${t.id === current ? "bg-primary-subtle" : ""}`);
                item.type = 'button';
                const top = el('div', 'd-flex justify-content-between small text-body-secondary');
                top.append(el('span', 'fw-semibold', last.from === 'iato' ? `IATO · ${last.name}` : 'You'), el('span', '', last.at.split(' · ')[0]));
                const preview = el('div', 'small text-body-secondary text-truncate', last.text);
                const foot = el('div', 'd-flex flex-wrap gap-2 align-items-center mt-1');
                foot.append(el('span', 'badge rounded-pill bg-body-tertiary text-body border', t.topic), badge(t));
                item.append(top, el('div', `${t.status === 'answered' ? 'fw-bold' : 'fw-semibold'}`, t.subject), preview, foot);
                item.addEventListener('click', () => { current = t.id; draw(); });
                return item;
            }) : [el('div', 'text-center text-body-secondary small py-5', 'No conversations here.')]));

            drawView();
        }

        function drawView() {
            const view = $('view');
            view.replaceChildren();
            const t = THREADS.find(x => x.id === current);
            if (!t) {
                view.appendChild(el('div', 'text-center text-body-secondary small my-auto py-5', 'Choose a conversation, or start a new message to IATO.'));
                return;
            }
            const head = el('div', 'card-header bg-white border-0 rounded-top-4 d-flex flex-wrap justify-content-between align-items-start gap-2 px-4 pt-4');
            const titles = el('div');
            titles.append(el('h2', 'fs-6 fw-bold mb-1', t.subject), el('div', 'small text-body-secondary', t.check ? t.topic : `${t.id} · ${t.topic}`));
            head.append(titles, badge(t));
            const body = el('div', 'card-body px-4');
            if (t.check) {
                const c = t.check;
                const card = el('div', 'border rounded-3 p-3 mb-3');
                card.innerHTML = `<div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><div><div class="fw-bold"><i class="bi bi-person-vcard text-primary me-1"></i></div><div class="small text-body-secondary"></div></div><a class="btn btn-sm btn-light border fw-semibold" href=""><i class="bi bi-box-arrow-up-right me-1"></i> Open the record</a></div>
                    <div class="row g-2 small mt-1"><div class="col-6"><div class="bg-body-tertiary rounded-3 p-2"><div class="text-body-secondary">IATO record</div><div class="fw-bold" data-iato></div></div></div><div class="col-6"><div class="bg-body-tertiary rounded-3 p-2"><div class="text-body-secondary">LAMP record</div><div class="fw-bold" data-lamp></div></div></div></div>`;
                card.querySelector('.fw-bold').append(c.name);
                card.querySelector('.small').textContent = `${c.id} · ${c.unit}`;
                card.querySelector('a').href = `${c.url}?open=${encodeURIComponent(c.id)}`;
                card.querySelector('[data-iato]').textContent = c.iato;
                card.querySelector('[data-lamp]').textContent = c.lamp;
                body.appendChild(card);
            }
            const thread = el('div', 'vstack gap-2');
            t.messages.forEach(m => {
                const mine = m.from === 'me';
                const b = el('div', `rounded-3 px-3 py-2 small ${mine ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}`);
                b.style.maxWidth = '88%';
                const who = el('div', 'fw-bold', mine ? `You · ${ME.role}` : `${m.name} · ${m.role}`);
                who.appendChild(el('span', 'fw-normal text-body-secondary', ` · ${m.at}`));
                b.append(who, el('div', '', m.text));
                thread.appendChild(b);
            });
            body.appendChild(thread);
            if (t.outcome) {
                const o = el('div', 'small text-success-emphasis mt-2');
                o.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>';
                o.append(t.outcome);
                body.appendChild(o);
            }

            if (t.status !== 'resolved') {
                const reply = el('textarea', 'form-control mt-3');
                reply.rows = 2;
                reply.placeholder = 'Write a reply to IATO…';
                reply.setAttribute('aria-label', 'Reply to IATO');
                const actions = el('div', 'd-flex flex-wrap gap-2 mt-2');
                const send = el('button', 'btn btn-sm btn-primary fw-semibold');
                send.type = 'button';
                send.innerHTML = '<i class="bi bi-reply me-1"></i> Send';
                send.addEventListener('click', () => {
                    const text = reply.value.trim();
                    reply.classList.toggle('is-invalid', !text);
                    if (!text) return reply.focus();
                    t.messages.push({ from: 'me', at: nowLabel(), text });
                    t.status = 'waiting';
                    draw();
                    toast('Sent to IATO. (Demo only — not saved yet.)');
                });
                actions.appendChild(send);
                if (t.check) {
                    const ok = el('button', 'btn btn-sm btn-success fw-semibold');
                    ok.type = 'button';
                    ok.innerHTML = '<i class="bi bi-check-lg me-1"></i> IATO is right, update our record';
                    ok.addEventListener('click', () => {
                        t.messages.push({ from: 'me', at: nowLabel(), text: `Confirmed. Our record now shows ${t.check.iato}.` });
                        t.status = 'resolved';
                        t.outcome = `LAMP updated its record to ${t.check.iato}`;
                        draw();
                        toast(`${t.check.name}: records now match. IATO was notified. (Demo only — not saved yet.)`);
                    });
                    const fix = el('button', 'btn btn-sm btn-light border fw-semibold');
                    fix.type = 'button';
                    fix.innerHTML = '<i class="bi bi-pencil me-1"></i> Correct the IATO record';
                    fix.addEventListener('click', () => openFix(t));
                    actions.append(ok, fix);
                } else if (t.status === 'answered') {
                    const done = el('button', 'btn btn-sm btn-light border fw-semibold');
                    done.type = 'button';
                    done.innerHTML = '<i class="bi bi-check-lg me-1"></i> Mark resolved';
                    done.addEventListener('click', () => { t.status = 'resolved'; draw(); toast('Marked as resolved. (Demo only — not saved yet.)'); });
                    actions.appendChild(done);
                }
                body.append(reply, actions);
            }
            view.append(head, body);
        }

        // ---- Correct the IATO record (LAMP's scholar checks) ----
        let fixing = null;
        function openFix(t) {
            fixing = t;
            $('fixForm').reset();
            $('fixForm').classList.remove('was-validated');
            $('fixSub').textContent = `${t.check.name} · IATO has ${t.check.iato}`;
            bootstrap.Modal.getOrCreateInstance($('fixModal')).show();
        }
        $('fixForm')?.addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return $('fixType').focus();
            const t = fixing, v = $('fixType').value, note = $('fixNote').value.trim();
            t.messages.push({ from: 'me', at: nowLabel(), text: `IATO record should be ${v}. Sent a correction to your Review Queue.` + (note ? ` ${note}` : '') });
            t.status = 'resolved';
            t.outcome = `Sent a correction to the IATO Review Queue: ${t.check.iato} → ${v}`;
            bootstrap.Modal.getOrCreateInstance($('fixModal')).hide();
            draw();
            toast('Correction sent to the IATO Review Queue. (Demo only — not saved yet.)');
        });

        // ---- New message ----
        const newModal = bootstrap.Modal.getOrCreateInstance($('newModal'));
        function openNew(subject = '', topic = '') {
            $('newForm').reset();
            $('newForm').classList.remove('was-validated');
            $('newSubject').value = subject;
            $('newTopic').value = topic;
            newModal.show();
        }
        $('newButton').addEventListener('click', () => openNew());
        $('newModal').addEventListener('shown.bs.modal', () => ($('newTopic').value ? $('newMessage') : $('newTopic')).focus());
        $('newForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return e.target.querySelector(':invalid')?.focus();
            const next = 'MSG-' + String(Math.max(...THREADS.filter(t => t.id.startsWith('MSG-')).map(t => +t.id.split('-')[1]), 0) + 1).padStart(4, '0');
            THREADS.unshift({ id: next, topic: $('newTopic').value, subject: $('newSubject').value.trim(), status: 'waiting', messages: [{ from: 'me', at: nowLabel(), text: $('newMessage').value.trim() }] });
            current = next;
            tab = 'all';
            newModal.hide();
            draw();
            toast('Sent to IATO. (Demo only — not saved yet.)');
        });

        // From a report's "Ask IATO": ?new=1&subject=About: …
        const params = new URLSearchParams(location.search);
        current = THREADS.find(t => t.status === 'answered')?.id ?? THREADS[0]?.id ?? null;
        draw();
        if (params.get('new')) openNew(params.get('subject') || '', 'Question about the data');
    </script>
@endsection
