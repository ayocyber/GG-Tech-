<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\Order;
use RuntimeException;

/**
 * Hands out the gateway for new payments (the active one) and for existing payments
 * (the one each order was started with), so switching gateways never affects old orders.
 */
class PaymentGateways
{
    public function __construct(private PaymentSettings $settings) {}

    public function active(): PaymentGateway
    {
        return $this->for($this->settings->activeProvider());
    }

    public function forOrder(Order $order): PaymentGateway
    {
        return $this->for($order->payment_gateway ?? $this->settings->activeProvider());
    }

    /**
     * @throws RuntimeException when the gateway's API key is missing
     */
    public function for(PaymentProvider $provider): PaymentGateway
    {
        return match ($provider) {
            PaymentProvider::Demo => new DemoPaymentGateway,
            PaymentProvider::PayGate => new PayGateGateway(
                apiKey: $this->settings->payGateApiKey() ?: throw new RuntimeException('PAYGATE_API_KEY is not set.'),
                baseUrl: config('services.paygate.base_url'),
            ),
            PaymentProvider::FedaPay => new FedaPayGateway(
                secretKey: $this->settings->fedaPaySecretKey() ?: throw new RuntimeException('The FedaPay secret key is not set.'),
                baseUrl: $this->settings->fedaPayBaseUrl(),
            ),
        };
    }
}
