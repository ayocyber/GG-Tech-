<?php

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\FedaPayWebhookController;
use App\Livewire\CheckoutForm;
use App\Mail\PaymentConfirmed;
use App\Mail\PaymentReceivedAlert;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Baskets\Cart;
use App\Services\Payments\PaymentSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Setting::store([
        'payments.online_enabled' => true,
        'payments.provider' => 'fedapay',
        'payments.fedapay.mode' => 'sandbox',
    ]);
    app(PaymentSettings::class)->storeSecret('payments.fedapay.secret_key', 'sk_sandbox_test');
    app(PaymentSettings::class)->storeSecret('payments.fedapay.webhook_secret', 'wh_sandbox_secret');
});

function approvedTransaction(Order $order, string $status = 'approved'): array
{
    return ['v1/transaction' => ['id' => (int) $order->payment_transaction_id, 'reference' => 'trx_9', 'amount' => $order->total, 'status' => $status]];
}

function signedWebhook(array $event, string $secret = 'wh_sandbox_secret', ?int $timestamp = null): array
{
    $body = json_encode($event);
    $timestamp ??= time();

    return [$body, ['X-FEDAPAY-SIGNATURE' => 't='.$timestamp.',s='.hash_hmac('sha256', $timestamp.'.'.$body, $secret)]];
}

it('sends an online order to the FedaPay payment page', function () {
    Http::fake([
        'sandbox-api.fedapay.com/v1/transactions' => Http::response(['id' => 555], 201),
        'sandbox-api.fedapay.com/v1/transactions/555/token' => Http::response(['url' => 'https://sandbox-process.fedapay.com/abc']),
    ]);
    $product = Product::factory()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->assertSee('Payer maintenant par mobile money ou carte')
        ->set('customer_name', 'Ama Koffi')
        ->set('email', 'ama@example.com')
        ->set('phone', '+228 90 12 34 56')
        ->set('fulfilment', 'pickup')
        ->set('payment_option', CheckoutForm::PayOnline)
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect('https://sandbox-process.fedapay.com/abc');

    expect(Order::query()->sole())
        ->payment_gateway->toBe(PaymentProvider::FedaPay)
        ->payment_transaction_id->toBe('555')
        ->payment_status->toBe(PaymentStatus::Pending);
});

it('keeps the order and offers to try again when FedaPay cannot be reached', function () {
    Http::fake(['*' => Http::failedConnection()]);
    $product = Product::factory()->priced(45000)->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->set('customer_name', 'Ama Koffi')
        ->set('email', 'ama@example.com')
        ->set('phone', '+228 90 12 34 56')
        ->set('fulfilment', 'pickup')
        ->set('payment_option', CheckoutForm::PayOnline)
        ->call('placeOrder')
        ->assertRedirectContains('/thank-you');

    expect(Order::query()->sole()->payment_status)->toBe(PaymentStatus::Pending);
});

it('marks the order paid when the customer comes back and FedaPay confirms it', function () {
    $order = Order::factory()->payingOnline(PaymentProvider::FedaPay)->has(OrderItem::factory(), 'items')
        ->create(['total' => 45000, 'payment_transaction_id' => '555']);
    Http::fake(['*/transactions/555' => Http::response(approvedTransaction($order))]);

    // FedaPay adds ?id=&status= to the return URL; only the API answer counts.
    $this->get(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]).'?id=555&status=approved')
        ->assertRedirectContains('/thank-you');

    expect($order->refresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->payment_reference->toBe('trx_9');
    Mail::assertQueued(PaymentConfirmed::class);
    Mail::assertQueued(PaymentReceivedAlert::class);
});

it('ignores a forged "approved" in the return URL', function () {
    $order = Order::factory()->payingOnline(PaymentProvider::FedaPay)->create(['payment_transaction_id' => '555']);
    Http::fake(['*/transactions/555' => Http::response(approvedTransaction($order, 'pending'))]);

    $this->get(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]).'?id=555&status=approved');

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending);
});

