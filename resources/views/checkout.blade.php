<x-layouts.store :title="__('Checkout')">
    <div class="container-page py-12">
        <a href="{{ route('cart') }}" class="text-sm font-medium text-muted hover:text-royal">← {{ __('Back to cart') }}</a>
        <h1 class="mt-3 text-3xl font-bold sm:text-4xl">{{ __('Checkout') }}</h1>
        <div class="mt-8">
            <livewire:checkout-form />
        </div>
    </div>
</x-layouts.store>
