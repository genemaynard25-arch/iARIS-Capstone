@extends('layouts.app')

{{--
    IATO's Messages (IatoMessagesController): the Super Admin's and IATO Staff's conversations with every other office.
    One list for three kinds: messages, Registrars' record questions, and LAMP scholar checks.
    Filter by sender, search, and the tabs; the open conversation is on the right. Quick replies fill the reply box.
    ?open=msg:MSG-0112 opens that conversation.
    FRONT END ONLY: new messages, replies, "Mark resolved" and "Reopen" stay until reload.
--}}

@section('title', 'iARIS — Messages')
@section('page-title', 'Messages')
@section('page-subtitle', 'Every conversation with deans, the Program Chair, leadership, the Registrars, and LAMP. Replies never change records.')

@section('content')
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" id="stats"></div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="input-group" style="max-width: 320px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="search" class="form-control border-start-0" id="search" placeholder="Search name, subject, or applicant no." aria-label="Search messages">
        </div>
        <select class="form-select w-auto" id="from" aria-label="Sender">
            <option value="">Everyone</option>
            @foreach ($groups as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
        <div class="d-flex flex-wrap gap-2 ms-lg-auto" id="tabs" role="group" aria-label="Filter conversations"></div>
        <button type="button" class="btn btn-primary fw-semibold" id="newButton"><i class="bi bi-pencil-square me-1"></i> New message</button>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="list-group list-group-flush rounded-4 overflow-auto" id="list" style="max-height: 75vh;" aria-label="Conversations"></div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100" id="view"></div>
        </div>
    </div>

    <div class="small text-body-secondary d-flex flex-wrap align-items-center gap-1">
        <span>To ask LAMP about a scholar, open the scholar in</span>
        <a href="{{ url('/records/scholars') }}" class="text-decoration-none fw-semibold"><i class="bi bi-award me-1"></i>Scholarships</a>
        <span>and use <b>Ask LAMP to check</b>. It shows up here.</span>
    </div>

    {{-- New message --}}
    <div class="modal fade" id="newModal" tabindex="-1" aria-labelledby="newTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="newForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="newTitle">New message</h3>
                        <p class="small text-white-50 mb-0">Send a note, a heads-up, or a follow-up.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Everyone outside IATO, from the users table --}}
                    <label for="newTo" class="form-label small fw-bold text-uppercase text-body-secondary">To</label>
                    <select class="form-select" id="newTo" required>
                        <option value="">Choose who</option>
                        @foreach (collect($people)->groupBy('group') as $group => $list)
                            <optgroup label="{{ $groups[$group] }}">
                                @foreach ($list as $person)
                                    <option value="{{ $person['id'] }}">{{ $person['name'] }} — {{ $person['role'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">Choose who gets this.</div>
                    <label for="newTopic" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Topic</label>
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
                    <input type="text" class="form-control" id="newSubject" maxlength="80" required placeholder="e.g. Exam schedule for June 15">
                    <div class="invalid-feedback">Write a short subject.</div>
                    <label for="newMessage" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Message</label>
                    <textarea class="form-control" id="newMessage" rows="4" required placeholder="Write your message. It appears in their Messages page."></textarea>
                    <div class="invalid-feedback">Write your message.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send</button>
                </div>
            </form>
        </div>
    </div>

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
        const ALL = @json($conversations);
        const PEOPLE = @json($people);
        const ME = { name: @json(auth()->user()->displayName()), role: @json(auth()->user()->roleInfo()[0]) };
        const QUICK = ['Thank you, we are checking this and will reply today.', 'The report is now in your Reports page.', 'This has been corrected in the record.'];
        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const toast = text => { $('messageToastText').textContent = text; bootstrap.Toast.getOrCreateInstance($('messageToast')).show(); };
        const nowLabel = () => { const d = new Date(); return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`; };
        const waitingFor = c => c.kind === 'lamp' ? 'LAMP' : c.kind === 'reg' ? 'Registrar' : 'them';

        // The state chip, from IATO's side: [label, Bootstrap colour, icon]
        const chip = c => {
            const [label, tone, icon] = c.state === 'iato' ? ['Needs your reply', 'warning', 'bi-exclamation-circle-fill']
                : c.state === 'other' ? [`Waiting for ${waitingFor(c)}`, 'info', 'bi-hourglass-split'] : ['Resolved', 'success', 'bi-check-circle-fill'];
            return `<span class="badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis"><i class="bi ${icon} me-1"></i>${label}</span>`;
        };

        let tab = 'all', from = '', search = '', current = null;
        const seen = new Set();   // conversations opened on this page (no longer bold)
        const shown = () => ALL.filter(c => (!from || c.group === from)
            && (!search || `${c.who} ${c.role} ${c.subject} ${c.record?.id ?? ''}`.toLowerCase().includes(search)));

        function draw() {
            const count = s => ALL.filter(c => c.state === s).length;
            $('stats').innerHTML = [
                ['Needs your reply', count('iato'), 'Deans, leaders, Registrars, LAMP', 'warning'],
                ['Waiting on them', count('other'), 'You replied or asked', 'info'],
                ['Record questions', ALL.filter(c => c.record && c.state !== 'done').length, 'Registrars and LAMP, still open', 'primary'],
                ['Resolved', count('done'), 'Closed', 'success'],
            ].map(([label, value, note, tone]) => `<div class="col"><div class="card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div><div class="fs-3 fw-bold text-${tone}-emphasis">${value}</div><div class="small text-body-secondary">${note}</div></div></div></div>`).join('');

            const list = shown();
            const n = s => list.filter(c => c.state === s).length;
            $('tabs').innerHTML = [['all', 'All', list.length], ['iato', 'Needs your reply', n('iato')], ['other', 'Waiting on them', n('other')], ['done', 'Resolved', n('done')]]
                .map(([key, label, count]) => `<button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold ${tab === key ? 'btn-primary' : 'btn-outline-secondary'}" data-tab="${key}">${label}<span class="ms-2 opacity-75">${count}</span></button>`).join('');
            $('tabs').querySelectorAll('button').forEach(b => b.addEventListener('click', () => { tab = b.dataset.tab; draw(); }));

            const nav = $('nav-count-messages');   // the sidebar's number
            if (nav) { nav.textContent = count('iato'); nav.classList.toggle('d-none', !count('iato')); }

            const rows = list.filter(c => tab === 'all' || c.state === tab);
            $('list').innerHTML = rows.length ? rows.map(c => {
                const last = c.messages.at(-1), unread = c.state === 'iato' && !seen.has(c.key);
                return `<button type="button" class="list-group-item list-group-item-action px-3 py-3 ${c.key === current ? 'bg-primary-subtle' : ''}" data-key="${esc(c.key)}">
                    <div class="d-flex justify-content-between align-items-center small text-body-secondary gap-2">
                        <span class="d-flex align-items-center gap-2 fw-semibold text-body"><span class="bg-iaris text-white rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;font-size:.7rem">${esc(c.initials)}</span>${esc(c.who)}</span>
                        <span class="text-nowrap">${esc(last.at.split(' · ')[0])}</span>
                    </div>
                    <div class="${unread ? 'fw-bold' : 'fw-semibold'} mt-1">${esc(c.subject)}</div>
                    <div class="small text-body-secondary text-truncate">${last.from === 'iato' ? 'IATO: ' : ''}${esc(last.text)}</div>
                    <div class="d-flex flex-wrap gap-2 align-items-center mt-1">
                        <span class="badge rounded-pill bg-body-tertiary text-body border">${esc(c.role)}</span>
                        ${c.record ? `<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><i class="bi bi-person-vcard me-1"></i>${esc(c.record.id)}</span>` : `<span class="badge rounded-pill bg-body-tertiary text-body-secondary border">${esc(c.topic)}</span>`}
                        ${chip(c)}
                    </div>
                </button>`;
            }).join('') : '<div class="text-center text-body-secondary small py-5"><i class="bi bi-chat-square-text d-block fs-3 mb-2"></i>No conversations here.</div>';
            $('list').querySelectorAll('[data-key]').forEach(b => b.addEventListener('click', () => open(b.dataset.key)));

            drawView();
        }

        function drawView() {
            const c = ALL.find(x => x.key === current);
            if (!c) {
                $('view').innerHTML = '<div class="text-center text-body-secondary small my-auto py-5"><i class="bi bi-envelope-open d-block fs-3 mb-2"></i>Choose a conversation to read and reply.</div>';
                return;
            }
            // The record it's about: link, and for LAMP checks both records side by side
            const record = c.record ? `<div class="border rounded-3 p-3 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><div class="fw-bold"><i class="bi bi-person-vcard text-primary me-1"></i>${esc(c.record.name)}</div><div class="small text-body-secondary">${esc(c.record.id)} · ${esc(c.record.unit)}</div></div>
                    <a class="btn btn-sm btn-light border fw-semibold" href="${esc(c.record.url)}"><i class="bi bi-box-arrow-up-right me-1"></i> Open the record</a>
                    ${c.kind === 'lamp' ? `<div class="row g-2 w-100 small"><div class="col-6"><div class="bg-body-tertiary rounded-3 p-2"><div class="text-body-secondary">IATO record</div><div class="fw-bold">${esc(c.record.iato)}</div></div></div>
                        <div class="col-6"><div class="bg-body-tertiary rounded-3 p-2"><div class="text-body-secondary">LAMP record</div><div class="fw-bold">${esc(c.record.lamp)}</div></div></div></div>` : ''}
                </div>` : '';
            const thread = c.messages.map(m => {
                const mine = m.from === 'iato';
                return `<div class="rounded-3 px-3 py-2 small ${mine ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}" style="max-width:88%">
                    <div class="fw-bold">${esc(m.name)} · ${esc(m.role)}<span class="fw-normal text-body-secondary"> · ${esc(m.at)}</span></div><div>${esc(m.text)}</div></div>`;
            }).join('') + (c.outcome ? `<div class="small text-success-emphasis"><i class="bi bi-check-circle-fill me-1"></i>${esc(c.outcome)}</div>` : '');

            let actions;
            if (c.state === 'done') {
                // IATO can reopen a LAMP check it closed; the other conversations are reopened by the sender
                actions = c.kind === 'lamp'
                    ? '<div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-sm btn-light border fw-semibold" id="reopen"><i class="bi bi-arrow-counterclockwise me-1"></i> Reopen</button></div>'
                    : '<p class="small text-body-secondary mt-3 mb-0"><i class="bi bi-check-circle-fill text-success me-1"></i>Resolved. The sender can reopen it.</p>';
            } else {
                actions = `<div class="d-flex flex-wrap gap-2 mt-3">${QUICK.map((q, i) => `<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill text-start" data-quick="${i}">${esc(q)}</button>`).join('')}</div>
                    <textarea class="form-control mt-2" rows="2" id="reply" placeholder="Reply to ${esc(c.who)}…" aria-label="Reply"></textarea>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <button type="button" class="btn btn-sm btn-primary fw-semibold" id="send"><i class="bi bi-send me-1"></i> Send</button>
                        ${c.kind === 'lamp' ? '<button type="button" class="btn btn-sm btn-light border fw-semibold" id="resolve"><i class="bi bi-check-lg me-1"></i> Mark resolved</button>' : ''}
                    </div>`;
            }

            $('view').innerHTML = `<div class="card-header bg-white border-0 rounded-top-4 d-flex flex-wrap justify-content-between align-items-start gap-2 px-4 pt-4">
                    <div><h2 class="fs-6 fw-bold mb-1">${esc(c.subject)}</h2><div class="small text-body-secondary">${esc(c.who)} · ${esc(c.role)} · ${esc(c.topic)}</div></div>${chip(c)}
                </div>
                <div class="card-body px-4">${record}<div class="vstack gap-2">${thread}</div>${actions}</div>`;

            $('view').querySelectorAll('[data-quick]').forEach(b => b.addEventListener('click', () => { $('reply').value = QUICK[b.dataset.quick]; $('reply').focus(); }));
            $('send')?.addEventListener('click', () => {
                const text = $('reply').value.trim();
                $('reply').classList.toggle('is-invalid', !text);
                if (!text) return $('reply').focus();
                c.messages.push({ from: 'iato', name: ME.name, role: ME.role, at: nowLabel(), text });
                c.state = 'other';
                draw();
                toast(`Reply sent to ${c.who}. (Demo only — not saved yet.)`);
            });
            $('resolve')?.addEventListener('click', () => { c.state = 'done'; c.outcome = `Marked resolved by ${ME.name}`; draw(); toast('Marked as resolved. (Demo only — not saved yet.)'); });
            $('reopen')?.addEventListener('click', () => { c.state = 'iato'; delete c.outcome; draw(); toast('Reopened. (Demo only — not saved yet.)'); });
        }

        function open(key) {
            current = key;
            seen.add(key);
            draw();
        }

        $('search').addEventListener('input', e => { search = e.target.value.trim().toLowerCase(); draw(); });
        $('from').addEventListener('change', e => { from = e.target.value; draw(); });

        // ---- New message ----
        const newModal = bootstrap.Modal.getOrCreateInstance($('newModal'));
        $('newButton').addEventListener('click', () => {
            $('newForm').reset();
            $('newForm').classList.remove('was-validated');
            newModal.show();
        });
        $('newModal').addEventListener('shown.bs.modal', () => $('newTo').focus());
        $('newForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return e.target.querySelector(':invalid')?.focus();
            const p = PEOPLE.find(x => String(x.id) === $('newTo').value);
            const next = 'MSG-' + String(Math.max(0, ...ALL.filter(c => c.kind === 'msg').map(c => +c.id.split('-')[1])) + 1).padStart(4, '0');
            ALL.unshift({
                key: `msg:${next}`, kind: 'msg', id: next, person: p.id, who: p.name, role: p.role, initials: p.initials, group: p.group,
                subject: $('newSubject').value.trim(), topic: $('newTopic').value, state: 'other', record: null,
                messages: [{ from: 'iato', name: ME.name, role: ME.role, at: nowLabel(), text: $('newMessage').value.trim() }],
            });
            tab = 'all';
            from = '';
            $('from').value = '';
            newModal.hide();
            open(`msg:${next}`);
            toast(`Sent to ${p.name}. (Demo only — not saved yet.)`);
        });

        // ?open=reg:APP-2026-I002 opens that conversation; otherwise the first one that needs a reply
        const linked = new URLSearchParams(location.search).get('open');
        open(ALL.find(c => c.key === linked)?.key ?? ALL.find(c => c.state === 'iato')?.key ?? ALL[0]?.key ?? null);
    </script>
@endsection
