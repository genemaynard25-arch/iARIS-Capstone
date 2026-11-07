{{-- Footer of a preferences card: "Unsaved changes" + Save (enabled once something changes) --}}
<div class="card-footer bg-transparent border-top d-flex flex-wrap align-items-center justify-content-end gap-2 p-3">
    <span class="small text-body-secondary me-auto"><i class="bi bi-info-circle me-1"></i>Saved in this browser for now.</span>
    <span class="small fw-semibold text-warning-emphasis d-none" data-dirty><i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>Unsaved changes</span>
    <button type="submit" class="btn btn-primary fw-semibold" data-save disabled><i class="bi bi-floppy me-1"></i> Save changes</button>
</div>
