<x-layouts.store :title="__('Cart')">
    <div class="container-page py-12">
        <h1 class="text-3xl font-bold sm:text-4xl">{{ __('Your cart') }}</h1>
        <div class="mt-8">
            <livewire:cart-page />
        </div>
    </div>
</x-layouts.store>
