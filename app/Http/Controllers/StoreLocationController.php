<?php

namespace App\Http\Controllers;

use App\Enums\Market;
use App\Models\DeliveryZone;
use Illuminate\View\View;

class StoreLocationController extends Controller
{
    public function __invoke(): View
    {
        return view('store', [
            'zones' => DeliveryZone::query()->activeFor(Market::Togo)->get(),
        ]);
    }
}
