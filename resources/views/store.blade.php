@php
    use App\Enums\Market;
    use App\Models\Setting;
    use App\Support\WhatsApp;
@endphp

<x-layouts.store :title="trans_choice('Our shop|Our shops', $shopLocations->count())" :description="__('Visit GG-TECH in Lomé: advice, demonstrations and free pickup of your orders.')">
    <section class="bg-navy text-white">
        <div class="container-page grid gap-10 py-16 lg:grid-cols-2 lg:items-center">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/60">{{ trans_choice('Our shop|Our shops', $shopLocations->count()) }}</p>
                <h1 class="mt-3 text-4xl font-bold text-white">{{ trans_choice('Visit our shop|Visit our shops', $shopLocations->count()) }}</h1>
                <p class="mt-4 max-w-lg text-white/70">{{ __('See the products, get advice from our team and collect your order for free.') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ WhatsApp::url() }}" target="_blank" rel="noopener" class="btn-whatsapp"><x-glyph name="chat" /> WhatsApp</a>
                </div>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['phone', __('Phone'), Setting::value('phone'), 'tel:'.Setting::value('phone')],
                    ['mail', __('Email'), Setting::value('email'), 'mailto:'.Setting::value('email')],
                ] as [$icon, $label, $value, $href])
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <dt class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-white/55"><x-glyph :name="$icon" class="size-4" /> {{ $label }}</dt>
                        <dd class="mt-2 text-sm text-white"><a href="{{ $href }}" class="break-all hover:underline">{{ $value }}</a></dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <section class="container-page py-16">
        <div @class(['grid gap-6', 'lg:grid-cols-2' => $shopLocations->count() > 1])>
            @foreach ($shopLocations as $location)
                <x-shop-card :location="$location" :map="true" />
            @endforeach
        </div>
        @if (Setting::value('google_business_url'))
            <a href="{{ Setting::value('google_business_url') }}" target="_blank" rel="noopener" class="btn-outline mt-6">{{ __('See our Google reviews') }}</a>
        @endif
    </section>

    <section class="container-page" id="delivery">
        <p class="eyebrow">{{ __('Delivery') }}</p>
        <h2 class="mt-3 text-3xl font-semibold">{{ __('Delivery zones and fees') }}</h2>
        <p class="mt-3 max-w-2xl text-muted">{{ __('The fee is added at checkout according to your zone. Heavy items (copiers, racks, UPS) are quoted by our team.') }}</p>

        <div class="card mt-8 max-w-3xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-pearl text-left text-xs uppercase tracking-wider text-muted">
                    <tr>
                        <th class="px-6 py-3 font-semibold">{{ __('Zone') }}</th>
                        <th class="px-6 py-3 font-semibold">{{ __('Time') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Fee') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-silver/50">
                    <tr>
                        <td class="px-6 py-3.5"><span class="font-semibold">{{ __('In-store pickup') }}</span><span class="block text-xs text-muted">{{ $shopLocations->map->title()->implode(' · ') }}</span></td>
                        <td class="px-6 py-3.5 text-muted">{{ __('Once confirmed') }}</td>
                        <td class="px-6 py-3.5 text-right font-semibold text-instock">{{ __('Free') }}</td>
                    </tr>
                    @foreach ($zones as $zone)
                        <tr>
                            <td class="px-6 py-3.5"><span class="font-semibold">{{ $zone->translate('name') }}</span><span class="block text-xs text-muted">{{ $zone->translate('areas') }}</span></td>
                            <td class="px-6 py-3.5 text-muted">{{ $zone->translate('delivery_time') }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold">{{ $zone->isQuoted() ? __('On quote') : Market::Togo->format($zone->fee) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.store>
