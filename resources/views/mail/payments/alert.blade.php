<x-mail::message>
# Paiement reçu — commande {{ $order->number }}

**Montant :** {{ $order->formatMoney($order->total) }}<br>
**Moyen :** {{ $order->payment_network }}<br>
**Référence PayGate :** {{ $order->payment_reference }}<br>
@if ($order->payment_provider_reference)
**Référence Flooz / T-Money :** {{ $order->payment_provider_reference }}<br>
@endif
**Payé le :** {{ $order->paid_at->format('d/m/Y H:i') }}

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
| **Livraison** | | {{ $order->formatMoney($order->delivery_fee) }} |
| **Total payé** | | **{{ $order->formatMoney($order->total) }}** |
</x-mail::table>

@if ($order->notes)
**Notes du client :** {{ $order->notes }}
@endif

<x-mail::button :url="\App\Filament\Resources\Orders\OrderResource::getUrl('view', ['record' => $order])">
Ouvrir dans l'administration
</x-mail::button>
</x-mail::message>
