@php
    $pickerStart = now()->month >= 6 ? now()->year : now()->year - 1;
    $pickerOptions = collect(\App\Support\AdmissionStats::schoolYears())
        ->merge(array_map(fn ($year) => $year . '-' . ($year + 1), range($pickerStart - 3, $pickerStart + 1)))
        ->filter(fn ($year) => preg_match('/^[0-9]{4}-[0-9]{4}$/', (string) $year) && (int) substr($year, 5) === (int) substr($year, 0, 4) + 1)
        ->unique()->sortDesc()->values();
@endphp
<div data-school-year-picker style="max-width: 280px;">
    <select id="{{ $id }}Choice" class="form-select d-none" data-school-year-choice aria-label="{{ $label }}">
        @foreach($pickerOptions as $year)
            <option value="{{ $year }}" @selected($value === $year)>{{ $year }}</option>
        @endforeach
        <option value="custom" @selected(!$pickerOptions->contains($value))>Custom school year…</option>
    </select>
    <input type="text" class="form-control" id="{{ $id }}" name="school_year" value="{{ $value }}"
        pattern="[0-9]{4}-[0-9]{4}" placeholder="YYYY-YYYY" required data-school-year-input aria-label="Custom school year">
    <div class="form-text" data-school-year-help>Enter consecutive years, for example 2025-2026.</div>
</div>
@once
    <script src="{{ asset('js/school-year-picker.js') }}" defer></script>
@endonce
