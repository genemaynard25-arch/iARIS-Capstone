{{--
    "Report a problem": the form behind the sidebar link (partials/report-problem-link). Every user except TOIC.
    Sends the problem to TOIC (IARIS_ISSUES in partials/issues-store) and lists your own reports with their status.
    FRONT END ONLY: kept in this browser until the backend has an issues table.
--}}
@php
    $me = auth()->user();
    [$myRole, $myOffice] = $me->roleInfo();
@endphp
<div class="modal fade" id="reportProblemModal" tabindex="-1" aria-labelledby="reportProblemTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="reportProblemForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="reportProblemTitle">Report a problem</h3>
                    <p class="small text-white-50 mb-0">Something not working? TOIC manages iARIS and will fix it. You'll see the status here.</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label for="rpWhat" class="form-label small fw-bold text-uppercase text-body-secondary">What isn't working?</label>
                <select class="form-select" id="rpWhat" required>
                    <option value="">Choose one</option>
                    <option>A page won't load or is very slow</option>
                    <option>I can't sign in or MFA isn't working</option>
                    <option>An import or upload failed</option>
                    <option>A download or report failed</option>
                    <option>Ask iARIS isn't answering</option>
                    <option>Something else</option>
                </select>
                <div class="invalid-feedback">Choose one.</div>

                <label for="rpWhere" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Where</label>
                <input type="text" class="form-control" id="rpWhere" maxlength="80">

                <label for="rpWhat2" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">What happened</label>
                <textarea class="form-control" id="rpWhat2" rows="3" maxlength="600" required placeholder="e.g. I clicked Download and nothing happened. I tried twice."></textarea>
                <div class="invalid-feedback">Tell TOIC what happened.</div>

                <div class="alert alert-info d-flex gap-2 small mt-3 mb-0"><i class="bi bi-person-lock"></i><span>Don't include applicant names or details. TOIC fixes the system and does not see applicant records.</span></div>

                {{-- Your reports (filled in by the script) --}}
                <div class="mt-4 d-none" id="rpMineWrap">
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-1 mb-2">Your reports</div>
                    <div class="vstack gap-2" id="rpMine"></div>
                </div>
            </div>
            {{-- Buttons on the left, so the floating AI button doesn't cover them --}}
            <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-start">
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-send me-1"></i> Send to TOIC</button>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 start-0 p-3">
    <div class="toast align-items-center border-0 text-bg-dark" id="rpToast" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="rpToastText"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    (() => {
        const $ = id => document.getElementById(id);
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const ME = { id: @json($me->id), name: @json($me->displayName()), role: @json($myRole . ' · ' . $myOffice) };
        const LABELS = { new: ['Sent to TOIC', 'secondary'], investigating: ['TOIC is working on it', 'warning'], fixed: ['Fixed', 'success'], closed: ['Closed', 'secondary'] };
        const form = $('reportProblemForm');

        function drawMine() {
            const mine = IARIS_ISSUES.all().filter(x => x.by === ME.id);
            $('rpMineWrap').classList.toggle('d-none', !mine.length);
            $('rpMine').innerHTML = mine.map(x => {
                const last = x.tl[x.tl.length - 1], [label, tone] = LABELS[x.st];
                return `<div class="d-flex align-items-start gap-2 rounded-3 bg-body-tertiary p-2">
                    <div class="flex-grow-1 small"><div class="fw-semibold">${esc(x.title)}</div><div class="text-body-secondary">${x.id} · ${esc(last.toic ? 'TOIC: ' + last.text : x.at)}</div></div>
                    <span class="badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis">${label}</span></div>`;
            }).join('');
        }
        // Each time it opens: an empty form, "Where" set to this page, and your reports
        $('reportProblemModal').addEventListener('show.bs.modal', () => {
            form.reset();
            form.classList.remove('was-validated');
            $('rpWhere').value = document.title.replace(/^iARIS — /, '');
            drawMine();
        });
        $('reportProblemModal').addEventListener('shown.bs.modal', () => $('rpWhat').focus());

        form.addEventListener('submit', e => {
            e.preventDefault();
            if (!form.checkValidity()) return form.classList.add('was-validated');
            const what = $('rpWhat').value;
            // Which part of iARIS it is about, and how urgent (can't sign in = High)
            const svc = /sign in|MFA/.test(what) ? 'Sign-in' : /import|upload/.test(what) ? 'Import' : /download|report/.test(what) ? 'Reports'
                : /Ask iARIS/.test(what) ? 'AI assistant' : /page/.test(what) ? 'Web server' : 'Other';
            const issue = { id: IARIS_ISSUES.next(), title: what, svc, sev: /sign in/.test(what) ? 'High' : 'Normal', st: 'new',
                src: `Reported by ${ME.name} · ${ME.role}`, by: ME.id, page: $('rpWhere').value.trim() || '—', at: IARIS_ISSUES.now(),
                tl: [{ who: ME.name, role: ME.role, at: IARIS_ISSUES.now(), text: $('rpWhat2').value.trim() }] };
            IARIS_ISSUES.add(issue);
            const where = $('rpWhere').value;
            form.reset();
            $('rpWhere').value = where;
            form.classList.remove('was-validated');
            drawMine();
            $('rpToastText').textContent = `Sent to TOIC as ${issue.id}. You can follow it here. (Demo only — saved in this browser.)`;
            bootstrap.Toast.getOrCreateInstance($('rpToast')).show();
        });
    })();
</script>
