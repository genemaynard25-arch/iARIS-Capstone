@php($tone = $changeTypes[$entry['type']] ?? 'secondary')
<tr data-type="{{ $entry['type'] }}">
    <td>
        <div class="fw-semibold" data-cell="action">{{ $entry['action'] }}</div>
        <div class="small text-body-secondary" data-cell="meta">{{ $entry['meta'] }}</div>
    </td>
    <td class="text-nowrap" data-cell="affected">{{ $entry['affected'] }}</td>
    <td class="text-nowrap" data-cell="module">{{ $entry['module'] }}</td>
    <td><span class="badge rounded-pill bg-{{ $tone }}-subtle text-{{ $tone }}-emphasis" data-cell="type">{{ $entry['type'] }}</span></td>
    <td class="text-nowrap" data-cell="by">{{ $entry['by'] }}</td>
    <td class="small text-body-secondary text-nowrap" data-cell="at">{{ $entry['at'] }}</td>
</tr>
