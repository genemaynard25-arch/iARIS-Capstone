@extends('layouts.app')

{{--
    My Submissions (SubmissionsController): the staff member's edits and imports and what the
    Super Admin did with them. FRONT END ONLY: Resend and Withdraw change this page until reload.
--}}

@section('title', 'iARIS — My Submissions')
@section('page-title', 'My Submissions')
@section('page-subtitle', 'Your edits and imports, and where each one is in the Review Queue')

@php
    // status => [label, Bootstrap colour, icon]
    $statuses = [
        'sent' => ['Sent back', 'danger', 'bi-arrow-return-left'],
        'waiting' => ['Waiting for review', 'warning', 'bi-hourglass-split'],
        'approved' => ['Approved', 'primary', 'bi-check-circle-fill'],
    ];
    // Sent back first: those need you
    $order = array_keys($statuses);
    $items = collect($items)->sortBy(fn ($i) => array_search($i['status'], $order))->values();
@endphp

@section('content')
    <div class="alert alert-light border d-flex align-items-start gap-2 rounded-4 small mb-4">
        <i class="bi bi-info-circle text-primary mt-1"></i>
        <div>Your edits and imports wait in the Review Queue until the Super Admin approves them. Until then, Deans, the Registrar, LAMP, and leadership still see the old data.</div>
    </div>

    {{-- Counts (the script keeps them up to date) --}}
    <div class="row row-cols-1 row-cols-sm-3 g-3 mb-4">
        @foreach ($statuses as $key => [$label, $tone])
            <div class="col">
                <div class="card border-0 border-top border-3 border-{{ $tone }} shadow-sm rounded-4 h-100">
                    <div class="card-body">
                        <div class="small fw-bold text-uppercase tracking-wide text-body-secondary mb-2">{{ $label }}</div>
                        <div class="fs-3 fw-bold text-{{ $tone }}-emphasis" data-count="{{ $key }}">0</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-3 border-bottom" id="statusPills" role="group" aria-label="Filter by status">
            <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-primary" data-filter="">All</button>
            @foreach ($statuses as $key => [$label])
                <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold btn-outline-secondary" data-filter="{{ $key }}">{{ $label }} <span class="ms-1" data-count="{{ $key }}"></span></button>
            @endforeach
        </div>

        <div class="list-group list-group-flush rounded-bottom-4" id="submissionList">
            @foreach ($items as $item)
                @php([$label, $tone, $icon] = $statuses[$item['status']])
                <div class="list-group-item px-4 py-3" data-id="{{ $item['id'] }}" data-status="{{ $item['status'] }}">
                    <div class="d-flex flex-wrap align-items-start gap-3">
                        <div class="rounded-3 bg-body-tertiary text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                            <i class="bi {{ $item['type'] === 'import' ? 'bi-cloud-arrow-up' : 'bi-pencil' }} fs-5"></i>
                        </div>
                        <div class="flex-grow-1" style="min-width: 220px;">
                            <div class="fw-bold">{{ $item['title'] }} · {{ $item['subject'] }}</div>
                            <div class="small text-body-secondary">
                                {{ $item['id'] }} · {{ $item['ref'] }} · {{ $item['unit'] }} · Sent {{ \Illuminate\Support\Carbon::parse($item['at'])->format('M j, g:i A') }}
                            </div>
                        </div>
                        <span class="badge rounded-pill bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis" data-badge><i class="bi {{ $icon }} me-1"></i>{{ $label }}</span>
                    </div>

                    <div class="ms-lg-5 ps-lg-3 mt-3">
                        {{-- What was sent --}}
                        @if ($item['type'] === 'import')
                            <div class="d-flex flex-wrap gap-2 small mb-2">
                                @foreach (['total' => 'rows', 'new' => 'new', 'updated' => 'updated', 'errors' => 'with errors (skipped)'] as $key => $word)
                                    <span class="rounded-pill bg-body-tertiary border px-3 py-1"><b>{{ $item['summary'][$key] }}</b> {{ $word }}</span>
                                @endforeach
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm small mb-2" style="max-width: 560px;">
                                    <thead><tr class="text-body-secondary"><th class="fw-semibold">Field</th><th class="fw-semibold">Before</th><th class="fw-semibold">Your change</th></tr></thead>
                                    <tbody>
                                        @foreach ($item['changes'] as [$field, $before, $after])
                                            <tr><td>{{ $field }}</td><td class="text-body-secondary">{{ $before }}</td><td class="fw-semibold">{{ $after }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if ($item['note'] ?? '')
                            <div class="small text-body-secondary mb-2"><i class="bi bi-chat-left-text me-1"></i> Your note: {{ $item['note'] }}</div>
                        @endif

                        {{-- What happened to it --}}
                        <div data-outcome>
                            @if ($item['status'] === 'sent')
                                <div class="alert alert-danger small py-2 px-3 mb-2">
                                    <div class="fw-bold"><i class="bi bi-arrow-return-left me-1"></i> {{ $item['reviewed'] }}</div>
                                    {{ $item['reason'] }}
                                </div>
                                <button type="button" class="btn btn-sm btn-primary fw-semibold" data-action="resend"><i class="bi bi-send me-1"></i> Fix and resend</button>
                            @elseif ($item['status'] === 'approved')
                                <div class="small text-primary-emphasis"><i class="bi bi-check-circle-fill me-1"></i> {{ $item['reviewed'] }}. Everyone can see it now.</div>
                            @else
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="small text-body-secondary"><i class="bi bi-eye-slash me-1"></i> Not visible to other users yet.</span>
                                    <button type="button" class="btn btn-sm btn-light border fw-semibold" data-action="withdraw"><i class="bi bi-x-lg me-1"></i> Withdraw</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="text-center text-body-secondary py-5 d-none" id="noResults">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i> Nothing here.
        </div>
    </div>

    {{-- Fix and resend --}}
    <div class="modal fade" id="resendModal" tabindex="-1" aria-labelledby="resendTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4 overflow-hidden" id="resendForm" novalidate>
                <div class="modal-header bg-iaris text-white border-0 p-4">
                    <div>
                        <h3 class="modal-title fs-5 fw-bold" id="resendTitle">Fix and resend</h3>
                        <p class="small text-white-50 mb-0" id="resendSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger small py-2 px-3" id="resendReason"></div>
                    <label for="resendNote" class="form-label small fw-bold text-uppercase text-body-secondary">What did you fix?</label>
                    <textarea class="form-control" id="resendNote" rows="3" placeholder="e.g. Changed the amount to ₱1,500.00 after checking the receipt." required></textarea>
                    <div class="invalid-feedback">Say what you changed, so the Super Admin can check it quickly.</div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light border flex-fill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold flex-fill"><i class="bi bi-send me-1"></i> Resend for review</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-0 p-3">
        <div class="toast align-items-center border-0 text-bg-dark" id="submissionToast" role="status" aria-live="polite">
            <div class="d-flex">
                <div class="toast-body" id="submissionToastText"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const STATUSES = @json($statuses);
        const list = document.getElementById('submissionList');
        let filter = '';
        let resending = null;   // the item in the Fix and resend pop-up

        function toast(text) {
            document.getElementById('submissionToastText').textContent = text;
            bootstrap.Toast.getOrCreateInstance(document.getElementById('submissionToast')).show();
        }

        // Counts on the cards, the pills and the sidebar; then show only the chosen status
        function refresh() {
            const items = [...list.children];
            Object.keys(STATUSES).forEach(key => document.querySelectorAll(`[data-count="${key}"]`)
                .forEach(el => el.textContent = items.filter(i => i.dataset.status === key).length));
            const nav = document.getElementById('nav-count-my-submissions');
            if (nav) {
                const sent = items.filter(i => i.dataset.status === 'sent').length;
                nav.textContent = sent;
                nav.classList.toggle('d-none', sent === 0);
            }
            let shown = 0;
            items.forEach(i => {
                const show = !filter || i.dataset.status === filter;
                i.classList.toggle('d-none', !show);
                shown += show;
            });
            document.getElementById('noResults').classList.toggle('d-none', shown > 0);
        }

        document.querySelectorAll('#statusPills [data-filter]').forEach(pill => pill.addEventListener('click', () => {
            document.querySelectorAll('#statusPills [data-filter]').forEach(p => {
                p.classList.toggle('btn-primary', p === pill);
                p.classList.toggle('btn-outline-secondary', p !== pill);
            });
            filter = pill.dataset.filter;
            refresh();
        }));

        // Put an item in a new status: new badge and a one-line outcome
        function setStatus(item, status, outcome) {
            const [label, tone, icon] = STATUSES[status];
            item.dataset.status = status;
            const badge = item.querySelector('[data-badge]');
            badge.className = `badge rounded-pill bg-${tone}-subtle text-${tone}-emphasis`;
            badge.innerHTML = `<i class="bi ${icon} me-1"></i>`;
            badge.append(label);
            const box = item.querySelector('[data-outcome]');
            box.replaceChildren();
            const line = document.createElement('div');
            line.className = 'small text-body-secondary';
            line.textContent = outcome;
            box.appendChild(line);
            refresh();
        }

        list.addEventListener('click', e => {
            const button = e.target.closest('[data-action]');
            if (!button) return;
            const item = button.closest('[data-id]');
            if (button.dataset.action === 'withdraw') {
                if (!confirm('Withdraw this submission? It leaves the Review Queue and nothing changes.')) return;
                item.remove();
                refresh();
                toast(`${item.dataset.id} withdrawn. (Demo only — not saved yet.)`);
            } else {
                resending = item;
                document.getElementById('resendSubtitle').textContent = item.querySelector('.fw-bold').textContent;
                document.getElementById('resendReason').textContent = item.querySelector('.alert').textContent.trim().replace(/\s+/g, ' ');
                document.getElementById('resendForm').reset();
                document.getElementById('resendNote').classList.remove('is-invalid');
                bootstrap.Modal.getOrCreateInstance(document.getElementById('resendModal')).show();
            }
        });

        document.getElementById('resendForm').addEventListener('submit', e => {
            e.preventDefault();
            const note = document.getElementById('resendNote');
            note.classList.toggle('is-invalid', !note.value.trim());
            if (!note.value.trim()) return note.focus();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('resendModal')).hide();
            setStatus(resending, 'waiting', `Resent just now. Your fix: ${note.value.trim()}`);
            toast(`${resending.dataset.id} is back in the Review Queue. (Demo only — not saved yet.)`);
        });
        document.getElementById('resendNote').addEventListener('input', e => e.target.classList.remove('is-invalid'));

        refresh();
    </script>
@endsection
