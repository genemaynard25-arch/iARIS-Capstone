{{--
    "Requests from IATO" on the LAMP Scholars page, plus the "Correct the IATO record" pop-up.
    The list is drawn by iato-requests-script. Front end only: answers stay until reload.
--}}
<div class="card border-0 shadow-sm rounded-4 mb-4" id="requestsCard">
    <div class="card-header bg-white border-0 rounded-top-4 d-flex align-items-start justify-content-between gap-3 px-4 pt-4 pb-2">
        <div>
            <h2 class="fs-6 fw-bold mb-1"><i class="bi bi-inbox me-1 text-primary"></i> Requests from IATO</h2>
            <p class="small text-body-secondary mb-0">IATO found scholars whose records don't match yours. Confirm, correct, or reply.</p>
        </div>
        <span class="badge rounded-pill text-bg-warning" id="requestsCount"></span>
    </div>
    <div class="card-body px-4 pt-2 pb-4">
        <div class="vstack gap-3" id="requestsList"></div>
        <div class="text-center text-body-secondary py-4 d-none" id="requestsEmpty">
            <i class="bi bi-check-circle fs-3 d-block mb-2 text-primary"></i> No requests from IATO right now.
        </div>
        <div class="small text-primary-emphasis bg-primary-subtle rounded-3 px-3 py-2 mt-3 d-none" id="requestsResolved"></div>
    </div>
</div>

{{-- One request, copied by the script --}}
<template id="requestTemplate">
    <div class="border rounded-4 p-3">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <div class="fw-bold" data-cell="name"></div>
                <div class="small text-body-secondary" data-cell="sub"></div>
            </div>
            <span data-cell="chip"></span>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="bg-body-tertiary rounded-3 px-3 py-2 h-100">
                    <div class="small fw-bold text-uppercase text-body-secondary">IATO record</div>
                    <div class="fw-bold" data-cell="iato"></div>
                </div>
            </div>
            <div class="col-6">
                <div class="bg-body-tertiary rounded-3 px-3 py-2 h-100">
                    <div class="small fw-bold text-uppercase text-body-secondary">Your record</div>
                    <div class="fw-bold" data-cell="lamp"></div>
                </div>
            </div>
        </div>
        <div class="vstack gap-2 mb-3" data-cell="thread"></div>
        <textarea class="form-control mb-2" rows="2" placeholder="Reply to IATO…" aria-label="Reply to IATO" data-cell="reply"></textarea>
        <div class="invalid-feedback mb-2">Type a reply first.</div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-primary fw-semibold" data-request-action="agree"><i class="bi bi-check-lg me-1"></i> IATO is right, update our record</button>
            <button type="button" class="btn btn-sm btn-light border fw-semibold" data-request-action="correct"><i class="bi bi-pencil me-1"></i> Correct the IATO record</button>
            <button type="button" class="btn btn-sm btn-light border fw-semibold" data-request-action="reply"><i class="bi bi-reply me-1"></i> Send reply</button>
        </div>
    </div>
</template>

{{-- Correct the IATO record --}}
<div class="modal fade" id="correctModal" tabindex="-1" aria-labelledby="correctModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="correctForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="correctModalTitle">Correct the IATO record</h3>
                    <p class="small text-white-50 mb-0" id="correctSubtitle"></p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <label for="correctType" class="form-label small fw-bold text-uppercase text-body-secondary">Correct scholarship type</label>
                <select class="form-select mb-1" id="correctType" required>
                    <option value="">Choose one</option>
                    @foreach ($correctTypes as $type)
                        <option>{{ $type }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback mb-2">Choose the correct type.</div>
                <label for="correctNote" class="form-label small fw-bold text-uppercase text-body-secondary mt-3">Note for IATO <span class="fw-normal text-lowercase">(optional)</span></label>
                <textarea class="form-control" id="correctNote" rows="3" placeholder="e.g. Moved to the DLSL Scholarship this semester."></textarea>
                <div class="small text-body-secondary mt-3">
                    <i class="bi bi-info-circle me-1"></i> The correction goes to the IATO Review Queue. IATO sees it after the Super Admin approves.
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Send correction</button>
            </div>
        </form>
    </div>
</div>
