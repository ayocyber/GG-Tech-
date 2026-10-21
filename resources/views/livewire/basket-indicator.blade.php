<div class="flex items-center gap-1">
    <a href="{{ route('quote') }}" class="relative rounded-full p-2.5 text-navy transition hover:bg-pearl" aria-label="{{ __('Quote basket') }} ({{ $quoteCount }})">
        <x-glyph name="quote" class="size-6" />
        @if ($quoteCount > 0)
            <span class="absolute right-1 top-1 flex min-w-4.5 items-center justify-center rounded-full bg-royal px-1 text-[10px] font-bold leading-4.5 text-white">{{ $quoteCount }}</span>
        @endif
    </a>
    <a href="{{ route('cart') }}" class="relative rounded-full p-2.5 text-navy transition hover:bg-pearl" aria-label="{{ __('Cart') }} ({{ $cartCount }})">
        <x-glyph name="cart" class="size-6" />
        @if ($cartCount > 0)
            <span class="absolute right-1 top-1 flex min-w-4.5 items-center justify-center rounded-full bg-signal px-1 text-[10px] font-bold leading-4.5 text-white">{{ $cartCount }}</span>
        @endif
    </a>
</div>
