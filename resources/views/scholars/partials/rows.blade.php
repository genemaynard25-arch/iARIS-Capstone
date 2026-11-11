                    @foreach ($scholars as $i => $s)
                        @php
                            [$lampTone, $lampIcon] = $lampBadges[$s['lamp']];
                        @endphp
                        <tr class="scholar-row" style="cursor: pointer;" tabindex="0"
                            data-unit="{{ $s['unit'] }}" data-index="{{ $i }}" data-status="{{ $s['status'] }}" data-exam="{{ $s['exam'] }}"
                            data-scholarship="{{ $s['scholarship_status'] }}" data-lamp="{{ $s['lamp'] }}"
                            data-search="{{ strtolower($s['name'] . ' ' . $s['id']) }}"
                            data-applied="{{ $s['applied'] }}" data-name="{{ $s['name'] }}">
                            <td class="ps-4">
                                <div class="fw-semibold text-nowrap">{{ $s['name'] }}</div>
                                <div class="small text-body-secondary">{{ $s['id'] }}</div>
                            </td>
                            <td>{{ $s['unit'] }}</td>
                            <td>{{ $s['program'] }}</td>
                            <td><span class="badge rounded-pill bg-{{ $s['exam_tone'] }}-subtle text-{{ $s['exam_tone'] }}-emphasis">{{ $s['exam_label'] }}</span></td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $lampTone }}-subtle text-{{ $lampTone }}-emphasis"><i class="bi {{ $lampIcon }} me-1" aria-hidden="true"></i>{{ $s['lamp'] }}</span>
                                {{-- "Waiting for LAMP" / "LAMP replied" / "Resolved" (filled in by the Ask LAMP script) --}}
                                <div data-thread-tag></div>
                            </td>
                            <td class="small text-body-secondary text-nowrap">{{ $s['applied_label'] }}</td>
                            <td class="pe-4 text-end text-nowrap">
                                @if ($canEdit)
                                    <button type="button" class="btn btn-sm btn-light border" title="View details" aria-label="View {{ $s['name'] }}"><i class="bi bi-eye" aria-hidden="true"></i></button>
                                    <button type="button" class="btn btn-sm btn-light border" data-row-action="edit" title="Edit scholar" aria-label="Edit {{ $s['name'] }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                                @else
                                    <span class="btn btn-sm btn-light border"><i class="bi bi-chevron-right" aria-hidden="true"></i></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr id="noResults" class="d-none">
                        <td colspan="7" class="text-center text-body-secondary py-5">
                            <i class="bi bi-search fs-3 d-block mb-2" aria-hidden="true"></i>
                            No scholars match your search or filters.
                        </td>
                    </tr>
