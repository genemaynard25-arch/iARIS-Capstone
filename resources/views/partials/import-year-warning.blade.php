<div class="alert alert-warning" role="alert">
    <strong>Check import batch #{{ $warning['batch'] }}:</strong> {{ $warning['file'] }} declares SY {{ $warning['declared'] }}, but its {{ number_format($warning['count']) }} active records are assigned to {{ $warning['assigned'] }}.
    @if($warning['matching'])
        {{ number_format($warning['matching']) }} application numbers also exist in {{ $warning['declared'] }}@if($warning['target_batches']) (batch {{ implode(', ', array_map(fn ($id) => '#'.$id, $warning['target_batches'])) }})@endif. This may be an extra upload of the same file.
    @endif
    <a class="alert-link" href="{{ url('/import/'.$warning['batch']) }}">Review and correct this batch (#{{ $warning['batch'] }})</a>
</div>
