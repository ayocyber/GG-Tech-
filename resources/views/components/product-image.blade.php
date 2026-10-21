@props([
    'image' => null,
    'alt' => '',
    'icon' => 'accessory',
    'sizes' => '(min-width: 1024px) 25vw, 50vw',
    'eager' => false,
    'model' => null,
    'brand' => null,
])

@if ($image)
    <img
        src="{{ $image->url(800) }}"
        srcset="{{ $image->srcset() }}"
        sizes="{{ $sizes }}"
        alt="{{ $alt }}"
        @unless ($eager) loading="lazy" @endunless
        decoding="async"
        {{ $attributes->merge(['class' => 'size-full object-contain']) }}
    >
@elseif ($model)
    {{-- No photo yet: show the reference itself, so cartridges stay easy to tell apart. --}}
    <div {{ $attributes->merge(['class' => 'relative flex size-full flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-navy via-navy-800 to-royal text-white @container']) }} role="img" aria-label="{{ $alt }}">
        <svg class="absolute -left-6 top-0 h-full w-auto opacity-25" viewBox="0 0 120 400" fill="none" aria-hidden="true">
            <path d="M110 0C20 70 -10 260 100 400" stroke="#D7102B" stroke-width="8" />
            <path d="M120 30C45 110 25 260 115 370" stroke="#8fa0ff" stroke-width="3" />
        </svg>
        @if ($brand)
            <span class="relative text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">{{ $brand }}</span>
        @endif
        <span class="relative mt-1 max-w-[90%] truncate text-center font-display text-[clamp(1.5rem,16cqw,3.5rem)] font-extrabold leading-none tracking-tight">{{ $model }}</span>
        <span class="relative mt-3 flex items-center gap-1.5 text-[11px] font-medium text-white/60">
            <x-glyph :name="$icon" class="size-3.5" />
            {{ __('Photo coming soon') }}
        </span>
    </div>
@else
    <div {{ $attributes->merge(['class' => 'flex size-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-pearl to-white text-royal/40']) }}>
        <x-glyph :name="$icon" class="size-14" />
        <span class="text-xs font-medium text-muted">{{ __('Photo coming soon') }}</span>
    </div>
@endif
