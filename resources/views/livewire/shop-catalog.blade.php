<div>
    <div class="flex flex-col gap-4 border-b border-silver/50 pb-5 md:flex-row md:items-center md:justify-between">
        <div class="relative w-full md:max-w-sm">
            <label for="catalog-search" class="sr-only">{{ __('Search') }}</label>
            <x-glyph name="search" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input id="catalog-search" type="search" wire:model.live.debounce.400ms="search"
                   placeholder="{{ __('Model, brand, printer…') }}" class="field-input rounded-full pl-10">
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-ink">
                <input type="checkbox" wire:model.live="inStockOnly" class="size-4 rounded border-silver text-royal focus:ring-royal">
                {{ __('In stock only') }}
            </label>

            <label for="catalog-sort" class="sr-only">{{ __('Sort by') }}</label>
            <select id="catalog-sort" wire:model.live="sort" class="rounded-full border border-silver bg-white py-2.5 pl-4 pr-9 text-sm font-medium focus:border-royal focus:outline-none focus:ring-4 focus:ring-royal/10">
                <option value="featured">{{ __('Recommended') }}</option>
                <option value="price_asc">{{ __('Price: low to high') }}</option>
                <option value="price_desc">{{ __('Price: high to low') }}</option>
                <option value="name">{{ __('Name') }}</option>
            </select>
        </div>
    </div>

    @if ($brands->count() > 1)
        <div class="mt-5 flex gap-2 overflow-x-auto pb-2 [scrollbar-width:thin]" role="group" aria-label="{{ __('Brands') }}">
            <button type="button" wire:click="$set('brand', '')"
                    @class(['shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition', 'border-navy bg-navy text-white' => $brand === '', 'border-silver bg-white text-ink hover:border-royal' => $brand !== ''])>
                {{ __('All brands') }}
            </button>
            @foreach ($brands as $brandOption)
                <button type="button" wire:click="$set('brand', '{{ $brandOption->slug }}')" wire:key="brand-{{ $brandOption->id }}"
                        @class(['shrink-0 rounded-full border px-4 py-2 text-sm font-medium transition', 'border-navy bg-navy text-white' => $brand === $brandOption->slug, 'border-silver bg-white text-ink hover:border-royal' => $brand !== $brandOption->slug])>
                    {{ $brandOption->name }} <span class="opacity-60">{{ $brandOption->products_count }}</span>
                </button>
            @endforeach
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between text-sm text-muted">
        <p wire:loading.remove>{{ trans_choice(':count product|:count products', $products->total(), ['count' => $products->total()]) }}</p>
        <p wire:loading class="text-royal">{{ __('Loading…') }}</p>
        @if ($hasFilters)
            <button type="button" wire:click="clearFilters" class="font-medium text-royal hover:underline">{{ __('Clear filters') }}</button>
        @endif
    </div>

    @if ($products->isEmpty())
        <div class="mt-8 rounded-card border border-dashed border-silver bg-pearl/60 px-6 py-16 text-center">
            <x-glyph name="search" class="mx-auto size-10 text-royal/40" />
            <p class="mt-4 font-display text-lg font-semibold text-navy">{{ __('No product matches your search.') }}</p>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted">{{ __('Ask us on WhatsApp: we will find it for you.') }}</p>
            <a href="{{ \App\Support\WhatsApp::url(__('Hello GG-TECH, I am looking for: :search', ['search' => $search])) }}" target="_blank" rel="noopener" class="btn-whatsapp mt-6">
                <x-glyph name="chat" /> {{ __('Ask on WhatsApp') }}
            </a>
        </div>
    @else
        <div class="mt-4 grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4" wire:loading.class="opacity-60">
            @foreach ($products as $product)
                <x-product-card :product="$product" :market="$market" wire:key="product-{{ $product->id }}" />

                @if ($loop->iteration === 8 && $products->count() > 8)
                    <a href="{{ route('quote') }}" class="col-span-2 flex flex-col justify-between gap-4 rounded-card bg-navy p-6 text-white md:col-span-3 xl:col-span-4 sm:flex-row sm:items-center" wire:key="bulk-banner">
                        <span>
                            <span class="block font-display text-lg font-semibold">{{ __('Buying in bulk?') }}</span>
                            <span class="mt-1 block text-sm text-white/70">{{ __('Companies and resellers: request a quote and get a better price.') }}</span>
                        </span>
                        <span class="btn-white shrink-0">{{ __('Request a quote') }} <x-glyph name="arrow-right" class="size-4" /></span>
                    </a>
                @endif
            @endforeach
        </div>

        <div class="mt-10">
            {{ $products->links(data: ['scrollTo' => '#catalog']) }}
        </div>
    @endif
</div>
