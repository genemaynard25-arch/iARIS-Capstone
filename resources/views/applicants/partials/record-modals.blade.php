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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg applicant-entry-modal">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="recordForm" novalidate>
            <div class="modal-header border-bottom p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="recordTitle">Add applicant</h3>
                    <p class="small text-body-secondary mb-0" id="recordSubtitle"></p>
                </div>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
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
                    <div class="col-sm-6">
                        <label for="recGender" class="form-label small fw-bold text-uppercase">Sex <span class="text-danger">*</span></label>
                        <select class="form-select" id="recGender" required>
                            <option value="">Choose…</option>
                            <option>Female</option>
                            <option>Male</option>
                        </select>
                        <div class="invalid-feedback">Choose a sex.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recEmail" class="form-label small fw-bold text-uppercase">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="recEmail" required placeholder="name@example.com">
                        <div class="invalid-feedback">Enter a valid email.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recContact" class="form-label small fw-bold text-uppercase">Mobile number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="recContact" required placeholder="0917 000 0000">
                    </div>
                    <div class="col-12">
                        <label for="recAddress" class="form-label small fw-bold text-uppercase">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="recAddress" required placeholder="City, Province">
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
                        <div class="form-text" id="recProgramHint">Choose a college or unit first.</div>
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
                        <label for="recYear" class="form-label small fw-bold text-uppercase"><span id="recYearLabel">{{ ($scope ?? null) === 'is' ? 'Grade' : 'Year level' }}</span> <span class="text-danger">*</span></label>
                        <select class="form-select" id="recYear" required></select>
                        <div class="invalid-feedback">Choose a year or grade level.</div>
                        <div class="form-text" id="recYearHint">2nd Year and up for transferees.</div>
                    </div>
                    <input type="hidden" id="recStatus" value="Pending">
                    <div class="col-sm-6">
                        <label for="recApplied" class="form-label small fw-bold text-uppercase">Date Applied <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="recApplied" required>
                        <div class="invalid-feedback">Pick the date.</div>
                    </div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3 mt-4">Admission</div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="recExamDate" class="form-label small fw-bold">Exam / interview schedule</label>
                        <div class="d-flex gap-2">
                            <input type="date" id="recExamDate" class="form-control" aria-label="Exam / interview date">
                            <input type="time" id="recExamTime" class="form-control" aria-label="Exam / interview time">
                        </div>
                        <div class="form-text">Leave blank if not yet scheduled.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recExamPaid" class="form-label small fw-bold">Exam fee paid <span class="text-danger">*</span></label>
                        <select id="recExamPaid" class="form-select" required><option value="0">No</option><option value="1">Yes</option></select>
                        <div class="form-text">Set to Yes once the Cashier confirms the payment.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recExamResult" class="form-label small fw-bold">Exam result <span class="text-danger">*</span></label>
                        <select id="recExamResult" class="form-select" required>
                            @foreach (\App\Support\ApplicantRecords::EXAM_STATUSES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Passing results issue the Notice of Admission.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recReservationPaid" class="form-label small fw-bold">Reservation fee paid <span class="text-danger">*</span></label>
                        <select id="recReservationPaid" class="form-select" required><option value="0">No</option><option value="1">Yes</option></select>
                        <div class="form-text">Only for Passed or Reconsidered. Set to Yes once the Cashier confirms.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recApplyingScholarship" class="form-label small fw-bold">Applying for scholarship <span class="text-danger">*</span></label>
                        <select id="recApplyingScholarship" class="form-select" required><option value="0">No</option><option value="1">Yes</option></select>
                        <div class="form-text">LAMP sees this applicant once they pass.</div>
                    </div>
                    <div class="col-sm-6">
                        <label for="recNeeds" class="form-label small fw-bold">PWD / SEN <span class="text-danger">*</span></label>
                        <select id="recNeeds" class="form-select" required><option value="none">No</option><option value="pwd">PWD</option><option value="ssen">SEN</option><option value="both">PWD and SEN</option></select>
                    </div>
                </div>
                <details class="mt-4">
                    <summary class="small fw-bold text-iaris">Previous school and other details</summary>
                    <div class="row g-3 mt-1">
                        @foreach (['recFeeder' => ['School last attended', 'text'], 'recSchoolAddress' => ['School address', 'text'], 'recNationality' => ['Nationality', 'text'], 'recPermit' => ['Permit number', 'text'], 'recGwa' => ['GWA', 'number']] as $id => [$label, $type])
                            <div class="col-sm-6"><label for="{{ $id }}" class="form-label small fw-bold">{{ $label }}</label><input id="{{ $id }}" type="{{ $type }}" class="form-control" @if ($type === 'number') step="0.01" min="0" max="99.99" @endif></div>
                        @endforeach
                        <div class="col-sm-6"><label for="recSchoolClass" class="form-label small fw-bold">School classification</label><select id="recSchoolClass" class="form-select"><option value="">Select</option><option>Public</option><option>Private</option></select></div>
                        <div class="col-sm-6"><label for="recStudentType" class="form-label small fw-bold">Student type</label><select id="recStudentType" class="form-select"><option value="">Select</option><option>New Student/ Freshman</option><option>Transferee</option><option>Returning Student</option></select></div>
                        <div class="col-sm-6"><label for="recModality" class="form-label small fw-bold">Learning modality</label><select id="recModality" class="form-select"><option value="">Select</option><option>FACE TO FACE LEARNING</option><option>ONLINE LEARNING</option><option>BLENDED LEARNING</option></select></div>
                    </div>
                </details>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 d-flex flex-wrap align-items-center gap-2">
                @if (! $realBackend)
                    <span class="small text-body-secondary me-auto"><i class="bi bi-info-circle me-1"></i> Front end only for now: nothing is saved until the backend is connected.</span>
                @elseif ($staffReview)
                    <span class="small text-body-secondary me-auto"><i class="bi bi-clipboard-check me-1"></i> Your changes go to the Review Queue. Other users see them only after the Super Admin approves.</span>
                @else
                    <span class="small text-body-secondary me-auto"><i class="bi bi-clipboard-check me-1"></i> Your changes apply immediately and are recorded in Audit Logs. Staff changes go through the Review Queue first.</span>
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
            $('recStatus').value = 'Pending';
            fillYears();
        }

        function fillPrograms(existing = '') {
            const select = $('recProgram');
            if (select.tagName !== 'SELECT') return;
            const group = $('recGroup').value;
            const school = R.scope === 'is' || group.startsWith('Integrated School · ');
            const noStrand = school && group && !group.endsWith('Senior High');
            select.closest('.col-sm-6').classList.toggle('d-none', !!noStrand);
            select.required = !noStrand;
            $('recProgramLabel').textContent = school ? 'Strand' : R.labels().program;
            $('recProgramHint').textContent = group ? '' : (school ? 'Choose a level first.' : 'Choose a college or unit first.');
            const choices = noStrand ? ['General Curriculum'] : (IarisAdmissionCatalog.programs(R.scope, group) || []);
            // Preserve legacy imported values on edit without offering unrelated programs on new records.
            const values = [...new Set([...choices, ...(existing ? [existing] : [])])];
            select.replaceChildren(new Option('Choose…', ''), ...values.map(v => new Option(v, v)));
            select.disabled = !$('recGroup').value;
            if (existing) select.value = existing;
            else if (noStrand) select.value = 'General Curriculum';
        }
        const schoolGroup = () => $('recGroup').value.replace('Integrated School · ', '');
        function fillYears(existing = '') {
            const grades = {Preschool: ['Nursery 1', 'Nursery 2', 'Kinder'], 'Grade School': Array.from({length: 6}, (_, i) => `Grade ${i + 1}`), 'Junior High': Array.from({length: 4}, (_, i) => `Grade ${i + 7}`), 'Senior High': ['Grade 11', 'Grade 12']};
            const school = R.scope === 'is' || $('recGroup').value.startsWith('Integrated School · ');
            $('recYearLabel').textContent = school ? 'Grade' : 'Year level';
            $('recYear').disabled = school && !$('recGroup').value;
            if ($('recYear').disabled) {
                $('recYear').replaceChildren(new Option('Choose a level first', ''));
                $('recYearHint').textContent = 'Choose a level first.';
                return;
            }
            const values = [...new Set([...(grades[schoolGroup()] || ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year', '6th Year']), ...(existing && existing !== '—' ? [existing] : [])])];
            $('recYear').replaceChildren(new Option('Select', ''), ...values.map(v => new Option(v, v)));
            if (existing && existing !== '—') $('recYear').value = existing;
            else if (!grades[schoolGroup()]) $('recYear').value = '1st Year';
            $('recYearHint').textContent = grades[schoolGroup()] ? 'Choose the applicant’s grade level.' : '2nd Year and up for transferees.';
        }
        $('recGroup').addEventListener('change', () => { fillPrograms(); fillYears(); });

        // mode 'add' = empty form; 'edit' = filled in from an applicant
        window.openRecordForm = (mode, a = R.current()) => {
            editing = mode === 'edit' ? a : null;
            const f = editing ? R.toForm(editing) : null;
            fillChoices();
            $('recordForm').reset();
            $('recordForm').classList.remove('was-validated');
            $('recExamDate').required = false; $('recExamTime').required = false;
            $('recordTitle').textContent = editing ? 'Edit applicant' : 'Add applicant';
            $('recordSubtitle').textContent = editing ? `${editing.name} · ${editing.id}` : `A new ${R.scope === 'college' ? 'College' : R.scope === 'is' ? 'Integrated School' : R.noun} record. The applicant number is assigned automatically.`;
            $('recordSave').textContent = STAFF_REVIEW ? 'Submit for review' : (editing ? 'Save changes' : 'Add applicant');
            if (editing) {
                const set = (id, value) => { if (value && value !== '—') $(id).value = value; };
                set('recLast', f.last); set('recFirst', f.first); set('recMi', f.mi);
                set('recGender', f.gender); set('recEmail', f.email); set('recContact', f.contact);
                set('recAddress', f.address); set('recGroup', f.group); fillPrograms(f.program); set('recProgram', f.program);
                set('recStatus', f.status); set('recApplied', f.applied);
                fillYears(f.year_level || f.year || f.level);
                set('recExamResult', f.exam_status); set('recExamDate', f.exam_date); set('recExamTime', f.exam_time);
                $('recExamPaid').value = f.exam_paid ? '1' : '0';
                $('recReservationPaid').value = f.paid || f.status === 'Paid' ? '1' : '0';
                $('recApplyingScholarship').value = f.scholar || f.scholarship === 'Yes' ? '1' : '0';
                const yes = v => v === true || v === 'Yes';
                $('recNeeds').value = yes(f.pwd) ? (yes(f.ssen) ? 'both' : 'pwd') : (yes(f.ssen) ? 'ssen' : 'none');
                for (const [id, field] of Object.entries({recFeeder: 'feeder_school', recSchoolAddress: 'school_address', recNationality: 'nationality', recPermit: 'permit_no', recGwa: 'gwa', recSchoolClass: 'school_classification', recStudentType: 'student_type', recModality: 'learning_modalities'})) {
                    const el = $(id), value = f[field];
                    if (value && el.tagName === 'SELECT' && ![...el.options].some(o => o.value === value)) el.add(new Option(value, value));
                    set(id, value);
                }
                if ($('recScholarship')) set('recScholarship', f.scholarship);
            } else {
                $('recApplied').value = new Date().toISOString().slice(0, 10);
                fillYears();
            }
            bootstrap.Offcanvas.getInstance($('applicantDrawer'))?.hide();
            updateReservationAvailability();
            recordModal.show();
        };

        document.querySelectorAll('[data-record-form]').forEach(btn => btn.addEventListener('click', () => openRecordForm(btn.dataset.recordForm)));

        $('recExamDate').addEventListener('change', () => { $('recExamTime').required = !!$('recExamDate').value; });
        $('recExamTime').addEventListener('change', () => { $('recExamDate').required = !!$('recExamTime').value; });

        function updateReservationAvailability() {
            const allowed = ['passed', 'reconsidered'].includes($('recExamResult').value);
            $('recReservationPaid').disabled = !allowed;
            // Previously recorded payments remain visible when a result changes.
        }
        $('recExamResult').addEventListener('change', updateReservationAvailability);

        // Submit the Add / Edit form.
        $('recordForm').addEventListener('submit', async e => {
            e.preventDefault();
            const form = e.target;
            $('recExamTime').required = !!$('recExamDate').value;
            $('recExamDate').required = !!$('recExamTime').value;
            form.classList.add('was-validated');
            if (!form.checkValidity()) return form.querySelector(':invalid')?.focus();
            const name = `${$('recLast').value.trim()}, ${$('recFirst').value.trim()}`;
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
                year_level: $('recYear').value,
                exam_date: $('recExamDate').value || null,
                exam_time: $('recExamTime').value || null,
                exam_result: $('recExamResult').value,
                exam_fee_paid: $('recExamPaid').value === '1',
                ...(!$('recReservationPaid').disabled ? {reservation_fee_paid: $('recReservationPaid').value === '1'} : {}),
                applying_for_scholarship: $('recApplyingScholarship').value === '1',
                pwd: ['pwd', 'both'].includes($('recNeeds').value),
                ssen: ['ssen', 'both'].includes($('recNeeds').value),
                ...Object.fromEntries(Object.entries({recFeeder: 'feeder_school', recSchoolAddress: 'school_address', recNationality: 'nationality', recPermit: 'permit_no', recGwa: 'gwa', recSchoolClass: 'school_classification', recStudentType: 'student_type', recModality: 'learning_modalities'}).map(([id, field]) => [field, $(id).value || null])),
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
                if (!res.ok) {
                    const error = await res.json().catch(() => ({}));
                    throw new Error(Object.values(error.errors || {}).flat().join(' ') || error.message || 'Could not save this record.');
                }
                const data = await res.json();
                recordModal.hide();
                if (data.review) {
                    if (editing) tagRow(editing, 'edit', data.rq_number);
                    return toast(`Sent to the Review Queue as ${data.rq_number}. Others see it after the Super Admin approves.`);
                }
                toast(`${name} ${editing ? 'updated' : 'added'}. Reloading…`);
                setTimeout(() => location.reload(), 600);
            } catch (err) {
                toast(err.message || 'Something went wrong saving this record. Please try again.');
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