it('records a declined payment so the customer can try again', function () {
    $order = Order::factory()->payingOnline(PaymentProvider::FedaPay)->create(['payment_transaction_id' => '555']);
    Http::fake(['*/transactions/555' => Http::response(approvedTransaction($order, 'declined'))]);

    $this->get(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]));

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Cancelled);
});

it('confirms a payment from a signed FedaPay webhook', function () {
    $order = Order::factory()->payingOnline(PaymentProvider::FedaPay)->has(OrderItem::factory(), 'items')
        ->create(['payment_transaction_id' => '555']);
    Http::fake(['*/transactions/555' => Http::response(approvedTransaction($order))]);
    [$body, $headers] = signedWebhook(['name' => 'transaction.approved', 'entity' => ['id' => 555, 'status' => 'approved']]);

    $this->call('POST', '/fedapay/webhook', server: ['HTTP_X_FEDAPAY_SIGNATURE' => $headers['X-FEDAPAY-SIGNATURE'], 'CONTENT_TYPE' => 'application/json'], content: $body)
        ->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
});

it('rejects a webhook with a wrong or old signature', function (string $secret, int $age) {
    $order = Order::factory()->payingOnline(PaymentProvider::FedaPay)->create(['payment_transaction_id' => '555']);
    Http::fake();
    [$body, $headers] = signedWebhook(['name' => 'transaction.approved', 'entity' => ['id' => 555]], $secret, time() - $age);

    $this->call('POST', '/fedapay/webhook', server: ['HTTP_X_FEDAPAY_SIGNATURE' => $headers['X-FEDAPAY-SIGNATURE'], 'CONTENT_TYPE' => 'application/json'], content: $body)
        ->assertStatus(400);

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending);
    Http::assertNothingSent();
})->with([
    'wrong secret' => ['someone-else', 0],
    'older than 5 minutes' => ['wh_sandbox_secret', 600],
]);

it('acknowledges a signed webhook for a transaction it does not know', function () {
    Http::fake();
    [$body, $headers] = signedWebhook(['name' => 'transaction.approved', 'entity' => ['id' => 999]]);

    $this->call('POST', '/fedapay/webhook', server: ['HTTP_X_FEDAPAY_SIGNATURE' => $headers['X-FEDAPAY-SIGNATURE'], 'CONTENT_TYPE' => 'application/json'], content: $body)
        ->assertOk();

    Http::assertNothingSent();
});

it('checks the signature header format strictly', function (string $header) {
    expect(FedaPayWebhookController::hasValidSignature('{}', $header, 'secret'))->toBeFalse();
})->with(['empty' => '', 'no timestamp' => 's=abc', 'no signature' => 't=123', 'not a number' => 't=abc,s=def']);

it('keeps checking an order with the gateway it was paid with after a switch', function () {
    config(['services.paygate.api_key' => 'paygate-key']);
    $order = Order::factory()->payingOnline(PaymentProvider::PayGate)->has(OrderItem::factory(), 'items')->create(['total' => 50000]);
    Http::fake(['paygateglobal.com/*' => Http::response(['status' => 0, 'tx_reference' => 'PG1', 'payment_method' => 'FLOOZ'])]);

    $this->get(route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]));

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'paygateglobal.com/api/v2/status'));
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'fedapay.com'));
});

it('starts a new attempt with the gateway that is active now', function () {
    $order = Order::factory()->payingOnline(PaymentProvider::Demo)->create(['payment_status' => PaymentStatus::Cancelled]);
    Http::fake([
        '*/transactions' => Http::response(['id' => 777], 201),
        '*/transactions/777/token' => Http::response(['url' => 'https://sandbox-process.fedapay.com/new']),
    ]);

    $this->post(route('payments.retry', ['order' => $order, 'key' => $order->paymentKey()]))
        ->assertRedirect('https://sandbox-process.fedapay.com/new');

    expect($order->refresh())
        ->payment_gateway->toBe(PaymentProvider::FedaPay)
        ->payment_transaction_id->toBe('777');
});
