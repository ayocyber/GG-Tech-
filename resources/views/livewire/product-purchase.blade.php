@php
    use App\Support\WhatsApp;
@endphp

<div class="space-y-6">
    {{-- Price --}}
    <div class="flex flex-wrap items-end gap-x-4 gap-y-2">
        @if ($product->price_on_request || $price === null)
            <p class="font-display text-3xl font-bold text-navy">{{ __('Price on request') }}</p>
        @else
            <p class="font-display text-3xl font-bold text-navy" wire:key="price-{{ $variantId }}">{{ $market->format($price) }}</p>
        @endif
        @if ($this->variant)
            <x-stock-badge :in-stock="$this->variant->in_stock" class="mb-1.5" />
        @endif
    </div>

    {{-- Variant chips --}}
    @if ($product->hasVariantChoice())
        <fieldset>
            <legend class="mb-2.5 text-sm font-semibold text-ink">{{ __('Choose an option') }}</legend>
            <div class="flex flex-wrap gap-2">
                @foreach ($product->variants as $option)
                    <button type="button" wire:click="selectVariant({{ $option->id }})" wire:key="variant-{{ $option->id }}"
                            @class([
                                'rounded-full border px-4 py-2 text-sm font-semibold transition',
                                'border-navy bg-navy text-white shadow-sm' => $option->id === $variantId,
                                'border-silver bg-white text-ink hover:border-royal' => $option->id !== $variantId && $option->in_stock,
                                'border-silver/60 bg-pearl text-muted line-through' => $option->id !== $variantId && ! $option->in_stock,
                            ])
                            aria-pressed="{{ $option->id === $variantId ? 'true' : 'false' }}">
                        {{ $option->displayLabel() ?? __('Option :number', ['number' => $loop->iteration]) }}
                    </button>
                @endforeach
            </div>
        </fieldset>
    @endif

    {{-- Quantity + actions --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="flex h-12 items-center rounded-full border border-silver bg-white">
            <button type="button" wire:click="decrement" class="flex size-12 items-center justify-center rounded-full text-navy hover:bg-pearl" aria-label="{{ __('Decrease quantity') }}">
                <x-glyph name="minus" class="size-4" />
            </button>
            <label for="quantity" class="sr-only">{{ __('Quantity') }}</label>
            <input id="quantity" type="number" min="1" max="999" wire:model.live.debounce.300ms="quantity"
                   class="w-12 border-0 bg-transparent text-center text-sm font-semibold [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none">
            <button type="button" wire:click="increment" class="flex size-12 items-center justify-center rounded-full text-navy hover:bg-pearl" aria-label="{{ __('Increase quantity') }}">
                <x-glyph name="plus" class="size-4" />
            </button>
        </div>

        @if ($product->canBeAddedToCart())
            <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-red h-12 flex-1"
                    @disabled(! $this->variant?->in_stock)>
                <x-glyph name="cart" class="size-5" />
                <span wire:loading.remove wire:target="addToCart">{{ __('Add to cart') }}</span>
                <span wire:loading wire:target="addToCart">{{ __('Adding…') }}</span>
            </button>
        @endif

        <button type="button" wire:click="addToQuote" wire:loading.attr="disabled" @class(['h-12', 'btn-outline flex-1' => $product->canBeAddedToCart(), 'btn-blue flex-1' => ! $product->canBeAddedToCart()])>
            <x-glyph name="quote" class="size-5" />
            {{ __('Add to quote') }}
        </button>
    </div>

    @error('cart')
        <p class="field-error">{{ $message }}</p>
    @enderror

    @if ($added === 'cart')
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-instock/30 bg-instock/5 px-4 py-3 text-sm" role="status">
            <span class="flex items-center gap-2 font-medium text-instock"><x-glyph name="check" class="size-5" /> {{ __('Added to your cart.') }}</span>
            <a href="{{ route('cart') }}" class="font-semibold text-royal hover:underline">{{ __('View cart') }} →</a>
        </div>
    @elseif ($added === 'quote')
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-royal/20 bg-royal-50 px-4 py-3 text-sm" role="status">
            <span class="flex items-center gap-2 font-medium text-royal"><x-glyph name="check" class="size-5" /> {{ __('Added to your quote request.') }}</span>
            <a href="{{ route('quote') }}" class="font-semibold text-royal hover:underline">{{ __('Finish my quote') }} →</a>
        </div>
    @endif

    @if ($isBulk)
        <p class="rounded-2xl bg-pearl px-4 py-3 text-sm text-ink">
            <strong>{{ __('Large quantity?') }}</strong> {{ __('Request a quote and get a better price.') }}
        </p>
    @endif

    {{-- WhatsApp --}}
    <a href="{{ $product->price_on_request ? WhatsApp::priceRequest($product) : WhatsApp::productQuestion($product) }}" target="_blank" rel="noopener"
       class="flex items-center gap-3 rounded-2xl border border-whatsapp/30 bg-whatsapp/5 px-4 py-3 text-sm font-medium text-ink transition hover:border-whatsapp hover:bg-whatsapp/10">
        <span class="flex size-9 items-center justify-center rounded-full bg-whatsapp text-white"><x-glyph name="chat" class="size-5" /></span>
        <span>
            {{ $product->price_on_request ? __('Ask for the price on WhatsApp') : __('A question about this product? Ask us on WhatsApp') }}
        </span>
    </a>
</div>
