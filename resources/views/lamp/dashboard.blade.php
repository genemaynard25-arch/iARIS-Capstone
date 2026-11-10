@extends('layouts.app')

{{--
    LAMP Office Dashboard (LampController::dashboard): the scholarship applicants IATO shares (they answered Yes on
    the application), four numbers, the applicants waiting for LAMP's check, and updates from IATO.
    Applicants and saved LAMP checks are loaded from the database.
--}}

@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', auth()->user()->name)[0];

    // The numbers are filled in by the script, so they change after a check
    $stats = [
        ['key' => 'apps', 'label' => 'Scholarship applicants', 'sub' => 'Answered Yes on the application', 'tone' => 'success'],
        ['key' => 'passed', 'label' => 'Passed the exam', 'sub' => 'Pass or Reconsidered', 'tone' => 'info'],
        ['key' => 'yes', 'label' => 'Confirmed scholars', 'sub' => 'Checked by LAMP', 'tone' => 'primary'],
        ['key' => 'wait', 'label' => 'Waiting for LAMP', 'sub' => 'Passed, not yet checked', 'tone' => 'warning'],
    ];
@endphp

@section('title', 'iARIS — LAMP Office')
@section('page-breadcrumb-root', 'LAMP Office')
@section('page-breadcrumb', 'Dashboard')
@section('page-title', "{$greeting}, {$firstName}!")
@section('page-subtitle', "Scholarship applicants from IATO for AY {$academicYear}")

@section('content')
    <div class="row row-cols-2 row-cols-xl-4 g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $stat['tone'] }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold text-{{ $stat['tone'] }}-emphasis" data-stat="{{ $stat['key'] }}">0</div>
                        <div class="small text-body-secondary">{{ $stat['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        {{-- Who is waiting for LAMP --}}
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom rounded-top-4 d-flex align-items-start justify-content-between gap-3 px-4 py-3">
                    <div>
                        <h2 class="fs-6 fw-bold mb-1">Waiting for your check</h2>
                        <p class="small text-body-secondary mb-0">Applicants who passed the exam and applied for a scholarship</p>
                    </div>
                    <a href="{{ url('/lamp/scholars') }}" class="small fw-bold text-decoration-none text-nowrap">See all applicants <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase">
                                <th class="ps-4 text-body-secondary fw-bold">Applicant</th>
                                <th class="text-body-secondary fw-bold">Unit</th>
                                <th class="text-body-secondary fw-bold">Exam result</th>
                                <th class="text-body-secondary fw-bold">LAMP check</th>
                                <th class="pe-4"></th>
                            </tr>
                        </thead>
                        <tbody id="waitingRows"></tbody>
                    </table>
                </div>
                <div class="card-footer bg-white rounded-bottom-4 small text-body-secondary px-4 py-3 d-none" id="waitingMore"></div>
            </div>
        </div>

        {{-- Updates from IATO --}}
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom rounded-top-4 px-4 py-3">
                    <h2 class="fs-6 fw-bold mb-1">Updates from IATO</h2>
                    <p class="small text-body-secondary mb-0">New exam results for scholarship applicants</p>
                </div>
                <div class="list-group list-group-flush rounded-bottom-4" id="updates"></div>
            </div>
        </div>
    </div>

    @include('lamp.partials.check')
@endsection

@section('scripts')
    @include('lamp.partials.check-script')
    <script>
        const REPORTS = @json($reports);
        const LAST_UPDATE = @json($lastUpdate);
        const SHOWN = 8;   // the table shows the first few; "See all applicants" has the rest
        const $ = id => document.getElementById(id);

        function render() {
            const t = LAMP.totals();
            document.querySelectorAll('[data-stat]').forEach(e => e.textContent = t[e.dataset.stat].toLocaleString('en-US'));

            // Waiting for a check, and the ones LAMP just checked (until IATO confirms)
            const rows = LAMP.people.filter(x => x.lamp === 'wait' || ((x.lamp === 'yes' || x.lamp === 'no') && x.at));
            $('waitingRows').innerHTML = rows.length ? rows.slice(0, SHOWN).map((x, i) => `<tr data-i="${LAMP.people.indexOf(x)}" style="cursor: pointer;">
                    <td class="ps-4">${LAMP.nameCell(x)}</td>
                    <td>${LAMP.esc(x.unit)}<div class="small text-body-secondary">${LAMP.esc(x.program)}</div></td>
                    <td>${LAMP.result(x)}</td>
                    <td>${LAMP.label(x)}</td>
                    <td class="pe-4 text-end"><button type="button" class="btn btn-sm ${x.lamp === 'wait' ? 'btn-primary' : 'btn-light border'} fw-semibold">${x.lamp === 'wait' ? 'Check' : 'View'}</button></td>
                </tr>`).join('')
                : `<tr><td colspan="5" class="text-center text-body-secondary py-5">${LAMP.people.length ? 'Nothing waiting. Every applicant who passed has been checked.' : 'No scholarship applicants yet. They appear here once IATO imports applicants who answered Yes to the scholarship question.'}</td></tr>`;
            $('waitingRows').querySelectorAll('tr[data-i]').forEach(tr => tr.addEventListener('click', () => LAMP.open(LAMP.people[tr.dataset.i])));
            const more = $('waitingMore');
            more.classList.toggle('d-none', rows.length <= SHOWN);
            more.textContent = `Showing ${SHOWN} of ${rows.length}.`;

            // Updates: the exam results, the newest report, and how it works
            const item = (icon, tone, text, when) => `<div class="list-group-item d-flex gap-3 px-4 py-3"><i class="bi ${icon} text-${tone} fs-5"></i><div class="small"><div>${text}</div><div class="text-body-secondary mt-1">${when}</div></div></div>`;
            const latest = REPORTS[0];
            $('updates').innerHTML =
                item('bi-clipboard-data', 'info', t.wait ? `New exam results for scholarship applicants are in. ${t.wait} passed and ${t.wait === 1 ? 'is' : 'are'} waiting for your check.` : 'No one is waiting for your check right now.', LAST_UPDATE || '—')
                + (latest ? item('bi-file-earmark-text', 'success', `New report from IATO: ${LAMP.esc(latest.title)}.`, LAMP.esc(latest.date)) : '')
                + item('bi-info-circle', 'secondary', 'Only applicants who pass the entrance exam reach you. Those still waiting for the exam show as Not eligible yet.', 'How it works');
        }
        LAMP.onChange(render);
        render();
    </script>
@endsection
