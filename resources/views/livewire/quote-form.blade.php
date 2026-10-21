<form wire:submit="submit" class="grid gap-8 lg:grid-cols-12 lg:gap-12" novalidate>
    <div class="space-y-8 lg:col-span-7">
        <section class="card p-6 sm:p-8">
            <h2 class="text-xl font-semibold">{{ __('Your company') }}</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="company_name" class="field-label">{{ __('Company name') }}</label>
                    <input id="company_name" type="text" wire:model.blur="company_name" autocomplete="organization" class="field-input">
                    @error('company_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="contact_name" class="field-label">{{ __('Contact name') }}</label>
                    <input id="contact_name" type="text" wire:model.blur="contact_name" autocomplete="name" class="field-input">
                    @error('contact_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="job_title" class="field-label">{{ __('Role') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                    <input id="job_title" type="text" wire:model.blur="job_title" autocomplete="organization-title" class="field-input">
                </div>
                <div>
                    <label for="quote-email" class="field-label">{{ __('Email') }}</label>
                    <input id="quote-email" type="email" wire:model.blur="email" autocomplete="email" class="field-input">
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="quote-phone" class="field-label">{{ __('Phone (WhatsApp)') }}</label>
                    <input id="quote-phone" type="tel" wire:model.blur="phone" autocomplete="tel" class="field-input">
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tax_id" class="field-label">{{ __('Tax ID (NIF)') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                    <input id="tax_id" type="text" wire:model.blur="tax_id" class="field-input">
                </div>
                <div>
                    <label for="quote-city" class="field-label">{{ __('Delivery city') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                    <input id="quote-city" type="text" wire:model.blur="city" class="field-input">
                </div>
                <div>
                    <label for="needed_by" class="field-label">{{ __('Needed by') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                    <input id="needed_by" type="date" wire:model.blur="needed_by" min="{{ now()->toDateString() }}" class="field-input">
                    @error('needed_by') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="quote-notes" class="field-label">{{ __('Notes') }} <span class="font-normal text-muted">({{ __('optional') }})</span></label>
                    <textarea id="quote-notes" rows="4" wire:model.blur="notes" placeholder="{{ __('Brand preferences, installation needs, budget…') }}" class="field-input"></textarea>
                </div>
            </div>
        </section>
    </div>

    <aside class="lg:col-span-5">
        <div class="sticky top-28 space-y-6 rounded-card bg-pearl p-6 sm:p-8">
            <div>
                <h2 class="text-lg font-semibold">{{ __('Products to quote') }}</h2>
                @if ($lines->isEmpty())
                    <p class="mt-2 text-sm text-muted">{{ __('Your quote basket is empty. Add products from the catalogue, or describe what you need below.') }}</p>
                @else
                    <ul class="mt-4 divide-y divide-silver/60" role="list">
                        @foreach ($lines as $line)
                            <li class="flex items-center gap-3 py-3" wire:key="quote-line-{{ $line->variant->id }}">
                                <div class="size-12 shrink-0 overflow-hidden rounded-lg border border-silver/50 bg-white">
                                    <x-product-image :image="$line->product()->coverImage" :alt="$line->product()->translate('name')" sizes="48px" />
                                </div>
                                <div class="min-w-0 flex-1 text-sm">
                                    <p class="line-clamp-2 font-medium text-ink">{{ $line->product()->translate('name') }}</p>
                                    @if ($line->variant->displayLabel())
                                        <p class="text-xs text-muted">{{ $line->variant->displayLabel() }}</p>
                                    @endif
                                </div>
                                <label for="quote-qty-{{ $line->variant->id }}" class="sr-only">{{ __('Quantity') }}</label>
                                <input id="quote-qty-{{ $line->variant->id }}" type="number" min="1" max="999" value="{{ $line->quantity }}"
                                       wire:change="updateQuantity({{ $line->variant->id }}, $event.target.value)"
                                       class="w-16 rounded-full border border-silver bg-white px-2 py-1.5 text-center text-sm font-semibold">
                                <button type="button" wire:click="remove({{ $line->variant->id }})" class="rounded-full p-1.5 text-muted hover:text-signal" aria-label="{{ __('Remove') }}">
                                    <x-glyph name="trash" class="size-4" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="border-t border-silver/60 pt-5">
                <p class="text-sm font-semibold text-navy">{{ __('Other items') }}</p>
                <p class="mt-1 text-xs text-muted">{{ __('Something not on the website? Describe it and we will source it.') }}</p>
                <div class="mt-3 space-y-2">
                    @foreach ($extraItems as $index => $item)
                        <div class="flex items-start gap-2" wire:key="extra-{{ $index }}">
                            <div class="flex-1">
                                <label for="extra-{{ $index }}" class="sr-only">{{ __('Item') }}</label>
                                <input id="extra-{{ $index }}" type="text" wire:model.blur="extraItems.{{ $index }}.description" placeholder="{{ __('e.g. HP 207A toner, cyan') }}" class="field-input py-2.5">
                                @error("extraItems.{$index}.description") <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <label for="extra-qty-{{ $index }}" class="sr-only">{{ __('Quantity') }}</label>
                            <input id="extra-qty-{{ $index }}" type="number" min="1" wire:model.blur="extraItems.{{ $index }}.quantity" class="field-input w-20 py-2.5 text-center">
                            <button type="button" wire:click="removeExtraItem({{ $index }})" class="mt-2 rounded-full p-1.5 text-muted hover:text-signal" aria-label="{{ __('Remove') }}">
                                <x-glyph name="close" class="size-4" />
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="addExtraItem" class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-royal hover:underline">
                    <x-glyph name="plus" class="size-4" /> {{ __('Add an item') }}
                </button>
            </div>

            @error('items') <p class="field-error">{{ $message }}</p> @enderror

            <button type="submit" class="btn-blue w-full" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">{{ __('Send my quote request') }}</span>
                <span wire:loading wire:target="submit">{{ __('Sending…') }}</span>
            </button>
            <p class="text-center text-xs text-muted">{{ __('Reply within one working day, by email and WhatsApp.') }}</p>
        </div>
    </aside>
</form>
