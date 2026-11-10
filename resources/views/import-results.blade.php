@extends('layouts.app')

@section('title', 'iARIS — Import Results')
@section('page-title', 'Import Results')
@section('page-subtitle', 'Batch #' . $batch->id . ' · ' . $batch->original_filename)

@php($levels = ['is' => 'Integrated School', 'college' => 'College', 'graduate_school' => 'Graduate School', 'eteeap' => 'ETEEAP'])

@section('content')
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @foreach($otherImportYearWarnings as $warning)
        @include('partials.import-year-warning', ['warning' => $warning])
    @endforeach
    <div class="card card-body mb-4">
        <h2 class="h5">School year for this import</h2>
        <p>Assigned school year: <strong>{{ implode(', ', $assignedYears) ?: 'No saved applicants' }}</strong>
        @if($declaredYear) · Filename declares <strong>{{ $declaredYear }}</strong>@endif</p>
        @if(auth()->user()->isAdmin() && count($assignedYears) === 1 && $batch->status === 'completed' && $duplicateSummary['count'] > 0)
            <form method="POST" action="{{ route('imports.school-year.update', $batch) }}" id="correctSchoolYearForm" class="d-flex flex-wrap align-items-end gap-3">
                @csrf
                <input type="hidden" name="from_school_year" value="{{ $assignedYears[0] }}">
                <input type="hidden" name="expected_count" value="{{ $applicants->count() }}">
                <div><label class="form-label" for="correctSchoolYearChoice">Correct school year</label>
                @include('partials.school-year-picker', ['id' => 'correctSchoolYear', 'label' => 'Correct school year', 'value' => old('school_year', $declaredYear ?? $assignedYears[0])])</div>
                <button class="btn btn-warning" type="submit">Correct school year for {{ number_format($applicants->count()) }} applicants</button>
            </form>
            <p class="small mt-2 mb-1" id="schoolYearCorrectionStatus" role="status" aria-live="polite"></p>
            <p class="small text-body-secondary mt-2 mb-0">Moves the existing batch to the selected school year. Applicant IDs, reference numbers, scholarship decisions and payments are preserved. The correction is audited.</p>
        @endif
    </div>
    @if($duplicateSummary['count'] === 0 && $applicants->isNotEmpty())
        <div class="alert alert-info">This batch has no active records. Its archived records are retained and excluded from Analytics.</div>
    @endif
    @if(auth()->user()->isAdmin() && $duplicateSummary['count'] > 0 && $duplicateSummary['matching'] === $duplicateSummary['count'] && count($duplicateSummary['assigned']) === 1)
        <section class="card card-body border-warning mb-4" id="duplicateCopyReview">
            <h2 class="h5">Review the extra copy — batch #{{ $batch->id }}</h2>
            <p>All {{ number_format($duplicateSummary['count']) }} active application numbers in this batch also exist in SY {{ $duplicateSummary['declared'] }}@if($duplicateSummary['target_batches']) (batch {{ implode(', ', array_map(fn ($id) => '#'.$id, $duplicateSummary['target_batches'])) }})@endif.</p>
            <p>Verify matching applicant details and imported payment flags, then archive this extra copy. Both sets of records and all payment records are retained. Differing details, recorded payment amounts, or scholarship/request history require manual review.</p>
            <form method="POST" action="{{ route('imports.archive-duplicates', $batch) }}" id="archiveDuplicateCopyForm">@csrf
                <input type="hidden" name="from_school_year" value="{{ $duplicateSummary['assigned'][0] }}">
                <input type="hidden" name="expected_count" value="{{ $duplicateSummary['count'] }}">
                <button class="btn btn-warning" type="submit">Verify and archive {{ number_format($duplicateSummary['count']) }} duplicate records</button>
            </form>
        </section>
    @endif
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 d-flex flex-wrap align-items-center gap-3">
            <div class="icon-circle rounded-circle bg-primary text-white fs-5 d-flex align-items-center justify-content-center">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">Import complete</div>
                <div class="small text-body-secondary">
                    {{ $applicants->count() }} {{ Str::plural('applicant', $applicants->count()) }} imported from "{{ $batch->original_filename }}".
                    @if ($batch->error_count)
                        <span class="text-warning-emphasis fw-semibold">{{ $batch->error_count }} {{ Str::plural('row', $batch->error_count) }} skipped</span> - see below.
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ url('/import') }}" class="btn btn-outline-primary"><i class="bi bi-upload me-1"></i> Import another file</a>
                <a href="{{ url('/home') }}" class="btn btn-primary">Go to dashboard</a>
            </div>
        </div>
    </div>

    @if ($batch->error_count)
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-warning">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-1"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Rows that weren't imported ({{ $batch->error_count }})</h2>
                <p class="small text-body-secondary mb-3">These rows had a problem the file itself needs fixed - nothing was guessed or defaulted on your behalf. Fix them in the source file and re-import.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead>
                            <tr class="text-uppercase text-nowrap">
                                <th class="text-body-secondary fw-bold">Row</th>
                                <th class="text-body-secondary fw-bold">Name</th>
                                <th class="text-body-secondary fw-bold">Problem</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batch->errors as $error)
                                <tr>
                                    <td>{{ $error['row'] }}</td>
                                    <td>{{ $error['name'] ?: '—' }}</td>
                                    <td>{{ $error['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h2 class="fs-6 fw-bold mb-3">Imported Applicants</h2>

            @if ($applicants->isEmpty())
                <p class="small text-body-secondary text-center py-4 mb-0">No applicants were imported from this file.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead>
                            <tr class="text-uppercase text-nowrap">
                                <th class="text-body-secondary fw-bold">Reference #</th>
                                <th class="text-body-secondary fw-bold">Name</th>
                                <th class="text-body-secondary fw-bold">Gender</th>
                                <th class="text-body-secondary fw-bold">Level</th>
                                <th class="text-body-secondary fw-bold">School Year</th>
                                <th class="text-body-secondary fw-bold">Program / Track</th>
                                <th class="text-body-secondary fw-bold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($applicants as $applicant)
                                <tr>
                                    <td class="text-nowrap font-monospace">{{ $applicant->reference_number }}</td>
                                    <td class="fw-semibold">{{ $applicant->last_name }}, {{ $applicant->first_name }}</td>
                                    <td class="text-capitalize">{{ $applicant->gender }}</td>
                                    <td>
                                        {{ $levels[$applicant->level] ?? Str::headline($applicant->level) }}
                                        @if ($applicant->sub_level)
                                            <span class="text-body-secondary">· {{ strtoupper($applicant->sub_level) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $applicant->school_year }}</td>
                                    <td>{{ $applicant->program_or_track ?: '—' }}</td>
                                    <td><span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ Str::headline($applicant->application_status) }}</span>@if($applicant->archived_at) <span class="badge text-bg-secondary">Archived</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.getElementById('archiveDuplicateCopyForm')?.addEventListener('submit', event => {
        event.currentTarget.querySelector('button[type="submit"]').disabled = true;
    });
    const correctionForm = document.getElementById('correctSchoolYearForm');
    if (correctionForm) {
        const targetYear = correctionForm.elements.school_year;
        const sourceYear = correctionForm.elements.from_school_year.value;
        const correctionButton = correctionForm.querySelector('button[type="submit"]');
        const correctionStatus = document.getElementById('schoolYearCorrectionStatus');
        const applicantCount = Number(correctionForm.elements.expected_count.value).toLocaleString('en-US');
        function updateCorrectionStatus() {
            const match = /^(\d{4})-(\d{4})$/.exec(targetYear.value);
            const valid = match && Number(match[2]) === Number(match[1]) + 1;
            const unchanged = targetYear.value === sourceYear;
            correctionButton.disabled = !valid || unchanged;
            correctionStatus.classList.toggle('text-danger', !valid);
            correctionStatus.textContent = !valid
                ? 'Enter a valid consecutive school year, for example 2025-2026.'
                : unchanged
                    ? `This batch is already assigned to SY ${sourceYear}. No change is needed.`
                    : `Move ${applicantCount} applicants from SY ${sourceYear} to SY ${targetYear.value}.`;
        }
        correctionForm.addEventListener('input', updateCorrectionStatus);
        correctionForm.addEventListener('change', updateCorrectionStatus);
        correctionForm.addEventListener('submit', event => {
            updateCorrectionStatus();
            if (correctionButton.disabled) event.preventDefault();
            else correctionButton.disabled = true;
        });
        updateCorrectionStatus();
    }
</script>
@endsection
