@extends('layouts.app')

@section('title', 'iARIS — Import Data')
@section('page-title', 'Import Data')
@section('page-subtitle', 'Upload Excel or CSV files to batch-import applicant records into iARIS')

@php
    // Import Target choices: the levels the applicants table accepts ('' = the file has several levels)
    $targets = ['' => 'Mixed: each row\'s level column decides', 'is' => 'Integrated School Applicants', 'college' => 'College Applicants',
        'graduate_school' => 'Graduate Programs Applicants', 'eteeap' => 'ETEEAP Applicants'];
    // Templates to download: level => [name, description, icon]
    $templates = [
        'college' => ['College Applicants', 'Standard college applicant template', 'bi-mortarboard'],
        'is' => ['Integrated School', 'K–12 applicants, with sub-level and strand', 'bi-building'],
        'graduate_school' => ['Graduate Programs', "Master's and doctoral applicants", 'bi-journal-bookmark'],
        'eteeap' => ['ETEEAP', 'Working adults applying through ETEEAP', 'bi-briefcase'],
    ];
    // IATO Staff (not a Temporary Admin): imports go to the Review Queue (front end only for now, see the script)
    $staffReview = auth()->user()->role === 'iato_staff' && ! auth()->user()->isAdmin();
    $steps = ['Upload File', 'Check Columns', 'Preview & Validate', $staffReview ? 'Submit for Review' : 'Import'];
@endphp

