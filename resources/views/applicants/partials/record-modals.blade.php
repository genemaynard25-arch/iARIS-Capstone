{{--
    Add / Edit / Remove an applicant on the Records pages (only for roles with the 'edit' permission).
    FRONT END ONLY for now: the form checks the fields and the toast says nothing was saved.
    Remove hides the row on this page until the next reload.
    TODO (backend): POST / PUT / DELETE routes for the applicants table, then send these forms there.
    Uses the page's script (applicants/index.blade.php): APPLICANTS, PAGE, rows, render(), drawer, drawerApplicant.
--}}

{{-- Add / Edit form --}}
<div class="modal fade" id="recordModal" tabindex="-1" aria-labelledby="recordTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="recordForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="recordTitle">Add applicant</h3>
                    <p class="small text-white-50 mb-0" id="recordSubtitle"></p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Applicant</div>
                <div class="row g-3 mb-4">
                    <div class="col-sm-5">
                        <label for="recLast" class="form-label small fw-bold text-uppercase">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="recLast" required>
                        <div class="invalid-feedback">Enter the last name.</div>
                    </div>
                    <div class="col-sm-5">
                        <label for="recFirst" class="form-label small fw-bold text-uppercase">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="recFirst" required>
                        <div class="invalid-feedback">Enter the first name.</div>
                    </div>
                    <div class="col-sm-2">
                        <label for="recMi" class="form-label small fw-bold text-uppercase">M.I.</label>
                        <input type="text" class="form-control" id="recMi" maxlength="2">
                    </div>
                    <div class="col-sm-4">
                        <label for="recGender" class="form-label small fw-bold text-uppercase">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" id="recGender" required>
                            <option value="">Choose…</option>
                            <option>Female</option>
                            <option>Male</option>
                        </select>
                        <div class="invalid-feedback">Choose a gender.</div>
                    </div>
                    <div class="col-sm-8">
                        <label for="recEmail" class="form-label small fw-bold text-uppercase">Email</label>
                        <input type="email" class="form-control" id="recEmail" placeholder="name@example.com">
                        <div class="invalid-feedback">Enter a valid email, or leave it blank.</div>
                    </div>
                    <div class="col-sm-4">
                        <label for="recContact" class="form-label small fw-bold text-uppercase">Contact No.</label>
                        <input type="tel" class="form-control" id="recContact" placeholder="0917 000 0000">
                    </div>
                    <div class="col-sm-8">
                        <label for="recAddress" class="form-label small fw-bold text-uppercase">Address</label>
                        <input type="text" class="form-control" id="recAddress" placeholder="City, Province">
                    </div>
                </div>

                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Application</div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="recGroup" class="form-label small fw-bold text-uppercase"><span id="recGroupLabel">College</span> <span class="text-danger">*</span></label>
                        <select class="form-select" id="recGroup" required></select>
                        <div class="invalid-feedback">Choose one.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recProgram" class="form-label small fw-bold text-uppercase"><span id="recProgramLabel">Program</span> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="recProgram" list="recPrograms" required>
                        <datalist id="recPrograms"></datalist>
                        <div class="invalid-feedback">Enter the program.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recStatus" class="form-label small fw-bold text-uppercase">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="recStatus" required></select>
                    </div>
                    <div class="col-sm-6">
                        <label for="recApplied" class="form-label small fw-bold text-uppercase">Date Applied <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="recApplied" required>
                        <div class="invalid-feedback">Pick the date.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex flex-wrap align-items-center gap-2">
                <span class="small text-body-secondary me-auto"><i class="bi bi-info-circle me-1"></i> Front end only for now: nothing is saved until the backend is connected.</span>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-check-lg me-1"></i> <span id="recordSave">Add applicant</span></button>
            </div>
        </form>
    </div>
</div>

