<?php

use App\Mail\NewOrderAlert;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\OrderReceived;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestItem;

it('links the staff order alert to the order in the admin panel', function () {
    $order = Order::factory()->has(OrderItem::factory(), 'items')->create();

    expect((new NewOrderAlert($order->load(['items', 'deliveryZone'])))->render())
        ->toContain(url("/admin/orders/{$order->number}"));
});

it('links the staff quote alert to the request in the admin panel', function () {
    $quote = QuoteRequest::factory()->has(QuoteRequestItem::factory(), 'items')->create();

    expect((new NewQuoteRequestAlert($quote->load('items')))->render())
        ->toContain(url("/admin/quote-requests/{$quote->number}"));
});

it('writes the customer email in the language of the order', function () {
    $order = Order::factory()->has(OrderItem::factory(), 'items')->create(['locale' => 'en', 'customer_name' => 'John Doe']);

    $mail = (new OrderReceived($order->load(['items', 'deliveryZone'])))->locale('en');

    $mail->assertSeeInHtml('Thank you, John Doe!')
        ->assertSeeInHtml('Confirm on WhatsApp');
});