@section('content')
    <div class="alert alert-info d-flex gap-2 align-items-center" role="status">
        <i class="bi bi-info-circle"></i>
        <div class="small">
            This page is for importing individual <b>applicant records</b> (one row per student).
            Importing the Analytics &amp; Reports summary numbers instead?
            <a href="{{ url('/import/admission-stats') }}" class="fw-bold alert-link">Go to Import Admission Stats →</a>
        </div>
    </div>

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

    <div class="row g-4">
    <div class="col-12 col-xl-8">
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
                </div>

                {{-- Steps: the script moves the highlight along --}}
                {{-- IATO Staff: shown after "Submit for review" (the script fills in the numbers) --}}
                @if ($staffReview)
                    <div class="alert alert-success d-none d-flex gap-3 rounded-4 mb-4" id="reviewBanner" role="status">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                        <div class="small">
                            <b id="reviewBannerTitle"></b> Other users see them after the Super Admin approves.
                            <a href="{{ url('/submissions') }}" class="fw-bold alert-link">Track it in My Submissions →</a>
                            <div class="text-body-secondary mt-1">Demo only: nothing was saved. Staff imports need the Review Queue backend first.</div>
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

                {{-- Which records the file is for. Rows of another level get a warning. --}}
                <div class="mt-4">
                    <label for="importTarget" class="form-label small fw-bold text-uppercase text-body-secondary">Import Target — which records is this file for?</label>
                    <select class="form-select" id="importTarget">
                        @foreach ($targets as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">College of Law, iPACE and Scholars can't be imported yet: the applicants table has no level for them.</div>
                </div>
            </div>
        </div>

        {{-- 2. Preview --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 d-none" id="previewCard">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Data Preview</h2>
                        <p class="small text-body-secondary mb-0">The first rows from your file, exactly as they will be read, with a check of each row</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="rowCounts"></div>
                </div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-hover align-middle mb-0 small text-nowrap" id="previewTable"></table>
                </div>
                <div class="small text-body-secondary mt-2" id="previewNote"></div>
                {{-- The first few row problems, so they can be fixed in the file --}}
                <ul class="small text-danger mt-2 mb-0 ps-3 d-none" id="rowProblems"></ul>
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
    </div>

    <div class="col-12 col-xl-4 d-flex flex-column gap-4">
        {{-- Templates: a CSV with the right headers and example rows for each level --}}
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="fs-6 fw-bold mb-1">Download Templates</h2>
                <p class="small text-body-secondary mb-3">Start from these so the columns match</p>
                <div class="list-group list-group-flush">
                    @foreach ($templates as $level => [$name, $description, $icon])
                        <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-0" data-template="{{ $level }}">
                            <span class="icon-circle rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi {{ $icon }}"></i></span>
                            <span class="flex-grow-1 text-start">
                                <span class="d-block small fw-bold">{{ $name }}</span>
                                <span class="d-block small text-body-secondary">{{ $description }}</span>
                            </span>
                            <i class="bi bi-download text-body-secondary"></i>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

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
                        <span class="icon-circle rounded-3 bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi {{ $icon }}"></i></span>
                        <span class="overflow-hidden">
                            <span class="d-block small fw-bold text-truncate">{{ $batch->original_filename }}</span>
                            <span class="d-block small text-body-secondary">By {{ $batch->uploader ?? 'Unknown user' }} · {{ \Illuminate\Support\Carbon::parse($batch->created_at)->format('M j, Y · g:i A') }}</span>
                            <span class="badge rounded-pill bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis mt-1">{{ $label }}</span>
                        </span>
                    </a>
                @empty
                    <p class="small text-body-secondary mb-0">No files imported yet.</p>
                @endforelse
            </div>
        </div>
    </div>
    </div>
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

        // The only values the applicants table accepts (database enum columns). Anything else makes the import fail.
        const ALLOWED = {
            level: ['is', 'college', 'graduate_school', 'eteeap'],
            sub_level: ['', 'kindergarten', 'jhs', 'shs'],
            gender: ['male', 'female'],
            applicant_type: ['', 'regular', 'scholar'],
            admission_test_status: ['', 'not_taken', 'took_test', 'passed', 'failed', 'qualified_other_degree', 'reconsidered'],
            application_status: ['', 'pooling', 'submitted', 'completed', 'not_submitted'],
        };

        // Example rows for each template: [sub_level, first, last, gender, feeder school, program / track]
        const TEMPLATE_EXAMPLES = {
            college: [['', 'Maria', 'Santos', 'female', 'Lipa City National High School', 'BS Computer Science'],
                      ['', 'Juan', 'Dela Cruz', 'male', 'Batangas National High School', 'BS Accountancy']],
            is: [['shs', 'Ana', 'Reyes', 'female', 'Lipa City National High School', 'STEM'],
                 ['jhs', 'Paolo', 'Garcia', 'male', 'Marawoy Elementary School', 'Junior High School']],
            graduate_school: [['', 'Ramon', 'Silang', 'male', 'De La Salle Lipa', 'Master of Business Administration'],
                              ['', 'Luisa', 'Fernandez', 'female', 'Batangas State University', 'Doctor of Philosophy in Education']],
            eteeap: [['', 'Rogelio', 'Marasigan', 'male', '', 'BS Business Administration'],
                     ['', 'Josephine', 'Valdez', 'female', '', 'BS Hospitality Management']],
        };

        let lastFile = null, lastRows = null;   // kept so changing the Import Target re-checks the same file

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
            const target = document.getElementById('importTarget').value;
            form.reset();
            document.getElementById('importTarget').value = target;   // keep the chosen target
            [fileRow, readError, previewCard, checkCard].forEach(hide);
            const banner = document.getElementById('reviewBanner');   // IATO Staff only
            if (banner) hide(banner);
            show(dropzone);
            importButton.disabled = true;
            lastFile = lastRows = null;
            setStep(1);
        }

        // Step indicator: steps before n are done (check mark), n is current
        function setStep(n) {
            document.querySelectorAll('#importSteps [data-step]').forEach(li => {
                const i = Number(li.dataset.step), dot = li.querySelector('[data-dot]');
                dot.className = 'rounded-circle border border-2 fw-bold small d-flex align-items-center justify-content-center flex-shrink-0 '
                    + (i <= n ? 'bg-primary border-primary text-white' : 'text-body-secondary');
                dot.innerHTML = i < n ? '<i class="bi bi-check-lg"></i>' : i;
                li.querySelector('[data-label]').className = 'small fw-bold text-nowrap d-none d-md-inline ' + (i <= n ? 'text-primary' : 'text-body-secondary');
                li.querySelector('[data-line]')?.classList.toggle('border-primary', i < n);
                if (i === n) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
            });
        }
        setStep(1);

        // One row's check: errors stop the import, warnings don't. Returns { status, problems }.
        function checkRow(row, keys) {
            const value = key => { const i = keys.indexOf(key); return i === -1 ? '' : String(row[i] ?? '').trim(); };
            const target = document.getElementById('importTarget').value;
            const errors = [], warnings = [];
            COLUMNS.filter(c => c.required).forEach(c => { if (!value(c.key)) errors.push(`${c.label} is empty`); });
            Object.entries(ALLOWED).forEach(([key, allowed]) => {
                const v = value(key);
                if (v && !allowed.includes(v)) errors.push(`${key} "${v}" must be one of: ${allowed.filter(Boolean).join(', ')}`);
            });
            if (target && value('level') && value('level') !== target) warnings.push(`level is "${value('level')}", not "${target}"`);
            return { status: errors.length ? 'Error' : warnings.length ? 'Warning' : 'Valid', problems: [...errors, ...warnings] };
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
            lastFile = file;
            lastRows = rows;
            const headers = (rows[0] || []).map(h => String(h).trim());
            const dataRows = rows.slice(1).filter(r => r.some(cell => String(cell).trim() !== ''));
            rowCount = dataRows.length;
            if (!headers.length || !dataRows.length) {
                return fail(`"${file.name}" has no data rows under the header row.`);
            }

            document.getElementById('fileMeta').textContent =
                `${formatSize(file.size)} · ${plural(dataRows.length, 'row')} · ${plural(headers.length, 'column')}`;

            const checks = dataRows.map(r => checkRow(r, headers.map(toKey)));
            renderPreview(headers, dataRows, checks);
            renderCheck(headers, dataRows, checks);
        }

        // Built with textContent (not innerHTML) so nothing in the file can inject HTML into the page
        function renderPreview(headers, dataRows, checks) {
            const table = document.getElementById('previewTable');
            table.replaceChildren();

            const headRow = table.createTHead().insertRow();
            headers.forEach(h => {
                const th = document.createElement('th');
                th.className = 'text-body-secondary text-uppercase fw-bold bg-body-tertiary';
                th.textContent = h || '—';
                headRow.appendChild(th);
            });
            const checkTh = document.createElement('th');
            checkTh.className = 'text-body-secondary text-uppercase fw-bold bg-body-tertiary';
            checkTh.textContent = 'Check';
            headRow.prepend(checkTh);
            const rowTh = document.createElement('th');
            rowTh.className = 'text-body-secondary text-uppercase fw-bold bg-body-tertiary';
            rowTh.textContent = 'Row';
            headRow.prepend(rowTh);

            const tones = { Valid: 'primary', Warning: 'warning', Error: 'danger' };

            const body = table.createTBody();
            // Rows with problems first, so they show up in the preview
            const order = dataRows.map((r, i) => i).sort((a, b) => (checks[a].status === 'Valid') - (checks[b].status === 'Valid'));
            order.slice(0, PREVIEW_ROWS).forEach(index => {
                const tr = body.insertRow();
                const check = checks[index];
                if (check.status === 'Error') tr.className = 'table-danger';
                else if (check.status === 'Warning') tr.className = 'table-warning';
                tr.insertCell().textContent = index + 2;   // the row number in the spreadsheet (row 1 is the headers)
                const badge = document.createElement('span');
                badge.className = `badge rounded-pill bg-${tones[check.status]}-subtle text-${tones[check.status]}-emphasis`;
                badge.textContent = check.status;
                badge.title = check.problems.join('; ');
                tr.insertCell().appendChild(badge);
                headers.forEach((_, i) => { tr.insertCell().textContent = dataRows[index][i] ?? ''; });
            });

            // "38 Valid · 2 Warnings · 1 Error"
            const count = s => checks.filter(c => c.status === s).length;
            const counts = document.getElementById('rowCounts');
            counts.replaceChildren(...['Valid', 'Warning', 'Error'].filter(s => s === 'Valid' || count(s)).map(s => {
                const b = document.createElement('span');
                b.className = `badge rounded-pill bg-${tones[s]}-subtle text-${tones[s]}-emphasis px-3 py-2`;
                b.textContent = `${count(s)} ${s === 'Valid' ? 'Valid' : s + (count(s) === 1 ? '' : 's')}`;
                return b;
            }));

            // The first few problems, with their spreadsheet row numbers
            const list = document.getElementById('rowProblems');
            const problems = checks.flatMap((c, i) => c.status === 'Valid' ? [] : c.problems.map(p => `Row ${i + 2}: ${p}`));
            list.replaceChildren(...problems.slice(0, 5).map(text => { const li = document.createElement('li'); li.textContent = text; return li; }));
            if (problems.length > 5) list.append(Object.assign(document.createElement('li'), { textContent: `…and ${problems.length - 5} more` }));
            list.classList.toggle('d-none', !problems.length);

            document.getElementById('previewNote').textContent =
                `Showing ${Math.min(PREVIEW_ROWS, dataRows.length)} of ${plural(dataRows.length, 'row')}`;
            show(previewCard);
        }

        function renderCheck(headers, dataRows, checks) {
            const rowErrors = checks.filter(c => c.status === 'Error').length;
            const rowWarnings = checks.filter(c => c.status === 'Warning').length;
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
            } else if (rowErrors) {
                // Empty required cells and values the database won't accept would make the whole import fail
                summary.classList.add('alert-danger');
                summary.textContent = `Can't import yet: ${plural(rowErrors, 'row')} ${rowErrors === 1 ? 'has' : 'have'} errors (see the Data Preview). Fix them in the file and choose it again.`;
                importButton.disabled = true;
            } else if (rowWarnings) {
                summary.classList.add('alert-warning');
                summary.textContent = `Ready to import ${plural(dataRows.length, 'row')}, but ${plural(rowWarnings, 'row')} ${rowWarnings === 1 ? 'is' : 'are'} for a different level than the Import Target. They will be saved under their own level.`;
                importButton.disabled = false;
            } else {
                summary.classList.add('alert-success');
                summary.textContent = `Ready to import ${plural(dataRows.length, 'row')}.`;
                importButton.disabled = false;
            }
            show(checkCard);
            setStep(missingRequired.length ? 2 : 3);
            // IATO Staff: the button says how many records go for review
            if (STAFF_REVIEW) importButton.innerHTML = `<i class="bi bi-send me-1"></i> Submit for review (${plural(rowCount, 'record')})`;
        }

        // Changing the Import Target re-checks the file that's already chosen
        document.getElementById('importTarget').addEventListener('change', () => { if (lastFile) render(lastFile, lastRows); });

        // IATO Staff: the file goes to the Review Queue instead of being imported (front end only).
        // TODO (backend): save the batch as a pending Review Queue item; the Super Admin's Approve imports it.
        const STAFF_REVIEW = @json($staffReview);
        let nextReviewNumber = 413;   // made-up Review Queue numbers

        // Show progress while the server imports (the page reloads when it's done)
        form.addEventListener('submit', e => {
            if (STAFF_REVIEW) {
                e.preventDefault();
                const rq = 'RQ-' + String(nextReviewNumber++).padStart(4, '0');
                document.getElementById('reviewBannerTitle').textContent = `${plural(rowCount, 'record')} sent to the Review Queue as ${rq}.`;
                setStep(4);
                hide(document.getElementById('previewCard'));
                hide(document.getElementById('checkCard'));
                const banner = document.getElementById('reviewBanner');
                show(banner, 'flex');
                banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            setStep(4);
            importButton.disabled = true;
            importButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span> Importing…';
        });

        // Templates: a CSV with the header row plus two example rows for that level
        document.querySelectorAll('[data-template]').forEach(btn => btn.addEventListener('click', () => {
            const level = btn.dataset.template;
            const quote = v => /[",]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v;
            const rows = TEMPLATE_EXAMPLES[level].map(([sub, first, last, gender, school, program]) =>
                ['2025-2026', level, sub, first, last, gender, school, program, 'regular', 'not_taken', 'pooling']);
            const csv = [COLUMNS.map(c => c.key), ...rows].map(r => r.map(quote).join(',')).join('\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
            link.download = `iaris_template_${level}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        }));
    </script>
@endsection
