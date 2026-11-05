<x-mail::message>
# {{ __('Payment received, thank you :name!', ['name' => $order->customer_name]) }}

{{ __('We have received your payment of :amount for order :number. Our team is now preparing your order.', ['amount' => $order->formatMoney($order->total), 'number' => $order->number]) }}

<x-mail::panel>
**{{ __('Payment method') }} :** {{ $order->payment_network }}<br>
**{{ __('Transaction reference') }} :** {{ $order->payment_reference }}<br>
@if ($order->payment_provider_reference)
**{{ __('Mobile money reference') }} :** {{ $order->payment_provider_reference }}<br>
@endif
**{{ __('Date') }} :** {{ $order->paid_at->translatedFormat('j F Y, H:i') }}
</x-mail::panel>

<x-mail::table>
| {{ __('Product') }} | {{ __('Qty') }} | {{ __('Total') }} |
| :-- | :-: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_label ? ' — '.$item->variant_label : '' }} | {{ $item->quantity }} | {{ $order->formatMoney($item->line_total) }} |
@endforeach
| **{{ __('Subtotal') }}** | | {{ $order->formatMoney($order->subtotal) }} |
| **{{ __('Delivery') }}** | | {{ $order->delivery_fee === 0 ? __('Free') : $order->formatMoney($order->delivery_fee) }} |
| **{{ __('Total paid') }}** | | **{{ $order->formatMoney($order->total) }}** |
</x-mail::table>

**{{ $order->fulfilment->getLabel() }}**{{ $order->deliveryZone ? ' — '.$order->deliveryZone->translate('name') : '' }}<br>
@if ($order->address)
{{ $order->address }}, {{ $order->city }}
@elseif ($order->pickupLocation())
{{ $order->pickupLocation()->title() }} ({{ $order->pickupLocation()->shop->getLabel() }}) — {{ $order->pickupLocation()->address }}<br>
[{{ __('Get directions') }}]({{ $order->pickupLocation()->directionsUrl() }})
@endif

<x-mail::button :url="$whatsappUrl" color="success">
{{ __('Contact us on WhatsApp') }}
</x-mail::button>

{{ __('Keep this email: it is your proof of payment.') }}

{{ config('app.name') }} — {{ __('Technology · Solutions · Trust') }}
</x-mail::message>
