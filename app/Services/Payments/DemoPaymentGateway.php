<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

/**
 * Stands in for PayGate while testing: the customer lands on a demo page on
 * this site and chooses the outcome. No money moves.
 */
class DemoPaymentGateway implements PaymentGateway
{
    public function checkoutUrl(Order $order, string $returnUrl): string
    {
        return URL::route('payments.demo', ['order' => $order, 'key' => $order->paymentKey()]);
    }

    public function status(Order $order): PaymentResult
    {
        /** @var array{status: string, tx_reference: string, payment_reference: string|null, network: string, paid_at: string|null}|null $simulated */
        $simulated = Cache::get($this->cacheKey($order));

        if ($simulated === null) {
            return new PaymentResult(PaymentStatus::Pending);
        }

        return new PaymentResult(
            status: PaymentStatus::from($simulated['status']),
            txReference: $simulated['tx_reference'],
            providerReference: $simulated['payment_reference'],
            network: $simulated['network'],
            paidAt: $simulated['paid_at'] === null ? null : CarbonImmutable::parse($simulated['paid_at']),
        );
    }

    /**
     * Record the outcome the tester picked on the demo page, as PayGate would on its side.
     */
    public function simulate(Order $order, PaymentStatus $status, string $network = 'FLOOZ'): void
    {
        $isPaid = $status === PaymentStatus::Paid;

        Cache::put($this->cacheKey($order), [
            'status' => $status->value,
            'tx_reference' => 'DEMO-'.random_int(100000, 999999),
            'payment_reference' => $isPaid ? 'DEMO-'.strtoupper(bin2hex(random_bytes(4))) : null,
            'network' => 'DEMO '.$network,
            'paid_at' => $isPaid ? now()->toIso8601String() : null,
        ], now()->addDay());
    }

    private function cacheKey(Order $order): string
    {
        return 'demo-payment:'.$order->payment_identifier;
    }
}
