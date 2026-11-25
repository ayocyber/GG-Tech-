<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * The online payment gateways the shop can use. Staff pick the active one in the admin.
 */
enum PaymentProvider: string implements HasLabel
{
    case Demo = 'demo';
    case PayGate = 'paygate';
    case FedaPay = 'fedapay';

    public function getLabel(): string
    {
        return match ($this) {
            self::Demo => __('Demo (no real money)'),
            self::PayGate => 'PayGate Global',
            self::FedaPay => 'FedaPay',
        };
    }

    /**
     * The payment option title customers see at checkout.
     */
    public function checkoutTitle(): string
    {
        return match ($this) {
            self::Demo, self::PayGate => __('Pay now with Flooz or T-Money'),
            self::FedaPay => __('Pay now by mobile money or card'),
        };
    }

    public function checkoutDescription(): string
    {
        return match ($this) {
            self::Demo, self::PayGate => __('Secure mobile money payment through PayGate Global. You confirm the payment on your phone.'),
            self::FedaPay => __('Secure payment through FedaPay: Moov, Mixx by Yas and other mobile money, or a Visa / Mastercard card.'),
        };
    }
}
