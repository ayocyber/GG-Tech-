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

    <section class="container-page pt-8" id="catalog">
        <livewire:shop-catalog :category="$category" />
    </section>
</x-layouts.store>
