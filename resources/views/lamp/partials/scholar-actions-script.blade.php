{{--
    Script for the Confirm / Flag / Edit pop-ups (scholar-modals) and the action buttons.
    The page must define SCHOLARS (the list) and a scholarsChanged() function that redraws it.
    Front end only: changes live in SCHOLARS until reload, and nobody is notified yet.
    TODO: send each action to a backend route that saves it and notifies IATO.
--}}
<script>
    const LAMP_BADGES = @json($lampBadges);
    const TYPE_GROUPS = @json($typeGroups);
    const TYPE_TONES = @json($typeTones);
    const SCHOLARSHIP_TONES = @json($scholarshipTones);

    const scholarById = id => SCHOLARS.find(s => s.id === id);

    // Turn a <span> into a coloured badge, optionally with an icon in front
    function setBadge(el, text, tone, icon) {
        el.className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
        el.textContent = text;
        if (icon) {
            const i = document.createElement('i');
            i.className = `bi ${icon} me-1`;
            el.prepend(i);
        }
    }
    const setLampBadge = (el, status) => setBadge(el, status, ...LAMP_BADGES[status]);
    const setTypeBadge = (el, s) => setBadge(el, s.type, TYPE_TONES[TYPE_GROUPS[s.type]]);
    const setScholarshipBadge = (el, s) => setBadge(el, s.scholarship_status, SCHOLARSHIP_TONES[s.scholarship_status], 'bi-circle-fill small');

    function lampToast(text) {
        document.getElementById('lampToastText').textContent = text;
        bootstrap.Toast.getOrCreateInstance(document.getElementById('lampToast')).show();
    }

    // The buttons for one row: Confirm and Flag only while a record isn't confirmed yet
    function buildActions(s, withView = false) {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex gap-1 justify-content-end';
        const add = (action, icon, title, extra = '') => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = `btn btn-sm btn-light border ${extra}`;
            b.dataset.lampAction = action;
            b.dataset.id = s.id;
            b.title = title;
            b.setAttribute('aria-label', `${title}: ${s.name}`);
            b.innerHTML = `<i class="bi ${icon}"></i>`;
            wrap.appendChild(b);
        };
        if (s.lamp_status !== 'Confirmed Match') {
            add('confirm', 'bi-check-lg', 'Confirm match', 'text-primary');
            add('flag', 'bi-flag', 'Flag issue', 'text-danger');
        }
        add('edit', 'bi-pencil', 'Edit record');
        if (withView) add('view', 'bi-eye', 'View details');
        return wrap;
    }

    // ---- The pop-ups ----
    let actionId = null;   // which scholar the open pop-up is about
    const modals = {
        confirm: bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmModal')),
        flag: bootstrap.Modal.getOrCreateInstance(document.getElementById('flagModal')),
        edit: bootstrap.Modal.getOrCreateInstance(document.getElementById('editModal')),
    };

    function openAction(action, id) {
        actionId = id;
        const s = scholarById(id);
        const modalEl = document.getElementById(`${action}Modal`);
        const form = modalEl.querySelector('form');
        form.reset();
        form.classList.remove('was-validated');
        modalEl.querySelectorAll('[data-info]').forEach(el => el.textContent = s[el.dataset.info] ?? '—');
        if (action === 'flag' && s.lamp_status === 'Not Found') document.getElementById('flagIssue').value = 'Scholar not found in LAMP records';
        if (action === 'edit') {
            document.getElementById('editName').value = s.name;
            document.getElementById('editType').value = s.type;
            document.getElementById('editStatus').value = s.scholarship_status;
        }
        modals[action].show();
    }

    // Any button with data-lamp-action on the page ("view" is handled by the page itself)
    document.addEventListener('click', e => {
        const b = e.target.closest('[data-lamp-action]');
        if (!b || b.dataset.lampAction === 'view') return;
        e.stopPropagation();
        openAction(b.dataset.lampAction, b.dataset.id);
    });

    // Runs the form check, then the change, then redraws the page
    function onSubmit(formId, apply) {
        const form = document.getElementById(formId);
        form.addEventListener('submit', e => {
            e.preventDefault();
            form.classList.add('was-validated');
            if (!form.checkValidity()) return;
            const s = scholarById(actionId);
            const message = apply(s);
            bootstrap.Modal.getInstance(form.closest('.modal')).hide();
            scholarsChanged();
            lampToast(`${message} (sample only — IATO isn't notified yet)`);
        });
    }

    onSubmit('confirmForm', s => {
        s.lamp_status = 'Confirmed Match';
        s.lamp_record = s.type;   // LAMP now agrees with IATO's record
        return `${s.name} confirmed as a match`;
    });

    onSubmit('flagForm', s => {
        s.lamp_status = 'Discrepancy';
        s.flag = document.getElementById('flagIssue').value;
        return `${s.name} flagged: ${s.flag}`;
    });

    onSubmit('editForm', s => {
        s.type = document.getElementById('editType').value;
        s.scholarship_status = document.getElementById('editStatus').value;
        return `${s.name} updated`;
    });
</script>
