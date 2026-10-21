<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\View\View;

class OrderConfirmationController extends Controller
{
    public function __invoke(Order $order): View
    {
        return view('orders.thanks', ['order' => $order->load(['items', 'deliveryZone'])]);
    }
}
