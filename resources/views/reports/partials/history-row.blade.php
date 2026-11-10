<tr data-recipient="{{ $entry['recipient'] }}">
    <td>
        <div class="fw-semibold" data-cell="name">{{ $entry['name'] }}</div>
        <div class="small text-body-secondary" data-cell="meta">{{ $entry['meta'] }}</div>
    </td>
    <td class="text-nowrap" data-cell="recipient">{{ $entry['recipient'] }}</td>
    <td class="text-nowrap" data-cell="period">{{ $entry['period'] }}</td>
    <td><span class="badge bg-body-tertiary text-body-secondary border" data-cell="format">{{ $entry['format'] }}</span></td>
    <td class="text-nowrap" data-cell="by">{{ $entry['by'] }}</td>
    <td class="small text-body-secondary text-nowrap" data-cell="at">{{ $entry['at'] }}</td>
    <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-light border {{ empty($entry['download']) ? 'd-none' : '' }}" href="{{ $entry['download'] ?? '#' }}" data-download-report aria-label="Download saved report"><i class="bi bi-download"></i></a>
        @if(!empty($entry['download']))
            <a class="btn btn-sm btn-light border" href="{{ $entry['download'] }}?preview=1" aria-label="View saved report"><i class="bi bi-eye"></i></a>
        @else
        <button type="button" class="btn btn-sm btn-light border" data-view-report="{{ $entry['report'] }}" title="View report" aria-label="View report"><i class="bi bi-eye"></i></button>
        @endif
    </td>
</tr>
