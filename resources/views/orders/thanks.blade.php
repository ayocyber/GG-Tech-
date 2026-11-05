@php
    use App\Enums\FulfilmentMethod;
    use App\Enums\PaymentStatus;
    use App\Support\WhatsApp;
@endphp

<x-layouts.store :title="__('Order :number', ['number' => $order->number])">
    <div class="container-page max-w-3xl py-16">
        <div class="text-center">
            @if ($order->paysOnline() && ! $order->isPaid())
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                    <x-glyph name="sparkles" class="size-8" />
                </span>
                <h1 class="mt-6 text-3xl font-bold sm:text-4xl">
                    {{ $order->payment_status === PaymentStatus::Pending ? __('Waiting for your payment') : __('Your payment did not go through') }}
                </h1>
                <p class="mt-3 text-muted">
                    {{ $order->payment_status === PaymentStatus::Pending
                        ? __('If you have just confirmed the payment on your phone, refresh this page in a moment. Otherwise, try again.')
                        : __('No money was taken. Your order :number is saved: you can try again, or confirm it on WhatsApp and pay on delivery or at pickup.', ['number' => $order->number]) }}
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <form method="POST" action="{{ route('payments.retry', ['order' => $order, 'key' => $order->paymentKey()]) }}">
                        @csrf
                        <button type="submit" class="btn-red px-8 py-4 text-base">{{ __('Pay :amount', ['amount' => $order->formatMoney($order->total)]) }}</button>
                    </form>
                    <a href="{{ WhatsApp::orderConfirmation($order) }}" target="_blank" rel="noopener" class="btn-whatsapp px-8 py-4 text-base">
                        <x-glyph name="chat" /> {{ __('Confirm on WhatsApp') }}
                    </a>
                </div>
            @else
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-instock/10 text-instock">
                    <x-glyph name="check" class="size-8" />
                </span>
                <h1 class="mt-6 text-3xl font-bold sm:text-4xl">{{ __('Thank you, :name!', ['name' => $order->customer_name]) }}</h1>
                @if ($order->isPaid())
                    <p class="mt-3 text-muted">{{ __('We have received your payment of :amount for order :number. Our team is now preparing your order.', ['amount' => $order->formatMoney($order->total), 'number' => $order->number]) }}</p>
                    <p class="mt-2 text-sm text-muted">{{ __('Transaction reference') }} : <span class="font-semibold text-ink">{{ $order->payment_reference }}</span></p>
                @else
                    <p class="mt-3 text-muted">{{ __('Your order :number has been received. Confirm it on WhatsApp so our team can prepare it right away.', ['number' => $order->number]) }}</p>
                @endif
                <a href="{{ WhatsApp::orderConfirmation($order) }}" target="_blank" rel="noopener" class="btn-whatsapp mt-8 px-8 py-4 text-base">
                    <x-glyph name="chat" /> {{ $order->isPaid() ? __('Contact us on WhatsApp') : __('Confirm on WhatsApp') }}
                </a>
            @endif
        </div>

        <div class="card mt-12 p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">{{ __('Order :number', ['number' => $order->number]) }}</h2>
                <span class="text-sm text-muted">{{ $order->created_at->translatedFormat('j F Y, H:i') }}</span>
            </div>
            <ul class="mt-5 divide-y divide-silver/50" role="list">
                @foreach ($order->items as $item)
                    <li class="flex justify-between gap-4 py-3 text-sm">
                        <span><span class="font-semibold">{{ $item->quantity }} ×</span> {{ $item->product_name }}{{ $item->variant_label ? ' — '.$item->variant_label : '' }}</span>
                        <span class="font-semibold">{{ $order->formatMoney($item->line_total) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-4 space-y-2 border-t border-silver/50 pt-4 text-sm">
                <div class="flex justify-between"><dt class="text-muted">{{ __('Subtotal') }}</dt><dd>{{ $order->formatMoney($order->subtotal) }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-muted">{{ $order->fulfilment->getLabel() }}{{ $order->deliveryZone ? ' — '.$order->deliveryZone->translate('name') : '' }}</dt>
                    <dd>{{ $order->delivery_fee === null ? __('Confirmed by our team') : ($order->delivery_fee === 0 ? __('Free') : $order->formatMoney($order->delivery_fee)) }}</dd>
                </div>
                <div class="flex justify-between border-t border-silver/50 pt-3 text-base font-semibold"><dt>{{ __('Total') }}</dt><dd class="font-display text-navy">{{ $order->formatMoney($order->total) }}</dd></div>
            </dl>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-card bg-pearl p-6 text-sm">
                <p class="font-semibold text-navy">{{ __('What happens next?') }}</p>
                <ol class="mt-3 list-decimal space-y-1.5 pl-4 text-muted">
                    @if ($order->isPaid())
                        <li>{{ __('Your payment is confirmed.') }}</li>
                        <li>{{ $order->fulfilment === FulfilmentMethod::Pickup ? __('We tell you when your order is ready for pickup.') : __('We agree on the delivery time with you.') }}</li>
                    @else
                        <li>{{ __('We confirm availability on WhatsApp.') }}</li>
                        <li>{{ $order->fulfilment === FulfilmentMethod::Pickup ? __('We tell you when your order is ready for pickup.') : __('We agree on the delivery time with you.') }}</li>
                        <li>{{ $order->paysOnline() ? __('You pay online, or on delivery or at pickup.') : __('You pay on delivery or at pickup.') }}</li>
                    @endif
                </ol>
            </div>
            <div class="rounded-card bg-pearl p-6 text-sm">
                <p class="font-semibold text-navy">{{ $order->fulfilment === FulfilmentMethod::Pickup ? __('Pickup address') : __('Delivery address') }}</p>
                <p class="mt-3 text-muted">
                    @if ($order->pickupLocation())
                        <span class="block font-semibold text-ink">{{ $order->pickupLocation()->title() }} · {{ $order->pickupLocation()->shop->getLabel() }}</span>
                        {{ $order->pickupLocation()->address }}
                        <a href="{{ $order->pickupLocation()->directionsUrl() }}" target="_blank" rel="noopener" class="mt-2 block font-semibold text-royal hover:underline">{{ __('Get directions') }} →</a>
                    @else
                        {{ $order->address }}, {{ $order->city }}@if ($order->landmark)<br>{{ __('Landmark') }} : {{ $order->landmark }}@endif
                    @endif
                </p>
            </div>
        </div>

        @if (! $order->paysOnline() || $order->isPaid())
            <p class="mt-10 text-center text-sm text-muted">{{ __('A confirmation email has been sent to :email.', ['email' => $order->email]) }}</p>
        @endif
        <div class="mt-4 text-center">
            <a href="{{ route('shop') }}" class="font-semibold text-royal hover:underline">{{ __('Continue shopping') }} →</a>
        </div>
    </div>
</x-layouts.store>
