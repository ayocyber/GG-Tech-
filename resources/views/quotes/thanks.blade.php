<x-layouts.store :title="__('Quote request :number', ['number' => $quote->number])">
    <div class="container-page max-w-2xl py-16 text-center">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-royal-50 text-royal">
            <x-glyph name="check" class="size-8" />
        </span>
        <h1 class="mt-6 text-3xl font-bold sm:text-4xl">{{ __('Thank you, :name!', ['name' => $quote->contact_name]) }}</h1>
        <p class="mt-3 text-muted">{{ __('Your quote request :number for :company has been received. We reply within one working day.', ['number' => $quote->number, 'company' => $quote->company_name]) }}</p>

        <div class="card mt-10 p-6 text-left">
            <ul class="divide-y divide-silver/50" role="list">
                @foreach ($quote->items as $item)
                    <li class="flex justify-between gap-4 py-3 text-sm">
                        <span>{{ $item->description }}</span>
                        <span class="font-semibold">× {{ $item->quantity }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <a href="{{ \App\Support\WhatsApp::quoteFollowUp($quote) }}" target="_blank" rel="noopener" class="btn-whatsapp mt-8">
            <x-glyph name="chat" /> {{ __('Follow up on WhatsApp') }}
        </a>
        <div class="mt-6">
            <a href="{{ route('shop') }}" class="font-semibold text-royal hover:underline">{{ __('Continue shopping') }} →</a>
        </div>
    </div>
</x-layouts.store>
