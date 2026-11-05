<?php

namespace App\Services\Payments;

use App\Models\Order;

/**
 * A mobile money payment provider. Checkout only talks to this contract, so
 * switching between the demo and PayGate Global is a config change.
 */
interface PaymentGateway
{
    /**
     * Where to send the customer to pay the order's current payment attempt.
     */
    public function checkoutUrl(Order $order, string $returnUrl): string;

    /**
     * Ask the provider for the current state of the order's payment attempt.
     *
     * @throws PaymentGatewayException when the provider cannot be reached or answers with an error
     */
    public function status(Order $order): PaymentResult;
}
