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
        {{-- TODO: download the saved file once reports are generated for real --}}
        <button type="button" class="btn btn-sm btn-light border" disabled title="Download (coming soon)" aria-label="Download"><i class="bi bi-download"></i></button>
        <button type="button" class="btn btn-sm btn-light border" data-view-report="{{ $entry['report'] }}" title="View sample" aria-label="View sample"><i class="bi bi-eye"></i></button>
    </td>
</tr>
