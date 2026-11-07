{{--
    Access Requests tab of User Accounts. FRONT END ONLY: the requests are placeholders (App\Support\AccessRequests)
    and Approve / Decline change this page until reload.
--}}
@php
    $requestInitials = fn ($name) => collect(explode(', ', $name))->map(fn ($part) => mb_substr($part, 0, 1))->reverse()->implode('');
    $when = fn ($at) => \Illuminate\Support\Carbon::parse($at)->format('M j, Y · g:i A');
@endphp

<div class="alert alert-light border small d-flex gap-2">
    <i class="bi bi-info-circle mt-1"></i>
    <div>People request access from the login page and choose their position and department. Approving creates the account with that role, limited to that department, and emails them an invitation. They set up MFA on first login.
        <strong>Front end only for now:</strong> these are sample requests and nothing is saved.</div>
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
                        <button type="button" class="btn btn-primary fw-semibold flex-fill" data-decide="approve"><i class="bi bi-check-lg me-1"></i> Approve</button>
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
        const ME = @json(auth()->user()->name);
        const $ = id => document.getElementById(id);
        const declineModal = bootstrap.Modal.getOrCreateInstance($('declineModal'));
        let declining = null;

        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const stamp = () => new Date().toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }).replace(/, (\d+:)/, ' · $1');

        // Move a card into "Decided requests" and update the counts
        function decide(card, approved, note = '') {
            const get = sel => card.querySelector(sel).textContent.trim();
            const email = card.querySelector('[data-name]').nextElementSibling.textContent.trim();
            $('decidedRows').insertAdjacentHTML('afterbegin', `<tr class="table-warning">
                <td><div class="fw-semibold">${esc(get('[data-name]'))}</div><div class="text-body-secondary">${esc(email)}</div></td>
                <td>${esc(get('[data-position]'))} · ${esc(get('[data-department]'))}</td>
                <td>${approved ? '<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><i class="bi bi-check-lg me-1"></i>Approved</span>'
                    : `<span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"><i class="bi bi-x-lg me-1"></i>Declined</span>${note ? `<div class="text-body-secondary mt-1">${esc(note)}</div>` : ''}`}</td>
                <td>${esc(ME)}</td><td class="text-nowrap text-body-secondary">${stamp()}</td></tr>`);
            card.remove();
            const left = document.querySelectorAll('#requestCards [data-request]').length;
            $('requestsCount').textContent = left;
            $('requestsCount').classList.toggle('d-none', !left);
            $('noRequests').classList.toggle('d-none', left > 0);
            toast(approved
                ? `${get('[data-name]')} approved on this page only. Creating the account and sending the invitation need the backend.`
                : `${get('[data-name]')} declined on this page only. Not saved yet: needs the backend.`);
        }

        $('requestCards').addEventListener('click', e => {
            const btn = e.target.closest('[data-decide]');
            if (!btn) return;
            const card = btn.closest('[data-request]');
            if (btn.dataset.decide === 'approve') return decide(card, true);
            declining = card;
            $('declineText').textContent = `${card.querySelector('[data-name]').textContent.trim()} won't get an account. They can request again later.`;
            $('declineForm').reset();
            declineModal.show();
        });

        $('declineForm').addEventListener('submit', e => {
            e.preventDefault();
            declineModal.hide();
            decide(declining, false, $('declineNote').value.trim());
        });
    });
</script>
