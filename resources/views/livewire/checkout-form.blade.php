<form wire:submit="placeOrder" class="grid gap-8 lg:grid-cols-12 lg:gap-12" novalidate>
    <div class="space-y-8 lg:col-span-7">
        {{-- Step 1: contact --}}
        <section class="card p-6 sm:p-8">
            <div class="flex items-center gap-3">
                <span class="flex size-8 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">1</span>
                <h2 class="text-xl font-semibold">{{ __('Contact') }}</h2>
            </div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="customer_name" class="field-label">{{ __('Full name') }}</label>
                    <input id="customer_name" type="text" wire:model.blur="customer_name" autocomplete="name" class="field-input" required>
                    @error('customer_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="field-label">{{ __('Email') }}</label>
                    <input id="email" type="email" wire:model.blur="email" autocomplete="email" class="field-input" required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="field-label">{{ __('Phone (WhatsApp)') }}</label>
                    <input id="phone" type="tel" wire:model.blur="phone" autocomplete="tel" class="field-input" required>
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        {{-- Step 2: delivery --}}
        <section class="card p-6 sm:p-8">
            <div class="flex items-center gap-3">
                <span class="flex size-8 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">2</span>
                <h2 class="text-xl font-semibold">{{ __('Delivery') }}</h2>
            </div>

            <div @class(['mt-6 grid gap-3', 'sm:grid-cols-3' => $this->countries->isNotEmpty(), 'sm:grid-cols-2' => $this->countries->isEmpty()])>
                <label @class(['flex cursor-pointer gap-3 rounded-2xl border p-4 transition', 'border-royal bg-royal-50 ring-1 ring-royal' => $isPickup, 'border-silver hover:border-royal/40' => ! $isPickup])>
                    <input type="radio" wire:model.live="fulfilment" value="pickup" class="mt-1 text-royal focus:ring-royal">
                    <span>
                        <span class="flex items-center gap-2 font-semibold text-navy"><x-glyph name="store" class="size-4" /> {{ __('In-store pickup') }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ __('Free · ready once confirmed') }}</span>
                    </span>
                </label>
                <label @class(['flex cursor-pointer gap-3 rounded-2xl border p-4 transition', 'border-royal bg-royal-50 ring-1 ring-royal' => $fulfilment === 'delivery', 'border-silver hover:border-royal/40' => $fulfilment !== 'delivery'])>
                    <input type="radio" wire:model.live="fulfilment" value="delivery" class="mt-1 text-royal focus:ring-royal">
                    <span>
                        <span class="flex items-center gap-2 font-semibold text-navy"><x-glyph name="truck" class="size-4" /> {{ __('Delivery in Togo') }}</span>
                        <span class="mt-1 block text-sm text-muted">{{ __('Anywhere in Togo') }}</span>
                    </span>
                </label>
                @if ($this->countries->isNotEmpty())
                    <label @class(['flex cursor-pointer gap-3 rounded-2xl border p-4 transition', 'border-royal bg-royal-50 ring-1 ring-royal' => $isInternational, 'border-silver hover:border-royal/40' => ! $isInternational])>
                        <input type="radio" wire:model.live="fulfilment" value="international" class="mt-1 text-royal focus:ring-royal">
                        <span>
                            <span class="flex items-center gap-2 font-semibold text-navy"><x-glyph name="globe" class="size-4" /> {{ __('International delivery') }}</span>
                            <span class="mt-1 block text-sm text-muted">{{ __('To selected countries') }}</span>
                        </span>
                    </label>
                @endif
            </div>
            @error('fulfilment') <p class="field-error">{{ $message }}</p> @enderror

            @if ($isPickup)
                <fieldset class="mt-6">
                    <legend class="field-label">{{ __('Pickup shop') }}</legend>
                    <div class="grid gap-2">
                        @foreach ($shopLocations as $location)
                            <label wire:key="shop-{{ $location->shop->value }}" @class(['flex cursor-pointer gap-3 rounded-2xl border p-4 text-sm transition', 'border-royal bg-royal-50' => $pickup_shop === $location->shop->value, 'border-silver hover:border-royal/40' => $pickup_shop !== $location->shop->value])>
                                <input type="radio" wire:model.live="pickup_shop" value="{{ $location->shop->value }}" class="mt-1 text-royal focus:ring-royal">
                                <span>
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-navy">{{ $location->title() }}</span>
                                        <x-shop-badge :shop="$location->shop" />
                                    </span>
                                    <span class="mt-1 block text-muted">{{ $location->address }}</span>
                                    @if ($location->hours())
                                        <span class="block text-muted">{{ $location->hours() }}</span>
                                    @endif
                                    <a href="{{ $location->directionsUrl() }}" target="_blank" rel="noopener" class="mt-1 inline-block font-semibold text-royal hover:underline">{{ __('Get directions') }} →</a>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('pickup_shop') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
            @elseif ($isInternational)
                <div class="mt-6 space-y-5">
                    @if ($restrictedLines->isNotEmpty())
                        <div class="rounded-2xl border border-signal/30 bg-signal/5 p-4 text-sm" role="alert">
                            <p class="font-semibold text-signal-dark">{{ __('These products can only be delivered in Togo') }}</p>
                            <p class="mt-1 text-signal">{{ __('Remove them to send the rest of your order abroad, or choose a delivery in Togo.') }}</p>
                            <ul class="mt-3 space-y-2" role="list">
                                @foreach ($restrictedLines as $line)
                                    <li wire:key="restricted-{{ $line->variant->id }}" class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-2">
                                        <span class="min-w-0 font-medium text-ink">{{ $line->product()->translate('name') }}</span>
                                        <button type="button" wire:click="removeFromCart({{ $line->variant->id }})" class="shrink-0 text-sm font-semibold text-signal hover:underline">{{ __('Remove') }}</button>
                                    </li>
                                @endforeach
                            </ul>
                            <button type="button" wire:click="deliverInTogo" class="mt-3 font-semibold text-royal hover:underline">{{ __('Deliver in Togo instead') }} →</button>
                        </div>
                    @else
                        <div>
                            <label for="delivery_country" class="field-label">{{ __('Delivery country') }}</label>
                            <select id="delivery_country" wire:model.live="delivery_country" autocomplete="country" class="field-input">
                                <option value="">{{ __('Choose a country') }}</option>
                                @foreach ($this->countries as $country)
                                    <option wire:key="country-{{ $country->country_code }}" value="{{ $country->country_code }}">{{ $country->name() }} — <x-money :amount="$country->fee" /></option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-muted">{{ __('We only deliver abroad to the countries in this list.') }}</p>
                            @error('delivery_country') <p class="field-error">{{ $message }}</p> @enderror
                        </div>

                        @if ($this->selectedCountry && ($this->selectedCountry->translate('delivery_time') || $this->selectedCountry->translate('conditions')))
                            <div class="rounded-2xl border border-royal/20 bg-royal-50 p-4 text-sm">
                                @if ($this->selectedCountry->translate('delivery_time'))
                                    <p class="font-semibold text-navy">{{ __('Delivery time: :time', ['time' => $this->selectedCountry->translate('delivery_time')]) }}</p>
                                @endif
                                @if ($this->selectedCountry->translate('conditions'))
                                    <p class="mt-1 text-muted">{{ $this->selectedCountry->translate('conditions') }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="intl_address" class="field-label">{{ __('Street address') }}</label>
                                <input id="intl_address" type="text" wire:model.blur="address" autocomplete="street-address" class="field-input">
                                @error('address') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="intl_city" class="field-label">{{ __('City') }}</label>
                                <input id="intl_city" type="text" wire:model.blur="city" autocomplete="address-level2" class="field-input">
                                @error('city') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="region" class="field-label">{{ __('Region / state') }} <span class="font-normal text-muted">({{ __('if any') }})</span></label>
                                <input id="region" type="text" wire:model.blur="region" autocomplete="address-level1" class="field-input">
                                @error('region') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="postal_code" class="field-label">{{ __('Postal code') }} <span class="font-normal text-muted">({{ __('if any') }})</span></label>
                                <input id="postal_code" type="text" wire:model.blur="postal_code" autocomplete="postal-code" class="field-input">
                                @error('postal_code') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="mt-6 space-y-5">
                    <fieldset>
                        <legend class="field-label">{{ __('Delivery zone') }}</legend>
                        <div class="grid gap-2">
                            @foreach ($this->zones as $zone)
                                <label wire:key="zone-{{ $zone->id }}" @class(['flex cursor-pointer items-center justify-between gap-4 rounded-xl border px-4 py-3 transition', 'border-royal bg-royal-50' => $delivery_zone_id === $zone->id, 'border-silver hover:border-royal/40' => $delivery_zone_id !== $zone->id])>
                                    <span class="flex items-center gap-3">
                                        <input type="radio" wire:model.live="delivery_zone_id" value="{{ $zone->id }}" class="text-royal focus:ring-royal">
                                        <span>
                                            <span class="block text-sm font-semibold text-navy">{{ $zone->translate('name') }}</span>
                                            <span class="block text-xs text-muted">{{ $zone->translate('areas') }}</span>
                                        </span>
                                    </span>
                                    <span class="text-right">
                                        <span class="block text-sm font-semibold text-navy">@if ($zone->isQuoted()) {{ __('On quote') }} @else <x-money :amount="$zone->fee" /> @endif</span>
                                        <span class="block text-xs text-muted">{{ $zone->translate('delivery_time') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('delivery_zone_id') <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="address" class="field-label">{{ __('Address') }}</label>
                            <input id="address" type="text" wire:model.blur="address" autocomplete="street-address" class="field-input">
                            @error('address') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="city" class="field-label">{{ __('City / neighbourhood') }}</label>
                            <input id="city" type="text" wire:model.blur="city" autocomplete="address-level2" class="field-input">
                            @error('city') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="landmark" class="field-label">{{ __('Landmark') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                            <input id="landmark" type="text" wire:model.blur="landmark" placeholder="{{ __('e.g. opposite the pharmacy') }}" class="field-input">
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-5">
                <label for="notes" class="field-label">{{ __('Special instructions') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                <textarea id="notes" rows="3" wire:model.blur="notes" class="field-input"></textarea>
                @error('notes') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- Step 3: payment --}}
        <section class="card p-6 sm:p-8">
            <div class="flex items-center gap-3">
                <span class="flex size-8 items-center justify-center rounded-full bg-navy text-sm font-bold text-white">3</span>
                <h2 class="text-xl font-semibold">{{ __('Payment') }}</h2>
            </div>
            @if ($this->onlinePaymentsEnabled)
                <fieldset class="mt-6 grid gap-3">
                    <legend class="sr-only">{{ __('Payment method') }}</legend>
                    <label @class([
                        'flex gap-4 rounded-2xl border p-5 transition',
                        'cursor-pointer' => $canPayOnline,
                        'cursor-not-allowed opacity-60' => ! $canPayOnline,
                        'border-royal bg-royal-50 ring-1 ring-royal' => $paysOnline,
                        'border-silver/70 hover:border-royal/50' => ! $paysOnline,
                    ])>
                        <input type="radio" wire:model.live="payment_option" value="online" class="mt-1 accent-royal" @disabled(! $canPayOnline)>
                        <span class="text-sm">
                            <span class="block font-semibold text-navy">{{ $this->paymentProvider->checkoutTitle() }}</span>
                            <span class="mt-1 block text-muted">
                                {{ $canPayOnline
                                    ? $this->paymentProvider->checkoutDescription()
                                    : ($isInternational ? __('Available once you choose a delivery country.') : __('Available once the delivery fee is known: choose a zone with a fixed fee, or pick up in store.')) }}
                            </span>
                        </span>
                    </label>
                    <label @class([
                        'flex cursor-pointer gap-4 rounded-2xl border p-5 transition',
                        'border-royal bg-royal-50 ring-1 ring-royal' => ! $paysOnline,
                        'border-silver/70 hover:border-royal/50' => $paysOnline,
                    ])>
                        <input type="radio" wire:model.live="payment_option" value="later" class="mt-1 accent-royal">
                        <span class="text-sm">
                            <span class="block font-semibold text-navy">{{ $isInternational ? __('Pay after confirmation') : ($isPickup ? __('Pay at pickup') : __('Pay on delivery')) }}</span>
                            <span class="mt-1 block text-muted">{{ $isInternational ? __('We confirm your order on WhatsApp and send you the payment details before shipping.') : __('We confirm your order on WhatsApp during opening hours.') }}</span>
                        </span>
                    </label>
                </fieldset>
                @error('payment_option') <p class="field-error">{{ $message }}</p> @enderror
            @else
                <div class="mt-6 flex gap-4 rounded-2xl border border-royal/20 bg-royal-50 p-5">
                    <x-glyph name="sparkles" class="size-6 text-royal" />
                    <div class="text-sm">
                        <p class="font-semibold text-navy">{{ __('Online payment coming soon') }}</p>
                        <p class="mt-1 text-muted">{{ __('Place your order now: we confirm it on WhatsApp during opening hours. You pay on delivery or at pickup.') }}</p>
                    </div>
                </div>
            @endif
        </section>
    </div>

    {{-- Summary --}}
    <aside class="lg:col-span-5">
        <div class="sticky top-28 rounded-card bg-pearl p-6 sm:p-8">
            <h2 class="text-lg font-semibold">{{ __('Your order') }}</h2>
            <ul class="mt-5 divide-y divide-silver/60" role="list">
                @foreach ($lines as $line)
                    <li class="flex gap-3 py-3" wire:key="summary-{{ $line->variant->id }}">
                        <div class="relative size-14 shrink-0 overflow-hidden rounded-lg border border-silver/50 bg-white">
                            <x-product-image :image="$line->product()->coverImage" :alt="$line->product()->translate('name')" sizes="56px" />
                            <span class="absolute -right-0 -top-0 flex size-5 items-center justify-center rounded-bl-lg bg-navy text-[10px] font-bold text-white">{{ $line->quantity }}</span>
                        </div>
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="line-clamp-2 font-medium text-ink">{{ $line->product()->translate('name') }}</p>
                            @if ($line->variant->displayLabel())
                                <p class="text-xs text-muted">{{ $line->variant->displayLabel() }}</p>
                            @endif
                        </div>
                        <p class="text-sm font-semibold"><x-money :amount="$line->lineTotal() ?? 0" /></p>
                    </li>
                @endforeach
            </ul>

            <dl class="mt-4 space-y-3 border-t border-silver/60 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('Subtotal') }}</dt><dd class="font-semibold"><x-money :amount="$subtotal" /></dd></div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">
                        {{ $isPickup ? __('In-store pickup') : __('Delivery') }}
                        @if ($isInternational && $this->selectedCountry)
                            <span class="block text-xs">{{ $this->selectedCountry->name() }}</span>
                        @endif
                    </dt>
                    <dd class="text-right font-semibold">
                        @if ($isPickup || $deliveryFee === 0)
                            <span class="text-instock">{{ __('Free') }}</span>
                        @elseif ($deliveryPending)
                            <span class="text-muted">{{ __('Confirmed by our team') }}</span>
                        @elseif ($deliveryFee === null)
                            <span class="text-muted">{{ $isInternational ? __('Choose a country') : __('Choose a zone') }}</span>
                        @else
                            <x-money :amount="$deliveryFee" />
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-4 flex items-baseline justify-between border-t border-silver/60 pt-4">
                <span class="font-semibold">{{ __('Total') }}</span>
                <span class="font-display text-2xl font-bold text-navy"><x-money :amount="$total" /></span>
            </div>
            @unless ($displayCurrency->isFcfa())
                <div class="mt-3 rounded-xl border border-royal/20 bg-royal-50 p-3 text-xs text-navy">
                    <p class="flex justify-between gap-3 font-semibold">
                        <span>{{ __('Amount charged') }}</span>
                        <span>{{ $market->format($total) }}</span>
                    </p>
                    <p class="mt-1 text-muted">{{ __('Payment is made in FCFA. Amounts in :currency are converted at today’s rate and are for information only.', ['currency' => $displayCurrency->code()]) }}</p>
                </div>
            @endunless

            <button type="submit" class="btn-red mt-6 w-full" wire:loading.attr="disabled" wire:target="placeOrder">
                <span wire:loading.remove wire:target="placeOrder">{{ $paysOnline && $canPayOnline ? __('Pay :amount', ['amount' => $market->format($total)]) : __('Place my order') }}</span>
                <span wire:loading wire:target="placeOrder">{{ __('Sending…') }}</span>
            </button>
            <p class="mt-3 text-center text-xs text-muted">{{ __('Free in-store pickup · 24–48 h delivery in Lomé · WhatsApp support') }}</p>
        </div>
    </aside>
</form>
