<x-layouts.store
    :title="$category?->translate('name') ?? __('All products')"
    :description="$category?->translate('description')"
>
    <section class="border-b border-silver/50 bg-pearl/70">
        <div class="container-page py-10 sm:py-14">
            <nav class="flex items-center gap-1.5 text-xs text-muted" aria-label="{{ __('Breadcrumb') }}">
                <a href="{{ route('home') }}" class="hover:text-royal">{{ __('Home') }}</a>
                <x-glyph name="chevron-right" class="size-3" />
                @if ($category)
                    <a href="{{ route('shop') }}" class="hover:text-royal">{{ __('Shop') }}</a>
                    <x-glyph name="chevron-right" class="size-3" />
                    <span class="text-ink">{{ $category->translate('name') }}</span>
                @else
                    <span class="text-ink">{{ __('Shop') }}</span>
                @endif
            </nav>
            <h1 class="mt-4 text-3xl font-bold sm:text-4xl">{{ $category?->translate('name') ?? __('All products') }}</h1>
            <p class="mt-3 max-w-2xl text-muted">{{ $category?->translate('description') ?? __('Toners, printers, networking, computers and power equipment, in stock in Lomé.') }}</p>
        </div>
    </section>

    <section class="container-page pt-6" id="catalog">
        <nav class="no-scrollbar relative -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-1 sm:-mx-6 sm:px-6 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0"
             aria-label="{{ __('Categories') }}"
             x-data
             x-init="const current = $el.querySelector('[aria-current]'); if (current) { $el.scrollLeft = current.offsetLeft - ($el.clientWidth - current.offsetWidth) / 2 }">
            <a href="{{ route('shop') }}" @if (! $category) aria-current="page" @endif
               @class(['flex shrink-0 items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-semibold transition', 'border-navy bg-navy text-white' => ! $category, 'border-silver bg-white text-navy hover:border-royal' => $category])>
                {{ __('All products') }}
            </a>
            @foreach ($menuCategories as $menuCategory)
                <a href="{{ route('shop', $menuCategory) }}" @if ($category?->is($menuCategory)) aria-current="page" @endif
                   @class(['flex shrink-0 items-center gap-2 rounded-full border px-4 py-2.5 text-sm font-semibold transition', 'border-navy bg-navy text-white' => $category?->is($menuCategory), 'border-silver bg-white text-navy hover:border-royal' => ! $category?->is($menuCategory)])>
                    <x-glyph :name="$menuCategory->icon" class="size-4 shrink-0" />
                    {{ $menuCategory->translate('name') }}
                </a>
            @endforeach
        </nav>

        <livewire:shop-catalog :category="$category" />
    </section>
</x-layouts.store>
