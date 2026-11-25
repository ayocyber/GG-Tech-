<?php

namespace App\Http\Controllers;

use App\Actions\SyncOrderPayment;
use App\Models\Order;
use App\Services\Payments\PaymentGatewayException;
use App\Services\Payments\PaymentGateways;
use App\Services\Payments\PaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Starts a new payment attempt after one was cancelled, expired or abandoned.
 * The new attempt goes through the gateway that is active now.
 */
class PaymentRetryController extends Controller
{
    public function __invoke(Order $order, string $key, SyncOrderPayment $syncPayment, PaymentSettings $settings, PaymentGateways $gateways): RedirectResponse
    {
        abort_unless($order->hasPaymentKey($key) && $order->paysOnline(), 404);

        // The previous attempt may have gone through in the meantime: never let the customer pay twice.
        if ($syncPayment->handle($order)->isPaid() || ! $settings->canTakeOnlinePayments()) {
            return redirect()->to(URL::signedRoute('orders.thanks', ['order' => $order]));
        }

        $order->startPaymentAttempt($settings->activeProvider());

        return redirect()->away(self::paymentPageFor($order, $gateways));
    }

    /**
     * The gateway's payment page for the order's current attempt. When the gateway cannot be
     * reached, the customer lands on the order page instead, where they can try again.
     */
    public static function paymentPageFor(Order $order, PaymentGateways $gateways): string
    {
        try {
            return $gateways->forOrder($order)->checkoutUrl($order, PaymentReturnController::urlFor($order));
        } catch (PaymentGatewayException|RuntimeException $exception) {
            Log::error('Could not open the payment page', ['order' => $order->number, 'error' => $exception->getMessage()]);

            return URL::signedRoute('orders.thanks', ['order' => $order]);
        }
    }
}
