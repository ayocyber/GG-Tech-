<?php

use App\Enums\PaymentStatus;
use App\Mail\PaymentConfirmed;
use App\Mail\PaymentReceivedAlert;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config(['services.paygate.mode' => 'demo']);
    Mail::fake();
});

function demoOrder(): Order
{
    return Order::factory()->payingOnline()->has(OrderItem::factory(), 'items')->create(['email' => 'ama@example.com']);
}

it('shows the demo payment page', function () {
    $order = demoOrder();

    $this->get(route('payments.demo', ['order' => $order, 'key' => $order->paymentKey()]))
        ->assertOk()
        ->assertSee("Mode démo : aucun argent réel n'est débité")
        ->assertSee($order->number);
});

it('marks the order paid and emails the customer and the admin', function () {
    $order = demoOrder();

    $this->post(route('payments.demo.complete', ['order' => $order, 'key' => $order->paymentKey()]), [
        'outcome' => 'paid',
        'network' => 'TMONEY',
    ])->assertRedirect(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]));

    $this->get(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]))
        ->assertRedirectContains("/orders/{$order->number}/thank-you");

    expect($order->refresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->payment_method->toBe('mobile_money')
        ->payment_network->toBe('DEMO TMONEY')
        ->payment_reference->toStartWith('DEMO-')
        ->paid_at->not->toBeNull();

    Mail::assertQueued(PaymentConfirmed::class, fn (PaymentConfirmed $mail) => $mail->hasTo('ama@example.com'));
    Mail::assertQueued(PaymentReceivedAlert::class, fn (PaymentReceivedAlert $mail) => $mail->hasTo('greatgodtech@gmail.com'));
});

it('sends the payment mails only once when the customer comes back twice', function () {
    $order = demoOrder();
    $returnUrl = route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]);

    $this->post(route('payments.demo.complete', ['order' => $order, 'key' => $order->paymentKey()]), ['outcome' => 'paid', 'network' => 'FLOOZ']);
    $this->get($returnUrl);
    $this->get($returnUrl);

    Mail::assertQueuedCount(2);
});

it('records a cancelled payment and lets the customer try again', function () {
    $order = demoOrder();
    $firstIdentifier = $order->payment_identifier;

    $this->post(route('payments.demo.complete', ['order' => $order, 'key' => $order->paymentKey()]), ['outcome' => 'cancelled', 'network' => 'FLOOZ']);
    $this->get(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]));

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Cancelled);
    Mail::assertNothingQueued();

    $this->get(URL::signedRoute('orders.thanks', ['order' => $order]))
        ->assertOk()
        ->assertSee("Votre paiement n'a pas abouti")
        ->assertSee(route('payments.retry', ['order' => $order, 'key' => $order->paymentKey()]));

    $this->post(route('payments.retry', ['order' => $order, 'key' => $order->paymentKey()]))
        ->assertRedirect(route('payments.demo', ['order' => $order, 'key' => $order->paymentKey()]));

    expect($order->refresh())
        ->payment_status->toBe(PaymentStatus::Pending)
        ->payment_identifier->not->toBe($firstIdentifier);
});

it('does not start a new attempt once the order is paid', function () {
    $order = demoOrder();
    $this->post(route('payments.demo.complete', ['order' => $order, 'key' => $order->paymentKey()]), ['outcome' => 'paid', 'network' => 'FLOOZ']);
    $identifier = $order->payment_identifier;

    $this->post(route('payments.retry', ['order' => $order, 'key' => $order->paymentKey()]))
        ->assertRedirectContains('/thank-you');

    expect($order->refresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->payment_identifier->toBe($identifier);
});

it('shows the payment confirmation on the thank-you page', function () {
    $order = demoOrder();
    $order->update(['payment_status' => PaymentStatus::Paid, 'payment_reference' => 'TX-123', 'paid_at' => now()]);

    $this->get(URL::signedRoute('orders.thanks', ['order' => $order]))
        ->assertOk()
        ->assertSee('Nous avons bien reçu votre paiement', false)
        ->assertSee('TX-123');
});

it('rejects payment links with a wrong key', function (string $routeName, string $method) {
    $order = demoOrder();

    $this->{$method}(route($routeName, ['order' => $order, 'key' => 'not-the-key']), ['outcome' => 'paid', 'network' => 'FLOOZ'])
        ->assertNotFound();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending);
})->with([
    'demo page' => ['payments.demo', 'get'],
    'demo outcome' => ['payments.demo.complete', 'post'],
    'return' => ['payments.return', 'get'],
    'retry' => ['payments.retry', 'post'],
]);

it('has no demo page in live mode', function () {
    config(['services.paygate.mode' => 'live', 'services.paygate.api_key' => 'test-key']);
    $order = demoOrder();

    $this->post(route('payments.demo.complete', ['order' => $order, 'key' => $order->paymentKey()]), ['outcome' => 'paid', 'network' => 'FLOOZ'])
        ->assertNotFound();
});