{{-- Remove: ask for a reason first --}}
<div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4" id="removeForm" novalidate>
            <div class="modal-body p-4">
                <div class="d-flex gap-3 mb-3">
                    <span class="icon-circle rounded-circle bg-danger-subtle text-danger-emphasis fs-5 d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-trash"></i></span>
                    <div>
                        <h3 class="fs-5 fw-bold mb-1" id="removeTitle">Remove this applicant?</h3>
                        <p class="small text-body-secondary mb-0">The record leaves this list, the Applicants pipeline, and reports.</p>
                    </div>
                </div>
                <div class="rounded-3 bg-body-tertiary p-3 small d-flex flex-wrap gap-3 mb-3">
                    <div><div class="text-body-secondary">Applicant</div><div class="fw-bold" id="removeName"></div></div>
                    <div><div class="text-body-secondary">App No.</div><div class="fw-bold font-monospace" id="removeId"></div></div>
                    <div><div class="text-body-secondary">Status</div><div class="fw-bold" id="removeStatus"></div></div>
                </div>
                <label for="removeReason" class="form-label small fw-bold text-uppercase">Reason <span class="text-danger">*</span></label>
                <select class="form-select mb-1" id="removeReason" required>
                    <option value="">Choose a reason</option>
                    <option>Duplicate record</option>
                    <option>Application withdrawn by applicant</option>
                    <option>Entered by mistake</option>
                    <option>Other</option>
                </select>
                <div class="invalid-feedback">Choose a reason.</div>
                <label for="removeNote" class="form-label small fw-bold text-uppercase mt-3">Note <span class="text-body-secondary fw-normal text-lowercase">(optional)</span></label>
                <textarea class="form-control" id="removeNote" rows="2"></textarea>
                <div class="invalid-feedback">Add a short note when the reason is Other.</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger fw-semibold"><i class="bi bi-trash me-1"></i> Remove</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast align-items-center border-0 text-bg-dark" id="recordToast" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="recordToastText"></div>
            {{-- Shown only after Remove --}}
            <button type="button" class="btn btn-sm btn-light fw-semibold my-auto d-none" id="recordUndo">Undo</button>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    // Runs after the page's own script and Bootstrap have loaded
    document.addEventListener('DOMContentLoaded', () => {
        const $ = id => document.getElementById(id);
        const recordModal = bootstrap.Modal.getOrCreateInstance($('recordModal'));
        const removeModal = bootstrap.Modal.getOrCreateInstance($('removeModal'));
        // undo: shows the Undo button (only after Remove)
        const toast = (text, undo = false) => {
            $('recordToastText').textContent = text;
            $('recordUndo').classList.toggle('d-none', !undo);
            bootstrap.Toast.getOrCreateInstance($('recordToast')).show();
        };
        let lastRemoved = null;   // { row, position } so Undo can put it back
        const unique = values => [...new Set(values)].filter(v => v && v !== '—').sort((a, b) => a.localeCompare(b, undefined, { numeric: true }));
        let editing = null;

        // Fill the selects from what's on this page (colleges / sub-levels / programs, and this page's statuses)
        function fillChoices() {
            const labels = currentLabels();
            $('recGroupLabel').textContent = labels.group;
            $('recProgramLabel').textContent = labels.program;
            $('recGroup').replaceChildren(new Option('Choose…', ''), ...unique([...(GROUP_ORDER || []), ...APPLICANTS.map(a => a.group)]).map(v => new Option(v, v)));
            $('recPrograms').replaceChildren(...unique(APPLICANTS.map(a => a.program)).map(v => new Option(v)));
            $('recStatus').replaceChildren(...PAGE.statuses.map(v => new Option(PAGE.statusLabels[v] ?? v, v)));
        }

        // mode 'add' = empty form; 'edit' = filled in from an applicant
        window.openRecordForm = (mode, a = drawerApplicant) => {
            editing = mode === 'edit' ? a : null;
            fillChoices();
            $('recordForm').reset();
            $('recordForm').classList.remove('was-validated');
            $('recordTitle').textContent = editing ? 'Edit applicant' : 'Add applicant';
            $('recordSubtitle').textContent = editing ? `${editing.name} · ${editing.id}` : `New ${PAGE.noun} for AY {{ $academicYear }}`;
            $('recordSave').textContent = editing ? 'Save changes' : 'Add applicant';
            if (editing) {
                const set = (id, value) => { if (value && value !== '—') $(id).value = value; };
                set('recLast', editing.last); set('recFirst', editing.first); set('recMi', editing.mi);
                set('recGender', editing.gender); set('recEmail', editing.email); set('recContact', editing.contact);
                set('recAddress', editing.address); set('recGroup', editing.group); set('recProgram', editing.program);
                set('recStatus', editing.status); set('recApplied', editing.applied);
            } else {
                $('recApplied').value = new Date().toISOString().slice(0, 10);
            }
            bootstrap.Offcanvas.getInstance($('applicantDrawer'))?.hide();
            recordModal.show();
        };

        document.querySelectorAll('[data-record-form]').forEach(btn => btn.addEventListener('click', () => openRecordForm(btn.dataset.recordForm)));

        $('recordForm').addEventListener('submit', e => {
            e.preventDefault();
            const form = e.target;
            form.classList.add('was-validated');
            if (!form.checkValidity()) return form.querySelector(':invalid')?.focus();
            const name = `${$('recLast').value.trim()}, ${$('recFirst').value.trim()}`;
            recordModal.hide();
            // TODO (backend): send the form, then reload the list
            toast(editing
                ? `Changes to ${name} were checked but not saved: saving records comes with the backend.`
                : `${name} was checked but not added: adding records comes with the backend.`);
        });

        // ---- Remove ----
        document.querySelector('[data-record-remove]')?.addEventListener('click', () => {
            const a = drawerApplicant;
            $('removeName').textContent = a.name;
            $('removeId').textContent = a.id;
            $('removeStatus').textContent = a.status_label;
            $('removeForm').reset();
            $('removeForm').classList.remove('was-validated');
            drawer.hide();
            removeModal.show();
        });

        // Undo: put the removed row back where it was
        $('recordUndo').addEventListener('click', () => {
            if (!lastRemoved) return;
            rows.splice(lastRemoved.position, 0, lastRemoved.row);
            document.getElementById('applicantRows').appendChild(lastRemoved.row);
            render();
            lastRemoved = null;
            toast('Restored.');
        });

        // "Other" needs a note
        $('removeReason').addEventListener('change', e => $('removeNote').required = e.target.value === 'Other');

        $('removeForm').addEventListener('submit', e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return;
            // Hide the row on this page only (it comes back on reload until the backend can delete it)
            const index = APPLICANTS.indexOf(drawerApplicant);
            const row = rows.find(r => Number(r.dataset.index) === index);
            if (row) {
                lastRemoved = { row, position: rows.indexOf(row) };
                rows.splice(lastRemoved.position, 1);
                row.remove();
                render();
            }
            removeModal.hide();
            toast(`${drawerApplicant.name} was removed from this page only (it comes back when you reload, until the backend is connected).`, true);
        });
    });
</script>
