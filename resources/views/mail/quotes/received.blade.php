<x-mail::message>
# {{ __('Thank you, :name!', ['name' => $quote->contact_name]) }}

{{ __('Your quote request :number for :company has been received. We reply within one working day.', ['number' => $quote->number, 'company' => $quote->company_name]) }}

<x-mail::table>
| {{ __('Item') }} | {{ __('Qty') }} |
| :-- | :-: |
@foreach ($quote->items as $item)
| {{ $item->description }} | {{ $item->quantity }} |
@endforeach
</x-mail::table>

{{ config('app.name') }} — {{ __('Technology · Solutions · Trust') }}
</x-mail::message>
