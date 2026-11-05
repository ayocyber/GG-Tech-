<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * State of an online (PayGate Global) payment. Orders paid at pickup or on delivery have no payment status.
 */
enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('Awaiting payment'),
            self::Paid => __('Paid online'),
            self::Expired => __('Payment expired'),
            self::Cancelled => __('Payment cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Expired, self::Cancelled => 'danger',
        };
    }

    /**
     * Whether the customer can start a new payment attempt for the order.
     */
    public function canRetry(): bool
    {
        return $this !== self::Paid;
    }
}
