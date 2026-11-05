<?php

use App\Enums\PaymentStatus;
use App\Mail\NewOrderAlert;
use App\Mail\NewQuoteRequestAlert;
use App\Mail\OrderReceived;
use App\Mail\PaymentConfirmed;
use App\Mail\PaymentReceivedAlert;
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

it('gives the customer their payment references in the language of the order', function () {
    $order = Order::factory()->payingOnline()->has(OrderItem::factory(), 'items')->create([
        'locale' => 'en',
        'customer_name' => 'John Doe',
        'payment_status' => PaymentStatus::Paid,
        'payment_reference' => 'TX-998877',
        'payment_provider_reference' => 'FLZ-123',
        'payment_network' => 'FLOOZ',
        'paid_at' => now(),
    ]);

    $mail = (new PaymentConfirmed($order->load(['items', 'deliveryZone'])))->locale('en');

    $mail->assertHasSubject("Payment received for your GG-TECH order {$order->number}")
        ->assertSeeInHtml('Payment received, thank you John Doe!')
        ->assertSeeInHtml('TX-998877')
        ->assertSeeInHtml('FLZ-123');
});

it('links the staff payment alert to the order in the admin panel', function () {
    $order = Order::factory()->payingOnline()->has(OrderItem::factory(), 'items')->create([
        'payment_status' => PaymentStatus::Paid,
        'payment_reference' => 'TX-998877',
        'payment_network' => 'TMONEY',
        'paid_at' => now(),
    ]);

    $mail = new PaymentReceivedAlert($order->load(['items', 'deliveryZone']));

    $mail->assertSeeInHtml('TX-998877')
        ->assertSeeInHtml(url("/admin/orders/{$order->number}"));
});
