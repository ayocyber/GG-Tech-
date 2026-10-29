<div>
    @if ($lines->isEmpty())
        <div class="rounded-card border border-dashed border-silver bg-pearl/60 px-6 py-20 text-center">
            <x-glyph name="cart" class="mx-auto size-12 text-royal/40" />
            <p class="mt-4 font-display text-xl font-semibold text-navy">{{ __('Your cart is empty.') }}</p>
            <p class="mt-2 text-sm text-muted">{{ __('Discover our toners, printers and network equipment.') }}</p>
            <a href="{{ route('shop') }}" class="btn-blue mt-6">{{ __('Browse the catalogue') }}</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-12 lg:gap-12">
            <ul class="divide-y divide-silver/50 rounded-card border border-silver/60 lg:col-span-8" role="list">
                @foreach ($lines as $line)
                    <li class="flex gap-4 p-4 sm:gap-6 sm:p-6" wire:key="line-{{ $line->variant->id }}">
                        <a href="{{ route('products.show', $line->product()) }}" class="size-24 shrink-0 overflow-hidden rounded-xl border border-silver/50 bg-white sm:size-28">
                            <x-product-image :image="$line->product()->coverImage" :alt="$line->product()->translate('name')" sizes="112px" />
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:justify-between">
                            <div class="min-w-0">
                                <p class="eyebrow">{{ $line->product()->brand?->name }}</p>
                                <a href="{{ route('products.show', $line->product()) }}" class="mt-1 block font-semibold text-ink hover:text-royal">{{ $line->product()->translate('name') }}</a>
                                @if ($line->variant->displayLabel())
                                    <p class="mt-1 text-sm text-muted">{{ $line->variant->displayLabel() }}</p>
                                @endif
                                <p class="mt-1 text-sm text-muted">{{ $market->format($line->unitPrice ?? 0) }} / {{ __('unit') }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                                <div class="flex items-center gap-2">
                                    <label for="qty-{{ $line->variant->id }}" class="sr-only">{{ __('Quantity') }}</label>
                                    <input id="qty-{{ $line->variant->id }}" type="number" min="1" max="999" value="{{ $line->quantity }}"
                                           wire:change="updateQuantity({{ $line->variant->id }}, $event.target.value)"
                                           class="w-20 rounded-full border border-silver px-3 py-2 text-center text-sm font-semibold focus:border-royal focus:outline-none focus:ring-4 focus:ring-royal/10">
                                    <button type="button" wire:click="remove({{ $line->variant->id }})" class="rounded-full p-2 text-muted hover:bg-pearl hover:text-signal" aria-label="{{ __('Remove') }}">
                                        <x-glyph name="trash" class="size-5" />
                                    </button>
                                </div>
                                <p class="font-display text-lg font-bold text-navy">{{ $market->format($line->lineTotal() ?? 0) }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="lg:col-span-4">
                <div class="sticky top-28 space-y-5 rounded-card bg-pearl p-6">
                    <h2 class="text-lg font-semibold">{{ __('Order summary') }}</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-muted">{{ __('Subtotal') }}</dt><dd class="font-semibold">{{ $market->format($subtotal) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">{{ __('Delivery') }}</dt><dd class="text-right text-muted">{{ __('calculated at the next step') }}</dd></div>
                    </dl>
                    <div class="flex items-baseline justify-between border-t border-silver/60 pt-4">
                        <span class="font-semibold">{{ __('Total') }}</span>
                        <span class="font-display text-2xl font-bold text-navy">{{ $market->format($subtotal) }}</span>
                    </div>

                    @if ($needsDeliveryQuote)
                        <p class="rounded-xl bg-white px-4 py-3 text-xs text-muted">{{ __('Your cart contains a heavy item: our team will confirm the delivery fee.') }}</p>
                    @endif

                    <a href="{{ route('checkout') }}" class="btn-red w-full">{{ __('Checkout') }} <x-glyph name="arrow-right" class="size-4" /></a>

                    @if ($hasBulkLine)
                        <div class="rounded-xl border border-royal/20 bg-white p-4 text-sm">
                            <p class="font-semibold text-navy">{{ __('Buying in bulk?') }}</p>
                            <p class="mt-1 text-muted">{{ __('For large quantities, request a quote and get a better price.') }}</p>
                            <button type="button" wire:click="convertToQuote" class="mt-3 font-semibold text-royal hover:underline">{{ __('Turn my cart into a quote') }} →</button>
                        </div>
                    @endif

                    <ul class="space-y-2 border-t border-silver/60 pt-4 text-xs text-muted">
                        <li class="flex items-center gap-2"><x-glyph name="store" class="size-4 text-royal" /> {{ __('Free in-store pickup') }}</li>
                        <li class="flex items-center gap-2"><x-glyph name="truck" class="size-4 text-royal" /> {{ __('Delivery in 24–48 h in Lomé') }}</li>
                        <li class="flex items-center gap-2"><x-glyph name="chat" class="size-4 text-royal" /> {{ __('WhatsApp support') }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    @endif
</div>
