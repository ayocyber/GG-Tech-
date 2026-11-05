<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;

final readonly class PaymentResult
{
    public function __construct(
        public PaymentStatus $status,
        public ?string $txReference = null,
        public ?string $providerReference = null,
        public ?string $network = null,
        public ?CarbonImmutable $paidAt = null,
    ) {}
}
