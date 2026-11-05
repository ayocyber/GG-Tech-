<x-layouts.store :title="__('Demo payment')">
    <div class="container-page max-w-xl py-16">
        <div class="card overflow-hidden">
            <div class="bg-amber-400 px-6 py-3 text-center text-sm font-bold uppercase tracking-wide text-navy">
                {{ __('Demo mode: no real money is charged') }}
            </div>
            <div class="p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wide text-muted">PayGate Global · {{ __('test page') }}</p>
                <h1 class="mt-2 text-2xl font-bold">{{ __('Order :number', ['number' => $order->number]) }}</h1>
                <p class="mt-1 font-display text-3xl font-bold text-navy">{{ $order->formatMoney($order->total) }}</p>
                <p class="mt-4 text-sm text-muted">{{ __('In live mode, the customer pays here with Flooz or T-Money and confirms on their phone. Choose what should happen for this test.') }}</p>

                <form method="POST" action="{{ route('payments.demo.complete', ['order' => $order, 'key' => $key]) }}" class="mt-6 space-y-5">
                    @csrf
                    <fieldset>
                        <legend class="field-label">{{ __('Network') }}</legend>
                        <div class="mt-2 grid grid-cols-2 gap-3">
                            @foreach (['FLOOZ' => 'Flooz (Moov)', 'TMONEY' => 'T-Money (Yas)'] as $network => $label)
                                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-silver/70 p-4 text-sm font-semibold has-[:checked]:border-royal has-[:checked]:bg-royal-50">
                                    <input type="radio" name="network" value="{{ $network }}" class="accent-royal" @checked($loop->first)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid gap-3">
                        <button type="submit" name="outcome" value="paid" class="btn-red w-full">{{ __('Simulate a successful payment') }}</button>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="submit" name="outcome" value="cancelled" class="rounded-full border border-silver px-4 py-3 text-sm font-semibold text-ink hover:bg-pearl">{{ __('Cancel payment') }}</button>
                            <button type="submit" name="outcome" value="expired" class="rounded-full border border-silver px-4 py-3 text-sm font-semibold text-ink hover:bg-pearl">{{ __('Let it expire') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.store>
