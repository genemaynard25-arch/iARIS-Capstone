{{-- The program filter at the top of the College pages.
     Dean: pills that reload the page for one program (?program=BSCS). Program chair: their program, locked.
     Usage: @include('college.partials.program-bar', ['route' => 'college.dashboard'])
     Add 'export' => true for an "Export PDF" button (opens the print window; choose "Save as PDF" there). --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-2 px-4 py-3">
        @if ($isChair)
            <span class="small fw-bold text-uppercase tracking-wide text-body-secondary me-1">Your program</span>
            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                <i class="bi bi-lock-fill me-1"></i> {{ $scopeName }}
            </span>
            <span class="small text-body-secondary">You can see figures for this program only.</span>
        @else
            <span class="small fw-bold text-uppercase tracking-wide text-body-secondary me-1">Program</span>
            {{-- Each pill reloads the page for that program (?program=BSCS) --}}
            <nav class="nav nav-pills gap-2" aria-label="Program">
                <a href="{{ route($route) }}" class="nav-link rounded-pill border px-3 py-1 small fw-semibold {{ $selected ? '' : 'active' }}">All Programs</a>
                @foreach ($programs as $code => $program)
                    <a href="{{ route($route, ['program' => $code]) }}" title="{{ $program['name'] }}"
                       class="nav-link rounded-pill border px-3 py-1 small fw-semibold {{ $selected === $code ? 'active' : '' }}">{{ $code }}</a>
                @endforeach
            </nav>
        @endif
        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-3 py-2 ms-sm-auto">
            <i class="bi bi-eye me-1"></i> Read-only view
        </span>
        @if ($export ?? false)
            <button type="button" class="btn btn-primary fw-semibold no-print" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
            </button>
        @endif
    </div>
</div>
