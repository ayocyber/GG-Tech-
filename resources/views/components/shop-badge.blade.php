@props(['shop'])

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider',
    'bg-signal text-white' => $shop === \App\Enums\Shop::HeadOffice,
    'bg-royal-50 text-royal' => $shop !== \App\Enums\Shop::HeadOffice,
]) }}>
    <x-glyph name="store" class="size-3.5" />
    {{ $shop->getLabel() }}
</span>
