@php
    use App\Models\Setting;
    use App\Support\WhatsApp;

    $heroProducts = $featuredProducts->filter(fn ($product) => $product->coverImage)->take(3)->values();
@endphp

<x-layouts.store>
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-navy text-white">
        <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
            <div class="absolute -left-40 top-1/3 size-[640px] rounded-full bg-royal/40 blur-[120px]"></div>
            <div class="absolute -right-32 -top-40 size-[520px] rounded-full bg-signal/20 blur-[120px]"></div>
            <svg class="absolute -left-24 top-0 h-full w-auto opacity-20" viewBox="0 0 200 600" fill="none">
                <path d="M190 0C40 90 -10 380 170 600" stroke="#D7102B" stroke-width="10" />
                <path d="M200 40C80 140 40 360 190 560" stroke="#8fa0ff" stroke-width="4" />
            </svg>
        </div>

        <div class="container-page grid items-center gap-12 py-16 lg:grid-cols-12 lg:py-24">
            <div class="lg:col-span-6">
                <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-white/80">
                    <span class="size-1.5 rounded-full bg-signal"></span>
                    {{ __('Technology · Solutions · Trust') }}
                </p>
                <h1 class="mt-6 text-4xl font-bold leading-[1.08] text-white sm:text-5xl lg:text-[56px]">
                    {{ __('Professional IT equipment, delivered in 24–48 h') }}
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-white/70">
                    {{ __('Original toners, printers, copiers, networking and power equipment for companies, resellers and individuals across Togo.') }}
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('shop') }}" class="btn-white px-7 py-3.5">{{ __('Browse the catalogue') }} <x-glyph name="arrow-right" class="size-4" /></a>
                    <a href="{{ route('quote') }}" class="btn-ghost-light px-7 py-3.5">{{ __('Request a quote') }}</a>
                </div>
                <dl class="mt-12 grid max-w-lg grid-cols-3 gap-6 border-t border-white/10 pt-8">
                    <div>
                        <dt class="text-xs text-white/55">{{ __('Products') }}</dt>
                        <dd class="mt-1 font-display text-2xl font-bold">150+</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-white/55">{{ __('Delivery in Lomé') }}</dt>
                        <dd class="mt-1 font-display text-2xl font-bold">24 h</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-white/55">{{ __('In-store pickup') }}</dt>
                        <dd class="mt-1 font-display text-2xl font-bold">{{ __('Free') }}</dd>
                    </div>
                </dl>
            </div>

            @if ($heroProducts->isNotEmpty())
                <div class="relative lg:col-span-6">
                    <div class="grid grid-cols-2 gap-4">
                        @foreach ($heroProducts as $heroProduct)
                            <a href="{{ route('products.show', $heroProduct) }}"
                               @class([
                                   'group relative overflow-hidden rounded-3xl bg-white p-3 shadow-[0_30px_80px_-30px_rgba(0,0,0,0.6)] transition hover:-translate-y-1',
                                   'row-span-2 self-center' => $loop->first,
                               ])>
                                <div class="aspect-square overflow-hidden rounded-2xl">
                                    <x-product-image :image="$heroProduct->coverImage" :alt="$heroProduct->translate('name')" sizes="(min-width: 1024px) 22vw, 45vw" :eager="true" />
                                </div>
                                <div class="px-2 pb-1 pt-3">
                                    <p class="truncate text-xs font-semibold uppercase tracking-wider text-royal">{{ $heroProduct->brand?->name }}</p>
                                    <p class="truncate text-sm font-semibold text-navy">{{ $heroProduct->translate('name') }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <div class="absolute -bottom-5 left-6 hidden items-center gap-3 rounded-2xl bg-white px-4 py-3 text-navy shadow-xl sm:flex">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-signal/10 text-signal"><x-glyph name="store" /></span>
                        <span>
                            <span class="block text-sm font-semibold">{{ __('Free in-store pickup') }}</span>
                            <span class="block text-xs text-muted">Amoutivé, Lomé</span>
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Trust badges --}}
    <section class="border-b border-silver/50 bg-white">
        <div class="container-page grid grid-cols-2 gap-6 py-8 lg:grid-cols-4">
            @foreach ([
                ['shield', __('Genuine products'), __('Original and premium compatible')],
                ['truck', __('Fast delivery'), __('Anywhere in Togo')],
                ['store', __('In-store pickup'), __('Free, in Amoutivé')],
                ['chat', __('Expert advice'), __('Quick answers on WhatsApp')],
            ] as [$icon, $title, $subtitle])
                <div class="flex items-center gap-3.5">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-royal-50 text-royal"><x-glyph :name="$icon" class="size-5.5" /></span>
                    <span>
                        <span class="block text-sm font-semibold text-navy">{{ $title }}</span>
                        <span class="block text-xs text-muted">{{ $subtitle }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Categories --}}
    <section class="container-page py-20">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">{{ __('Shop by category') }}</p>
                <h2 class="mt-3 text-3xl font-semibold sm:text-4xl">{{ __('Everything your office needs') }}</h2>
            </div>
            <a href="{{ route('shop') }}" class="text-sm font-semibold text-royal hover:underline">{{ __('All products') }} →</a>
        </div>
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($menuCategories as $category)
                <a href="{{ route('shop', $category) }}" class="group relative overflow-hidden rounded-card border border-silver/60 bg-white p-7 transition hover:border-royal/30 hover:shadow-[0_24px_50px_-30px_rgba(10,16,51,0.45)]">
                    <div class="absolute -right-6 -top-6 size-28 rounded-full bg-pearl transition group-hover:bg-royal-50"></div>
                    <span class="relative flex size-14 items-center justify-center rounded-2xl bg-navy text-white transition group-hover:bg-royal">
                        <x-glyph :name="$category->icon" class="size-7" />
                    </span>
                    <h3 class="relative mt-6 text-lg font-semibold">{{ $category->translate('name') }}</h3>
                    <p class="relative mt-2 text-sm leading-6 text-muted">{{ $category->translate('description') }}</p>
                    <span class="relative mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-royal">
                        {{ __('Explore') }} <x-glyph name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Toner finder --}}
    <section class="container-page">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-royal to-navy px-6 py-12 text-white sm:px-12 lg:py-16">
            <div class="pointer-events-none absolute -right-20 -top-20 size-80 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative grid items-center gap-8 lg:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">{{ __('Toner finder') }}</p>
                    <h2 class="mt-3 text-3xl font-semibold text-white">{{ __('Find the toner for your printer') }}</h2>
                    <p class="mt-3 text-white/70">{{ __('Type your printer model or the cartridge reference: HP 26A, M402, Canon C-EXV 42…') }}</p>
                </div>
                <form action="{{ route('shop', 'toners') }}" method="GET" class="flex flex-col gap-3 sm:flex-row">
                    <label for="toner-finder" class="sr-only">{{ __('Printer model or cartridge') }}</label>
                    <input id="toner-finder" name="q" type="search" placeholder="{{ __('e.g. LaserJet Pro M402') }}"
                           class="w-full rounded-full border-0 bg-white px-6 py-4 text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-4 focus:ring-white/30">
                    <button type="submit" class="btn-red shrink-0 px-7 py-4">{{ __('Find') }}</button>
                </form>
            </div>
        </div>
    </section>

    {{-- Featured --}}
    @if ($featuredProducts->isNotEmpty())
        <section class="container-page py-20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">{{ __('Premium selection') }}</p>
                    <h2 class="mt-3 text-3xl font-semibold sm:text-4xl">{{ __('Most requested equipment') }}</h2>
                </div>
                <a href="{{ route('shop') }}" class="text-sm font-semibold text-royal hover:underline">{{ __('See all') }} →</a>
            </div>
            <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-product-card :product="$product" :market="$market" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- B2B band --}}
    <section class="bg-navy">
        <div class="container-page grid items-center gap-10 py-16 lg:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('Companies & resellers') }}</p>
                <h2 class="mt-3 text-3xl font-semibold text-white sm:text-4xl">{{ __('Businesses and resellers: get a quote within 24 h') }}</h2>
                <p class="mt-4 max-w-lg text-white/70">{{ __('Volume pricing, a dedicated contact and delivery to your premises. Add products to your quote basket or simply describe what you need.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3 lg:justify-end">
                <a href="{{ route('quote') }}" class="btn-red px-7 py-3.5">{{ __('Request a quote') }}</a>
                <a href="{{ WhatsApp::url(__('Hello GG-TECH, I would like a quote for my company.')) }}" target="_blank" rel="noopener" class="btn-ghost-light px-7 py-3.5">
                    <x-glyph name="chat" /> {{ __('Talk to a sales advisor') }}
                </a>
            </div>
        </div>
    </section>

    {{-- New --}}
    @if ($newProducts->isNotEmpty())
        <section class="container-page py-20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="eyebrow">{{ __('New arrivals') }}</p>
                    <h2 class="mt-3 text-3xl font-semibold sm:text-4xl">{{ __('Just arrived in store') }}</h2>
                </div>
                <a href="{{ route('shop') }}" class="text-sm font-semibold text-royal hover:underline">{{ __('See all') }} →</a>
            </div>
            <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                @foreach ($newProducts as $product)
                    <x-product-card :product="$product" :market="$market" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Brands --}}
    @if ($brands->isNotEmpty())
        <section class="border-y border-silver/50 bg-pearl/60">
            <div class="container-page py-10">
                <p class="text-center text-xs font-semibold uppercase tracking-[0.14em] text-muted">{{ __('The brands we carry') }}</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-4">
                    @foreach ($brands as $brand)
                        <a href="{{ route('shop', ['brand' => $brand->slug]) }}" class="font-display text-xl font-bold tracking-tight text-navy/40 transition hover:text-navy">{{ $brand->name }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Visit the shop --}}
    <section class="container-page py-20">
        <div class="grid overflow-hidden rounded-[2rem] border border-silver/60 lg:grid-cols-2">
            <div class="p-8 sm:p-12">
                <p class="eyebrow">{{ __('Our shop') }}</p>
                <h2 class="mt-3 text-3xl font-semibold">{{ __('Visit us in Amoutivé') }}</h2>
                <p class="mt-4 text-muted">{{ __('See the products, get advice from our team and collect your order for free.') }}</p>
                <ul class="mt-8 space-y-4 text-sm">
                    <li class="flex gap-3"><x-glyph name="map-pin" class="mt-0.5 text-royal" /> {{ Setting::value('address') }}</li>
                    <li class="flex gap-3"><x-glyph name="clock" class="mt-0.5 text-royal" /> {{ Setting::value('opening_hours')[app()->getLocale()] ?? '' }}</li>
                    <li class="flex gap-3"><x-glyph name="phone" class="mt-0.5 text-royal" /> <a href="tel:{{ Setting::value('phone') }}" class="hover:text-royal">{{ Setting::value('phone') }}</a></li>
                </ul>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ Setting::value('maps_url') }}" target="_blank" rel="noopener" class="btn-blue">{{ __('Get directions') }}</a>
                    <a href="{{ route('store') }}" class="btn-outline">{{ __('Shop details') }}</a>
                </div>
            </div>
            <div class="min-h-80 bg-pearl">
                <iframe title="{{ __('Map: GG-TECH shop in Amoutivé, Lomé') }}" class="size-full min-h-80"
                        src="https://www.google.com/maps?q={{ urlencode('Marché d\'Amoutivé, Lomé, Togo') }}&output=embed"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>
</x-layouts.store>
