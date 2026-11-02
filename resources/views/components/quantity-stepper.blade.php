@props(['id' => 'quantity'])

<div class="flex h-12 items-center rounded-full border border-silver bg-white">
    <button type="button" wire:click="decrement" class="flex size-12 items-center justify-center rounded-full text-navy hover:bg-pearl" aria-label="{{ __('Decrease quantity') }}">
        <x-glyph name="minus" class="size-4" />
    </button>
    <label for="{{ $id }}" class="sr-only">{{ __('Quantity') }}</label>
    <input id="{{ $id }}" type="number" min="1" max="999" wire:model.live.debounce.300ms="quantity"
           class="w-12 border-0 bg-transparent text-center text-sm font-semibold [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none">
    <button type="button" wire:click="increment" class="flex size-12 items-center justify-center rounded-full text-navy hover:bg-pearl" aria-label="{{ __('Increase quantity') }}">
        <x-glyph name="plus" class="size-4" />
    </button>
</div>
