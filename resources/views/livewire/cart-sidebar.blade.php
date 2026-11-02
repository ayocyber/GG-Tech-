<div x-data="{ isOpen: false }" x-on:open-cart-sidebar.window="isOpen = true" x-on:keydown.escape.window="isOpen = false">
    <div x-cloak x-show="isOpen" x-trap.noscroll="isOpen" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-labelledby="cart-sidebar-title">
        <div class="absolute inset-0 bg-navy/60" x-on:click="isOpen = false" x-show="isOpen" x-transition.opacity></div>

        <div class="absolute inset-y-0 right-0 flex w-[92%] max-w-md flex-col bg-white shadow-2xl" x-show="isOpen"
             x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex h-[76px] shrink-0 items-center justify-between gap-4 border-b border-silver/50 px-4 sm:px-6">
                <h2 id="cart-sidebar-title" class="font-sans text-lg font-semibold text-navy">{{ __('Your cart') }} ({{ $lines->sum('quantity') }})</h2>
                <button type="button" class="-mr-2 rounded-full p-2 text-navy hover:bg-pearl" x-on:click="isOpen = false" aria-label="{{ __('Close') }}">
                    <x-glyph name="close" class="size-6" />
                </button>
            </div>

            @if ($lines->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center px-6 text-center">
                    <x-glyph name="cart" class="size-12 text-royal/40" />
                    <p class="mt-4 font-display text-xl font-semibold text-navy">{{ __('Your cart is empty.') }}</p>
                    <p class="mt-2 text-sm text-muted">{{ __('Discover our toners, printers and network equipment.') }}</p>
                    <a href="{{ route('shop') }}" class="btn-blue mt-6">{{ __('Browse the catalogue') }}</a>
                </div>
            @else
                <ul class="flex-1 divide-y divide-silver/50 overflow-y-auto px-4 sm:px-6" role="list">
                    @foreach ($lines as $line)
                        <li class="flex gap-3 py-4" wire:key="sidebar-line-{{ $line->variant->id }}">
                            <a href="{{ route('products.show', $line->product()) }}" class="size-20 shrink-0 overflow-hidden rounded-xl border border-silver/50 bg-white">
                                <x-product-image :image="$line->product()->coverImage" :alt="$line->product()->translate('name')" sizes="80px" />
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <a href="{{ route('products.show', $line->product()) }}" class="line-clamp-2 text-sm font-semibold text-ink hover:text-royal">{{ $line->product()->translate('name') }}</a>
                                    <button type="button" wire:click="remove({{ $line->variant->id }})" class="-mr-1 -mt-1 shrink-0 rounded-full p-1.5 text-muted hover:bg-pearl hover:text-signal" aria-label="{{ __('Remove') }}">
                                        <x-glyph name="trash" class="size-5" />
                                    </button>
                                </div>
                                @if ($line->variant->displayLabel())
                                    <p class="mt-0.5 text-xs text-muted">{{ $line->variant->displayLabel() }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-muted">{{ $market->format($line->unitPrice ?? 0) }} / {{ __('unit') }}</p>

                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <div class="flex items-center rounded-full border border-silver">
                                        <button type="button" wire:click="decrement({{ $line->variant->id }}, $event.timeStamp)" @disabled($line->quantity <= 1) class="flex size-8 items-center justify-center rounded-full text-navy hover:bg-pearl disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Decrease quantity') }}">
                                            <x-glyph name="minus" class="size-3.5" />
                                        </button>
                                        <span class="min-w-7 text-center text-sm font-semibold" aria-label="{{ __('Quantity') }}">{{ $line->quantity }}</span>
                                        <button type="button" wire:click="increment({{ $line->variant->id }}, $event.timeStamp)" class="flex size-8 items-center justify-center rounded-full text-navy hover:bg-pearl" aria-label="{{ __('Increase quantity') }}">
                                            <x-glyph name="plus" class="size-3.5" />
                                        </button>
                                    </div>
                                    <p class="whitespace-nowrap font-display text-sm font-bold text-navy sm:text-base">{{ $market->format($line->lineTotal() ?? 0) }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="shrink-0 space-y-3 border-t border-silver/50 bg-pearl/60 p-4 sm:px-6">
                    <div class="flex items-baseline justify-between gap-4">
                        <span class="text-sm font-semibold text-ink">{{ __('Subtotal') }}</span>
                        <span class="font-display text-xl font-bold text-navy">{{ $market->format($subtotal) }}</span>
                    </div>
                    <p class="text-xs text-muted">{{ __('Delivery') }} : {{ __('calculated at the next step') }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('cart') }}" class="btn-outline px-3">{{ __('View cart') }}</a>
                        <a href="{{ route('checkout') }}" class="btn-red px-3">{{ __('Checkout') }}</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
