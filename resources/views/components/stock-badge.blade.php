@props(['inStock'])

@if ($inStock)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full bg-instock/10 px-2.5 py-1 text-xs font-semibold text-instock']) }}>
        <span class="size-1.5 rounded-full bg-instock"></span>
        {{ __('In stock') }}
    </span>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full bg-pearl px-2.5 py-1 text-xs font-semibold text-muted']) }}>
        <span class="size-1.5 rounded-full bg-muted/60"></span>
        {{ __('Out of stock') }}
    </span>
@endif
