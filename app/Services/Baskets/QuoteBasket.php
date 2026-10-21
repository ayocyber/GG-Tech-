<?php

namespace App\Services\Baskets;

class QuoteBasket extends SessionBasket
{
    protected function sessionKey(): string
    {
        return 'quote';
    }
}
