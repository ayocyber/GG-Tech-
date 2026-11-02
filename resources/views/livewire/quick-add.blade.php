<div>
    {{-- Confirmation shown after any quick add --}}
    <div wire:ignore
         x-data="{ message: null, wasAdded: false, hideTimer: null }"
         x-on:quick-add-notice.window="message = $event.detail.message; wasAdded = $event.detail.wasAdded; clearTimeout(hideTimer); hideTimer = setTimeout(() => message = null, 5000)"
         class="pointer-events-none fixed inset-x-4 top-24 z-50 flex justify-end"
         role="status">
        <div x-cloak x-show="message" x-transition.opacity
             class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl border border-silver/60 bg-white p-4 text-sm shadow-[0_24px_60px_-20px_rgba(10,16,51,0.5)]">
            <span class="min-w-0 flex-1">
                <span class="block font-medium" x-bind:class="wasAdded ? 'text-instock' : 'text-signal'" x-text="message"></span>
                <a x-show="wasAdded" href="{{ route('cart') }}" class="mt-1 inline-block font-semibold text-royal hover:underline">{{ __('View cart') }} →</a>
            </span>
            <button type="button" x-on:click="message = null" class="shrink-0 rounded-full p-1 text-muted hover:bg-pearl" aria-label="{{ __('Close') }}">
                <x-glyph name="close" class="size-4" />
            </button>
        </div>
    </div>

    {{-- Option picker for products with a choice --}}
    @if ($this->product)
        @php
            $price = $this->variant?->priceFor($market);
        @endphp

        <div x-data x-trap.noscroll="true" x-on:keydown.escape.window="$wire.close()"
             class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4"
             role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
            <div class="absolute inset-0 bg-navy/60" wire:click="close"></div>

            <div class="relative max-h-[90vh] w-full space-y-5 overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <h2 id="quick-add-title" class="font-sans text-lg font-semibold leading-6 text-ink">{{ $this->product->translate('name') }}</h2>
                    <button type="button" wire:click="close" class="-mr-1 -mt-1 shrink-0 rounded-full p-1.5 text-navy hover:bg-pearl" aria-label="{{ __('Close') }}">
                        <x-glyph name="close" class="size-5" />
                    </button>
                </div>

                <div class="flex flex-wrap items-end gap-x-4 gap-y-2">
                    @if ($price === null)
                        <p class="font-display text-2xl font-bold text-navy">{{ __('Price on request') }}</p>
                    @else
                        <p class="font-display text-2xl font-bold text-navy">{{ $market->format($price) }}</p>
                    @endif
                    @if ($this->variant)
                        <x-stock-badge :in-stock="$this->variant->in_stock" class="mb-1" />
                    @endif
                </div>

                <x-variant-chips :product="$this->product" :selected-variant-id="$variantId" />

                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-quantity-stepper id="quick-add-quantity" />

                    <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-red h-12 flex-1"
                            @disabled(! $this->product->canSellOnline($this->variant))>
                        <x-glyph name="cart" class="size-5" />
                        <span wire:loading.remove wire:target="addToCart">{{ __('Add to cart') }}</span>
                        <span wire:loading wire:target="addToCart">{{ __('Adding…') }}</span>
                    </button>
                </div>

                @error('cart')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif
</div>
