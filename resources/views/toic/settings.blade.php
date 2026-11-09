@extends('layouts.app')
@section('title', 'iARIS — TOIC Settings')
@section('body-class', 'toic-settings-page')
@section('page-title', 'Settings')
@section('page-subtitle', 'Your office account and what TOIC is alerted about')
@push('styles')
    <link href="{{ asset('css/toic-settings.css') }}" rel="stylesheet">
@endpush
@php($passwordErrors = $errors->updatePassword)
@section('content')
    <div class="row g-3 toic-settings-grid">
        <div class="col-12 col-lg-6">
            <section class="card toic-settings-card" aria-labelledby="officeAccountTitle">
                <div class="card-header"><h2 id="officeAccountTitle">Office account</h2></div>
                <div class="card-body">
                    <div class="toic-account-row"><i class="bi bi-building" aria-hidden="true"></i><div><strong>{{ $profile['name'] }}</strong><span>{{ $profile['email'] }} · office account</span></div></div>
                    <div class="toic-account-row"><i class="bi bi-person-lock" aria-hidden="true"></i><div><strong>Access: system data only</strong><span>Set by the IATO Super Admin in Access Control. No applicant records, admissions reports, or Messages from other offices.</span></div></div>
                    <div class="toic-account-row"><i class="bi bi-phone" aria-hidden="true"></i><div><strong>Two-factor authentication {{ $twoFactorOn ? 'on' : 'not set up' }}</strong><span>{{ $twoFactorOn ? 'Authenticator app · set up ' . $twoFactorSince : 'Set up an authenticator app to protect this account.' }}</span><a href="{{ url('/two-factor') }}" class="small fw-semibold">{{ $twoFactorOn ? 'View recovery codes' : 'Set up two-factor authentication' }}</a></div></div>
                </div>
            </section>
            <button class="btn btn-link toic-security-toggle mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#toicAccountSecurity" aria-controls="toicAccountSecurity" aria-expanded="{{ $passwordErrors->any() || session('status') === 'password-updated' ? 'true' : 'false' }}"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i> Password and recent sign-ins</button>
        </div>
        <div class="col-12 col-lg-6">
            <form id="toicAlertsForm" class="card toic-settings-card" aria-labelledby="alertsTitle">
                <div class="card-header"><h2 id="alertsTitle">Alerts</h2><span>Sent to {{ $profile['email'] }}</span></div>
                <div class="card-body">
                    @foreach ([
                        ['uptime', 'iARIS is down or slow', 'When uptime checks fail or pages take over 3 seconds.', true],
                        ['backup', 'Backup failed', "If the nightly backup doesn't complete.", true],
                        ['signins', 'Many failed sign-ins', '10 or more in an hour.', true],
                        ['certificate', 'Certificate about to expire', '30 days before the SSL certificate ends.', true],
                        ['summary', 'Weekly system summary', 'Every Monday, 8:00 AM.', false],
                    ] as [$key, $label, $description, $enabled])
                        <div class="toic-alert-row"><label for="toicAlert-{{ $key }}"><strong>{{ $label }}</strong><span>{{ $description }}</span></label><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" id="toicAlert-{{ $key }}" name="{{ $key }}" @checked($enabled)></div></div>
                    @endforeach
                    <p class="toic-preview-note mb-0">Preferences are saved in this browser. Email alert delivery is not connected yet.</p>
                </div>
            </form>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-3"><button type="submit" form="toicAlertsForm" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i> Save changes</button></div>
    <div id="toicAlertsStatus" class="small text-end mt-2" role="status" aria-live="polite"></div>
    <div class="collapse mt-4 {{ $passwordErrors->any() || session('status') === 'password-updated' ? 'show' : '' }}" id="toicAccountSecurity">@include('settings.partials.security')</div>
@endsection
@section('scripts')
    @include('settings.partials.password-script')
    <script>
        const alertsForm = document.getElementById('toicAlertsForm');
        const alertStorageKey = 'iaris.toic.alerts.{{ auth()->id() }}';
        try {
            const saved = JSON.parse(localStorage.getItem(alertStorageKey) || '{}');
            alertsForm.querySelectorAll('input[type="checkbox"]').forEach(input => {
                if (typeof saved[input.name] === 'boolean') input.checked = saved[input.name];
            });
        } catch { /* Default alert choices remain available when storage is blocked. */ }
        alertsForm.addEventListener('submit', event => {
            event.preventDefault();
            const preferences = Object.fromEntries([...alertsForm.querySelectorAll('input[type="checkbox"]')].map(input => [input.name, input.checked]));
            try {
                localStorage.setItem(alertStorageKey, JSON.stringify(preferences));
                document.getElementById('toicAlertsStatus').textContent = 'Saved in this browser. Email delivery is not connected yet.';
            } catch {
                document.getElementById('toicAlertsStatus').textContent = 'Could not save: this browser is blocking local storage.';
            }
        });
    </script>
@endsection
