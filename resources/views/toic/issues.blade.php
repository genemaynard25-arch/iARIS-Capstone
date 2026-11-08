@extends('layouts.app')

{{--
    Issues (ToicController::issuesPage): problems reported by users ("Report a problem") or found by system checks.
    List on the left (Open / Fixed / All), the chosen issue on the right:
      New → "Start working on it" → quick fixes, "Post update", "Mark as fixed" → "Close" (or "Reopen").
    Updates show up in the reporter's "Your reports". #INC-0034 opens that issue (System Overview links here).
    FRONT END ONLY: kept in this browser (partials/issues-store) until the backend has an issues table.
--}}

@section('title', 'iARIS — Issues')
@section('page-title', 'Issues')
@section('page-subtitle', 'Problems reported by users or found by system checks. TOIC fixes them and keeps the people who reported them updated.')

@section('content')
    @include('toic.partials.privacy')

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4" id="stats"></div>

    <div class="d-flex flex-wrap gap-2 mb-3" id="tabs" role="group" aria-label="Filter issues"></div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="list-group list-group-flush rounded-4" id="list" aria-label="Issues"></div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100" id="view"></div>
        </div>
    </div>

    {{-- Restore the last backup? --}}
    <div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-body p-4">
                    <h3 class="fs-5 fw-bold" id="restoreTitle">Restore the last backup?</h3>
                    <p class="small text-body-secondary mb-0">iARIS goes into maintenance mode while the backup is restored. Changes made after the backup are lost.</p>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                    <button type="button" class="btn btn-danger fw-semibold" id="restoreYes"><i class="bi bi-clock-history me-1"></i> Restore</button>
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    @include('toic.partials.kit')
@endsection

