{{-- iARIS logo (temporary): an "i" whose dot is a mortarboard, on a green rounded tile.
     Usage: @include('partials.logo-mark', ['size' => 40]). The same icon is public/iaris-icon.svg (the browser tab icon). --}}
<svg width="{{ $size ?? 40 }}" height="{{ $size ?? 40 }}" viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="flex-shrink-0">
    <defs>
        <linearGradient id="iarisLogoBg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#0E9168"/>
            <stop offset="1" stop-color="#055A40"/>
        </linearGradient>
    </defs>
    <rect width="256" height="256" rx="58" fill="url(#iarisLogoBg)"/>
    <polygon points="128,34 188,64 128,94 68,64" fill="#F5B93A"/>
    <path d="M96 82 V100 C96 108 160 108 160 100 V82 L128 98 Z" fill="#E09F1C"/>
    <line x1="184" y1="66" x2="184" y2="92" stroke="#F5B93A" stroke-width="5" stroke-linecap="round"/>
    <circle cx="184" cy="96" r="6" fill="#F5B93A"/>
    <rect x="106" y="130" width="44" height="96" rx="22" fill="#FFFFFF"/>
</svg>
