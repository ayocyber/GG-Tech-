<?php

namespace App\Services\Delivery;

use App\Models\DeliveryCountry;
use Illuminate\Support\Collection;

/**
 * The initial pricing model: one fixed fee per destination country, whatever the basket holds.
 */
class FixedFeePerCountry implements InternationalDeliveryPricing
{
    public function feeFor(DeliveryCountry $country, Collection $lines): int
    {
        return $country->fee;
    }
}
