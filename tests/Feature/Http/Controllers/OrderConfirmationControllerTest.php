<?php

use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\URL;

it('shows the thank-you page through a signed link', function () {
    $order = Order::factory()->create(['customer_name' => 'Kossi Mensah']);

    $this->get(URL::signedRoute('orders.thanks', ['order' => $order]))
        ->assertOk()
        ->assertSee('Merci, Kossi Mensah !')
        ->assertSee('Confirmer sur WhatsApp');
});

it('forbids opening an order without a valid signature', function () {
    $order = Order::factory()->create();

    $this->get("/fr/orders/{$order->number}/thank-you")->assertForbidden();
});

it('forbids opening a quote request without a valid signature', function () {
    $quote = QuoteRequest::factory()->create();

    $this->get("/fr/quotes/{$quote->number}/thank-you")->assertForbidden();
});
