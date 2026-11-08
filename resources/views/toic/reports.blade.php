@extends('layouts.app')

{{-- Reports (ToicController::reports): four system reports with no applicant data, each downloadable as PDF, Excel or CSV --}}

@section('title', 'iARIS — Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'System reports for TOIC. They contain no applicant data.')

@section('content')
    @include('toic.partials.privacy')

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 rounded-top-4 d-flex justify-content-between align-items-center px-4 pt-4 pb-2">
            <h2 class="fs-6 fw-bold mb-0">System reports</h2>
            <span class="small text-body-secondary">Download as PDF, Excel, or CSV</span>
        </div>
        <div class="list-group list-group-flush rounded-bottom-4">
            @foreach ($reports as $i => [$title, $icon, $text])
                <div class="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                    <span class="rounded-3 bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;"><i class="bi {{ $icon }}"></i></span>
                    <div class="flex-grow-1"><div class="fw-semibold small">{{ $title }}</div><div class="small text-body-secondary">{{ $text }}</div></div>
                    <div data-report="{{ $i }}"></div>
                </div>
            @endforeach
        </div>
    </div>

    @include('toic.partials.kit')
@endsection

@section('scripts')
    <script>
        // [title, icon, description, header, rows]
        const REPORTS = @json($reports);
        document.querySelectorAll('[data-report]').forEach(slot => {
            const [title, , , head, rows] = REPORTS[+slot.dataset.report];
            slot.appendChild(downloadMenu(() => ({ title, head, rows })));
        });
    </script>
@endsection
