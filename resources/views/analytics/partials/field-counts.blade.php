@php
    $fieldTotal = $charts['funnel']['values'][0] ?? 0;
    $fieldPercent = fn ($count) => number_format($fieldTotal > 0 ? $count / $fieldTotal * 100 : 0, 1, '.', '');
    $reservedIndex = array_search('Reserved', $charts['funnel']['labels'], true);
    $fieldCounts = [
        ['label' => 'Male', 'value' => $yesNoBreakdown['male'], 'icon' => 'bi-gender-male', 'tone' => 'blue'],
        ['label' => 'Female', 'value' => $yesNoBreakdown['female'], 'icon' => 'bi-gender-female', 'tone' => 'red'],
        ['label' => 'Applying for scholarship', 'value' => $yesNoBreakdown['scholars'], 'icon' => 'bi-award', 'tone' => 'purple'],
        ['label' => 'Paid entrance exam', 'value' => $yesNoBreakdown['paidExam'], 'icon' => 'bi-cash-coin', 'tone' => 'green'],
        ['label' => 'Paid reservation', 'value' => $reservedIndex === false ? 0 : $charts['funnel']['values'][$reservedIndex], 'icon' => 'bi-bookmark-check', 'tone' => 'blue'],
        ['label' => 'NOA issued', 'value' => $yesNoBreakdown['noaIssued'], 'icon' => 'bi-patch-check', 'tone' => 'green'],
        ['label' => 'PWD', 'value' => $yesNoBreakdown['pwd'], 'icon' => 'bi-person-wheelchair', 'tone' => 'orange'],
        ['label' => 'SSEN', 'value' => $yesNoBreakdown['ssen'], 'icon' => 'bi-person-badge', 'tone' => 'orange'],
    ];
@endphp
<section class="ar-field-card card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="fieldCountTitle">
    <div class="card-body p-4">
        <div class="ar-card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div><h2 id="fieldCountTitle">Applicant field counts</h2><p>Read automatically from the selected applicant scope · <a href="{{ url('/import') }}">Import Data</a></p></div>
            <div class="ar-card-controls d-flex flex-wrap gap-2">
                @if ($hasReports)
                    <button type="button" class="btn btn-sm btn-light border" data-add-report="Applicant field counts" aria-pressed="false"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add to report</button>
                @endif
                <div class="btn-group" role="group" aria-label="Field counts display">
                    <button type="button" class="btn btn-sm btn-light border active" data-field-view="cards" aria-pressed="true"><i class="bi bi-grid me-1" aria-hidden="true"></i> Cards</button>
                    <button type="button" class="btn btn-sm btn-light border" data-field-view="table" aria-pressed="false"><i class="bi bi-table me-1" aria-hidden="true"></i> Table</button>
                </div>
                <button type="button" class="btn btn-sm btn-light border" id="exportFieldCounts"><i class="bi bi-download me-1" aria-hidden="true"></i> CSV</button>
            </div>
        </div>
        <div class="ar-field-grid" id="fieldCountCards">
            @foreach ($fieldCounts as $field)
                <div class="ar-field-tile ar-field-{{ $field['tone'] }}" data-field-name="{{ $field['label'] }}" data-field-value="{{ $field['value'] }}" data-field-percent="{{ $fieldPercent($field['value']) }}">
                    <div class="ar-field-icon"><i class="bi {{ $field['icon'] }}" aria-hidden="true"></i></div>
                    <div class="ar-field-details">
                        <strong>{{ number_format($field['value']) }}</strong>
                        <span>{{ $field['label'] }}</span>
                        <small>{{ $fieldPercent($field['value']) }}% of applicants</small>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="table-responsive d-none" id="fieldCountTable">
            <table class="table align-middle mb-0"><thead><tr><th scope="col">Imported field</th><th scope="col" class="text-end">Selected scope</th><th scope="col" class="text-end">% of applicants</th></tr></thead><tbody>
                @foreach ($fieldCounts as $field)
                    <tr><th scope="row">{{ $field['label'] }}</th><td class="text-end">{{ number_format($field['value']) }}</td><td class="text-end" data-field-percent>{{ $fieldPercent($field['value']) }}%</td></tr>
                @endforeach
            </tbody></table>
        </div>
    </div>
</section>
