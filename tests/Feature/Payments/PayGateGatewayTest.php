<?php

use App\Models\Order;
use App\Services\Payments\DemoPaymentGateway;
use App\Services\Payments\PayGateGateway;
use App\Services\Payments\PaymentGateway;

it('builds the PayGate hosted payment page link', function () {
    $order = Order::factory()->payingOnline()->create(['number' => 'GG-1042', 'total' => 90000, 'phone' => '+228 90 12 34 56']);
    $gateway = new PayGateGateway('secret-key');

    $url = $gateway->checkoutUrl($order, 'https://gg-tech.example/fr/orders/GG-1042/payment/abc/return');

    expect($url)->toStartWith('https://paygateglobal.com/v1/page?');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toMatchArray([
        'token' => 'secret-key',
        'amount' => '90000',
        'identifier' => $order->payment_identifier,
        'url' => 'https://gg-tech.example/fr/orders/GG-1042/payment/abc/return',
        'phone' => '90123456',
    ]);
});

it('normalises Togolese phone numbers for PayGate', function (string $phone, ?string $expected) {
    expect(PayGateGateway::localPhoneNumber($phone))->toBe($expected);
})->with([
    'international with spaces' => ['+228 90 12 34 56', '90123456'],
    'local' => ['90123456', '90123456'],
    'too short' => ['+228 90 12', null],
    'foreign' => ['+234 803 123 4567', null],
]);

it('uses the demo gateway unless live mode is configured', function () {
    config(['services.paygate.mode' => 'demo']);
    expect(app(PaymentGateway::class))->toBeInstanceOf(DemoPaymentGateway::class);

    config(['services.paygate.mode' => 'live', 'services.paygate.api_key' => 'secret-key']);
    expect(app(PaymentGateway::class))->toBeInstanceOf(PayGateGateway::class);
});

it('refuses to run live without an API key', function () {
    config(['services.paygate.mode' => 'live', 'services.paygate.api_key' => null]);

    app(PaymentGateway::class);
})->throws(RuntimeException::class, 'PAYGATE_API_KEY is not set.');
