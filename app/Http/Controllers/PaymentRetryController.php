<?php

namespace App\Http\Controllers;

use App\Actions\SyncOrderPayment;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

/**
 * Starts a new payment attempt after one was cancelled, expired or abandoned.
 */
class PaymentRetryController extends Controller
{
    public function __invoke(Order $order, string $key, SyncOrderPayment $syncPayment, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($order->hasPaymentKey($key) && $order->paysOnline(), 404);

        // The previous attempt may have gone through in the meantime: never let the customer pay twice.
        if ($syncPayment->handle($order)->isPaid()) {
            return redirect()->to(URL::signedRoute('orders.thanks', ['order' => $order]));
        }

        $order->startPaymentAttempt();

        return redirect()->away($gateway->checkoutUrl($order, PaymentReturnController::urlFor($order)));
    }
}
