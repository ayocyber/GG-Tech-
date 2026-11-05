<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Mail\PaymentConfirmed;
use App\Mail\PaymentReceivedAlert;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentGatewayException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Asks the payment provider how an order's payment stands and records it.
 * Runs when the customer comes back from paying and when PayGate calls us,
 * often both at once, so the "paid" update is atomic and mails go out once.
 */
class SyncOrderPayment
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Order $order): Order
    {
        if (! $order->paysOnline() || $order->isPaid()) {
            return $order;
        }

        try {
            $result = $this->gateway->status($order);
        } catch (PaymentGatewayException $exception) {
            Log::warning('Payment status check failed', ['order' => $order->number, 'error' => $exception->getMessage()]);

            return $order;
        }

        if ($result->status === PaymentStatus::Pending) {
            return $order;
        }

        if ($result->status !== PaymentStatus::Paid) {
            $order->update(['payment_status' => $result->status]);

            return $order;
        }

        $markedPaid = Order::query()
            ->whereKey($order->getKey())
            ->where('payment_identifier', $order->payment_identifier)
            ->where('payment_status', '!=', PaymentStatus::Paid)
            ->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_method' => 'mobile_money',
                'payment_reference' => $result->txReference,
                'payment_provider_reference' => $result->providerReference,
                'payment_network' => $result->network,
                'paid_at' => $result->paidAt ?? now(),
                'updated_at' => now(),
            ]);

        $order->refresh()->load(['items', 'deliveryZone']);

        if ($markedPaid === 1) {
            // The payment is recorded either way, so a mail outage must not fail it.
            rescue(fn () => Mail::to($order->email)->locale($order->locale)->queue(new PaymentConfirmed($order)));
            rescue(fn () => Mail::to(config('store.payments_email'))->queue(new PaymentReceivedAlert($order)));
        }

        return $order;
    }
}
