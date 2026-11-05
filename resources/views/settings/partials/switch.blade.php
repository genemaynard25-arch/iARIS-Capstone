{{-- One on/off setting. Usage: @include('settings.partials.switch', ['name' => 'weekly', 'label' => '…', 'description' => '…', 'checked' => true, 'disabled' => false]) --}}
<div class="d-flex align-items-start justify-content-between gap-3 py-3 border-bottom">
    <label class="flex-grow-1" for="switch-{{ $name }}">
        <span class="d-block fw-semibold">{{ $label }}</span>
        <span class="d-block small text-body-secondary">{{ $description }}</span>
    </label>
    <div class="form-check form-switch m-0 fs-5">
        <input class="form-check-input" type="checkbox" role="switch" name="{{ $name }}" id="switch-{{ $name }}" @checked($checked) @disabled($disabled ?? false)>
    </div>
</div>
