<?php

namespace App\Services\Delivery;

use App\Models\DeliveryCountry;
use App\Services\Baskets\BasketLine;
use Illuminate\Support\Collection;

/**
 * Works out what an international delivery costs. Checkout only talks to this
 * contract, so a weight- or quantity-based model can replace the fixed fee later.
 */
interface InternationalDeliveryPricing
{
    /**
     * The delivery fee in FCFA for sending these basket lines to the country.
     *
     * @param  Collection<int, BasketLine>  $lines
     */
    public function feeFor(DeliveryCountry $country, Collection $lines): int;
}
