@extends('layouts.app')

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', auth()->user()->name)[0];

    // The numbers are filled in by the script, so they change after Confirm / Flag
    $stats = [
        ['key' => 'total', 'label' => 'Total Scholars', 'sub' => 'All scholarship types', 'tone' => 'primary'],
        ['key' => 'review', 'label' => 'Discrepancies', 'sub' => 'Need your review', 'tone' => 'danger'],
        ['key' => 'pending', 'label' => 'Pending Cross-Check', 'sub' => 'Awaiting confirmation', 'tone' => 'warning'],
        ['key' => 'confirmed', 'label' => 'Confirmed Match', 'sub' => 'Records verified', 'tone' => 'primary'],
    ];
    $reportTones = ['Unread' => 'danger', 'Awaiting Reply' => 'warning', 'Replied' => 'primary', 'Read' => 'secondary'];
@endphp

@section('title', 'iARIS — LAMP Office')
@section('page-title', "{$greeting}, {$firstName}!")
@section('page-subtitle', "Your scholar cross-check overview for AY {$academicYear}")

@section('content')
    {{-- Stat cards --}}
    <div class="row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] === 'primary' ? 'body' : $stat['tone'] . '-emphasis' }}" data-stat="{{ $stat['key'] }}">0</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Discrepancy alert (hidden by the script when there's nothing to review) --}}
    <div class="alert alert-danger d-flex flex-wrap flex-md-nowrap align-items-center gap-3 rounded-4 mb-4" role="alert" id="reviewAlert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div class="flex-grow-1 small">
            <strong><span id="reviewCount"></span> flagged by IATO.</strong>
            These scholar records don't match LAMP Office records, or are missing from them. Confirm or flag each one below.
        </div>
        <button type="button" class="btn btn-danger btn-sm fw-semibold text-nowrap" id="reviewNow">
            <i class="bi bi-arrow-down me-1"></i> Review Now
        </button>
    </div>

    <div class="row g-4">
        {{-- Scholars that need attention first --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100" id="scholarCard">
                <div class="card-header bg-white border-bottom rounded-top-4 d-flex align-items-start justify-content-between gap-3 px-4 py-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Scholar Records</h2>
                        <p class="small text-body-secondary mb-0">Records that need you are shown first — confirm or flag each one</p>
                    </div>
                    <a href="{{ url('/lamp/scholars') }}" class="small fw-bold text-decoration-none text-nowrap">View All <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                                <th class="text-body-secondary fw-bold">Scholarship</th>
                                <th class="text-body-secondary fw-bold">LAMP Status</th>
                                <th class="pe-4 text-end text-body-secondary fw-bold">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="scholarRows"></tbody>
                    </table>
                </div>
                <div class="card-footer bg-white rounded-bottom-4 small text-body-secondary px-4 py-3" id="showingText"></div>
            </div>
        </div>

        <div class="col-xl-4 d-flex flex-column gap-4">
            {{-- Latest reports --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom rounded-top-4 d-flex align-items-start justify-content-between gap-3 px-4 py-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Reports from IATO</h2>
                        <p class="small text-body-secondary mb-0">Latest reports sent to the LAMP Office</p>
                    </div>
                    <a href="{{ url('/lamp/reports') }}" class="small fw-bold text-decoration-none text-nowrap">All <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @foreach ($reports as $report)
                        @php
                            $tone = $reportTones[$report['status']];
                        @endphp
                        <a href="{{ url('/lamp/reports') }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
                            <div class="icon-circle rounded-3 bg-{{ $report['status'] === 'Unread' ? 'danger' : 'primary' }}-subtle text-{{ $report['status'] === 'Unread' ? 'danger' : 'primary' }}-emphasis d-flex align-items-center justify-content-center">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="small fw-bold">{{ $report['title'] }}</div>
                                <div class="small text-body-secondary">
                                    Sent by IATO · {{ explode(' · ', $report['sent_label'])[0] }}
                                    <span class="badge rounded-pill bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis ms-1">{{ $report['status'] === 'Unread' ? 'New' : $report['status'] }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Notifications --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom rounded-top-4 px-4 py-3">
                    <h2 class="fs-6 fw-bold mb-1">Notifications</h2>
                    <p class="small text-body-secondary mb-0">Updates from IATO Admin</p>
                </div>
                <ul class="list-group list-group-flush rounded-bottom-4">
                    @foreach ($notifications as $note)
                        <li class="list-group-item d-flex gap-3 px-4 py-3">
                            <i class="bi bi-circle-fill text-{{ $note['tone'] }} mt-1" style="font-size: 0.5rem;"></i>
                            <div>
                                <div class="small"><b>{{ $note['bold'] }}</b> {{ $note['text'] }}</div>
                                <div class="small text-body-secondary">{{ $note['at'] }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- One table row, copied by the script for every scholar shown --}}
    <template id="scholarRowTemplate">
        <tr>
            <td class="ps-4">
                <div class="fw-semibold text-nowrap" data-cell="name"></div>
                <div class="small text-body-secondary" data-cell="id"></div>
            </td>
            <td><span data-cell="type"></span></td>
            <td><span data-cell="lamp"></span></td>
            <td class="pe-4" data-cell="actions"></td>
        </tr>
    </template>

    @include('lamp.partials.scholar-modals')
@endsection

@section('scripts')
    <script>
        const SCHOLARS = @json($scholars);
        const SHOW = 6;   // rows on the dashboard; the rest are on the Scholars page

        // Needs-attention order: Discrepancy, Not Found, Pending Check, Confirmed Match
        const ORDER = Object.keys(@json($lampBadges));
        const count = status => SCHOLARS.filter(s => s.lamp_status === status).length;

        function scholarsChanged() {
            const review = count('Discrepancy') + count('Not Found');
            const stats = { total: SCHOLARS.length, review, pending: count('Pending Check'), confirmed: count('Confirmed Match') };
            document.querySelectorAll('[data-stat]').forEach(el => el.textContent = stats[el.dataset.stat]);

            document.getElementById('reviewAlert').classList.toggle('d-none', review === 0);
            document.getElementById('reviewCount').textContent = `${review} ${review === 1 ? 'record' : 'records'}`;

            const sorted = [...SCHOLARS].sort((a, b) => ORDER.indexOf(a.lamp_status) - ORDER.indexOf(b.lamp_status));
            const template = document.getElementById('scholarRowTemplate').content.firstElementChild;
            document.getElementById('scholarRows').replaceChildren(...sorted.slice(0, SHOW).map(s => {
                const row = template.cloneNode(true);
                row.querySelector('[data-cell="name"]').textContent = s.name;
                row.querySelector('[data-cell="id"]').textContent = s.id;
                setTypeBadge(row.querySelector('[data-cell="type"]'), s);
                setLampBadge(row.querySelector('[data-cell="lamp"]'), s.lamp_status);
                row.querySelector('[data-cell="actions"]').appendChild(buildActions(s));
                return row;
            }));
            document.getElementById('showingText').textContent = `Showing ${Math.min(SHOW, SCHOLARS.length)} of ${SCHOLARS.length} scholars`;
        }

        document.getElementById('reviewNow').addEventListener('click', () =>
            document.getElementById('scholarCard').scrollIntoView({ behavior: 'smooth' }));
    </script>
    @include('lamp.partials.scholar-actions-script')
    <script>scholarsChanged();</script>
@endsection
