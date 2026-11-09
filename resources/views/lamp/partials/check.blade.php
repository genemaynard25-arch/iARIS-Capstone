{{--
    The applicant drawer shared by the LAMP Dashboard and Scholars pages (opened by LAMP.open(applicant), see check-script).
    Also brings in the toast() and Download menu the TOIC pages use (toic/partials/kit).
--}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="checkDrawer" aria-labelledby="checkName" style="--bs-offcanvas-width: 520px;">
    <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
        <div class="rounded-4 bg-white bg-opacity-25 border border-white border-opacity-25 fs-4 fw-bold font-brand d-flex align-items-center justify-content-center flex-shrink-0"
             style="width: 64px; height: 64px;" id="checkAvatar"></div>
        <div class="flex-grow-1">
            <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="checkName"></h2>
            <div class="small text-white-50" id="checkSub"></div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-4" id="checkBody"></div>
    <div class="border-top p-3 d-flex flex-wrap justify-content-end gap-2" id="checkButtons"></div>
</div>

@include('toic.partials.kit')
