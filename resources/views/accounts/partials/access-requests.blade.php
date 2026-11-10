{{--
    Access Requests tab of User Accounts. Backed by the access_requests table (App\Models\AccessRequest).
    Approve opens a small modal where the admin confirms the actual role/department before the account
    is created (the requester's own "Position" is free text, not a users.role value).
--}}
@php
    $requestInitials = fn ($name) => collect(explode(', ', $name))->map(fn ($part) => mb_substr($part, 0, 1))->reverse()->implode('');
    $when = fn ($at) => \Illuminate\Support\Carbon::parse($at)->format('M j, Y · g:i A');
    // Best-effort guess from the requester's free-text Position so the admin starts with a sensible
    // default; they can change it before approving. No match -> blank, admin must choose.
    $positionToRole = [
        'IATO Staff' => 'iato_staff',
        'Dean' => 'dean',
        'Program Chair' => 'program_chair',
        'Integrated School Principal' => 'principal',
        'Registrar' => 'registrar',
        'LAMP Office' => 'lamp',
        'President' => 'president',
        'Chancellor' => 'chancellor',
        'Vice Chancellor' => 'vice_chancellor',
        'Vice President' => 'vice_president',
        'iPACE Director' => 'director',
    ];
    // Same idea for the requester's free-text Department against AccountController::DEPARTMENTS.
    $departmentGuess = [
        'Institutional Admissions & Testing Office' => 'IATO',
        'College of Nursing' => 'CON',
        'Lasallian Assistance Management Program' => 'Lasallian Mission Office',
    ];
@endphp

<div class="alert alert-light border small d-flex gap-2">
    <i class="bi bi-info-circle mt-1"></i>
    <div>People request access from the login page and choose their position and department. Approving creates the account with the role and department you confirm here. They set up MFA on first login.</div>
</div>

{{-- Waiting --}}
<div class="row row-cols-1 row-cols-lg-2 g-3 mb-4" id="requestCards">
    @foreach (collect($accessRequests)->where('status', 'waiting') as $request)
        <div class="col" data-request="{{ $request['id'] }}">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="rounded-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">{{ $requestInitials($request['name']) }}</span>
                        <div class="overflow-hidden">
                            <div class="fw-bold text-truncate" data-name>{{ $request['name'] }}</div>
                            <div class="small text-body-secondary text-truncate">{{ $request['email'] }}</div>
                        </div>
                        @unless (str_ends_with($request['email'], '@dlsl.edu.ph'))
                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis ms-auto" title="Not a DLSL email address"><i class="bi bi-exclamation-triangle me-1"></i>Not DLSL</span>
                        @endunless
                    </div>
                    <dl class="row g-2 small mb-3">
                        <div class="col-6"><dt class="text-body-secondary fw-semibold">Position</dt><dd class="fw-bold mb-0" data-position>{{ $request['position'] }}</dd></div>
                        <div class="col-6"><dt class="text-body-secondary fw-semibold">Department</dt><dd class="fw-bold mb-0" data-department>{{ $request['department'] }}</dd></div>
                        <div class="col-12"><dt class="text-body-secondary fw-semibold">Reason</dt><dd class="mb-0 fst-italic">“{{ $request['reason'] }}”</dd></div>
                    </dl>
                    <div class="small text-body-secondary mb-3 mt-auto">Requested {{ $when($request['at']) }}</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary fw-semibold flex-fill" data-decide="approve" data-role-guess="{{ $positionToRole[$request['position']] ?? '' }}" data-dept-guess="{{ $departmentGuess[$request['department']] ?? (in_array($request['department'], $departments) ? $request['department'] : '') }}"><i class="bi bi-check-lg me-1"></i> Approve</button>
                        <button type="button" class="btn btn-outline-danger flex-fill" data-decide="decline"><i class="bi bi-x-lg me-1"></i> Decline</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
<p class="text-center text-body-secondary py-4 {{ collect($accessRequests)->where('status', 'waiting')->isEmpty() ? '' : 'd-none' }}" id="noRequests">
    <i class="bi bi-inbox fs-3 d-block mb-2"></i> No access requests waiting.
</p>

{{-- Decided --}}
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h2 class="fs-6 fw-bold mb-1">Decided requests</h2>
        <p class="small text-body-secondary mb-3">Approved and declined requests</p>
        <div class="table-responsive">
            <table class="table align-middle small mb-0">
                <thead class="table-light">
                    <tr class="text-uppercase text-nowrap">
                        <th class="text-body-secondary">Requester</th>
                        <th class="text-body-secondary">Requested</th>
                        <th class="text-body-secondary">Decision</th>
                        <th class="text-body-secondary">By</th>
                        <th class="text-body-secondary">Date</th>
                    </tr>
                </thead>
                <tbody id="decidedRows">
                    @foreach (collect($accessRequests)->where('status', '!=', 'waiting') as $request)
                        <tr>
                            <td><div class="fw-semibold">{{ $request['name'] }}</div><div class="text-body-secondary">{{ $request['email'] }}</div></td>
                            <td>{{ $request['position'] }} · {{ $request['department'] }}</td>
                            <td>
                                @if ($request['status'] === 'approved')
                                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><i class="bi bi-check-lg me-1"></i>Approved</span>
                                @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"><i class="bi bi-x-lg me-1"></i>Declined</span>
                                    @isset($request['note'])<div class="text-body-secondary mt-1">{{ $request['note'] }}</div>@endisset
                                @endif
                            </td>
                            <td>{{ $request['by'] }}</td>
                            <td class="text-nowrap text-body-secondary">{{ $when($request['decided']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Approve: confirm the actual role/department before the account is created --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4" id="approveForm" novalidate>
            <div class="modal-body p-4">
                <h3 class="fs-5 fw-bold mb-1" id="approveTitle">Approve this request?</h3>
                <p class="small text-body-secondary mb-3" id="approveText"></p>
                <div class="alert alert-danger small d-none" id="approveError"></div>
                <div class="mb-3">
                    <label for="approveRole" class="form-label small fw-bold text-uppercase">Role</label>
                    <select class="form-select" id="approveRole" required>
                        <option value="">Select role</option>
                        @foreach ($roles as $key => $role)
                            @continue($key === 'admin')
                            <option value="{{ $key }}">{{ $role['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="approveDept" class="form-label small fw-bold text-uppercase">Department / unit</label>
                    <select class="form-select" id="approveDept" required>
                        <option value="">Select department</option>
                        @foreach ($departments as $department)
                            <option>{{ $department }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-none" id="approveCollegeField">
                    <label for="approveCollege" class="form-label small fw-bold text-uppercase">Assigned college</label>
                    <select class="form-select" id="approveCollege">
                        <option value="">Not assigned yet</option>
                        @foreach ($colleges as $college)
                            <option>{{ $college }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold">Approve &amp; Create Account</button>
            </div>
        </form>
    </div>
</div>

{{-- Decline: optional note to the requester --}}
<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4" id="declineForm">
            <div class="modal-body p-4">
                <h3 class="fs-5 fw-bold mb-1" id="declineTitle">Decline this request?</h3>
                <p class="small text-body-secondary" id="declineText"></p>
                <label for="declineNote" class="form-label small fw-bold text-uppercase">Note to the requester <span class="text-body-secondary fw-normal text-lowercase">(optional)</span></label>
                <textarea class="form-control" id="declineNote" rows="2" placeholder="e.g. Please use your DLSL email address."></textarea>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger fw-semibold">Decline</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Runs after Bootstrap has loaded
    document.addEventListener('DOMContentLoaded', () => {
        const $ = id => document.getElementById(id);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const approveModal = bootstrap.Modal.getOrCreateInstance($('approveModal'));
        const declineModal = bootstrap.Modal.getOrCreateInstance($('declineModal'));
        let current = null; // the card being approved/declined

        $('approveRole').addEventListener('change', () =>
            $('approveCollegeField').classList.toggle('d-none', !['dean', 'program_chair'].includes($('approveRole').value)));

        $('requestCards').addEventListener('click', e => {
            const btn = e.target.closest('[data-decide]');
            if (!btn) return;
            current = btn.closest('[data-request]');
            const name = current.querySelector('[data-name]').textContent.trim();

            if (btn.dataset.decide === 'approve') {
                $('approveText').textContent = `Creates an account for ${name}. They'll set up MFA on first login.`;
                $('approveError').classList.add('d-none');
                $('approveForm').reset();
                $('approveRole').value = btn.dataset.roleGuess || '';
                $('approveDept').value = btn.dataset.deptGuess || '';
                $('approveCollegeField').classList.toggle('d-none', !['dean', 'program_chair'].includes($('approveRole').value));
                approveModal.show();
                return;
            }

            $('declineText').textContent = `${name} won't get an account. They can request again later.`;
            $('declineForm').reset();
            declineModal.show();
        });

        $('approveForm').addEventListener('submit', async e => {
            e.preventDefault();
            const submit = e.submitter;
            submit.disabled = true;
            try {
                const res = await fetch(`/access-requests/${current.dataset.request}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        role: $('approveRole').value,
                        department: $('approveDept').value,
                        assigned_college: $('approveCollege').value || null,
                    }),
                });
                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    const message = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Something went wrong.');
                    $('approveError').textContent = message;
                    $('approveError').classList.remove('d-none');
                    return;
                }
                location.reload();
            } catch {
                $('approveError').textContent = 'Could not reach the server. Please try again.';
                $('approveError').classList.remove('d-none');
            } finally {
                submit.disabled = false;
            }
        });

        $('declineForm').addEventListener('submit', async e => {
            e.preventDefault();
            const submit = e.submitter;
            submit.disabled = true;
            try {
                const res = await fetch(`/access-requests/${current.dataset.request}/decline`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ note: $('declineNote').value.trim() || null }),
                });
                if (!res.ok) throw new Error();
                location.reload();
            } catch {
                declineModal.hide();
                toast('Something went wrong declining that request. Please try again.');
            } finally {
                submit.disabled = false;
            }
        });
    });
</script>
