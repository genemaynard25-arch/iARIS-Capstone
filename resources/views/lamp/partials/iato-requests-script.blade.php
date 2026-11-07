{{--
    Script for "Requests from IATO" (iato-requests). Needs SCHOLARS, scholarsChanged() and lampToast()
    from the Scholars page, so include it after scholar-actions-script.
    Front end only: answers change the page until reload, and IATO isn't notified yet.
    TODO: save replies and corrections, and send corrections to the Review Queue.
--}}
<script>
    const REQUESTS = @json($iatoRequests);
    let nextReviewNumber = 413;   // made-up Review Queue numbers (RQ-0413, RQ-0414, …)
    let correcting = null;        // request shown in the Correct pop-up

    // e.g. "Jun 12, 2026 · 9:20 AM"
    const nowLabel = () => {
        const d = new Date();
        return `${d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} · ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
    };
    const addMessage = (r, text) => r.messages.push({ from: 'lamp', name: 'LAMP Office', role: 'LAMP Office', at: nowLabel(), text });

    function drawRequests() {
        const open = REQUESTS.filter(r => r.status !== 'resolved')
            .sort((a, b) => (a.status === 'waiting' ? 0 : 1) - (b.status === 'waiting' ? 0 : 1));
        const resolved = REQUESTS.filter(r => r.status === 'resolved');
        const waiting = REQUESTS.filter(r => r.status === 'waiting').length;

        // Header count and the amber number on the sidebar
        const count = document.getElementById('requestsCount');
        count.textContent = `${waiting} need${waiting === 1 ? 's' : ''} you`;
        count.classList.toggle('d-none', waiting === 0);
        const nav = document.getElementById('navRequestsBadge');
        if (nav) {
            nav.textContent = waiting;
            nav.classList.toggle('d-none', waiting === 0);
        }

        const template = document.getElementById('requestTemplate').content.firstElementChild;
        document.getElementById('requestsList').replaceChildren(...open.map(r => {
            const item = template.cloneNode(true);
            item.dataset.id = r.id;
            item.querySelector('[data-cell="name"]').textContent = r.name;
            item.querySelector('[data-cell="sub"]').textContent = `${r.id} · ${r.program} · ${r.reason}`;
            item.querySelector('[data-cell="iato"]').textContent = r.iato;
            item.querySelector('[data-cell="lamp"]').textContent = r.lamp;

            const chip = item.querySelector('[data-cell="chip"]');
            const mine = r.status === 'waiting';
            chip.className = `badge rounded-pill ${mine ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis'}`;
            chip.innerHTML = `<i class="bi ${mine ? 'bi-exclamation-circle-fill' : 'bi-hourglass-split'} me-1"></i>`;
            chip.append(mine ? 'Needs your answer' : 'Waiting for IATO');

            // Messages: IATO on the right, LAMP (you) on the left
            item.querySelector('[data-cell="thread"]').replaceChildren(...r.messages.map(m => {
                const bubble = document.createElement('div');
                const iato = m.from === 'iato';
                bubble.className = `rounded-3 px-3 py-2 small ${iato ? 'bg-primary-subtle align-self-end' : 'bg-body-tertiary align-self-start'}`;
                bubble.style.maxWidth = '92%';
                const who = document.createElement('div');
                who.className = 'fw-bold';
                who.textContent = m.name;
                const meta = document.createElement('span');
                meta.className = 'fw-normal text-body-secondary';
                meta.textContent = ` · ${m.role} · ${m.at}`;
                who.appendChild(meta);
                const text = document.createElement('div');
                text.textContent = m.text;
                bubble.append(who, text);
                return bubble;
            }));
            return item;
        }));

        document.getElementById('requestsEmpty').classList.toggle('d-none', open.length > 0);
        const done = document.getElementById('requestsResolved');
        done.classList.toggle('d-none', resolved.length === 0);
        done.innerHTML = '<i class="bi bi-check-lg me-1"></i>';
        done.append('Resolved: ' + resolved.map(r => `${r.name} (${r.outcome})`).join(' · '));
    }

    document.getElementById('requestsList').addEventListener('click', e => {
        const button = e.target.closest('[data-request-action]');
        if (!button) return;
        const item = button.closest('[data-id]');
        const r = REQUESTS.find(x => x.id === item.dataset.id);

        if (button.dataset.requestAction === 'reply') {
            const box = item.querySelector('[data-cell="reply"]');
            const text = box.value.trim();
            box.classList.toggle('is-invalid', !text);
            if (!text) return box.focus();
            addMessage(r, text);
            r.status = 'replied';
            drawRequests();
            lampToast('Reply sent to IATO. (Demo only — not saved yet.)');
        } else if (button.dataset.requestAction === 'agree') {
            // LAMP changes its own record to IATO's, so the two now match
            addMessage(r, `Confirmed. Our record now shows ${r.iato}.`);
            r.status = 'resolved';
            r.outcome = `LAMP updated its record to ${r.iato}`;
            const scholar = scholarById(r.id);
            if (scholar) {
                scholar.lamp_record = r.iato;
                scholar.lamp_status = 'Confirmed Match';
            }
            drawRequests();
            scholarsChanged();
            lampToast(`${r.name}: records now match. (Demo only — not saved yet.)`);
        } else {
            correcting = r;
            document.getElementById('correctSubtitle').textContent = `${r.name} · IATO has ${r.iato}`;
            document.getElementById('correctForm').reset();
            document.getElementById('correctType').classList.remove('is-invalid');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('correctModal')).show();
        }
    });

    // Clear the red outline once they start typing a reply
    document.getElementById('requestsList').addEventListener('input', e => e.target.classList.remove('is-invalid'));

    document.getElementById('correctForm').addEventListener('submit', e => {
        e.preventDefault();
        const type = document.getElementById('correctType');
        type.classList.toggle('is-invalid', !type.value);
        if (!type.value) return type.focus();

        const r = correcting;
        const number = 'RQ-' + String(nextReviewNumber++).padStart(4, '0');
        const note = document.getElementById('correctNote').value.trim();
        addMessage(r, `IATO record should be ${type.value}. Sent a correction to your Review Queue (${number}).` + (note ? ' ' + note : ''));
        r.status = 'resolved';
        r.outcome = `correction sent to the Review Queue as ${number}: ${r.iato} → ${type.value}`;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('correctModal')).hide();
        drawRequests();
        lampToast(`Correction sent to the IATO Review Queue as ${number}. (Demo only — not saved yet.)`);
    });
    document.getElementById('correctType').addEventListener('change', e => e.target.classList.remove('is-invalid'));

    drawRequests();
</script>
