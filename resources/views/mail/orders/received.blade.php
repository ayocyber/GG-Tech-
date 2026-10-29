<x-mail::message>
# {{ __('Thank you, :name!', ['name' => $order->customer_name]) }}

{{ __('We have received your order :number. Our team will confirm it on WhatsApp shortly.', ['number' => $order->number]) }}

<x-mail::table>
| {{ __('Product') }} | {{ __('Qty') }} | {{ __('Total') }} |
| :-- | :-: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_label ? ' — '.$item->variant_label : '' }} | {{ $item->quantity }} | {{ $order->formatMoney($item->line_total) }} |
@endforeach
| **{{ __('Subtotal') }}** | | {{ $order->formatMoney($order->subtotal) }} |
| **{{ __('Delivery') }}** | | {{ $order->delivery_fee === null ? __('To be confirmed') : ($order->delivery_fee === 0 ? __('Free') : $order->formatMoney($order->delivery_fee)) }} |
| **{{ __('Total') }}** | | **{{ $order->formatMoney($order->total) }}** |
</x-mail::table>

**{{ $order->fulfilment->getLabel() }}**{{ $order->deliveryZone ? ' — '.$order->deliveryZone->translate('name') : '' }}<br>
@if ($order->address)
{{ $order->address }}, {{ $order->city }}
@elseif ($order->pickupLocation())
{{ $order->pickupLocation()->title() }} ({{ $order->pickupLocation()->shop->getLabel() }}) — {{ $order->pickupLocation()->address }}<br>
[{{ __('Get directions') }}]({{ $order->pickupLocation()->directionsUrl() }})
@endif

<x-mail::button :url="$whatsappUrl" color="success">
{{ __('Confirm on WhatsApp') }}
</x-mail::button>

{{ __('Online payment is coming soon. For now, you pay on delivery or at pickup.') }}

{{ config('app.name') }} — {{ __('Technology · Solutions · Trust') }}
</x-mail::message>
