<?php

namespace App\Actions;

use App\Enums\Market;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\QuoteRequestReceived;
use App\Models\QuoteRequest;
use App\Models\Setting;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\QuoteBasket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SubmitQuoteRequest
{
    public function __construct(private QuoteBasket $basket) {}

    /**
     * @param  array{company_name: string, contact_name: string, job_title?: string|null, email: string, phone: string, tax_id?: string|null, city?: string|null, needed_by?: string|null, notes?: string|null}  $details
     * @param  list<array{description: string, quantity: int}>  $extraItems  Items the customer typed in that are not on the site.
     */
    public function handle(array $details, array $extraItems, Market $market): QuoteRequest
    {
        $lines = $this->basket->lines($market);

        $quote = DB::transaction(function () use ($details, $extraItems, $market, $lines) {
            $quote = QuoteRequest::create([
                ...$details,
                'locale' => app()->getLocale(),
                'market' => $market,
            ]);

            $quote->update(['number' => 'Q-'.($quote->id + 1000)]);

            $quote->items()->createMany([
                ...$lines->map(fn (BasketLine $line) => [
                    'product_id' => $line->product()->id,
                    'product_variant_id' => $line->variant->id,
                    'description' => trim($line->product()->translate('name').' '.$line->variant->displayLabel()),
                    'quantity' => $line->quantity,
                ])->all(),
                ...$extraItems,
            ]);

            return $quote;
        });

        $this->basket->clear();

        $quote->load('items');

        // The request is saved either way, so a mail outage must not fail the form.
        rescue(fn () => Mail::to($quote->email)->locale($quote->locale)->queue(new QuoteRequestReceived($quote)));
        rescue(fn () => Mail::to(Setting::value('email'))->queue(new NewQuoteRequestAlert($quote)));

        return $quote;
    }
}
