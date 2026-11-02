@props(['product', 'market'])

@php
    $price = $product->startingPrice($market);
    $labels = $product->variants->map->displayLabel()->filter()->take(4);
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-card border border-silver/60 bg-white transition duration-300 hover:-translate-y-0.5 hover:border-royal/30 hover:shadow-[0_18px_40px_-24px_rgba(10,16,51,0.45)]']) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden bg-white">
        <x-product-image
            :image="$product->coverImage"
            :alt="$product->translate('name')"
            :icon="$product->category->icon"
            :model="$product->model"
            :brand="$product->brand?->name"
            class="transition duration-500 group-hover:scale-[1.03]"
        />

        <div class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
            @if ($product->toner_type)
                <x-toner-type-badge :type="$product->toner_type" />
            @endif
            @if ($product->is_featured)
                <span class="rounded-full bg-navy px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-white">{{ __('Premium') }}</span>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col gap-2 border-t border-silver/40 p-4">
        <div class="flex items-center justify-between gap-2">
            <span class="eyebrow truncate">{{ $product->brand?->name ?? $product->category->translate('name') }}</span>
            <x-stock-badge :in-stock="$product->isInStock()" class="shrink-0" />
        </div>

        <h3 class="line-clamp-2 min-h-10 font-sans text-[15px] font-semibold leading-5 text-ink">
            <a href="{{ route('products.show', $product) }}" class="after:absolute after:inset-0">
                {{ $product->translate('name') }}
            </a>
        </h3>

        @if ($labels->isNotEmpty())
            <div class="flex flex-wrap gap-1">
                @foreach ($labels as $label)
                    <span class="rounded-full border border-silver px-2 py-0.5 text-[11px] font-medium text-muted">{{ $label }}</span>
                @endforeach
                @if ($product->variants->count() > $labels->count())
                    <span class="px-1 py-0.5 text-[11px] font-medium text-muted">+{{ $product->variants->count() - $labels->count() }}</span>
                @endif
            </div>
        @endif

        <div class="mt-auto pt-2">
            @if ($product->price_on_request || $price === null)
                <span class="text-sm font-semibold text-royal">{{ __('Price on request') }}</span>
            @else
                <div class="leading-tight">
                    @if ($product->hasVariantChoice())
                        <span class="block text-[11px] font-medium text-muted">{{ __('From') }}</span>
                    @endif
                    <span class="font-display text-lg font-bold text-navy">{{ $market->format($price) }}</span>
                </div>
            @endif
        </div>

        @if ($product->canBeAddedToCart())
            <button type="button"
                    x-data="{ isWaiting: false }"
                    x-on:click="isWaiting = true; $dispatch('quick-add', { productId: {{ $product->id }} })"
                    x-on:quick-add-finished.window="isWaiting = false"
                    x-bind:disabled="isWaiting"
                    class="btn-red relative z-10 w-full px-2 py-2.5 text-xs sm:text-sm">
                <x-glyph name="cart" class="hidden size-4 shrink-0 sm:block" />
                {{ $product->hasVariantChoice() ? __('Choose options') : __('Add to cart') }}
            </button>
        @else
            <a href="{{ route('products.show', $product) }}" class="btn-outline relative z-10 w-full px-2 py-2.5 text-xs sm:text-sm">
                {{ __('View product') }}
            </a>
        @endif
    </div>
</article>
