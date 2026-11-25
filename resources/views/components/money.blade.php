@props(['amount'])
{{ app(\App\Services\Currency\DisplayCurrency::class)->format((int) $amount) }}
