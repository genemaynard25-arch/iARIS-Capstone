@extends('layouts.app')

{{--
    Security (ToicController::security): MFA, failed sign-ins, locked accounts, encryption, audit log health.
    From the database: accounts and MFA per role, the number of audit log entries.
    Sample until recorded: failed sign-ins, locked accounts. "Check now" is front end only.
--}}

@section('title', 'iARIS — Security')
@section('page-title', 'Security')
@section('page-subtitle', 'Sign-ins, two-factor authentication, encryption, and audit log health')

@section('content')
    @include('toic.partials.privacy')

    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
        @foreach ([
            ['Accounts with MFA', "{$withMfa} of {$accounts}", ($accounts - $withMfa) . ' not set up yet', 'primary'],
            ['Failed sign-ins · 7 days', '14', 'Down from 22', 'warning'],
            ['Locked accounts', '1', 'Unlocks after 30 minutes', 'danger'],
            ['Encryption', 'AES-256 · TLS 1.3', 'At rest and in transit', 'success'],
        ] as [$label, $value, $note, $tone])
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $tone }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                        <div class="fs-4 fw-bold">{{ $value }}</div>
                        <div class="small text-body-secondary">{{ $note }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- MFA by role --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">MFA by role</h2>
                    <span class="small text-body-secondary">Roles come from IATO's Access Control</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light"><tr><th class="ps-4 text-body-secondary">Role</th><th class="text-end text-body-secondary">Accounts</th><th class="text-end text-body-secondary">With MFA</th><th class="pe-4 text-body-secondary">Data scope</th></tr></thead>
                        <tbody>
                            @foreach ($roles as $r)
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $r['label'] }}</td>
                                    <td class="text-end">{{ $r['accounts'] }}</td>
                                    <td class="text-end"><span class="{{ $r['mfa'] < $r['accounts'] ? 'text-danger fw-bold' : '' }}">{{ $r['mfa'] }}</span></td>
                                    <td class="pe-4 text-body-secondary">{{ $r['scope'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Security controls --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 px-4 pt-4 pb-2"><h2 class="fs-6 fw-bold mb-0">Security controls</h2></div>
                <div class="list-group list-group-flush rounded-bottom-4">
                    @foreach ([
                        ['bi-people', 'Role-based access', count($roles) . ' roles. Each sees only its own level or unit. Set by the Super Admin in Access Control.'],
                        ['bi-shield-check', 'Two-factor authentication', 'Required for every account. Codes from an authenticator app.'],
                        ['bi-lock', 'Encryption', 'Database and backups encrypted with AES-256. All connections use TLS 1.3.'],
                        ['bi-clock-history', 'Timestamps on every change', 'Each record keeps who changed it and when.'],
                        ['bi-journal-check', 'Audit logs', 'Cannot be edited or deleted early. Checked nightly for gaps.'],
                        ['bi-hourglass-split', 'Session timeout', 'Signed out after 30 minutes without activity. Locked after 5 wrong passwords.'],
                    ] as [$icon, $title, $text])
                        <div class="list-group-item d-flex gap-3 px-4 py-2">
                            <i class="bi {{ $icon }} text-primary"></i>
                            <div class="small"><div class="fw-semibold">{{ $title }}</div><div class="text-body-secondary">{{ $text }}</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Failed sign-ins --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Failed sign-ins · last 7 days</h2>
                    <div id="failedDownload"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small" id="failedTable">
                        <thead class="table-light"><tr><th class="ps-4 text-body-secondary">When</th><th class="text-body-secondary">Account</th><th class="text-body-secondary">Reason</th><th class="pe-4 text-body-secondary">Result</th></tr></thead>
                        <tbody>
                            @foreach ($failed as [$when, $account, $reason, $result])
                                <tr>
                                    <td class="ps-4 text-nowrap">{{ $when }}</td>
                                    <td>{{ $account }}</td>
                                    <td>{{ $reason }}</td>
                                    <td class="pe-4"><span class="badge rounded-pill {{ preg_match('/Blocked|Locked/', $result) ? 'bg-danger-subtle text-danger-emphasis' : 'bg-body-tertiary text-body-secondary' }}">{{ $result }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Audit log health --}}
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
                    <h2 class="fs-6 fw-bold mb-0">Audit log health</h2>
                    <button type="button" class="btn btn-sm btn-light border fw-semibold" id="checkNow"><i class="bi bi-arrow-repeat me-1"></i> Check now</button>
                </div>
                <div class="card-body px-4">
                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-check-circle-fill text-success fs-4"></i>
                        <div class="small"><div class="fw-semibold" id="auditVerified">Verified Jun 12, 2026 · 6:00 AM</div><div class="text-body-secondary">{{ number_format($auditEntries) }} {{ \Illuminate\Support\Str::plural('entry', $auditEntries) }} · no gaps · no edits found</div></div>
                    </div>
                    <div class="d-flex gap-3 mb-3">
                        <i class="bi bi-archive text-primary fs-5"></i>
                        <div class="small"><div class="fw-semibold">Kept for at least 5 years</div><div class="text-body-secondary">The Super Admin sets the period in Settings. Entries can't be deleted early.</div></div>
                    </div>
                    <div class="d-flex gap-3">
                        <i class="bi bi-eye-slash text-body-secondary fs-5"></i>
                        <div class="small"><div class="fw-semibold">TOIC sees the health check only</div><div class="text-body-secondary">The entries themselves name applicants, so only IATO can read them.</div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('toic.partials.kit')
@endsection

@section('scripts')
    <script>
        document.getElementById('failedDownload').appendChild(downloadMenu(() => ({
            title: 'Failed sign-ins · last 7 days', sub: 'iARIS security · emails partly hidden', ...tableData(document.getElementById('failedTable')),
        })));
        document.getElementById('checkNow').addEventListener('click', () => {
            const label = document.getElementById('auditVerified');
            label.textContent = 'Checking…';
            setTimeout(() => {
                label.textContent = 'Verified ' + IARIS_ISSUES.now();
                toast('Audit log checked: no gaps, no edits. (Demo only.)');
            }, 900);
        });
    </script>
@endsection
