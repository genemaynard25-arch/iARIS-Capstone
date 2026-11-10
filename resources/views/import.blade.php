@extends('layouts.app')

@section('title', 'iARIS — Import Data')
@section('page-title', 'Import Data')
@section('page-subtitle', 'Upload the admissions portal export to batch-import applicant records into iARIS')

@php
    // IATO Staff (not a Temporary Admin): imports go to the Review Queue (front end only for now, see the script)
    $staffReview = auth()->user()->role === 'iato_staff' && ! auth()->user()->isAdmin();
    $steps = ['Upload applicant list', 'Check rows', $staffReview ? 'Submit for Review' : 'Import'];
    $currentSchoolYear = now()->month >= 6 ? now()->year . '-' . (now()->year + 1) : (now()->year - 1) . '-' . now()->year;
@endphp

@include('partials.admin-workspace', ['workspacePage' => 'import-page', 'workspaceGroup' => 'System', 'workspaceLabel' => 'Import Data'])

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="admin-import-grid row g-4">
    <div class="col-12 col-xl-8">
    <form method="POST" action="{{ url('/import') }}" enctype="multipart/form-data" id="importForm">
        @csrf

        {{-- 1. Upload --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Upload the applicant list</h2>
                        <p class="small text-body-secondary mb-0">Upload the export straight from the admissions portal — one file for every level. Each row's level (Integrated School, College, Graduate Programs, ETEEAP) is read from the file's own section headers and Year Level column.</p>
                    </div>
                </div>

                {{-- Steps: the script moves the highlight along --}}
                {{-- Shown after a staff submission lands back here (session('reviewSubmitted'), set by ImportController::stageImportForReview()) --}}
                @if (session('reviewSubmitted'))
                    <div class="alert alert-success d-flex gap-3 rounded-4 mb-4" id="reviewBanner" role="status">
                        <i class="bi bi-check-circle-fill fs-4" aria-hidden="true"></i>
                        <div class="small">
                            <b>{{ session('reviewSubmitted.count') }} record{{ session('reviewSubmitted.count') === 1 ? '' : 's' }} sent to the Review Queue as {{ session('reviewSubmitted.rq_number') }}.</b>
                            Other users see them after the Super Admin approves.
                            <a href="{{ url('/submissions') }}" class="fw-bold alert-link">Track it in My Submissions →</a>
                        </div>
                    </div>
                @endif
                <ol class="list-unstyled d-flex align-items-center gap-2 mb-4" id="importSteps" aria-label="Import steps">
                    @foreach ($steps as $i => $label)
                        <li class="d-flex align-items-center gap-2 {{ $loop->last ? '' : 'flex-fill' }}" data-step="{{ $i + 1 }}">
                            <span class="rounded-circle border border-2 fw-bold small d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px;" data-dot>{{ $i + 1 }}</span>
                            <span class="small fw-bold text-nowrap d-none d-md-inline" data-label>{{ $label }}</span>
                            @unless ($loop->last)<span class="flex-fill border-top border-2 mx-1" data-line></span>@endunless
                        </li>
                    @endforeach
                </ol>

                {{-- The whole box is a <label>, so clicking anywhere opens the file picker --}}
                <label for="file" id="dropzone" class="d-block text-center rounded-4 border border-2 border-primary-subtle p-5" style="--bs-border-style: dashed; cursor: pointer;">
                    <span class="icon-circle rounded-circle bg-primary-subtle text-primary fs-4 d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                    </span>
                    <span class="d-block fw-bold mb-1">Drag and drop your file here</span>
                    <span class="d-block small text-body-secondary mb-3">Supported formats: .xlsx, .csv</span>
                    <span class="btn btn-primary fw-semibold px-4">Browse Files</span>
                </label>
                <input type="file" name="file" id="file" class="d-none" accept=".xlsx,.csv" required>

                {{-- Selected file --}}
                <div class="d-none align-items-center gap-3 rounded-3 border bg-body-tertiary p-3 mt-3" id="fileRow">
                    <span class="icon-circle rounded-3 bg-primary-subtle text-primary fs-5 d-flex align-items-center justify-content-center">
                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                    </span>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-semibold text-truncate" id="fileName"></div>
                        <div class="small text-body-secondary" id="fileMeta"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger fw-semibold text-decoration-none" id="removeFile">Remove</button>
                </div>

                <div class="alert alert-danger small mt-3 mb-0 d-none" id="readError" role="alert"></div>

                <div class="mt-4">
                    <label for="school_year" class="form-label small fw-bold text-uppercase text-body-secondary">School Year — which AY is this batch for?</label>
                    <input type="text" class="form-control" id="school_year" name="school_year" value="{{ old('school_year', $currentSchoolYear) }}" pattern="\d{4}-\d{4}" placeholder="2026-2027" required style="max-width: 200px;">
                    <div class="form-text">The portal export doesn't include a school year column, so every applicant in this file is recorded under the year you enter here.</div>
                </div>
            </div>
        </div>

        {{-- 2. Preview --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 d-none" id="previewCard">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">File Preview</h2>
                        <p class="small text-body-secondary mb-0">A raw look at your file's first rows. The real column-by-column reading (Yes/No fields, gender, level, section headers) happens on the server when you import.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="rowCounts"></div>
                </div>
                <div class="table-responsive border rounded-3" style="max-height: 420px;">
                    <table class="table table-sm table-hover align-middle mb-0 small text-nowrap" id="previewTable"></table>
                </div>
                <div class="small text-body-secondary mt-2" id="previewNote"></div>
                <div class="alert alert-warning small mt-3 mb-0 d-none" id="headerWarning" role="alert"></div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-light border px-4" id="cancelImport">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4" id="importButton">
                        <i class="bi bi-upload me-1" aria-hidden="true"></i> Import Data
                    </button>
                </div>
            </div>
        </div>
    </form>
    </div>

    <div class="col-12 col-xl-4 d-flex flex-column gap-4">
        {{-- Template: a sample file in the exact shape the real portal exports --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-1">Sample File</h2>
                <p class="small text-body-secondary mb-3">One file, every level — the portal's own export layout, with a section header per department/grade/program</p>
                <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-0 border-0" id="downloadSample">
                    <span class="icon-circle rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i></span>
                    <span class="flex-grow-1 text-start">
                        <span class="d-block small fw-bold">Download Sample Template</span>
                        <span class="d-block small text-body-secondary">Shows the expected columns and a section header example</span>
                    </span>
                    <i class="bi bi-download text-body-secondary" aria-hidden="true"></i>
                </button>
            </div>
        </div>

    </div>
    </div>

    <section class="admin-import-history mt-4" aria-label="Import history">
        {{-- Import History: real uploads (import_batches) --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-1">Import History</h2>
                <p class="small text-body-secondary mb-3">Recent file imports</p>
                @forelse ($history as $batch)
                    @php
                        [$tone, $icon, $label] = match ($batch->status) {
                            'failed' => ['danger', 'bi-x-circle', 'Failed'],
                            'processing' => ['warning', 'bi-hourglass-split', 'Processing'],
                            default => ['primary', 'bi-check-circle', number_format($batch->saved_count) . ' imported'],
                        };
                    @endphp
                    <a href="{{ url('/import/' . $batch->id) }}" class="d-flex gap-3 py-3 text-decoration-none text-body {{ $loop->first ? '' : 'border-top' }}">
                        <span class="icon-circle rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi {{ $icon }}" aria-hidden="true"></i></span>
                        <span class="overflow-hidden">
                            <span class="d-block small fw-bold text-truncate">{{ $batch->original_filename }}</span>
                            <span class="d-block small text-body-secondary">By {{ $batch->uploader ?? 'Unknown user' }} · {{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('M j, Y · g:i A') }}</span>
                            <span class="badge rounded-pill bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis mt-1">{{ $label }}</span>
                            @if ($batch->error_count)
                                <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis mt-1">{{ $batch->error_count }} skipped</span>
                            @endif
                        </span>
                    </a>
                @empty
                    <p class="small text-body-secondary mb-0">No files imported yet.</p>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    {{-- SheetJS reads the file in the browser for a raw preview only; the real import happens on the server --}}
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        // The portal's own column order. App\Imports\ApplicantsImport reads these by position,
        // not by header name, since section header rows share the sheet with data rows.
        const EXPECTED_HEADERS = [
            '#', 'APP. NO.', 'APP. DATE', 'FULL NAME', 'EMAIL', 'MOBILE NO.', 'ADDRESS',
            'PERMIT NO.', 'GWA', 'AGE', 'SEX', 'PROGRAM CODE', 'STRAND / MAJOR', 'YEAR LEVEL',
            'TYPE', 'PAID EXAM', 'PAID RESERVATION', 'APPLYING FOR SCHOLARSHIP', 'SSEN',
            'TEST RESULT', 'WITH NOA', 'STATUS', 'SCHOOL LAST ATTENDED', 'SCHOOL ADDRESS',
            'SCHOOL CLASSIFICATION', 'TESTING DATE', 'TEST STATUS', 'LEARNING MODALITIES', 'PWD', 'NATIONALITY',
        ];
        const PREVIEW_ROWS = 15;

        const form = document.getElementById('importForm');
        const input = document.getElementById('file');
        const dropzone = document.getElementById('dropzone');
        const fileRow = document.getElementById('fileRow');
        const readError = document.getElementById('readError');
        const previewCard = document.getElementById('previewCard');
        const importButton = document.getElementById('importButton');

        const formatSize = bytes => bytes < 1024 * 1024 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1024 / 1024).toFixed(1) + ' MB';
        const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;
        let rowCount = 0;   // data rows in the chosen file (for the staff "Submit for review" button)
        const show = (el, display = 'block') => { el.classList.remove('d-none'); el.classList.add('d-' + display); };
        const hide = el => { el.classList.add('d-none'); el.classList.remove('d-flex', 'd-block'); };

        // Drag and drop onto the box
        ['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, e => {
            e.preventDefault();
            dropzone.classList.add('bg-primary-subtle');
        }));
        ['dragleave', 'drop'].forEach(evt => dropzone.addEventListener(evt, () => dropzone.classList.remove('bg-primary-subtle')));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            if (!e.dataTransfer.files.length) return;
            input.files = e.dataTransfer.files; // put the dropped file into the real form input
            handleFile(input.files[0]);
        });

        input.addEventListener('change', () => input.files.length ? handleFile(input.files[0]) : reset());
        document.getElementById('removeFile').addEventListener('click', reset);
        document.getElementById('cancelImport').addEventListener('click', reset);

        function reset() {
            const schoolYear = document.getElementById('school_year').value;
            form.reset();
            document.getElementById('school_year').value = schoolYear;
            [fileRow, readError, previewCard].forEach(hide);
            const banner = document.getElementById('reviewBanner');   // IATO Staff only
            if (banner) hide(banner);
            show(dropzone);
            setStep(1);
        }

        // Step indicator: steps before n are done (check mark), n is current
        function setStep(n) {
            document.querySelectorAll('#importSteps [data-step]').forEach(li => {
                const i = Number(li.dataset.step), dot = li.querySelector('[data-dot]');
                dot.className = 'rounded-circle border border-2 fw-bold small d-flex align-items-center justify-content-center flex-shrink-0 '
                    + (i <= n ? 'bg-primary border-primary text-white' : 'text-body-secondary');
                dot.innerHTML = i < n ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : i;
                li.querySelector('[data-label]').className = 'small fw-bold text-nowrap d-none d-md-inline ' + (i <= n ? 'text-primary' : 'text-body-secondary');
                li.querySelector('[data-line]')?.classList.toggle('border-primary', i < n);
                if (i === n) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
            });
        }
        setStep(1);

        function handleFile(file) {
            hide(readError);
            if (!/\.(xlsx|csv)$/i.test(file.name)) {
                return fail(`"${file.name}" isn't an .xlsx or .csv file.`);
            }

            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileMeta').textContent = formatSize(file.size) + ' · reading…';
            hide(dropzone);
            show(fileRow, 'flex');

            const reader = new FileReader();
            reader.onload = e => {
                try {
                    const workbook = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false });
                    render(file, rows);
                } catch (err) {
                    fail(`Couldn't read "${file.name}". Make sure it's a valid Excel or CSV file.`);
                }
            };
            reader.readAsArrayBuffer(file);
        }

        function fail(message) {
            reset();
            readError.textContent = message;
            show(readError);
        }

        function render(file, rows) {
            // The header is usually row 1, but some exports have a sheet-title row above it
            // (e.g. "list-of-applicants (96)") - find the real header wherever it is, the
            // same way the server does, instead of assuming it's always the first row.
            let headerIndex = rows.findIndex(r => String(r[0] ?? '').trim() === '#');
            if (headerIndex === -1) headerIndex = 0; // couldn't find it - fall back to row 1

            const headers = (rows[headerIndex] || []).map(h => String(h).trim());
            const dataRows = rows.slice(headerIndex + 1).filter(r => r.some(cell => String(cell).trim() !== ''));
            rowCount = dataRows.filter(r => String(r[3] ?? '').trim() !== '').length; // col 3 = FULL NAME

            document.getElementById('fileMeta').textContent =
                `${formatSize(file.size)} · ${plural(dataRows.length, 'row')} · ${plural(headers.length, 'column')}`;

            renderPreview(headers, dataRows, headerIndex);

            // A light sanity check only — not a full column-by-column validation. The server does the real read.
            const matches = EXPECTED_HEADERS.filter((h, i) => (headers[i] || '').toUpperCase() === h).length;
            const warning = document.getElementById('headerWarning');
            if (matches < EXPECTED_HEADERS.length * 0.6) {
                warning.textContent = `This file's header row doesn't look like the admissions portal's export format (only ${matches} of ${EXPECTED_HEADERS.length} expected columns matched). You can still try importing it, but check it's the right file.`;
                show(warning);
            } else {
                hide(warning);
            }

            show(previewCard);
            setStep(2);
            if (STAFF_REVIEW) importButton.innerHTML = `<i class="bi bi-send me-1" aria-hidden="true"></i> Submit for review (${plural(rowCount, 'record')})`;
        }

        // Built with textContent (not innerHTML) so nothing in the file can inject HTML into the page
        function renderPreview(headers, dataRows, headerIndex) {
            const table = document.getElementById('previewTable');
            table.replaceChildren();

            const headRow = table.createTHead().insertRow();
            const rowTh = document.createElement('th');
            rowTh.className = 'text-body-secondary text-uppercase fw-bold bg-body-tertiary';
            rowTh.textContent = 'Row';
            headRow.appendChild(rowTh);
            headers.forEach(h => {
                const th = document.createElement('th');
                th.className = 'text-body-secondary text-uppercase fw-bold bg-body-tertiary';
                th.textContent = h || '—';
                headRow.appendChild(th);
            });

            const body = table.createTBody();
            dataRows.slice(0, PREVIEW_ROWS).forEach((row, index) => {
                const tr = body.insertRow();
                // A section header row (no FULL NAME, but something in column 0) gets highlighted
                const isSection = String(row[3] ?? '').trim() === '' && String(row[0] ?? '').trim() !== '' && isNaN(row[0]);
                if (isSection) tr.className = 'table-primary';
                tr.insertCell().textContent = index + headerIndex + 2;
                headers.forEach((_, i) => { tr.insertCell().textContent = row[i] ?? ''; });
            });

            document.getElementById('previewNote').textContent =
                `Showing ${Math.min(PREVIEW_ROWS, dataRows.length)} of ${plural(dataRows.length, 'row')} · rows highlighted in blue are section headers, not applicants`;
        }

        // IATO Staff: the server stages the file as a Review Queue item instead of importing it
        // directly (see ImportController::stageImportForReview()) and redirects back here with
        // the real RQ number - the banner above shows it (filled in server-side, not here).
        const STAFF_REVIEW = @json($staffReview);

        // Show progress while the server imports/validates (the page reloads when it's done)
        form.addEventListener('submit', () => {
            setStep(3);
            importButton.disabled = true;
            importButton.innerHTML = STAFF_REVIEW
                ? '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Submitting…'
                : '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Importing…';
        });

        // A minimal sample: the header row, one section header, and one filled example row —
        // matches the shape of the real portal export (see the "Sample File" card).
        document.getElementById('downloadSample').addEventListener('click', () => {
            const quote = v => /[",]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v;
            const rows = [
                EXPECTED_HEADERS,
                ['COLLEGE OF INFORMATION TECHNOLOGY AND ENGINEERING  (1)'],
                ['1', '2027110001', '2026-11-07', 'SANTOS, MARIA ISABEL L', 'M.SANTOS@EXAMPLE.COM', '(+63) 917-000-0381',
                 'Lipa City, Batangas', '', '', '18', 'F', 'BSCS', '', '1st Year', 'New Student/ Freshman',
                 'Yes', 'No', 'No', 'No', 'In-Progress', 'No', 'In-Process', 'Lipa City Senior High School',
                 'Lipa City, Batangas', 'Public', '', 'In-Process', 'FACE TO FACE LEARNING', 'No', 'Filipino'],
            ];
            const csv = rows.map(r => r.map(quote).join(',')).join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = 'iaris_sample_import.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    </script>
@endsection
