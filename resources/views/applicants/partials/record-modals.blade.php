{{--
    Add / Edit / Remove an applicant on the Records pages (only for roles with the 'edit' permission).
    College and Integrated School forms use the applicant endpoints. IATO Staff changes are
    staged in the Review Queue; unsupported scopes display an explicit backend notice.
    The page tells the form where its things are with window.RECORD_FORM (set in the page's script):
      items (the people), rows (table rows), tbody (its id), render(), drawer, current() (the person in the drawer),
      labels() ({group, program}), groupOrder, statuses, statusLabels, noun, toForm(person) (fields for the form).
    Used by the Records pages (applicants/index) and Scholars. Scholars passes $scholarFields for its extra Scholarship Status select.
--}}

{{-- Add / Edit form --}}
@php($staffReview = auth()->user()->role === 'iato_staff' && ! auth()->user()->isAdmin())
@php($realBackend = in_array($scope ?? null, ['college', 'is']))
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
                        @if (($scope ?? null) === 'law')
                            <input type="text" class="form-control" id="recProgram" required>
                        @else
                            <select class="form-select" id="recProgram" required></select>
                        @endif
                        <div class="invalid-feedback">{{ ($scope ?? null) === 'law' ? 'Enter the prior degree.' : 'Choose one.' }}</div>
                    </div>
                    @isset($scholarFields)
                        <div class="col-sm-6">
                            <label for="recScholarship" class="form-label small fw-bold text-uppercase">Scholarship Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="recScholarship" required>
                                @foreach ($scholarFields['statuses'] as $status)
                                    <option>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endisset
                    <div class="col-sm-6">
                        <label for="recStatus" class="form-label small fw-bold text-uppercase">{{ isset($scholarFields) ? 'Admission Status' : 'Status' }} <span class="text-danger">*</span></label>
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
                @if (! $realBackend)
                    <span class="small text-body-secondary me-auto"><i class="bi bi-info-circle me-1"></i> Front end only for now: nothing is saved until the backend is connected.</span>
                @elseif ($staffReview)
                    <span class="small text-body-secondary me-auto"><i class="bi bi-clipboard-check me-1"></i> Your changes go to the Review Queue. Other users see them only after the Super Admin approves.</span>
                @endif
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
                        @if ($staffReview)
                            <h3 class="fs-5 fw-bold mb-1" id="removeTitle">Request removal?</h3>
                            <p class="small text-body-secondary mb-0">The Super Admin reviews it first. The record stays until they approve.</p>
                        @else
                            <h3 class="fs-5 fw-bold mb-1" id="removeTitle">Remove this applicant?</h3>
                            <p class="small text-body-secondary mb-0">The record leaves this list, the Applicants pipeline, and reports.</p>
                        @endif
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
                <button type="submit" class="btn btn-danger fw-semibold"><i class="bi bi-trash me-1"></i> {{ $staffReview ? 'Request removal' : 'Remove' }}</button>
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

<script src="{{ asset('js/admission-catalog.js') }}"></script>
<script>
    // Runs after the page's own script and Bootstrap have loaded
    document.addEventListener('DOMContentLoaded', () => {
        const $ = id => document.getElementById(id);
        const R = window.RECORD_FORM;   // set by the page (see the comment at the top)
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

        // IATO Staff: the server stages the change in the Review Queue instead of applying it
        // (see ApplicantController::needsReview()) and says so in its response.
        const STAFF_REVIEW = @json($staffReview);
        const REAL_BACKEND = ['college', 'is'].includes(R.scope);
        // Put a "waiting for review" tag under the person's name in their table row
        function tagRow(a, kind, rq) {
            const row = R.rows.find(r => Number(r.dataset.index) === R.items.indexOf(a));
            const cell = row && [...row.cells].find(td => td.textContent.includes(a.name));
            if (!cell) return;
            cell.querySelector('[data-review-tag]')?.remove();
            const tag = document.createElement('div');
            tag.dataset.reviewTag = '';
            tag.className = `badge rounded-pill mt-1 ${kind === 'remove' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-warning-subtle text-warning-emphasis'}`;
            tag.title = `Sent to the Review Queue as ${rq}`;
            tag.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>';
            tag.append(`${kind === 'remove' ? 'Removal' : 'Edit'} waiting for review · ${rq}`);
            cell.appendChild(tag);
        }

        // Offer the complete admission catalog even on empty pages; keep imported groups available.
        function fillChoices() {
            const labels = R.labels();
            $('recGroupLabel').textContent = labels.group;
            $('recProgramLabel').textContent = labels.program;
            $('recGroup').replaceChildren(new Option('Choose…', ''), ...unique([...IarisAdmissionCatalog.groups(R.scope), ...(R.groupOrder || []), ...R.items.map(a => R.toForm(a).group)]).map(v => new Option(v, v)));
            fillPrograms();
            $('recStatus').replaceChildren(...R.statuses.map(v => new Option(R.statusLabels[v] ?? v, v)));
        }

        function fillPrograms(existing = '') {
            const select = $('recProgram');
            if (select.tagName !== 'SELECT') return;
            const choices = IarisAdmissionCatalog.programs(R.scope, $('recGroup').value) || [];
            // Preserve legacy imported values on edit without offering unrelated programs on new records.
            const values = [...new Set([...choices, ...(existing ? [existing] : [])])];
            select.replaceChildren(new Option('Choose…', ''), ...values.map(v => new Option(v, v)));
            select.disabled = !$('recGroup').value;
            if (existing) select.value = existing;
        }
        $('recGroup').addEventListener('change', () => fillPrograms());

        // mode 'add' = empty form; 'edit' = filled in from an applicant
        window.openRecordForm = (mode, a = R.current()) => {
            editing = mode === 'edit' ? a : null;
            const f = editing ? R.toForm(editing) : null;
            fillChoices();
            $('recordForm').reset();
            $('recordForm').classList.remove('was-validated');
            $('recordTitle').textContent = editing ? 'Edit applicant' : 'Add applicant';
            $('recordSubtitle').textContent = editing ? `${editing.name} · ${editing.id}` : `New ${R.noun} for AY {{ $academicYear }}`;
            $('recordSave').textContent = STAFF_REVIEW ? 'Submit for review' : (editing ? 'Save changes' : 'Add applicant');
            if (editing) {
                const set = (id, value) => { if (value && value !== '—') $(id).value = value; };
                set('recLast', f.last); set('recFirst', f.first); set('recMi', f.mi);
                set('recGender', f.gender); set('recEmail', f.email); set('recContact', f.contact);
                set('recAddress', f.address); set('recGroup', f.group); fillPrograms(f.program); set('recProgram', f.program);
                set('recStatus', f.status); set('recApplied', f.applied);
                if ($('recScholarship')) set('recScholarship', f.scholarship);
            } else {
                $('recApplied').value = new Date().toISOString().slice(0, 10);
            }
            bootstrap.Offcanvas.getInstance($('applicantDrawer'))?.hide();
            recordModal.show();
        };

        document.querySelectorAll('[data-record-form]').forEach(btn => btn.addEventListener('click', () => openRecordForm(btn.dataset.recordForm)));

        // Submit the Add / Edit form.
        $('recordForm').addEventListener('submit', async e => {
            e.preventDefault();
            const form = e.target;
            form.classList.add('was-validated');
            if (!form.checkValidity()) return form.querySelector(':invalid')?.focus();
            const name = `${$('recLast').value.trim()}, ${$('recFirst').value.trim()}`;
            recordModal.hide();

            if (!REAL_BACKEND) {
                return toast(editing
                    ? `Changes to ${name} were checked but not saved: saving records comes with the backend.`
                    : `${name} was checked but not added: adding records comes with the backend.`);
            }

            const payload = {
                scope: R.scope,
                last: $('recLast').value.trim(),
                first: $('recFirst').value.trim(),
                mi: $('recMi').value.trim(),
                gender: $('recGender').value,
                email: $('recEmail').value.trim() || null,
                contact: $('recContact').value.trim() || null,
                address: $('recAddress').value.trim() || null,
                group: $('recGroup').value,
                program: $('recProgram').value,
                status: $('recStatus').value,
                applied: $('recApplied').value,
            };

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const url = editing ? `/applicants/${editing.id}` : '/applicants';
            const method = editing ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (!res.ok) throw new Error();
                const data = await res.json();
                if (data.review) {
                    if (editing) tagRow(editing, 'edit', data.rq_number);
                    return toast(`Sent to the Review Queue as ${data.rq_number}. Others see it after the Super Admin approves.`);
                }
                toast(`${name} ${editing ? 'updated' : 'added'}. Reloading…`);
                setTimeout(() => location.reload(), 600);
            } catch (err) {
                toast('Something went wrong saving this record. Please try again.');
            }
        });

        // ---- Remove ----
        document.querySelector('[data-record-remove]')?.addEventListener('click', () => {
            const a = R.current();
            $('removeName').textContent = a.name;
            $('removeId').textContent = a.id;
            $('removeStatus').textContent = a.status_label ?? a.status;
            $('removeForm').reset();
            $('removeForm').classList.remove('was-validated');
            R.drawer.hide();
            removeModal.show();
        });

        // Undo: put the removed row back where it was
        $('recordUndo').addEventListener('click', async () => {
            if (!lastRemoved) return;
            if (lastRemoved.applicantId) {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                try {
                    const res = await fetch(`/applicants/${lastRemoved.applicantId}/restore`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    });
                    if (!res.ok) throw new Error();
                } catch (err) {
                    toast('Could not restore this record. Please reload and try again.');
                    return;
                }
            }
            R.rows.splice(lastRemoved.position, 0, lastRemoved.row);
            $(R.tbody).appendChild(lastRemoved.row);
            R.render();
            lastRemoved = null;
            toast('Restored.');
        });

        // "Other" needs a note
        $('removeReason').addEventListener('change', e => $('removeNote').required = e.target.value === 'Other');

        $('removeForm').addEventListener('submit', async e => {
            e.preventDefault();
            e.target.classList.add('was-validated');
            if (!e.target.checkValidity()) return;
            const current = R.current();

            if (!REAL_BACKEND) {
                const index = R.items.indexOf(current);
                const row = R.rows.find(r => Number(r.dataset.index) === index);
                if (row) {
                    lastRemoved = { row, position: R.rows.indexOf(row) };
                    R.rows.splice(lastRemoved.position, 1);
                    row.remove();
                    R.render();
                }
                removeModal.hide();
                return toast(`${current.name} was removed from this page only (it comes back when you reload, until the backend is connected).`, true);
            }

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            let data;
            try {
                const res = await fetch(`/applicants/${current.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error();
                data = await res.json();
            } catch (err) {
                removeModal.hide();
                toast('Something went wrong removing this record. Please try again.');
                return;
            }

            removeModal.hide();
            if (data.review) {
                tagRow(current, 'remove', data.rq_number);
                return toast(`Removal sent to the Review Queue as ${data.rq_number}. The record stays until the Super Admin approves.`);
            }

            const index = R.items.indexOf(current);
            const row = R.rows.find(r => Number(r.dataset.index) === index);
            if (row) {
                lastRemoved = { row, position: R.rows.indexOf(row), applicantId: current.id };
                R.rows.splice(lastRemoved.position, 1);
                row.remove();
                R.render();
            }
            toast(`${current.name} was removed.`, true);
        });
    });
</script>