@section('scripts')
    <script>
        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const ME = { who: 'TOIC Support Team', role: 'TOIC' };
        const STATUS = { new: ['New', 'danger'], investigating: ['Working on it', 'warning'], fixed: ['Fixed', 'success'], closed: ['Closed', 'secondary'] };
        const SEVERITY = { Critical: 'danger', High: 'warning', Normal: 'secondary' };
        // Quick fixes per area: [button, what goes in the history]
        const FIXES = {
            'Import': [['Restart the import worker', 'Restarted the import worker.'], ['Raise import memory to 2 GB', 'Raised the import worker memory from 512 MB to 2 GB.'], ['Retry the failed import', 'Retried the last failed import job.']],
            'Web server': [['Restart the web server', 'Restarted the web server.'], ['Renew the SSL certificate now', 'Renewed the SSL certificate. New expiry: Jul 3, 2027.']],
            'Backups': [['Run a backup now', 'Ran a manual backup.'], ['Free up disk space', 'Removed files past their retention period.']],
            'AI assistant': [['Restart the AI service', 'Restarted the AI assistant service.'], ['Renew the AI service key', 'Renewed the AI service key.']],
            'Sign-in': [['Resend the MFA setup link', 'Sent a new MFA setup link to the user.'], ['Unlock the account', 'Unlocked the account.']],
            'Reports': [['Restart the report service', 'Restarted the report and download service.']],
            'Other': [['Restart the web server', 'Restarted the web server.']],
        };
        const isOpen = x => x.st === 'new' || x.st === 'investigating';
        let tab = 'open', current = null;

        // Save, and update the sidebar's Issues badge
        function save() {
            IARIS_ISSUES.save();
            const badge = $('issuesBadge'), n = IARIS_ISSUES.open();
            if (badge) { badge.textContent = n; badge.classList.toggle('d-none', !n); }
        }
        const log = (x, text) => x.tl.push({ who: ME.who, role: ME.role, at: IARIS_ISSUES.now(), text, toic: true });
        const pill = (label, tone) => `<span class="badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis">${esc(label)}</span>`;

        // ---- Tiles, tabs and the list ----
        function draw() {
            const all = IARIS_ISSUES.all();
            const counts = { open: all.filter(isOpen).length, fixed: all.filter(x => !isOpen(x)).length, all: all.length };
            $('stats').innerHTML = [
                ['New', all.filter(x => x.st === 'new').length, 'Not looked at yet', 'danger'],
                ['Working on it', all.filter(x => x.st === 'investigating').length, 'In progress', 'warning'],
                ['Fixed', counts.fixed, 'This month', 'success'],
                ['Average time to fix', '3.1 hrs', 'Last 30 days', 'primary'],
            ].map(([label, value, note, tone]) => `<div class="col"><div class="card border-0 border-top border-3 border-${tone} shadow-sm rounded-4 h-100"><div class="card-body">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">${label}</div><div class="fs-3 fw-bold text-${tone}">${value}</div><div class="small text-body-secondary">${note}</div></div></div></div>`).join('');

            $('tabs').innerHTML = [['open', 'Open'], ['fixed', 'Fixed'], ['all', 'All']].map(([key, label]) =>
                `<button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold ${tab === key ? 'btn-primary' : 'btn-outline-secondary'}" data-tab="${key}" aria-pressed="${tab === key}">${label} · ${counts[key]}</button>`).join('');
            $('tabs').querySelectorAll('[data-tab]').forEach(b => b.addEventListener('click', () => { tab = b.dataset.tab; draw(); }));

            const shown = all.filter(x => tab === 'all' || (tab === 'open' ? isOpen(x) : !isOpen(x)));
            $('list').innerHTML = shown.length ? shown.map(x => `
                <button type="button" class="list-group-item list-group-item-action px-4 py-3 ${x.id === current ? 'bg-primary-subtle border-start border-4 border-primary' : ''}" data-id="${x.id}" aria-current="${x.id === current}">
                    <div class="small text-body-secondary">${x.id} · ${esc(x.at.split(' · ')[0])} · ${esc(x.svc)}</div>
                    <div class="fw-semibold">${esc(x.title)}</div>
                    <div class="small text-body-secondary">${esc(x.src)}</div>
                    <div class="d-flex gap-1 mt-1">${pill(x.sev, SEVERITY[x.sev] || 'secondary')}${pill(STATUS[x.st][0], STATUS[x.st][1])}</div>
                </button>`).join('')
                : '<div class="text-center text-body-secondary small py-5"><i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>Nothing here. iARIS is running fine.</div>';
            $('list').querySelectorAll('[data-id]').forEach(b => b.addEventListener('click', () => openIssue(b.dataset.id)));
            if (!current) $('view').innerHTML = '<div class="text-center text-body-secondary small py-5 my-auto"><i class="bi bi-tools fs-3 d-block mb-2"></i>Choose an issue to work on it.</div>';
        }

        // ---- One issue ----
        function openIssue(id) {
            const x = IARIS_ISSUES.get(id);
            if (!x) return draw();
            current = id;
            history.replaceState(null, '', '#' + id);
            const fixes = FIXES[x.svc] || FIXES.Other;
            const reporter = x.src.replace(/^Reported by /, '').split(' · ')[0];
            $('view').innerHTML = `
                <div class="card-header bg-white border-bottom rounded-top-4 px-4 py-3">
                    <h2 class="fs-5 fw-bold mb-1">${esc(x.title)}</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2 small text-body-secondary">${pill(x.sev, SEVERITY[x.sev] || 'secondary')}${pill(STATUS[x.st][0], STATUS[x.st][1])}<span>${x.id} · ${esc(x.svc)} · ${esc(x.page)}</span></div>
                    <div class="small text-body-secondary mt-1">${esc(x.src)} · opened ${esc(x.at)}</div>
                </div>
                <div class="card-body px-4">
                    ${x.tl.map(m => `<div class="rounded-3 p-3 mb-2 small ${m.toic ? 'bg-primary-subtle ms-4' : 'bg-body-tertiary me-4'}"><div class="fw-semibold">${esc(m.who)} <span class="fw-normal text-body-secondary">· ${esc(m.role)} · ${esc(m.at)}</span></div><div>${esc(m.text)}</div></div>`).join('')}
                    ${x.fix && !isOpen(x) ? `<div class="alert alert-success d-flex gap-2 small mt-3 mb-0"><i class="bi bi-check-circle"></i><div><b>How it was fixed</b><div>${esc(x.fix)}</div></div></div>` : ''}
                    ${x.st === 'new' ? `<div class="mt-3"><button type="button" class="btn btn-primary fw-semibold" id="startIssue"><i class="bi bi-person-gear me-1"></i> Start working on it</button></div>` : ''}
                    ${x.st === 'investigating' ? `
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-top pt-3 mt-3 mb-2">Quick fixes</div>
                        <div class="d-flex flex-wrap gap-2 mb-3">${fixes.map(([label], i) => `<button type="button" class="btn btn-sm btn-light border" data-fix="${i}"><i class="bi bi-wrench me-1"></i>${esc(label)}</button>`).join('')}
                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#restoreModal"><i class="bi bi-clock-history me-1"></i>Restore last backup</button></div>
                        <label for="issueNote" class="form-label small fw-bold">Update for ${esc(reporter)}</label>
                        <textarea class="form-control mb-2" id="issueNote" rows="3" maxlength="600" placeholder="What you found or did. The person who reported it sees this."></textarea>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-primary fw-semibold" id="fixIssue"><i class="bi bi-check-lg me-1"></i> Mark as fixed</button>
                            <button type="button" class="btn btn-light border" id="postUpdate"><i class="bi bi-send me-1"></i> Post update</button>
                            <div class="form-check form-switch ms-auto small m-0"><input class="form-check-input" type="checkbox" role="switch" id="issueMaintenance" ${IARIS_ISSUES.mode().on ? 'checked' : ''}><label class="form-check-label" for="issueMaintenance">Show maintenance notice to all users</label></div>
                        </div>` : ''}
                    ${x.st === 'fixed' ? `<div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-primary fw-semibold" id="closeIssue"><i class="bi bi-archive me-1"></i> Close</button><button type="button" class="btn btn-light border" id="reopenIssue"><i class="bi bi-arrow-counterclockwise me-1"></i> Reopen</button></div>` : ''}
                    ${x.st === 'closed' ? `<div class="mt-3"><button type="button" class="btn btn-light border" id="reopenIssue"><i class="bi bi-arrow-counterclockwise me-1"></i> Reopen</button></div>` : ''}
                </div>`;
            const on = (elId, fn) => $(elId)?.addEventListener('click', fn);
            const done = text => { save(); openIssue(id); toast(`${text} (Demo only — saved in this browser.)`); };

            on('startIssue', () => { x.st = 'investigating'; log(x, 'TOIC is working on it.'); done(`${x.id}: working on it.`); });
            $('view').querySelectorAll('[data-fix]').forEach(b => b.addEventListener('click', () => {
                const [, text] = fixes[+b.dataset.fix];
                b.disabled = true;
                b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Running…';
                setTimeout(() => { log(x, text); done(text); }, 900);
            }));
            on('postUpdate', () => {
                const text = $('issueNote').value.trim();
                if (!text) return $('issueNote').focus();
                log(x, text);
                done('Update posted.');
            });
            on('fixIssue', () => {
                const text = $('issueNote').value.trim();
                if (!text) { $('issueNote').placeholder = 'Say how you fixed it before marking it fixed.'; $('issueNote').focus(); return toast('Write how you fixed it first.'); }
                log(x, 'Fixed: ' + text);
                x.fix = text;
                x.st = 'fixed';
                if (IARIS_ISSUES.mode().on) setMaintenance(false);
                done(`${x.id} fixed. The reporter can see it.`);
            });
            $('issueMaintenance')?.addEventListener('change', e => {
                setMaintenance(e.target.checked, `TOIC is fixing a problem with ${x.svc.toLowerCase()}. You can view data, but changes are paused.`);
                toast(e.target.checked ? 'Maintenance notice on for all users.' : 'Maintenance notice off.');
            });
            on('closeIssue', () => { x.st = 'closed'; log(x, 'Closed.'); done(`${x.id} closed.`); });
            on('reopenIssue', () => { x.st = 'investigating'; log(x, 'Reopened.'); done(`${x.id} reopened.`); });
            draw();
        }

        // Maintenance notice: saved for everyone in this browser, and shown on this page right away
        function setMaintenance(on, msg = '') {
            IARIS_ISSUES.setMode({ on, msg });
            const banner = $('maintenanceBanner');
            banner.querySelector('span').textContent = msg;
            banner.classList.toggle('d-none', !on);
        }
        $('restoreYes').addEventListener('click', () => {
            bootstrap.Modal.getOrCreateInstance($('restoreModal')).hide();
            const x = IARIS_ISSUES.get(current);
            setMaintenance(true, 'iARIS is being restored from a backup. You can view data, but changes are paused.');
            log(x, 'Restoring the backup from Jun 11, 2026 · 11:00 PM. Maintenance notice is on.');
            save();
            openIssue(current);
            toast('Restoring. All users see the maintenance notice. (Demo only.)');
        });

        // Open the issue in the address (#INC-0034), or the first open one
        const hash = decodeURIComponent(location.hash.slice(1));
        const first = IARIS_ISSUES.get(hash) || IARIS_ISSUES.all().find(isOpen);
        if (first) { tab = isOpen(first) ? 'open' : 'fixed'; openIssue(first.id); } else draw();
        // Back / forward, or a link to another issue on this page
        window.addEventListener('hashchange', () => {
            const x = IARIS_ISSUES.get(decodeURIComponent(location.hash.slice(1)));
            if (x && x.id !== current) { tab = isOpen(x) ? 'open' : 'fixed'; openIssue(x.id); }
        });
    </script>
@endsection
