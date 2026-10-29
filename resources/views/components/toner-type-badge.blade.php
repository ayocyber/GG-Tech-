@props(['type'])

@if ($type === \App\Enums\TonerType::Original)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-royal px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-white']) }}>
        <x-glyph name="shield" class="size-3.5" />
        {{ $type->getLabel() }}
    </span>
@elseif ($type === \App\Enums\TonerType::PremiumCompatible)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border border-royal/25 bg-royal-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-royal']) }}>
        <x-glyph name="check" class="size-3.5" />
        {{ $type->getLabel() }}
    </span>
@endif
