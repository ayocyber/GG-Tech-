<x-mail::message>
# Nouvelle commande {{ $order->number }}

**Client :** {{ $order->customer_name }} · {{ $order->phone }} · {{ $order->email }}<br>
**Langue :** {{ strtoupper($order->locale) }}<br>
**Mode :** {{ $order->fulfilment->getLabel() }}{{ $order->deliveryZone ? ' — '.$order->deliveryZone->translate('name', 'fr') : '' }}
@if ($order->pickupLocation())
<br>**Boutique de retrait :** {{ $order->pickupLocation()->title() }} ({{ $order->pickupLocation()->shop->getLabel() }})
@endif

@if ($order->address)
**Adresse :** {{ $order->address }}, {{ $order->city }}{{ $order->landmark ? ' (repère : '.$order->landmark.')' : '' }}
@endif

<x-mail::table>
| Produit | Qté | Total |
| :-- | :-: | --: |
@foreach ($order->items as $item)
| {{ $item->product_name }}{{ $item->variant_label ? ' — '.$item->variant_label : '' }} | {{ $item->quantity }} | {{ $order->formatMoney($item->line_total) }} |
@endforeach
| **Livraison** | | {{ $order->delivery_fee === null ? 'À confirmer' : $order->formatMoney($order->delivery_fee) }} |
| **Total** | | **{{ $order->formatMoney($order->total) }}** |
</x-mail::table>

@if ($order->notes)
**Notes du client :** {{ $order->notes }}
@endif

<x-mail::button :url="\App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order])">
Ouvrir dans l'administration
</x-mail::button>
</x-mail::message>
