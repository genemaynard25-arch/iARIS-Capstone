{{--
    Confirm / Flag / Edit pop-ups for a scholar, used on the LAMP Dashboard and Scholars pages.
    The script in scholar-actions-script fills them in. Elements with data-info="x" show the scholar's x.
--}}

{{-- Confirm match --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="confirmForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="confirmModalTitle">Confirm Scholar Match</h3>
                    <p class="small text-white-50 mb-0">IATO Admin will be notified of your confirmation</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <dl class="row small mb-3">
                    <dt class="col-5 text-body-secondary">Applicant</dt><dd class="col-7 fw-semibold" data-info="name"></dd>
                    <dt class="col-5 text-body-secondary">App Number</dt><dd class="col-7 fw-semibold" data-info="id"></dd>
                    <dt class="col-5 text-body-secondary">Scholarship (IATO)</dt><dd class="col-7 fw-semibold" data-info="type"></dd>
                    <dt class="col-5 text-body-secondary">LAMP Record</dt><dd class="col-7 fw-semibold mb-0" data-info="lamp_record"></dd>
                </dl>
                <label for="confirmRemarks" class="form-label small fw-bold text-uppercase text-body-secondary">Remarks (optional)</label>
                <textarea class="form-control" id="confirmRemarks" rows="3" placeholder="Any notes for the IATO Admin…"></textarea>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-check-circle me-1"></i> Confirm Match</button>
            </div>
        </form>
    </div>
</div>

{{-- Flag a discrepancy --}}
<div class="modal fade" id="flagModal" tabindex="-1" aria-labelledby="flagModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="flagForm" novalidate>
            <div class="modal-header bg-danger text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="flagModalTitle">Flag Discrepancy</h3>
                    <p class="small text-white-50 mb-0">IATO Admin will be notified to review it</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <dl class="row small mb-3">
                    <dt class="col-5 text-body-secondary">Applicant</dt><dd class="col-7 fw-semibold" data-info="name"></dd>
                    <dt class="col-5 text-body-secondary">App Number</dt><dd class="col-7 fw-semibold mb-0" data-info="id"></dd>
                </dl>
                <div class="mb-3">
                    <label for="flagIssue" class="form-label small fw-bold text-uppercase text-body-secondary">Issue type</label>
                    <select class="form-select" id="flagIssue" required>
                        <option value="">Choose an issue…</option>
                        <option>Scholarship type mismatch</option>
                        <option>Scholar not found in LAMP records</option>
                        <option>Scholarship already cancelled</option>
                        <option>Duplicate entry</option>
                        <option>Other</option>
                    </select>
                    <div class="invalid-feedback">Choose the kind of issue.</div>
                </div>
                <label for="flagDetails" class="form-label small fw-bold text-uppercase text-body-secondary">Details</label>
                <textarea class="form-control" id="flagDetails" rows="3" placeholder="Describe the discrepancy…" required></textarea>
                <div class="invalid-feedback">Add a few details for the IATO Admin.</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger fw-semibold flex-fill"><i class="bi bi-flag me-1"></i> Send Flag to IATO</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit the scholarship --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 rounded-4 overflow-hidden" id="editForm" novalidate>
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="editModalTitle">Edit Scholar Record</h3>
                    <p class="small text-white-50 mb-0">IATO Admin will be notified of the change</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="editName" class="form-label small fw-bold text-uppercase text-body-secondary">Applicant</label>
                    <input type="text" class="form-control bg-body-tertiary" id="editName" readonly>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="editType" class="form-label small fw-bold text-uppercase text-body-secondary">Scholarship type</label>
                        <select class="form-select" id="editType">
                            @foreach ($types as $type)
                                <option>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label for="editStatus" class="form-label small fw-bold text-uppercase text-body-secondary">Scholarship status</label>
                        <select class="form-select" id="editStatus">
                            @foreach ($scholarshipTones as $status => $tone)
                                <option>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label for="editRemarks" class="form-label small fw-bold text-uppercase text-body-secondary">Reason for the change</label>
                <textarea class="form-control" id="editRemarks" rows="3" placeholder="e.g. Scholarship changed after re-evaluation" required></textarea>
                <div class="invalid-feedback">Say why the record changed. IATO Admin will see it.</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-floppy me-1"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast align-items-center border-0 text-bg-dark" id="lampToast" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="lampToastText"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
