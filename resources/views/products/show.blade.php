@php
    use App\Models\Setting;

    $name = $product->translate('name');
    $description = $product->translate('description');
    $shortDescription = $product->translate('short_description');
@endphp

<x-layouts.store :title="$name" :description="$shortDescription">
    <div class="container-page py-8">
        <nav class="flex flex-wrap items-center gap-1.5 text-xs text-muted" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('home') }}" class="hover:text-royal">{{ __('Home') }}</a>
            <x-glyph name="chevron-right" class="size-3" />
            <a href="{{ route('shop', $product->category) }}" class="hover:text-royal">{{ $product->category->translate('name') }}</a>
            <x-glyph name="chevron-right" class="size-3" />
            <span class="line-clamp-1 text-ink">{{ $name }}</span>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-12 lg:gap-14">
            {{-- Gallery --}}
            <div class="lg:col-span-7" x-data="{ active: 0 }">
                <div class="relative aspect-square overflow-hidden rounded-[1.75rem] border border-silver/60 bg-white">
                    @forelse ($product->images as $image)
                        <div x-show="active === {{ $loop->index }}" @if (! $loop->first) x-cloak @endif class="absolute inset-0 p-4">
                            <x-product-image :image="$image" :alt="$name" sizes="(min-width: 1024px) 55vw, 100vw" :eager="$loop->first" />
                        </div>
                    @empty
                        <x-product-image :alt="$name" :icon="$product->category->icon" :model="$product->model" :brand="$product->brand?->name" />
                    @endforelse
                </div>

                @if ($product->images->count() > 1)
                    <div class="mt-4 flex gap-3">
                        @foreach ($product->images as $image)
                            <button type="button" x-on:click="active = {{ $loop->index }}"
                                    class="size-20 overflow-hidden rounded-xl border-2 bg-white transition"
                                    :class="active === {{ $loop->index }} ? 'border-royal' : 'border-silver/60 hover:border-royal/40'"
                                    aria-label="{{ __('Photo :number', ['number' => $loop->iteration]) }}">
                                <x-product-image :image="$image" :alt="''" sizes="80px" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Buy box --}}
            <div class="lg:col-span-5">
                <p class="eyebrow">{{ $product->brand?->name ?? $product->category->translate('name') }}</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-[34px]">{{ $name }}</h1>
                @if ($product->model)
                    <p class="mt-2 text-sm text-muted">{{ __('Reference') }} : <span class="font-medium text-ink">{{ $product->model }}</span></p>
                @endif
                @if ($shortDescription)
                    <p class="mt-5 leading-7 text-muted">{{ $shortDescription }}</p>
                @endif

                <div class="mt-8">
                    <livewire:product-purchase :product="$product" />
                </div>

                <ul class="mt-8 space-y-3 border-t border-silver/50 pt-6 text-sm">
                    <li class="flex gap-3">
                        <x-glyph name="store" class="mt-0.5 text-instock" />
                        <span><strong class="font-semibold text-ink">{{ __('Available for pickup in Amoutivé, Lomé') }}</strong> — {{ __('ready once confirmed') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <x-glyph name="truck" class="mt-0.5 text-royal" />
                        <span>{{ $product->is_heavy ? __('Heavy item: delivery fee confirmed by our team.') : __('Estimated delivery: 1–2 days in Lomé, longer for other regions.') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <x-glyph name="shield" class="mt-0.5 text-royal" />
                        <span>{{ __('Genuine product, checked by our team before delivery.') }}</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Details --}}
        <div class="mt-16 grid gap-10 lg:grid-cols-12" x-data="{ tab: 'details' }">
            <div class="lg:col-span-8">
                <div class="flex gap-1 overflow-x-auto border-b border-silver/60" role="tablist">
                    @foreach (['details' => __('Description'), 'compatibility' => __('Compatibility'), 'delivery' => __('Delivery & pickup')] as $key => $label)
                        @continue($key === 'compatibility' && ! $product->compatibility)
                        <button type="button" role="tab" x-on:click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                                class="-mb-px shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition"
                                :class="tab === '{{ $key }}' ? 'border-royal text-royal' : 'border-transparent text-muted hover:text-ink'">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="py-8 leading-7 text-ink">
                    <div x-show="tab === 'details'" role="tabpanel">
                        @if ($description)
                            <p>{{ $description }}</p>
                        @elseif ($shortDescription)
                            <p>{{ $shortDescription }}</p>
                        @else
                            <p class="text-muted">{{ __('Contact us for the full technical specifications of this product.') }}</p>
                        @endif

                        @if ($product->hasVariantChoice())
                            <h3 class="mt-8 font-sans text-sm font-semibold uppercase tracking-wider text-muted">{{ __('Available options') }}</h3>
                            <table class="mt-3 w-full text-sm">
                                <tbody class="divide-y divide-silver/50">
                                    @foreach ($product->variants as $option)
                                        <tr>
                                            <td class="py-2.5 font-medium">{{ $option->displayLabel() ?? __('Option :number', ['number' => $loop->iteration]) }}</td>
                                            <td class="py-2.5 text-right">{{ $option->priceFor($market) ? $market->format($option->priceFor($market)) : __('Price on request') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    @if ($product->compatibility)
                        <div x-cloak x-show="tab === 'compatibility'" role="tabpanel">
                            <p class="font-semibold text-navy">{{ __('Compatible printers') }}</p>
                            <p class="mt-2">{{ $product->compatibility }}</p>
                            <p class="mt-4 text-sm text-muted">{{ __('Not sure? Send us your printer model on WhatsApp and we will confirm.') }}</p>
                        </div>
                    @endif
                    <div x-cloak x-show="tab === 'delivery'" role="tabpanel" class="space-y-3">
                        <p>{{ __('Free pickup at our shop: :address.', ['address' => Setting::value('address')]) }}</p>
                        <p>{{ __('Delivery anywhere in Togo. The fee depends on your zone and is shown at checkout.') }}</p>
                        <a href="{{ route('store') }}#delivery" class="font-semibold text-royal hover:underline">{{ __('See delivery zones') }} →</a>
                    </div>
                </div>
            </div>

            <aside class="lg:col-span-4">
                <div class="rounded-card bg-navy p-7 text-white">
                    <p class="font-display text-lg font-semibold">{{ __('Buying for your company?') }}</p>
                    <p class="mt-2 text-sm text-white/70">{{ __('Get a quote with volume pricing within one working day.') }}</p>
                    <a href="{{ route('quote') }}" class="btn-white mt-5">{{ __('Request a quote') }}</a>
                </div>
            </aside>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-10">
                <h2 class="text-2xl font-semibold">{{ __('You may also need') }}</h2>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <x-product-card :product="$related" :market="$market" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
