{{--
    The two ways to build a report from the Analytics tabs:
      1. "Generate from this view" (button next to the tabs): the current filters + the sections you tick.
      2. "Add to report" on each chart: the picked charts collect in the tray at the bottom, then "Build report".
    Needs $recipients (the "Send to" choices). The script is in analytics/partials/reports-script.blade.php.
--}}

@php($viewSections = ['Key figures', 'Applicant trends over time', 'Top programs by applicants', 'Conversion funnel', 'Applicants by location', 'AI-generated insights', 'Projected enrollment'])

{{-- 1. Generate from this view --}}
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-labelledby="viewReportTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="viewReportTitle">Generate from this view</h3>
                    <p class="small text-white-50 mb-0">Turn what you are looking at into a report</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @include('analytics.partials.builder-scope', ['prefix' => 'view'])
                <div class="mb-3">
                    <label for="viewTitle" class="form-label small fw-bold text-uppercase text-body-secondary">Report title</label>
                    <input type="text" class="form-control" id="viewTitle">
                </div>
                <div class="small fw-bold text-uppercase text-body-secondary mb-2">Include</div>
                <div class="row row-cols-1 row-cols-sm-2 g-1 mb-1">
                    @foreach ($viewSections as $section)
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="viewSection" value="{{ $section }}" id="viewSection{{ $loop->index }}" checked>
                                <label class="form-check-label small" for="viewSection{{ $loop->index }}">{{ $section }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="small text-danger mb-2 d-none" id="viewMessage" role="alert">Pick at least one section to include.</div>
                <div class="mt-3">@include('analytics.partials.builder-send', ['prefix' => 'view'])</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold flex-fill" id="viewGenerate"><i class="bi bi-box-arrow-up-right me-1"></i> Generate &amp; Download</button>
            </div>
        </div>
    </div>
</div>

{{-- 2. The tray: appears at the bottom once a chart is added --}}
<div class="report-tray text-bg-dark rounded-4 shadow-lg d-flex align-items-center justify-content-between gap-2 gap-sm-3 p-2 ps-3" id="reportTray" role="region" aria-label="Charts in your report">
    <span class="badge bg-primary fs-6" id="trayCount">0</span>
    {{-- The names are hidden on phones: there is not enough room next to the buttons --}}
    <div class="flex-grow-1 overflow-hidden d-none d-sm-block">
        <div class="small fw-bold text-nowrap">Charts in your report</div>
        <div class="small text-white-50 text-truncate" id="trayNames">—</div>
    </div>
    {{-- On phones Clear is just an ✕ so everything fits beside the AI button --}}
    <button type="button" class="btn btn-sm btn-outline-light ms-auto" id="trayClear" aria-label="Clear the report"><i class="bi bi-x-lg d-sm-none"></i><span class="d-none d-sm-inline">Clear</span></button>
    <button type="button" class="btn btn-sm btn-primary fw-semibold text-nowrap" data-bs-toggle="modal" data-bs-target="#trayReportModal"><i class="bi bi-file-earmark-plus me-1"></i> Build report</button>
</div>

<div class="modal fade" id="trayReportModal" tabindex="-1" aria-labelledby="trayReportTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 overflow-hidden">
            <div class="modal-header bg-iaris text-white border-0 p-4">
                <div>
                    <h3 class="modal-title fs-5 fw-bold" id="trayReportTitle">Build a custom report</h3>
                    <p class="small text-white-50 mb-0">From the charts you added. Reorder or remove them below.</p>
                </div>
                <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                @include('analytics.partials.builder-scope', ['prefix' => 'tray'])
                <div class="mb-3">
                    <label for="trayTitle" class="form-label small fw-bold text-uppercase text-body-secondary">Report title</label>
                    <input type="text" class="form-control" id="trayTitle">
                </div>
                <div class="small fw-bold text-uppercase text-body-secondary mb-2">Charts in this report</div>
                <ol class="list-group mb-2" id="trayList"></ol>
                <div class="small text-body-secondary mb-2 d-none" id="trayEmpty">No charts left. Close this and use "Add to report" on a chart.</div>
                <div class="mt-3">@include('analytics.partials.builder-send', ['prefix' => 'tray'])</div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary fw-semibold flex-fill" id="trayGenerate"><i class="bi bi-box-arrow-up-right me-1"></i> Generate &amp; Download</button>
            </div>
        </div>
    </div>
</div>
