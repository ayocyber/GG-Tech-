<?php

use App\Enums\PaymentStatus;
use App\Mail\PaymentConfirmed;
use App\Mail\PaymentReceivedAlert;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['services.paygate.mode' => 'live', 'services.paygate.api_key' => 'secret-key']);
    Mail::fake();
});

function liveOrder(): Order
{
    return Order::factory()->payingOnline()->has(OrderItem::factory(), 'items')->create(['total' => 50000]);
}

/**
 * @return array<string, mixed>
 */
function callbackPayload(Order $order): array
{
    return [
        'tx_reference' => 'TX-998877',
        'identifier' => $order->payment_identifier,
        'payment_reference' => 'FLZ-123',
        'amount' => 50000,
        'datetime' => '2026-11-05 10:15:00',
        'payment_method' => 'FLOOZ',
        'phone_number' => '90123456',
    ];
}

it('marks the order paid once the PayGate status API confirms it', function () {
    Http::fake(['paygateglobal.com/api/v2/status' => Http::response([
        'tx_reference' => 'TX-998877',
        'payment_reference' => 'FLZ-123',
        'status' => 0,
        'datetime' => '2026-11-05 10:15:00',
        'payment_method' => 'FLOOZ',
    ])]);
    $order = liveOrder();

    $this->postJson('/paygate/callback', callbackPayload($order))
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($order->refresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->payment_reference->toBe('TX-998877')
        ->payment_provider_reference->toBe('FLZ-123')
        ->payment_network->toBe('FLOOZ')
        ->paid_at->format('Y-m-d H:i')->toBe('2026-11-05 10:15');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://paygateglobal.com/api/v2/status'
        && $request['auth_token'] === 'secret-key'
        && $request['identifier'] === $order->payment_identifier);
    Mail::assertQueued(PaymentConfirmed::class);
    Mail::assertQueued(PaymentReceivedAlert::class);
});

it('ignores a forged confirmation that PayGate does not back up', function () {
    Http::fake(['paygateglobal.com/api/v2/status' => Http::response(['status' => 2])]);
    $order = liveOrder();

    $this->postJson('/paygate/callback', callbackPayload($order))->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending);
    Mail::assertNothingQueued();
});

it('records an expired payment', function () {
    Http::fake(['paygateglobal.com/api/v2/status' => Http::response(['status' => 4])]);
    $order = liveOrder();

    $this->postJson('/paygate/callback', callbackPayload($order))->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Expired);
});

it('sends the payment mails once when PayGate confirms the same payment twice', function () {
    Http::fake(['paygateglobal.com/api/v2/status' => Http::response(['status' => 0, 'tx_reference' => 'TX-1'])]);
    $order = liveOrder();

    $this->postJson('/paygate/callback', callbackPayload($order))->assertOk();
    $this->postJson('/paygate/callback', callbackPayload($order))->assertOk();

    Mail::assertQueuedCount(2);
    Http::assertSentCount(1);
});

it('leaves the order pending when PayGate cannot be reached', function () {
    Http::fake(['paygateglobal.com/*' => Http::response('Server error', 500)]);
    $order = liveOrder();

    $this->postJson('/paygate/callback', callbackPayload($order))->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Pending);
});

it('answers not found for an unknown transaction', function () {
    Http::fake();

    $this->postJson('/paygate/callback', ['identifier' => 'GG-0000-NOPE'])->assertNotFound();

    Http::assertNothingSent();
});

it('accepts the "identify" field name used in the PayGate guide', function () {
    Http::fake(['paygateglobal.com/api/v2/status' => Http::response(['status' => 0])]);
    $order = liveOrder();

    $this->postJson('/paygate/callback', ['identify' => $order->payment_identifier])->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
});
