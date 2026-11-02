@props(['product', 'selectedVariantId'])

<fieldset>
    <legend class="mb-2.5 text-sm font-semibold text-ink">{{ __('Choose an option') }}</legend>
    <div class="flex flex-wrap gap-2">
        @foreach ($product->variants as $option)
            <button type="button" wire:click="selectVariant({{ $option->id }})" wire:key="variant-{{ $option->id }}"
                    @class([
                        'rounded-full border px-4 py-2 text-sm font-semibold transition',
                        'border-navy bg-navy text-white shadow-sm' => $option->id === $selectedVariantId,
                        'border-silver bg-white text-ink hover:border-royal' => $option->id !== $selectedVariantId && $option->in_stock,
                        'border-silver/60 bg-pearl text-muted line-through' => $option->id !== $selectedVariantId && ! $option->in_stock,
                    ])
                    aria-pressed="{{ $option->id === $selectedVariantId ? 'true' : 'false' }}">
                {{ $option->displayLabel() ?? __('Option :number', ['number' => $loop->iteration]) }}
            </button>
        @endforeach
    </div>
</fieldset>
