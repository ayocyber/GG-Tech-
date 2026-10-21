@props(['variant' => 'dark'])

@php
    $onDark = $variant === 'light';
@endphp

{{-- GG-Tech wordmark redrawn as SVG from the client's logo, so it stays sharp at any size. --}}
<svg {{ $attributes->merge(['class' => 'h-11 w-auto']) }} viewBox="0 0 200 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="GG-Tech, Great God Technology">
    <path d="M30 3C9 9 1 37 25 60c-12-16-13-40 5-57Z" fill="#D7102B" />
    <path d="M35 9C18 17 13 39 29 54 20 40 21 23 35 9Z" fill="{{ $onDark ? '#8fa0ff' : '#1D2B8F' }}" />
    <text x="38" y="37" font-family="Sora, Inter, sans-serif" font-size="34" font-weight="800" letter-spacing="-1.5" fill="#D7102B">GG</text>
    <text x="93" y="35" font-family="Sora, Inter, sans-serif" font-size="23" font-weight="700" letter-spacing="-0.5" fill="{{ $onDark ? '#ffffff' : '#1D2B8F' }}">-Tech</text>
    <rect x="39" y="43" width="146" height="17" rx="8.5" fill="{{ $onDark ? '#ffffff' : '#1D2B8F' }}" />
    <text x="112" y="55.5" text-anchor="middle" font-family="Inter, sans-serif" font-size="10.5" font-weight="600" fill="{{ $onDark ? '#1D2B8F' : '#ffffff' }}">Great God Technology</text>
</svg>
