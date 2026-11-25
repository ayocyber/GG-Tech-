<?php

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payments\FedaPayGateway;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function fedaPay(): FedaPayGateway
{
    return new FedaPayGateway(secretKey: 'sk_sandbox_test', baseUrl: 'https://sandbox-api.fedapay.com/v1');
}

function fedaPayOrder(array $attributes = []): Order
{
    return Order::factory()->payingOnline(PaymentProvider::FedaPay)->create([
        'number' => 'GG-1042',
        'total' => 90000,
        'customer_name' => 'Ama Koffi Mensah',
        'email' => 'ama@example.com',
        'phone' => '+228 90 12 34 56',
        ...$attributes,
    ]);
}

it('creates a FedaPay transaction in FCFA and returns its payment page', function (array $created) {
    Http::fake([
        'sandbox-api.fedapay.com/v1/transactions' => Http::response($created, 201),
        'sandbox-api.fedapay.com/v1/transactions/987/token' => Http::response(['token' => 'tok', 'url' => 'https://sandbox-process.fedapay.com/tok']),
    ]);
    $order = fedaPayOrder();

    $url = fedaPay()->checkoutUrl($order, 'https://shop.test/return');

    expect($url)->toBe('https://sandbox-process.fedapay.com/tok')
        ->and($order->refresh()->payment_transaction_id)->toBe('987');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://sandbox-api.fedapay.com/v1/transactions'
        && $request->hasHeader('Authorization', 'Bearer sk_sandbox_test')
        && $request['amount'] === 90000
        && $request['currency'] === ['iso' => 'XOF']
        && $request['callback_url'] === 'https://shop.test/return'
        && $request['merchant_reference'] === $order->payment_identifier
        && $request['customer'] === [
            'firstname' => 'Ama',
            'lastname' => 'Koffi Mensah',
            'email' => 'ama@example.com',
            'phone_number' => ['number' => '+22890123456', 'country' => 'tg'],
        ]);
})->with([
    'flat answer' => [['id' => 987, 'reference' => 'trx_1', 'status' => 'pending']],
    'wrapped answer' => [['v1/transaction' => ['id' => 987, 'reference' => 'trx_1', 'status' => 'pending']]],
]);

it('leaves out a phone number whose country is not certain', function () {
    Http::fake([
        '*/transactions' => Http::response(['id' => 5], 201),
        '*/transactions/5/token' => Http::response(['url' => 'https://process.fedapay.com/x']),
    ]);

    fedaPay()->checkoutUrl(fedaPayOrder(['phone' => '+33 6 12 34 56 78']), 'https://shop.test/return');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/transactions')
        && ! array_key_exists('phone_number', $request['customer']));
});

it('reads the payment result from the transaction API', function (string $fedaPayStatus, PaymentStatus $expected) {
    Http::fake(['sandbox-api.fedapay.com/v1/transactions/987' => Http::response(['v1/transaction' => [
        'id' => 987,
        'reference' => 'trx_abc',
        'transaction_key' => 'KEY123',
        'amount' => 90000,
        'status' => $fedaPayStatus,
        'mode' => 'moov_tg',
        'approved_at' => '2026-11-25T10:00:00Z',
    ]])]);

    $result = fedaPay()->status(fedaPayOrder(['payment_transaction_id' => '987']));

    expect($result->status)->toBe($expected);

    if ($expected === PaymentStatus::Paid) {
        expect($result)
            ->txReference->toBe('trx_abc')
            ->providerReference->toBe('KEY123')
            ->network->toBe('FedaPay MOOV_TG');
    }
})->with([
    'approved' => ['approved', PaymentStatus::Paid],
    'transferred' => ['transferred', PaymentStatus::Paid],
    'pending' => ['pending', PaymentStatus::Pending],
    'declined' => ['declined', PaymentStatus::Cancelled],
    'canceled' => ['canceled', PaymentStatus::Cancelled],
    'refunded' => ['refunded', PaymentStatus::Cancelled],
    'expired' => ['expired', PaymentStatus::Expired],
]);

it('does not mark an order paid when the approved amount is different', function () {
    Http::fake(['*/transactions/987' => Http::response(['id' => 987, 'amount' => 100, 'status' => 'approved'])]);

    expect(fedaPay()->status(fedaPayOrder(['payment_transaction_id' => '987']))->status)->toBe(PaymentStatus::Pending);
});

it('looks the transaction up by our reference when its id was never saved', function () {
    Http::fake(['*/transactions/merchant/*' => Http::response(['id' => 3, 'amount' => 90000, 'status' => 'approved'])]);
    $order = fedaPayOrder();

    expect(fedaPay()->status($order)->status)->toBe(PaymentStatus::Paid);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/transactions/merchant/'.$order->payment_identifier));
});

it('reports FedaPay errors and outages to the caller', function (Closure $fake) {
    $fake();

    fedaPay()->status(fedaPayOrder(['payment_transaction_id' => '987']));
})->with([
    'error answer' => fn () => fn () => Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]),
    'unreachable' => fn () => fn () => Http::fake(['*' => Http::failedConnection()]),
])->throws(PaymentGatewayException::class);
