<x-mail::message>
# Demande de devis {{ $quote->number }}

**Entreprise :** {{ $quote->company_name }}{{ $quote->tax_id ? ' · NIF '.$quote->tax_id : '' }}<br>
**Contact :** {{ $quote->contact_name }}{{ $quote->job_title ? ' ('.$quote->job_title.')' : '' }} · {{ $quote->phone }} · {{ $quote->email }}<br>
**Ville :** {{ $quote->city ?? '—' }} · **Pour le :** {{ $quote->needed_by?->format('d/m/Y') ?? '—' }}

<x-mail::table>
| Article | Qté |
| :-- | :-: |
@foreach ($quote->items as $item)
| {{ $item->description }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>

@if ($quote->notes)
**Notes :** {{ $quote->notes }}
@endif

<x-mail::button :url="\App\Filament\Resources\QuoteRequests\QuoteRequestResource::getUrl('view', ['record' => $quote])">
Ouvrir dans l'administration
</x-mail::button>
</x-mail::message>
