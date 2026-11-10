{{-- The current iARIS mark, shared by authentication screens and every sidebar. --}}
<span class="auth-logo d-inline-flex {{ ($variant ?? '') === 'glass' ? 'auth-logo--glass' : '' }}" style="--logo-size: {{ $size ?? 58 }}px;" aria-hidden="true">
    <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" focusable="false">
        <rect x="88" y="88" width="24" height="72" rx="8" fill="#fff"/>
        <circle cx="100" cy="56" r="16" fill="#fff"/>
        <path d="M92 56.5 98 63 110 48" stroke="#005C46" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        <rect x="56" y="168" width="88" height="6" rx="3" fill="#fff" opacity=".25"/>
    </svg>
</span>
