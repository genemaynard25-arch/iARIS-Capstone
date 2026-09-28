@extends('layouts.app')

@section('title', 'iARIS — Import Data')
@section('page-title', 'Import Data')
@section('page-subtitle', 'Upload applicant records from Google Sheets / Excel exports')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger d-flex gap-2" role="alert">
            <i class="bi bi-exclamation-circle"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ url('/import') }}" enctype="multipart/form-data" id="importForm">
        @csrf

        {{-- 1. Upload --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Upload Excel File</h2>
                        <p class="small text-body-secondary mb-0">Drag and drop your applicant data file, or browse to select one</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="downloadTemplate">
                        <i class="bi bi-download me-1"></i> Download CSV template
                    </button>
                </div>

                {{-- The whole box is a <label>, so clicking anywhere opens the file picker --}}
                <label for="file" id="dropzone" class="d-block text-center rounded-4 border border-2 border-primary-subtle p-5" style="--bs-border-style: dashed; cursor: pointer;">
                    <span class="icon-circle rounded-circle bg-primary-subtle text-primary fs-4 d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </span>
                    <span class="d-block fw-bold mb-1">Drag and drop your file here</span>
                    <span class="d-block small text-body-secondary mb-3">Supported formats: .xlsx, .csv</span>
                    <span class="btn btn-primary fw-semibold px-4">Browse Files</span>
                    <span class="d-block small text-body-secondary mt-3">The first row must contain the column headers (see the column check after you choose a file)</span>
                </label>
                <input type="file" name="file" id="file" class="d-none" accept=".xlsx,.csv" required>

                {{-- Selected file --}}
                <div class="d-none align-items-center gap-3 rounded-3 border bg-body-tertiary p-3 mt-3" id="fileRow">
                    <span class="icon-circle rounded-3 bg-primary-subtle text-primary fs-5 d-flex align-items-center justify-content-center">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </span>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-semibold text-truncate" id="fileName"></div>
                        <div class="small text-body-secondary" id="fileMeta"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger fw-semibold text-decoration-none" id="removeFile">Remove</button>
                </div>

                <div class="alert alert-danger small mt-3 mb-0 d-none" id="readError" role="alert"></div>
            </div>
        </div>

        {{-- 2. Preview --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 d-none" id="previewCard">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-1">Data Preview</h2>
                <p class="small text-body-secondary mb-3">The first rows from your file, exactly as they will be read</p>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-hover align-middle mb-0 small text-nowrap" id="previewTable"></table>
                </div>
                <div class="small text-body-secondary mt-2" id="previewNote"></div>
            </div>
        </div>

        {{-- 3. Column check + import --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 d-none" id="checkCard">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-1">Column Check</h2>
                <p class="small text-body-secondary mb-3">iARIS matches columns by their header name. Required columns must be present before you can import.</p>

                <div class="row g-2 mb-3" id="columnList"></div>

                <div class="small text-body-secondary d-none" id="extraColumns"></div>

                <div class="alert small mt-3 mb-0" id="checkSummary" role="status"></div>

                <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-light border px-4" id="cancelImport">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4" id="importButton" disabled>
                        <i class="bi bi-upload me-1"></i> Import Data
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    {{-- SheetJS reads the file in the browser for the preview; the real import still happens on the server --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        // Must match the columns App\Imports\ApplicantsImport reads.
        // Headers are compared the way Laravel Excel does it: "First Name" -> "first_name".
        const COLUMNS = [
            { key: 'school_year', label: 'School Year', required: true, example: '2026-2027' },
            { key: 'level', label: 'Level', required: true, example: 'college' },
            { key: 'sub_level', label: 'Sub-Level', required: false, example: '' },
            { key: 'first_name', label: 'First Name', required: true, example: 'Maria' },
            { key: 'last_name', label: 'Last Name', required: true, example: 'Santos' },
            { key: 'gender', label: 'Gender', required: true, example: 'female' },
            { key: 'feeder_school', label: 'Feeder School', required: false, example: 'Lipa City Science HS' },
            { key: 'program_or_track', label: 'Program / Track', required: false, example: 'BS Computer Science' },
            { key: 'applicant_type', label: 'Applicant Type', required: false, example: 'regular' },
            { key: 'admission_test_status', label: 'Admission Test Status', required: false, example: 'not_taken' },
            { key: 'application_status', label: 'Application Status', required: false, example: 'pooling' },
        ];
        const PREVIEW_ROWS = 5;

        const form = document.getElementById('importForm');
        const input = document.getElementById('file');
        const dropzone = document.getElementById('dropzone');
        const fileRow = document.getElementById('fileRow');
        const readError = document.getElementById('readError');
        const previewCard = document.getElementById('previewCard');
        const checkCard = document.getElementById('checkCard');
        const importButton = document.getElementById('importButton');

        const toKey = header => String(header).trim().toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        const formatSize = bytes => bytes < 1024 * 1024 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1024 / 1024).toFixed(1) + ' MB';
        const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;
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
            form.reset();
            [fileRow, readError, previewCard, checkCard].forEach(hide);
            show(dropzone);
            importButton.disabled = true;
        }

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
            const headers = (rows[0] || []).map(h => String(h).trim());
            const dataRows = rows.slice(1).filter(r => r.some(cell => String(cell).trim() !== ''));
            if (!headers.length || !dataRows.length) {
                return fail(`"${file.name}" has no data rows under the header row.`);
            }

            document.getElementById('fileMeta').textContent =
                `${formatSize(file.size)} · ${plural(dataRows.length, 'row')} · ${plural(headers.length, 'column')}`;

            renderPreview(headers, dataRows);
            renderCheck(headers, dataRows);
        }

        // Built with textContent (not innerHTML) so nothing in the file can inject HTML into the page
        function renderPreview(headers, dataRows) {
            const table = document.getElementById('previewTable');
            table.replaceChildren();

            const headRow = table.createTHead().insertRow();
            headers.forEach(h => {
                const th = document.createElement('th');
                th.className = 'text-body-secondary text-uppercase fw-bold bg-body-tertiary';
                th.textContent = h || '—';
                headRow.appendChild(th);
            });

            const body = table.createTBody();
            dataRows.slice(0, PREVIEW_ROWS).forEach(r => {
                const tr = body.insertRow();
                headers.forEach((_, i) => { tr.insertCell().textContent = r[i] ?? ''; });
            });

            document.getElementById('previewNote').textContent =
                `Showing ${Math.min(PREVIEW_ROWS, dataRows.length)} of ${plural(dataRows.length, 'row')}`;
            show(previewCard);
        }

        function renderCheck(headers, dataRows) {
            const keys = headers.map(toKey);
            const list = document.getElementById('columnList');
            list.replaceChildren();

            let missingRequired = [];
            let emptyRequiredCells = 0;

            COLUMNS.forEach(col => {
                const index = keys.indexOf(col.key);
                const found = index !== -1;
                if (col.required && !found) missingRequired.push(col.label);
                if (col.required && found) {
                    emptyRequiredCells += dataRows.filter(r => String(r[index] ?? '').trim() === '').length;
                }

                const [icon, tone, note] = found
                    ? ['bi-check-circle-fill', 'primary', `Found as "${headers[index]}"`]
                    : col.required
                        ? ['bi-x-circle-fill', 'danger', 'Missing (required)']
                        : ['bi-dash-circle', 'secondary', 'Not in file (optional)'];

                const cell = document.createElement('div');
                cell.className = 'col-12 col-md-6 col-xl-4';
                cell.innerHTML = `
                    <div class="d-flex align-items-center gap-2 rounded-3 border px-3 py-2 h-100">
                        <i class="bi ${icon} text-${tone}"></i>
                        <div class="small">
                            <div class="fw-semibold"></div>
                            <div class="text-body-secondary"></div>
                        </div>
                    </div>`;
                cell.querySelector('.fw-semibold').textContent = col.label + (col.required ? ' *' : '');
                cell.querySelector('.text-body-secondary').textContent = note;
                list.appendChild(cell);
            });

            // Columns in the file that iARIS doesn't use
            const known = COLUMNS.map(c => c.key);
            const extras = headers.filter((h, i) => h && !known.includes(keys[i]));
            const extraEl = document.getElementById('extraColumns');
            extraEl.textContent = extras.length ? `Ignored columns: ${extras.join(', ')}` : '';
            extras.length ? show(extraEl) : hide(extraEl);

            const summary = document.getElementById('checkSummary');
            summary.classList.remove('alert-danger', 'alert-warning', 'alert-success');
            if (missingRequired.length) {
                summary.classList.add('alert-danger');
                summary.textContent = `Can't import yet: add the missing required column(s): ${missingRequired.join(', ')}.`;
                importButton.disabled = true;
            } else if (emptyRequiredCells) {
                summary.classList.add('alert-warning');
                summary.textContent = `All required columns found, but ${emptyRequiredCells} required cell(s) are empty. Rows with blanks may fail to import.`;
                importButton.disabled = false;
            } else {
                summary.classList.add('alert-success');
                summary.textContent = `Ready to import ${plural(dataRows.length, 'row')}.`;
                importButton.disabled = false;
            }
            show(checkCard);
        }

        // Show progress while the server imports (the page reloads when it's done)
        form.addEventListener('submit', () => {
            importButton.disabled = true;
            importButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Importing…';
        });

        // A CSV with the expected header row plus one example row
        document.getElementById('downloadTemplate').addEventListener('click', () => {
            const csv = [COLUMNS.map(c => c.key).join(','), COLUMNS.map(c => c.example).join(',')].join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = 'iaris_import_template.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
    </script>
@endsection
