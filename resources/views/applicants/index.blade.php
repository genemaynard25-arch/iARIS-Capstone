@extends('layouts.app')

{{--
    This view is used by the Records pages, picked by $scope:
    ('all' was /applicants, which now has its own view: applicants/pipeline.blade.php), 'college' = /records/college, 'is' = /records/is,
    'law' = /records/law, 'ipace' = /records/ipace, 'graduate' = /records/graduate.
    $page holds what's different between them ($defaults fills in anything a page leaves out):
      extras       extra columns: which field, its heading, and whether it goes
                   before the second column ('first' => true) or after it
      labels       headings / "All ..." filter text for the two filter columns
                   (null on Applicants, where they follow the College / IS tab).
                   'programAll' => null hides the second filter.
      statuses     which statuses get a stat card and a pill
      statusLabels a different name to show for a status
      programField the drawer's label for program_label
      drawerExtras more fields shown only in the drawer
      groupBadge   show the first column as a small tag
      programBadges  show some second-column values as a badge: value => [colour, icon]
      extraStep    one more timeline step after "Application Submitted"
                   ({program} in its text is replaced with the second column's value)
      examStep / finalStep   other titles for the exam and last timeline steps
--}}
@php
    // College, IS and Graduate Records read the database (see App\Support\ApplicantRecords), which knows these statuses.
    // ("Enrolled" isn't in the database yet.)
    $databaseStatuses = ['Pending', 'For Exam', 'Passed', 'Not Qualified', 'Paid'];
    // Pages that read the database: they get an "imported" empty message
    $fromDatabase = in_array($scope, ['college', 'is', 'graduate', 'eteeap']);
    // Add / Edit / Remove: only roles with the 'edit' permission (Access Control).
    // Registrars can view records but not change them (subtitle says so; Ask IATO in the drawer)
    $isRegistrar = auth()->user()->role === 'registrar';
    // The IS Principal sees this page too (CollegeController::applicants), view only, at /principal/applicants
    $isPrincipal = auth()->user()->isPrincipalOffice();
    $isGradChair = auth()->user()->isGradChair();
    $isDean = auth()->user()->role === 'dean';
    $office = auth()->user()->roleInfo();
    // The Principal's table: coloured Level chips, the scholarship tag under the number, counts on the pills
    $levelChips = ['Preschool' => ['#F1E7FF', '#7C3AED'], 'Grade School' => ['#E6EEFF', '#2558D9'], 'Junior High' => ['#FFF1DC', '#B96000'], 'Senior High' => ['#E3F4EC', '#00674F']];
    $canEdit = ! $isRegistrar && ! $isPrincipal && ! $isGradChair && ! $isDean && match ($scope) {
        'college', 'is', 'scholars' => auth()->user()->canEdit($scope),
        default => auth()->user()->canAccess('edit'),
    };

    $defaults = [
        'extras' => [], 'labels' => null, 'groupBadge' => false, 'programBadges' => [],
        'extraStep' => null, 'examStep' => null, 'finalStep' => null,
        'statuses' => ['Pending', 'For Exam', 'Paid', 'Enrolled'], 'statusLabels' => [],
        'programField' => 'Program / Track / Strand', 'examField' => 'Exam Schedule', 'drawerExtras' => [], 'guardian' => false,
    ];
    $page = array_merge($defaults, [
        'all' => [
            'title' => 'All Applicants', 'subtitle' => "Academic Year {$academicYear} · {$periodLabel}",
            'total' => 'Total Applicants', 'totalSub' => 'College + IS combined', 'noun' => 'applicant',
        ],
        'college' => [
            'title' => 'College Records', 'subtitle' => "College applicant records for AY {$academicYear}",
            'total' => 'Total College Students', 'totalSub' => 'All colleges combined', 'noun' => 'applicant',
            'extras' => [
                ['key' => 'year', 'label' => 'Year Level', 'first' => false],
                ['key' => 'scholarship', 'label' => 'Scholarship', 'first' => false],
            ],
            'labels' => ['group' => 'College', 'groupAll' => 'All Colleges', 'program' => 'Program', 'programAll' => 'All Programs'],
            'statuses' => $databaseStatuses,
        ],
        'is' => [
            'title' => 'Integrated School Records', 'subtitle' => "Preschool to Grade 12 applicant records for AY {$academicYear}",
            'total' => 'Total IS Students', 'totalSub' => 'All sub-levels combined', 'noun' => 'applicant',
            'extras' => [
                ['key' => 'level', 'label' => 'Grade', 'first' => true],
                ['key' => 'scholarship', 'label' => 'Scholarship', 'first' => false],
            ],
            'labels' => ['group' => 'Level', 'groupAll' => 'All Levels', 'program' => 'Strand', 'programAll' => 'All Strands'],
            'statuses' => $databaseStatuses,
            // Learners are children: show LRN and the parent / guardian (contact details are the guardian's)
            'guardian' => true,
            'examField' => 'Entrance Exam / Assessment',
        ],
        // ETEEAP: the first column is the program, the second the portfolio / assessment status
        'eteeap' => [
            'title' => 'ETEEAP Records', 'subtitle' => "Expanded Tertiary Education Equivalency and Accreditation Program — applicant records for AY {$academicYear}",
            'total' => 'Total Applicants', 'totalSub' => 'All programs combined', 'noun' => 'applicant',
            'extras' => [
                ['key' => 'age', 'label' => 'Age', 'first' => false],
                ['key' => 'education', 'label' => 'Highest Educ. Attainment', 'first' => false],
                ['key' => 'experience', 'label' => 'Work Experience', 'first' => false],
                ['key' => 'scholarship', 'label' => 'Scholarship', 'first' => false],
            ],
            'labels' => ['group' => 'Program', 'groupAll' => 'All Programs', 'program' => 'Portfolio / Assessment', 'programAll' => 'All Assessment Status'],
            'groupBadge' => true,
            'programBadges' => ['Pending' => ['secondary', 'bi-hourglass'], 'Submitted' => ['info', 'bi-folder-check'], 'Cleared' => ['primary', 'bi-patch-check'], 'Not Cleared' => ['danger', 'bi-x-circle']],
            'statuses' => $databaseStatuses,
            'statusLabels' => ['For Exam' => 'For Assessment'],
            'programField' => 'Program', 'examField' => 'Competency Assessment',
            'drawerExtras' => [
                ['key' => 'program', 'label' => 'Portfolio Status'],
                ['key' => 'employer', 'label' => 'Current Employer'],
            ],
            'examStep' => 'Competency Assessment Scheduled',
            'finalStep' => 'Admission Confirmed',
        ],
        // Law: the first column is the program (JD / LLM / JSD), the second is the undergraduate degree
        'law' => [
            'title' => 'College of Law Records', 'subtitle' => "Law program applicant records for AY {$academicYear}",
            'total' => 'Total Applicants', 'totalSub' => 'All law programs', 'noun' => 'applicant',
            'extras' => [['key' => 'bar', 'label' => 'Bar Exam', 'first' => false]],
            'labels' => ['group' => 'Program', 'groupAll' => 'All Programs', 'program' => 'Undergraduate Degree', 'programAll' => null],
            'groupBadge' => true,
            'statuses' => ['Pending', 'For Exam', 'Paid'],
            'statusLabels' => ['For Exam' => 'For Exam / Interview'],
            'programField' => 'Program', 'examField' => 'Exam / Interview Schedule',
            'drawerExtras' => [['key' => 'program', 'label' => 'Undergraduate / Prior Degree']],
            'extraStep' => ['title' => 'Documents Verified', 'icon' => 'bi-file-earmark-check', 'tone' => 'dark',
                'done' => 'Transcript, IDs and credentials reviewed', 'waiting' => 'Not yet verified'],
            'examStep' => 'Entrance Exam / Interview Scheduled',
            'finalStep' => 'Admission Confirmed',
        ],
        // iPACE: the first column is the program (BSIT, BSCS, ...), the second is the delivery mode
        'ipace' => [
            'title' => 'iPACE Records', 'subtitle' => "Individualized Program for Adult and Continuing Education — applicant records for AY {$academicYear}",
            'total' => 'Total Applicants', 'totalSub' => 'All programs combined', 'noun' => 'applicant',
            'extras' => [
                ['key' => 'year', 'label' => 'Year Level', 'first' => false],
                ['key' => 'working', 'label' => 'Working Student', 'first' => false],
            ],
            'labels' => ['group' => 'Program', 'groupAll' => 'All Programs', 'program' => 'Delivery Mode', 'programAll' => 'All Delivery Modes'],
            'groupBadge' => true,
            'programBadges' => ['Online' => ['info', 'bi-wifi'], 'Blended' => ['secondary', 'bi-layers']],
            'statuses' => ['Pending', 'For Exam', 'Paid'],
            'programField' => 'Program',
            'drawerExtras' => [
                ['key' => 'program', 'label' => 'Delivery Mode'],
                ['key' => 'employer', 'label' => 'Employer / Position'],
            ],
            'extraStep' => ['title' => 'Delivery Mode Confirmed', 'icon' => 'bi-laptop', 'tone' => 'info',
                'done' => '{program} delivery confirmed', 'waiting' => 'Not yet confirmed'],
            'finalStep' => 'Admission Confirmed',
        ],
        // Graduate: the first column is the program (MBA, MEd, ...), the second is the level
        'graduate' => [
            'title' => 'Graduate Programs Records', 'subtitle' => "Master's and doctoral applicant records for AY {$academicYear}",
            'total' => 'Total Applicants', 'totalSub' => 'All graduate programs', 'noun' => 'applicant',
            // "Prior" because PhD applicants already have a master's
            'extras' => [
                ['key' => 'degree', 'label' => 'Prior Degree', 'first' => false],
                ['key' => 'scholarship', 'label' => 'Scholarship', 'first' => false],
            ],
            'labels' => ['group' => 'Program', 'groupAll' => 'All Programs', 'program' => 'Level', 'programAll' => 'All Levels'],
            'groupBadge' => true,
            'programBadges' => ["Master's" => ['primary', 'bi-mortarboard'], 'Doctoral' => ['info', 'bi-journal-bookmark']],
            'statuses' => $databaseStatuses,
            'statusLabels' => ['For Exam' => 'For Exam / Interview'],
            'programField' => 'Program', 'examField' => 'Exam / Interview Schedule',
            'drawerExtras' => [['key' => 'program', 'label' => 'Level']],
            'examStep' => 'Exam / Interview Scheduled',
            'finalStep' => 'Admission Confirmed',
        ],
    ][$scope]);
    if ($isDean) {
        $page['totalSub'] = \App\Http\Controllers\CollegeController::college()['code'] . ' only';
    }
    // Extra columns that go before / after the second column
    $extrasFirst = array_filter($page['extras'], fn ($x) => $x['first']);
    $extrasAfter = array_filter($page['extras'], fn ($x) => ! $x['first']);
@endphp

@section('title', 'iARIS — ' . $page['title'])
@section('page-title', ($isPrincipal || $isGradChair || $isDean) ? 'Applicants' : $page['title'])
@section('page-subtitle', $isDean ? \App\Http\Controllers\CollegeController::college()['name'] . " applicants for AY {$academicYear} · View only" : ($isGradChair ? "Graduate Programs applicants for AY {$academicYear} · View only" : ($isPrincipal ? (auth()->user()->isAssistantPrincipal() ? $office[1] : 'Integrated School') . " applicants for AY {$academicYear} · View only" : $page['subtitle'] . ($isRegistrar ? ' · View only' : ''))))

@php
    // Bootstrap colour for each status (used for badges, stat cards and pills)
    $statusTones = ['Pending' => 'danger', 'For Exam' => 'warning', 'Passed' => 'primary', 'Not Qualified' => 'secondary', 'Paid' => 'info', 'Enrolled' => 'primary'];

    // Add display-ready fields to each placeholder applicant
    $rows = collect($applicants)->map(fn ($a) => $a + [
        // Database rows have no middle initial
        'name' => "{$a['last']}, {$a['first']}" . ($a['mi'] ? " {$a['mi']}." : ''),
        // A controller can send its own 'group' / 'unit_label' (Law does); ?? skips the rest then.
        // IS Records groups by sub-level (Preschool, Grade School, ...); the Applicants IS tab by grade
        'group' => $a['group'] ?? ($a['unit'] === 'college' ? $a['college'] : ($a['sub_level'] ?? $a['level'])),
        'unit_label' => $a['unit_label'] ?? ($a['unit'] === 'college' ? $a['college'] : implode(' · ', array_filter(['Integrated School', $a['sub_level'] ?? null, $a['level']]))),
        'applied_label' => \Illuminate\Support\Carbon::parse($a['applied'])->format('M j, Y'),
        'updated_label' => \Illuminate\Support\Carbon::parse($a['updated'])->format('M j, g:i A'),
        'dob_label' => $a['dob'] ? \Illuminate\Support\Carbon::parse($a['dob'])->format('F j, Y') : null,
        'paid_label' => $a['paid'] ? \Illuminate\Support\Carbon::parse($a['paid'])->format('M j, Y') : null,
        'tone' => $statusTones[$a['status']],
        'status_label' => $page['statusLabels'][$a['status']] ?? $a['status'],
        'program_label' => $a['program'],
    ]);

    // Presentation follows the record mockups; payment and exam values remain supplied by the controller.
    $examResults = [
        'not_taken' => ['Not yet taken', 'secondary'], 'passed' => ['Pass', 'primary'],
        'failed' => ['Fail', 'danger'], 'waitlisted' => ['Waitlisted', 'warning'],
        'reconsidered' => ['Reconsidered', 'info'], 'took_test' => ['Awaiting result', 'warning'],
        'qualified_other_degree' => ['Qualified (other program)', 'info'], 'unknown' => ['Not recorded', 'secondary'],
    ];
    $rows = $rows->map(function ($a) use ($examResults) {
        $result = $a['exam_status'] ?? match ($a['status']) {
            'Pending' => 'not_taken', 'Passed' => 'passed', 'Not Qualified' => 'failed', default => 'unknown',
        };
        [$resultLabel, $resultTone] = $examResults[$result] ?? $examResults['unknown'];
        $examPaid = $a['exam_paid'] ?? null;
        // No 'amount' field (IATO doesn't need the peso figure - see ApplicantRecords::shape());
        // the paid date already says whether the reservation fee came in.
        $reserved = $a['paid'] !== null || in_array($a['status'], ['Paid', 'Enrolled']);
        return $a + [
            'exam_result' => $result, 'exam_result_label' => $resultLabel, 'exam_result_tone' => $resultTone,
            'exam_fee_label' => $examPaid === null ? 'Not recorded' : ($examPaid ? 'Exam fee paid' : 'Exam fee not paid'),
            'exam_fee_tone' => $examPaid === null ? 'secondary' : ($examPaid ? 'primary' : 'danger'),
            'reservation_label' => $reserved ? 'Reservation paid' : 'Not yet paid',
            'reservation_tone' => $reserved ? 'info' : 'secondary', 'reserved' => $reserved,
        ];
    });
    $stats = [
        ['label' => 'Total Applicants', 'value' => $rows->count(), 'sub' => $scope === 'is' ? 'Nursery to Grade 12' : $page['totalSub'], 'tone' => 'primary'],
        ['label' => 'Exam fee paid', 'value' => $rows->where('exam_paid', true)->count(), 'sub' => 'Updates after online payment', 'tone' => 'primary'],
        ['label' => 'Passed', 'value' => $rows->whereIn('exam_result', ['passed', 'reconsidered', 'qualified_other_degree'])->count(), 'sub' => 'Pass or Reconsidered', 'tone' => 'purple'],
        ['label' => 'Reservation paid', 'value' => $rows->where('reserved', true)->count(), 'sub' => 'Ready for enrollment', 'tone' => 'info'],
    ];
    $recordColumns = match ($scope) {
        'college' => ['group' => 'College', 'program' => 'Program', 'year' => 'Year Level'],
        'is' => ['group' => 'Level', 'level' => 'Grade', 'program' => 'Strand'],
        'graduate' => ['group' => 'Program', 'program' => 'Level', 'degree' => 'Undergraduate Degree'],
        'law' => ['group' => 'Program', 'program' => 'Undergraduate Degree', 'bar' => 'Bar Exam Passer'],
        'ipace' => ['group' => 'Program', 'program' => 'Delivery Mode', 'year' => 'Year Level', 'working' => 'Working Student'],
        'eteeap' => ['group' => 'Program', 'age' => 'Age', 'education' => 'Highest Educ. Attainment', 'experience' => 'Work Experience', 'program' => 'Portfolio / Assessment'],
        default => ['group' => 'College', 'program' => 'Program'],
    };
    $categoryField = $scope === 'ipace' ? 'program' : 'group';
    $categoryLabel = match ($scope) { 'college' => 'College', 'is' => 'Level', 'ipace' => 'Delivery Mode', default => 'Program' };
    $categoryOptions = collect($groupOrder ?? [])->merge($rows->pluck($categoryField))->filter(fn ($v) => $v && $v !== '—')->unique()->values();
    if ($scope === 'ipace') $categoryOptions = collect(['Online', 'Blended']);
    $recordColumnCount = count($recordColumns) + ($isRegistrar ? 5 : 6);

@endphp

@include('partials.admin-workspace', ['workspacePage' => 'records-page records-' . $scope, 'workspaceGroup' => ($isPrincipal || $isGradChair || $isDean) ? $office[0] . ' · ' . $office[1] : ($isRegistrar ? (auth()->user()->assigned_level === 'is' ? 'IS Registrar' : 'College Registrar') : (auth()->user()->role === 'iato_staff' ? 'Staff' : 'Admin')), 'workspaceLabel' => ($isPrincipal || $isGradChair || $isDean) ? 'Applicants' : 'Records · ' . str_replace(' Records', '', $page['title'])])

@push('styles')
    <link href="{{ asset('css/records.css') }}" rel="stylesheet">
@endpush

@section('content')
    @unless ($fromDatabase)
        <p class="admin-preview-note mb-3"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Preview records · This division is not connected to applicant imports yet.</p>
    @endunless
    {{-- Stat cards --}}
    <div class="admin-stat-grid row row-cols-2 row-cols-md-2 row-cols-xl-{{ count($stats) }} g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="admin-stat-card card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] === 'primary' ? 'body' : $stat['tone'] . '-emphasis' }}">{{ number_format($stat['value']) }}</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="records-table-card card border-0 shadow-sm rounded-4">
        {{-- Tabs (the Records pages only have one unit, so no tabs there) --}}
        @if ($scope === 'all')
        <div class="card-header bg-white border-bottom rounded-top-4 px-4 pt-3 pb-0">
            <nav class="nav nav-underline" role="tablist">
                <button type="button" class="nav-link active fw-bold d-flex align-items-center gap-2 pb-3" data-unit="college" role="tab" aria-selected="true">
                    <i class="bi bi-mortarboard"></i> College
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $rows->where('unit', 'college')->count() }}</span>
                </button>
                <button type="button" class="nav-link fw-bold d-flex align-items-center gap-2 pb-3" data-unit="is" role="tab" aria-selected="false">
                    <i class="bi bi-building"></i> Integrated School
                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">{{ $rows->where('unit', 'is')->count() }}</span>
                </button>
            </nav>
        </div>
        @endif

        {{-- Search, filters, sort --}}
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text bg-white text-body-secondary"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" id="search" placeholder="Search name or applicant no…" aria-label="Search applicants">
            </div>
            <select class="form-select w-auto {{ in_array($scope, ['college', 'is']) ? 'd-none' : '' }}" id="groupFilter" aria-label="Filter by college or level"></select>
            <select class="form-select w-auto" id="programFilter" aria-label="Filter by program or strand"></select>
            @if ($scope === 'is')
                <select class="form-select w-auto" id="gradeFilter" aria-label="Filter by grade level">
                    <option value="">All Grade Levels</option>
                    @foreach ($rows->pluck('level')->unique()->filter(fn ($grade) => $grade && $grade !== '—')->sort() as $grade)
                        <option>{{ $grade }}</option>
                    @endforeach
                </select>
            @endif
            <select class="form-select w-auto" id="sort" aria-label="Sort">
                <option value="newest">Sort: Newest First</option>
                <option value="oldest">Sort: Oldest First</option>
                <option value="az">Sort: Name A–Z</option>
                <option value="za">Sort: Name Z–A</option>
                <option value="status">Sort: Status</option>
                @if ($scope === 'eteeap')<option value="experience">Sort: Work Experience</option>@endif
            </select>
            @if ($canEdit)
                <button type="button" class="btn btn-primary fw-semibold ms-lg-auto" data-record-form="add">
                    <i class="bi bi-plus-lg me-1"></i> Add Applicant
                </button>
            @endif
            <button type="button" class="btn btn-light border fw-semibold" id="exportButton" title="Download the filtered list as CSV">
                <i class="bi bi-download me-1"></i> Export
            </button>
        </div>

        {{-- Status pills --}}
        <div class="d-flex flex-wrap gap-2 px-4 py-3 border-bottom" id="statusPills">
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-primary" data-status="All" aria-pressed="true">All @if ($isPrincipal)<span class="ms-1 opacity-75">{{ $rows->count() }}</span>@endif</button>
            @foreach ($examResults as $result => [$label, $tone])
                @continue($isDean && $result === 'took_test')
                @continue(in_array($result, ['took_test', 'qualified_other_degree', 'unknown']) && ! $rows->contains('exam_result', $result))
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-outline-secondary" data-status="{{ $result }}" aria-pressed="false">{{ $label }} @if ($isPrincipal)<span class="ms-1 opacity-75">{{ $rows->where('exam_result', $result)->count() }}</span>@endif</button>
            @endforeach
        </div>
        <div class="records-category-pills d-flex flex-wrap align-items-center gap-2 border-bottom" id="categoryPills" aria-label="Filter by {{ strtolower($categoryLabel) }}">
            <span class="small fw-semibold text-body-secondary me-1">{{ $categoryLabel }}:</span>
            <button type="button" class="btn btn-sm btn-primary" data-category="" aria-pressed="true">All</button>
            @foreach ($categoryOptions as $category)
                <button type="button" class="btn btn-sm btn-light border" data-category="{{ $category }}" aria-pressed="false">{{ $category }} @if ($isPrincipal)<span class="ms-1 opacity-75">{{ $rows->where($categoryField, $category)->count() }}</span>@endif</button>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    {{-- Headings may wrap onto two lines so long ones ("Undergraduate Degree") don't widen the table --}}
                    <tr class="small text-uppercase">
                        <th class="ps-4 text-body-secondary fw-bold"><input type="checkbox" class="visually-hidden form-check-input" id="selectAll" aria-label="Select all on this page">Applicant</th>
                        @foreach ($recordColumns as $key => $label)
                            <th class="text-body-secondary fw-bold" @if ($key === 'group') id="groupHeading" @elseif ($key === 'program') id="programHeading" @endif>{{ $label }}</th>
                        @endforeach
                        @unless ($isRegistrar)<th scope="col">Exam Fee</th>@endunless<th scope="col">Exam Result</th><th scope="col">Reservation</th>
                        <th scope="col">{{ $scope === 'eteeap' ? 'Updated' : 'Date Applied' }}</th>
                        <th class="pe-4 text-end" scope="col"><span class="{{ $scope === 'is' ? '' : 'visually-hidden' }}">Actions</span></th>
                    </tr>
                </thead>
                <tbody id="applicantRows">
                    @foreach ($rows as $i => $a)
                        <tr class="applicant-row" style="cursor: pointer;" tabindex="0"
                            data-grade="{{ $a['level'] ?? '' }}" data-experience="{{ $a['experience'] ?? '' }}" data-exam-result="{{ $a['exam_result'] }}" data-index="{{ $i }}" data-unit="{{ $a['unit'] }}" data-status="{{ $a['status'] }}"
                            data-group="{{ $a['group'] }}" data-program="{{ $a['program'] }}"
                            data-search="{{ strtolower($a['name'] . ' ' . $a['id']) }}"
                            data-applied="{{ $a['applied'] }}" data-name="{{ $a['name'] }}">
                            <td class="ps-4">
                                <input type="checkbox" class="visually-hidden form-check-input row-check" aria-label="Select {{ $a['name'] }}">
                                <div class="fw-semibold text-nowrap">{{ $a['name'] }}</div>
                                <div class="small text-body-secondary">{{ $a['id'] }}</div>
                                @if ($isPrincipal && ($a['scholarship'] ?? 'No') === 'Yes')<span class="badge bg-primary-subtle text-primary-emphasis mt-1">Scholarship</span>@endif
                            </td>
                            @foreach ($recordColumns as $key => $label)
                                <td>
                                    @if ($isPrincipal && $key === 'group' && isset($levelChips[$a[$key] ?? '']))
                                        <span class="badge fw-semibold" style="background: {{ $levelChips[$a[$key]][0] }}; color: {{ $levelChips[$a[$key]][1] }};">{{ $a[$key] }}</span>
                                    @elseif ($key === 'group' && $page['groupBadge'])
                                        <span class="badge bg-dark-subtle text-dark-emphasis">{{ $a[$key] ?? '—' }}</span>
                                    @elseif ($key === 'program' && ($badge = $page['programBadges'][$a['program']] ?? null))
                                        <span class="badge bg-{{ $badge[0] }}-subtle text-{{ $badge[0] }}-emphasis">{{ $a[$key] }}</span>
                                    @else
                                        {{ $a[$key] ?? '—' }}
                                    @endif
                                </td>
                            @endforeach
                            @unless ($isRegistrar)
                            @if ($isPrincipal)
                                <td class="small fw-semibold">@if ($a['exam_paid'] ?? false)<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>@else<span class="text-body-secondary fw-normal">Not yet paid</span>@endif</td>
                            @else
                            <td><span class="records-payment badge bg-{{ $a['exam_fee_tone'] }}-subtle text-{{ $a['exam_fee_tone'] }}-emphasis">{{ $a['exam_fee_label'] }}</span></td>
                            @endif
                            @endunless
                            <td><span class="badge bg-{{ $a['exam_result_tone'] }}-subtle text-{{ $a['exam_result_tone'] }}-emphasis">{{ $a['exam_result_label'] }}</span></td>
                            @if ($isPrincipal)
                                <td class="small fw-semibold">@if ($a['reserved'])<span class="text-primary"><i class="bi bi-circle-fill me-1" style="font-size: .55rem;"></i>Paid</span>@if ($a['paid_label'])<small class="d-block text-body-secondary fw-normal mt-1">{{ $a['paid_label'] }}</small>@endif @else<span class="text-body-tertiary">—</span>@endif</td>
                            @else
                            <td><span class="records-payment badge bg-{{ $a['reservation_tone'] }}-subtle text-{{ $a['reservation_tone'] }}-emphasis">{{ $a['reservation_label'] }}</span>@if ($a['paid_label'])<small class="d-block text-body-secondary mt-1">{{ $a['paid_label'] }}</small>@endif</td>
                            @endif
                            <td class="small text-body-secondary text-nowrap">{{ $scope === 'eteeap' ? $a['updated_label'] : $a['applied_label'] }}</td>
                            <td class="pe-4 text-end text-nowrap">
                                @if ($canEdit)
                                    <button type="button" class="btn btn-sm btn-light border" data-row-action="view" title="View details" aria-label="View {{ $a['name'] }}"><i class="bi bi-eye"></i></button>
                                    <button type="button" class="btn btn-sm btn-light border" data-row-action="edit" title="Edit applicant" aria-label="Edit {{ $a['name'] }}"><i class="bi bi-pencil"></i></button>
                                @else
                                    <span class="btn btn-sm btn-light border"><i class="bi bi-chevron-right"></i></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($rows->isEmpty())
                        {{-- Nothing in the database yet --}}
                        <tr>
                            <td colspan="{{ $recordColumnCount }}" class="text-center text-body-secondary py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                @if ($fromDatabase)
                                    No applicants imported yet for AY {{ $academicYear }}.
                                    @if (auth()->user()->canAccess('import'))
                                        <a href="{{ url('/import') }}" class="d-block mt-1 fw-semibold text-decoration-none">Import applicants from Excel</a>
                                    @endif
                                @else
                                    No applicants yet.
                                @endif
                            </td>
                        </tr>
                    @endif
                    <tr id="noResults" class="d-none">
                        <td colspan="{{ $recordColumnCount }}" class="text-center text-body-secondary py-5">
                            <i class="bi bi-search fs-3 d-block mb-2"></i>
                            No applicants match your search or filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer: count + pagination --}}
        <div class="card-footer bg-white rounded-bottom-4 d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-3">
            <span class="small text-body-secondary" id="showingText"></span>
            <nav aria-label="Applicant pages">
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>

    {{-- Profile drawer (Bootstrap offcanvas) --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="applicantDrawer" aria-labelledby="drawerName" style="--bs-offcanvas-width: 540px;">
        <div class="offcanvas-header bg-iaris text-white align-items-start gap-3 p-4">
            <div class="rounded-4 bg-white bg-opacity-25 border border-white border-opacity-25 fs-3 fw-bold font-brand d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width: 72px; height: 72px;" id="drawerInitials"></div>
            <div class="flex-grow-1">
                <h2 class="offcanvas-title fs-5 fw-bold mb-1" id="drawerName"></h2>
                <div class="small text-white-50 mb-2" id="drawerSub"></div>
                <span class="badge rounded-pill" id="drawerBadge"></span>
            </div>
            <button type="button" class="btn-close btn-close-white no-print" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <ul class="nav nav-underline px-4 border-bottom no-print" role="tablist">
            <li class="nav-item"><button class="nav-link active fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabInfo" type="button" role="tab">{{ $page['guardian'] ? 'Personal & Guardian' : 'Personal Info' }}</button></li>
            @if ($scope === 'eteeap')
                <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabWork" type="button" role="tab">Work History</button></li>
                <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabAssessment" type="button" role="tab">Assessment</button></li>
            @endif
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabApplication" type="button" role="tab">Application</button></li>
            <li class="nav-item"><button class="nav-link fw-bold py-3" data-bs-toggle="tab" data-bs-target="#tabTimeline" type="button" role="tab">Timeline</button></li>
        </ul>

        <div class="offcanvas-body tab-content p-4">
            {{-- Registrars: "Ask IATO about this record" (applicants/partials/ask-iato) --}}
            <div id="askIatoBox"></div>
            {{-- Personal info --}}
            <div class="tab-pane fade show active" id="tabInfo" role="tabpanel">
                @if ($page['guardian'])
                    {{-- Integrated School: the learner, then the parent / guardian (the contact details are theirs) --}}
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Learner Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Full Name</div><div class="fw-semibold" data-field="name"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Applicant No.</div><div class="fw-semibold" data-field="id"></div></div>
                        {{-- LRN (Learner Reference Number): not in the applicants table yet --}}
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">LRN</div><div class="fw-semibold" data-field="lrn"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date of Birth</div><div class="fw-semibold" data-field="dob_label"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Gender</div><div class="fw-semibold" data-field="gender"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">PWD</div><div class="fw-semibold" data-field="pwd"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">SSEN</div><div class="fw-semibold" data-field="ssen"></div></div>
                        <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">Address</div><div class="fw-semibold" data-field="address"></div></div>
                    </div>
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Parent / Guardian</div>
                    <div class="row g-3">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Name</div><div class="fw-semibold" data-field="guardian_name"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Relationship</div><div class="fw-semibold" data-field="guardian_relationship"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Contact No.</div><div class="fw-semibold" data-field="contact"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Email</div><div class="fw-semibold text-break" data-field="email"></div></div>
                    </div>
                @else
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Personal Information</div>
                    <div class="row g-3 mb-4">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Full Name</div><div class="fw-semibold" data-field="name"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">App Number</div><div class="fw-semibold" data-field="id"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date of Birth</div><div class="fw-semibold" data-field="dob_label"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Gender</div><div class="fw-semibold" data-field="gender"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">PWD</div><div class="fw-semibold" data-field="pwd"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">SSEN</div><div class="fw-semibold" data-field="ssen"></div></div>
                    </div>
                    <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Contact Details</div>
                    <div class="row g-3">
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Email Address</div><div class="fw-semibold text-break" data-field="email"></div></div>
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Contact Number</div><div class="fw-semibold" data-field="contact"></div></div>
                        <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">Home Address</div><div class="fw-semibold" data-field="address"></div></div>
                    </div>
                @endif
            </div>

            {{-- Application --}}
            @if ($scope === 'eteeap')
                <div class="tab-pane fade" id="tabWork" role="tabpanel">
                    <h3 class="records-drawer-section">Educational background</h3>
                    <div class="small text-body-secondary mb-1">Highest educational attainment</div><div class="fw-semibold mb-4" data-field="education"></div>
                    <h3 class="records-drawer-section">Employment history</h3>
                    <div class="rounded-3 border p-3 text-body-secondary small mb-4">Detailed employment history has not been recorded for this applicant.</div>
                    <h3 class="records-drawer-section">Summary</h3>
                    <dl class="row g-3"><div class="col-6"><dt class="text-body-secondary small">Total work experience</dt><dd data-field="experience"></dd></div><div class="col-6"><dt class="text-body-secondary small">Current employer</dt><dd data-field="employer"></dd></div></dl>
                </div>
                <div class="tab-pane fade" id="tabAssessment" role="tabpanel">
                    <h3 class="records-drawer-section">Portfolio & competency assessment</h3>
                    <dl class="row g-3 mb-4"><div class="col-6"><dt class="text-body-secondary small">Portfolio status</dt><dd data-field="program"></dd></div><div class="col-6"><dt class="text-body-secondary small">Assessment result</dt><dd data-field="exam_result_label"></dd></div></dl>
                    <h3 class="records-drawer-section">Competency ratings</h3>
                    <div class="records-competencies">
                        @foreach (['Technical / Core Competency', 'Communication Skills', 'Problem-Solving', 'Leadership / Management', 'Digital Literacy'] as $competency)
                            <div class="d-flex justify-content-between gap-3 border-bottom py-3"><span>{{ $competency }}</span><span class="text-body-secondary">Not recorded</span></div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="tab-pane fade" id="tabApplication" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Program Details</div>
                <div class="row g-3 mb-4">
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">College / Unit</div><div class="fw-semibold" data-field="unit_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Academic Year</div><div class="fw-semibold">{{ $academicYear }}</div></div>
                    @foreach ($page['extras'] as $x)
                        <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">{{ $x['label'] }}</div><div class="fw-semibold" data-field="{{ $x['key'] }}"></div></div>
                    @endforeach
                    <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">{{ $page['programField'] }}</div><div class="fw-semibold" data-field="program_label"></div></div>
                    @foreach ($page['drawerExtras'] as $field)
                        <div class="col-12"><div class="small fw-bold text-uppercase text-body-secondary">{{ $field['label'] }}</div><div class="fw-semibold" data-field="{{ $field['key'] }}"></div></div>
                    @endforeach
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Entrance Test</div><div class="fw-semibold" data-field="test"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Previous School</div><div class="fw-semibold" data-field="feeder_school"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">Date Applied</div><div class="fw-semibold" data-field="applied_label"></div></div>
                    <div class="col-6"><div class="small fw-bold text-uppercase text-body-secondary">{{ $page['examField'] }}</div><div class="fw-semibold" data-field="exam"></div></div>
                </div>
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Payment &amp; Status</div>
                <div class="rounded-3 border bg-body-tertiary p-3">
                    <div class="d-flex justify-content-between small mb-2"><span class="text-body-secondary fw-semibold">Date of Payment</span><span class="fw-bold" data-field="paid_label"></span></div>
                    <div class="d-flex justify-content-between small"><span class="text-body-secondary fw-semibold">Payment Status</span><span class="fw-bold" id="drawerPayStatus"></span></div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="tab-pane fade" id="tabTimeline" role="tabpanel">
                <div class="small fw-bold text-uppercase tracking-wide text-body-secondary border-bottom pb-2 mb-3">Application Timeline</div>
                <ol class="list-unstyled mb-0" id="timeline"></ol>
            </div>
        </div>

        {{-- Buttons on the left: the AI button sits in the bottom-right corner --}}
        <div class="d-flex flex-wrap gap-2 border-top p-3 no-print">
            <button type="button" class="btn btn-light border" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
            @if ($canEdit)
                <button type="button" class="btn btn-outline-danger" data-record-remove><i class="bi bi-trash me-1"></i> Remove</button>
                <button type="button" class="btn btn-primary fw-semibold" data-record-form="edit"><i class="bi bi-pencil me-1"></i> Edit Record</button>
            @endif
        </div>
    </div>

    @if ($canEdit)
        @include('applicants.partials.record-modals')
    @endif
    @if ($isRegistrar)
        @include('applicants.partials.ask-iato')
    @endif
@endsection

@section('scripts')
    <script>
        const APPLICANTS = @json($rows->values());
        const PAGE_SIZE = 10;
        // How far along each status is (for the timeline). Not Qualified stops at the test result.
        const STATUS_ORDER = ['Pending', 'For Exam', 'Passed', 'Paid', 'Enrolled'];

        const SCOPE = @json($scope);
        const PAGE = @json($page);
        // Sub-levels go in school order, not A–Z
        const GROUP_ORDER = @json($groupOrder ?? []);
        // Applicants starts on the College tab; each Records page's rows use its scope as their unit
        const state = { unit: SCOPE === 'all' ? 'college' : SCOPE, status: 'All', group: '', program: '', grade: '', search: '', sort: 'newest', page: 1 };
        let currentMatches = [];   // every row that passes the filters (all pages), used by Export

        const tbody = document.getElementById('applicantRows');
        const rows = [...tbody.querySelectorAll('.applicant-row')];
        const groupFilter = document.getElementById('groupFilter');
        const programFilter = document.getElementById('programFilter');

        // ---- Filtering, sorting and paging (all in the browser, on the placeholder data) ----

        // '—' (nothing to filter by) is left out of the options
        function fillSelect(select, allLabel, values, order = []) {
            const byOrder = (a, b) => order.indexOf(a) - order.indexOf(b);
            const byName = (a, b) => a.localeCompare(b, undefined, { numeric: true });
            const options = [...new Set(values)].filter(v => v !== '—').sort(order.length ? byOrder : byName);
            select.replaceChildren(new Option(allLabel, ''), ...options.map(v => new Option(v, v)));
        }

        // Headings and filter text: fixed on the Records pages, by tab on Applicants
        function currentLabels() {
            if (PAGE.labels) return PAGE.labels;
            return state.unit === 'college'
                ? { group: 'College', groupAll: 'All Colleges', program: 'Program', programAll: 'All Programs' }
                : { group: 'Level', groupAll: 'All Levels', program: 'Strand / Track', programAll: 'All Strands / Tracks' };
        }

        function setUpFiltersForTab() {
            const inTab = rows.filter(r => r.dataset.unit === state.unit);
            const labels = currentLabels();
            fillSelect(groupFilter, labels.groupAll, inTab.map(r => r.dataset.group), GROUP_ORDER);
            if (labels.programAll) fillSelect(programFilter, labels.programAll, inTab.map(r => r.dataset.program));
            programFilter.classList.toggle('d-none', !labels.programAll || ['college', 'is'].includes(SCOPE));
            document.getElementById('groupHeading').textContent = labels.group;
            document.getElementById('programHeading').textContent = SCOPE === 'is' ? 'Strand' : labels.program;
            state.group = state.program = '';
        }

        function render() {
            const matches = rows.filter(r =>
                r.dataset.unit === state.unit &&
                (state.status === 'All' || r.dataset.examResult === state.status) &&
                (!state.group || r.dataset.group === state.group) &&
                (!state.program || r.dataset.program === state.program) &&
                (!state.grade || r.dataset.grade === state.grade) &&
                r.dataset.search.includes(state.search)
            );

            const compare = {
                status: (a, b) => a.dataset.examResult.localeCompare(b.dataset.examResult),
                experience: (a, b) => (parseFloat(b.dataset.experience) || 0) - (parseFloat(a.dataset.experience) || 0),
                newest: (a, b) => b.dataset.applied.localeCompare(a.dataset.applied),
                oldest: (a, b) => a.dataset.applied.localeCompare(b.dataset.applied),
                az: (a, b) => a.dataset.name.localeCompare(b.dataset.name),
                za: (a, b) => b.dataset.name.localeCompare(a.dataset.name),
            }[state.sort];
            matches.sort(compare);
            currentMatches = matches;

            const pages = Math.max(1, Math.ceil(matches.length / PAGE_SIZE));
            state.page = Math.min(state.page, pages);
            const start = (state.page - 1) * PAGE_SIZE;
            const pageRows = matches.slice(start, start + PAGE_SIZE);

            rows.forEach(r => r.classList.add('d-none'));
            pageRows.forEach(r => { r.classList.remove('d-none'); tbody.insertBefore(r, document.getElementById('noResults')); });
            document.getElementById('noResults').classList.toggle('d-none', matches.length > 0 || APPLICANTS.length === 0);

            document.getElementById('showingText').textContent = matches.length
                ? `Showing ${start + 1}–${start + pageRows.length} of ${matches.length} ${PAGE.noun}${matches.length === 1 ? '' : 's'}`
                : '';
            renderPagination(pages);

            document.getElementById('selectAll').checked = false;
            rows.forEach(r => { r.querySelector('.row-check').checked = false; });
        }

        function renderPagination(pages) {
            const ul = document.getElementById('pagination');
            ul.replaceChildren();
            const item = (label, page, { active = false, disabled = false, aria = '' } = {}) => {
                const li = document.createElement('li');
                li.className = `page-item${active ? ' active' : ''}${disabled ? ' disabled' : ''}`;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-link';
                btn.innerHTML = label;
                if (aria) btn.setAttribute('aria-label', aria);
                btn.addEventListener('click', () => { state.page = page; render(); });
                li.appendChild(btn);
                ul.appendChild(li);
            };
            item('<i class="bi bi-chevron-left"></i>', state.page - 1, { disabled: state.page === 1, aria: 'Previous page' });
            for (let p = 1; p <= pages; p++) item(p, p, { active: p === state.page });
            item('<i class="bi bi-chevron-right"></i>', state.page + 1, { disabled: state.page === pages, aria: 'Next page' });
        }

        // Tabs
        document.querySelectorAll('[data-unit]').forEach(tab => tab.addEventListener('click', () => {
            if (!tab.matches('button')) return;
            document.querySelectorAll('button[data-unit]').forEach(t => {
                t.classList.toggle('active', t === tab);
                t.setAttribute('aria-selected', t === tab);
            });
            state.unit = tab.dataset.unit;
            state.page = 1;
            setUpFiltersForTab();
            render();
        }));

        // Status pills
        document.querySelectorAll('#statusPills button').forEach(pill => pill.addEventListener('click', () => {
            document.querySelectorAll('#statusPills button').forEach(p => {
                p.setAttribute('aria-pressed', p === pill);
                p.classList.toggle('btn-primary', p === pill);
                p.classList.toggle('btn-outline-secondary', p !== pill);
            });
            state.status = pill.dataset.status;
            state.page = 1;
            render();
        }));

        // Search, filters, sort
        document.getElementById('search').addEventListener('input', e => { state.search = e.target.value.trim().toLowerCase(); state.page = 1; render(); });
        document.getElementById('gradeFilter')?.addEventListener('change', e => { state.grade = e.target.value; state.page = 1; render(); });
        groupFilter.addEventListener('change', e => { state.group = e.target.value; state.page = 1; render(); });
        programFilter.addEventListener('change', e => { state.program = e.target.value; state.page = 1; render(); });
        document.getElementById('sort').addEventListener('change', e => { state.sort = e.target.value; render(); });

        const categoryField = @json($categoryField);
        function syncCategoryPills() {
            document.querySelectorAll('[data-category]').forEach(button => {
                const active = button.dataset.category === state[categoryField];
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-light', !active);
                button.setAttribute('aria-pressed', active);
            });
        }
        document.getElementById('categoryPills').addEventListener('click', event => {
            const button = event.target.closest('[data-category]');
            if (!button) return;
            const select = categoryField === 'group' ? groupFilter : programFilter;
            select.value = button.dataset.category;
            state[categoryField] = button.dataset.category;
            state.page = 1;
            syncCategoryPills();
            render();
        });
        [groupFilter, programFilter].forEach(select => select.addEventListener('change', syncCategoryPills));

        // Checkboxes (select all = the rows visible on this page)
        document.getElementById('selectAll').addEventListener('change', e => {
            rows.filter(r => !r.classList.contains('d-none')).forEach(r => { r.querySelector('.row-check').checked = e.target.checked; });
        });

        // ---- Export: the filtered rows (all pages, not just this one) as a CSV file ----
        document.getElementById('exportButton').addEventListener('click', () => {
            const labels = currentLabels();
            // Same columns, in the same order, as the table
            const columns = @json($recordColumns);
            const includeExamFee = @json(! $isRegistrar);
            const header = ['App No.', 'Name', ...Object.values(columns), ...(includeExamFee ? ['Exam Fee'] : []), 'Exam Result', 'Reservation', SCOPE === 'eteeap' ? 'Updated' : 'Date Applied'];
            const lines = currentMatches.map(row => {
                const a = APPLICANTS[row.dataset.index];
                return [a.id, a.name, ...Object.keys(columns).map(key => a[key]), ...(includeExamFee ? [a.exam_fee_label] : []), a.exam_result_label, a.reservation_label, SCOPE === 'eteeap' ? a.updated_label : a.applied_label];
            });
            // Wrap every value in quotes (and double any quotes inside) so commas in names don't break columns
            const csv = [header, ...lines].map(cols => cols.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\r\n');
            const link = document.createElement('a');
            // \uFEFF at the start tells Excel the file is UTF-8 (for ñ, – and ₱)
            link.href = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv' }));
            link.download = SCOPE === 'all' ? `applicants-${state.unit}.csv` : `${SCOPE}-records.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
        });

        // ---- Profile drawer ----

        const drawerEl = document.getElementById('applicantDrawer');
        const drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

        let drawerApplicant = null;   // the applicant shown in the drawer (Edit / Remove use it)

        function openDrawer(a) {
            drawerApplicant = a;
            document.getElementById('drawerInitials').textContent = (a.first[0] + a.last[0]).toUpperCase();
            document.getElementById('drawerName').textContent = a.name;
            document.getElementById('drawerSub').textContent = `${a.id} · ${a.program_label}`;
            const badge = document.getElementById('drawerBadge');
            badge.textContent = a.status_label;
            badge.className = `badge rounded-pill bg-${a.tone}-subtle text-${a.tone}-emphasis`;

            // Every element with data-field="x" shows a[x] ("—" if empty)
            drawerEl.querySelectorAll('[data-field]').forEach(el => {
                const value = a[el.dataset.field];
                el.textContent = value ?? (el.dataset.field === 'exam' ? 'Not yet scheduled' : '—');
            });
            document.getElementById('drawerPayStatus').textContent = a.paid ? 'Confirmed' : 'Pending';

            renderTimeline(a);
            window.onApplicantDrawer?.(a);   // Registrars: Ask IATO box (applicants/partials/ask-iato)
            bootstrap.Tab.getOrCreateInstance(drawerEl.querySelector('[data-bs-target="#tabInfo"]')).show();
            drawer.show();
        }

        // Steps up to the applicant's current status are "done"; later ones are greyed out
        function renderTimeline(a) {
            const failed = a.status === 'Not Qualified';
            const reached = failed ? 2 : STATUS_ORDER.indexOf(a.status);
            const steps = [
                { title: 'Application Submitted', date: a.applied_label, icon: 'bi-file-earmark-plus', tone: 'secondary', done: true },
                { title: 'Entrance Exam Scheduled', date: a.exam ?? 'Not yet scheduled', icon: 'bi-calendar-check', tone: 'warning', done: reached >= 1, note: a.exam ? 'Venue: DLSL Main Campus — Testing Center' : null },
                { title: 'Exam Result', date: reached >= 2 ? (a.test && a.test !== '—' ? a.test : 'Passed') : 'Waiting for the exam', icon: failed ? 'bi-x-circle' : 'bi-patch-check', tone: failed ? 'secondary' : 'primary', done: reached >= 2 },
                { title: 'Reservation Payment', date: a.paid ? a.paid_label : 'Not yet paid', icon: 'bi-cash-coin', tone: 'info', done: reached >= 3 },
                { title: 'Enrolled', date: reached >= 4 ? a.updated_label : 'Not yet enrolled', icon: 'bi-check-circle', tone: 'primary', done: reached >= 4 },
            ];
            // Page-specific changes (Law and iPACE): other titles and one more step
            if (PAGE.examStep) steps[1].title = PAGE.examStep;
            if (PAGE.finalStep) steps[4] = { ...steps[4], title: PAGE.finalStep, date: reached >= 4 ? a.updated_label : 'Pending' };
            if (PAGE.extraStep) {
                const s = PAGE.extraStep;
                steps.splice(1, 0, { title: s.title, icon: s.icon, tone: s.tone, done: reached >= 1,
                    date: reached >= 1 ? s.done.replace('{program}', a.program) : s.waiting });
            }
            const list = document.getElementById('timeline');
            list.replaceChildren();
            steps.forEach((step, i) => {
                const li = document.createElement('li');
                li.className = `d-flex gap-3 ${i < steps.length - 1 ? 'pb-4' : ''} ${step.done ? '' : 'opacity-50'}`;
                li.innerHTML = `
                    <div class="icon-circle rounded-circle bg-${step.tone}-subtle text-${step.tone}-emphasis d-flex align-items-center justify-content-center">
                        <i class="bi ${step.icon}"></i>
                    </div>
                    <div>
                        <div class="fw-bold small"></div>
                        <div class="small text-body-secondary"></div>
                    </div>`;
                li.querySelector('.fw-bold').textContent = step.title;
                li.querySelector('.text-body-secondary').textContent = step.date;
                if (step.note && step.done) {
                    const note = document.createElement('div');
                    note.className = 'small text-body-secondary bg-body-tertiary rounded-2 px-2 py-1 mt-1';
                    note.textContent = step.note;
                    li.lastElementChild.appendChild(note);
                }
                list.appendChild(li);
            });
        }

        rows.forEach(row => {
            const open = () => openDrawer(APPLICANTS[row.dataset.index]);
            row.addEventListener('click', e => {
                if (e.target.closest('.row-check')) return;
                // The pencil opens the edit form instead of the drawer
                if (e.target.closest('[data-row-action="edit"]')) return window.openRecordForm?.('edit', APPLICANTS[row.dataset.index]);
                open();
            });
            row.addEventListener('keydown', e => { if (e.key === 'Enter') open(); });
        });

        setUpFiltersForTab();
        render();

        // Tells the Add / Edit / Remove partial (applicants/partials/record-modals) where things are on this page
        window.RECORD_FORM = {
            items: APPLICANTS, rows, tbody: 'applicantRows', render, drawer, current: () => drawerApplicant,
            labels: currentLabels, groupOrder: GROUP_ORDER, statuses: PAGE.statuses, statusLabels: PAGE.statusLabels, noun: PAGE.noun,
            scope: @json($scope),
            toForm: a => a,   // the rows already use the form's field names
        };

        // ?open=APP-2026-0381 opens that person's drawer (the Applicants master list's "Open in records" links here)
        const linkedApplicant = APPLICANTS.find(a => a.id === new URLSearchParams(location.search).get('open'));
        if (linkedApplicant) openDrawer(linkedApplicant);
    </script>
@endsection
