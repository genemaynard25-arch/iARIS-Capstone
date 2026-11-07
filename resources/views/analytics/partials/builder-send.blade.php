{{-- "Send to" and "Export format", the same in both report builders. $prefix: 'view' or 'tray' --}}
<div class="mb-3">
    <label for="{{ $prefix }}To" class="form-label small fw-bold text-uppercase text-body-secondary">Send to</label>
    <select class="form-select" id="{{ $prefix }}To">
        <option>Keep for myself</option>
        @foreach ($recipients as $recipient)
            <option>{{ $recipient }}</option>
        @endforeach
    </select>
</div>
<div class="small fw-bold text-uppercase text-body-secondary mb-2">Export format</div>
<div class="row g-2">
    @foreach (['PDF' => 'bi-file-earmark-pdf', 'XLSX' => 'bi-file-earmark-excel', 'Print' => 'bi-printer'] as $format => $icon)
        <div class="col-4">
            <input type="radio" class="btn-check" name="{{ $prefix }}Format" id="{{ $prefix }}Format{{ $format }}" value="{{ $format }}" {{ $loop->first ? 'checked' : '' }}>
            <label class="btn btn-outline-primary w-100 py-2" for="{{ $prefix }}Format{{ $format }}">
                <i class="bi {{ $icon }} d-block fs-5"></i> {{ $format === 'XLSX' ? 'Excel' : $format }}
            </label>
        </div>
    @endforeach
</div>
