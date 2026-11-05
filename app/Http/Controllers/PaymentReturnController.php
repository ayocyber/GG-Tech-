<?php

namespace App\Http\Controllers;

use App\Actions\SyncOrderPayment;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

/**
 * Where PayGate (or the demo page) sends the customer after paying. The key
 * lives in the path because PayGate may add its own query parameters.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Order $order, string $key, SyncOrderPayment $syncPayment): RedirectResponse
    {
        abort_unless($order->hasPaymentKey($key), 404);

        $syncPayment->handle($order);

        return redirect()->to(URL::signedRoute('orders.thanks', ['order' => $order]));
    }

    public static function urlFor(Order $order): string
    {
        return URL::route('payments.return', ['order' => $order, 'key' => $order->paymentKey()]);
    }
}
