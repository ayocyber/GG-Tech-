<x-layouts.store :title="__('Business quote')" :description="__('Companies and resellers: request a quote with volume pricing. Reply within one working day.')">
    <section class="bg-navy text-white">
        <div class="container-page py-14">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/60">{{ __('Companies & resellers') }}</p>
            <h1 class="mt-3 text-3xl font-bold text-white sm:text-4xl">{{ __('Request a quote') }}</h1>
            <p class="mt-3 max-w-2xl text-white/70">{{ __('Volume pricing, a dedicated contact and delivery to your premises. We reply within one working day.') }}</p>
        </div>
    </section>
    <div class="container-page py-12">
        <livewire:quote-form />
    </div>
</x-layouts.store>
