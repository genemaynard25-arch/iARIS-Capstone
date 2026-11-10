{{--
    LAMP's check of each scholarship applicant, for the Dashboard, Scholars and Reports pages. Needs $applicants (LampOffice::applicants).
    LAMP.people          the applicants, with LAMP's saved checks on top
    LAMP.totals()        {apps, passed, yes, wait, no}
    LAMP.open(x)         the drawer (needs partials/check on the page)
    LAMP.onChange(fn)    run fn after LAMP confirms or declines someone
    LAMP.label(x), LAMP.result(x), LAMP.nameCell(x), LAMP.esc(s), LAMP.LB
    Checks are saved to lamp_scholar_records through authenticated Laravel routes.
--}}
<script>
    const LAMP = (() => {
        const KEY = 'iaris.lampChecks';
        const people = @json($applicants);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        // LAMP check => [label, Bootstrap colour, icon]
        const LB = {
            wait: ['Waiting for LAMP', 'warning', 'bi-hourglass-split'],
            yes: ['Confirmed scholar', 'primary', 'bi-check-circle-fill'],
            no: ['Not a scholar', 'secondary', 'bi-dash-circle-fill'],
            notyet: ['Not eligible yet', 'secondary', 'bi-clock'],
            fail: ['Did not pass', 'danger', 'bi-x-circle-fill'],
        };
        const RC = { Pass: 'success', Reconsidered: 'info', Waitlisted: 'warning', Fail: 'danger', 'Not yet taken': 'secondary' };
        const listeners = [];

        // LAMP decisions are supplied by the server and persisted through an authenticated endpoint.
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const label = x => {
            const [text, tone, icon] = LB[x.lamp];
            return `<span class="badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis text-nowrap"><i class="bi ${icon} me-1"></i>${text}</span>`
                + (x.pending_review ? '<div class="small text-warning-emphasis mt-1">Awaiting Super Admin review: ' + esc(x.pending_review) + '</div>' : '')
                + (x.at ? '<div class="small text-body-secondary mt-1">' + (x.iato_status === 'confirmed' ? 'Confirmed by IATO' : 'Awaiting IATO confirmation') + '</div>' : '');
        };
        const result = x => `<span class="badge rounded-pill bg-${RC[x.result] || 'secondary'}-subtle text-${RC[x.result] || 'secondary'}-emphasis text-nowrap">${esc(x.result)}</span>`;
        const nameCell = x => `<div class="fw-semibold text-nowrap">${esc(x.name)}</div><div class="small text-body-secondary text-nowrap">${esc(x.id)}</div><div class="small text-body-secondary">${esc(x.sex)}${x.pwd ? ' · PWD / SEN' : ''}</div>`;
        const totals = () => ({
            apps: people.length,
            passed: people.filter(x => x.result === 'Pass' || x.result === 'Reconsidered').length,
            yes: people.filter(x => x.lamp === 'yes').length,
            wait: people.filter(x => x.lamp === 'wait').length,
            no: people.filter(x => x.lamp === 'no').length,
        });

        async function act(x, a) {
            const buttons = document.querySelectorAll('#checkButtons [data-a]');
            buttons.forEach(b => b.disabled = true);
            try {
                const response = await fetch(@json(url('/lamp/checks')) + '/' + encodeURIComponent(x.applicant_id), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ action: a }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Could not save the LAMP check.');
                x.pending_review = data.review_number;
                toast('Submitted to Super Admin review queue: ' + data.review_number);
                const el = document.getElementById('checkDrawer');
                if (el) bootstrap.Offcanvas.getOrCreateInstance(el).hide();
                listeners.forEach(fn => fn());

            } catch (error) {
                toast(error.message || 'Could not save the check. Please try again.');
            } finally {
                buttons.forEach(b => b.disabled = false);
            }
        }

        function open(x) {
            const el = document.getElementById('checkDrawer');
            if (!el) return;
            const initials = x.name.split(',').map(s => s.trim()[0] || '').join('').slice(0, 2);
            document.getElementById('checkAvatar').textContent = initials;
            document.getElementById('checkName').textContent = x.name;
            document.getElementById('checkSub').textContent = `${x.id} · ${x.unit}`;
            const passed = x.result === 'Pass' || x.result === 'Reconsidered';
            const field = (k, v) => `<div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">${k}</div><div class="fw-semibold">${v}</div></div>`;
            const head = t => `<div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">${t}</div>`;
            document.getElementById('checkBody').innerHTML = head('From IATO') + `<div class="row g-3 mb-4">
                    ${field('Unit', esc(x.unit))}${field('Program / Grade', esc(x.program))}
                    ${field('Sex', esc(x.sex))}${field('PWD / SEN', x.pwd ? 'Yes' : 'No')}
                    ${field('Applied for a scholarship', 'Yes')}${field('Entrance exam result', result(x))}
                    ${field('Date applied', esc(x.applied))}</div>`
                + head('LAMP check') + `<div class="row g-3 mb-4">${field('Status', label(x))}${field('Last change', x.at ? esc(x.at) : '—')}</div>
                <div class="alert alert-secondary d-flex gap-2 small mb-0"><i class="bi bi-info-circle"></i><span>${passed
                    ? 'This applicant passed. Check them against your own list, then confirm. Scholarship type and documents stay with the LAMP Office.'
                    : 'LAMP only checks applicants who pass the entrance exam.'}</span></div>`;
            const b = document.getElementById('checkButtons');
            const btn = (cls, icon, text, a) => `<button type="button" class="btn ${cls} fw-semibold" data-a="${a}"><i class="bi ${icon} me-1"></i>${text}</button>`;
            b.innerHTML = x.pending_review ? btn('btn-light border', 'bi-x-lg', 'Close', 'close') : x.lamp === 'wait'
                ? btn('btn-light border', 'bi-dash-circle', 'Not a scholar', 'no') + btn('btn-primary', 'bi-check-lg', 'Confirm scholar', 'yes')
                : (x.at && x.iato_status !== 'confirmed' ? btn('btn-light border', 'bi-arrow-counterclockwise', 'Undo my change', 'undo') : '') + btn('btn-light border', 'bi-x-lg', 'Close', 'close');
            b.querySelectorAll('[data-a]').forEach(bt => bt.addEventListener('click', () => bt.dataset.a === 'close' ? bootstrap.Offcanvas.getOrCreateInstance(el).hide() : act(x, bt.dataset.a)));
            bootstrap.Offcanvas.getOrCreateInstance(el).show();
        }

        return { people, LB, RC, esc, label, result, nameCell, totals, act, open, onChange: fn => listeners.push(fn) };
    })();
</script>
