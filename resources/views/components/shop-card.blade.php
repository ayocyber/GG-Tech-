@props(['location', 'map' => false])

<article {{ $attributes->merge(['class' => 'flex flex-col overflow-hidden rounded-card border border-silver/60 bg-white']) }}>
    @if ($map)
        <iframe title="{{ __('Map: :shop', ['shop' => $location->title()]) }}" class="h-64 w-full border-b border-silver/60"
                src="{{ $location->embedUrl() }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    @endif

    <div class="flex flex-1 flex-col p-6 sm:p-7">
        <x-shop-badge :shop="$location->shop" class="self-start" />
        <h3 class="mt-3 text-xl font-semibold text-navy">{{ $location->title() }}</h3>

        <ul class="mt-5 space-y-3 text-sm">
            <li class="flex gap-3"><x-glyph name="map-pin" class="mt-0.5 size-4 shrink-0 text-royal" /> <span>{{ $location->address }}</span></li>
            @if ($location->hours())
                <li class="flex gap-3"><x-glyph name="clock" class="mt-0.5 size-4 shrink-0 text-royal" /> <span>{{ $location->hours() }}</span></li>
            @endif
            <li class="flex gap-3"><x-glyph name="phone" class="mt-0.5 size-4 shrink-0 text-royal" /> <a href="tel:{{ $location->phoneNumber() }}" class="hover:text-royal">{{ $location->phoneNumber() }}</a></li>
        </ul>

        <div class="mt-auto pt-6">
            <a href="{{ $location->directionsUrl() }}" target="_blank" rel="noopener" class="btn-blue">
                <x-glyph name="map-pin" class="size-4" /> {{ __('Get directions') }}
            </a>
        </div>
    </div>
</article>
