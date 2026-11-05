<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payments\DemoPaymentGateway;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The fake PayGate page used while PAYGATE_MODE=demo. It does not exist in live mode.
 */
class DemoPaymentController extends Controller
{
    public function show(Order $order, string $key, PaymentGateway $gateway): View
    {
        $this->ensureDemo($order, $key, $gateway);

        return view('payments.demo', ['order' => $order, 'key' => $key]);
    }

    public function complete(Request $request, Order $order, string $key, PaymentGateway $gateway): RedirectResponse
    {
        $this->ensureDemo($order, $key, $gateway);

        $validated = $request->validate([
            'outcome' => ['required', Rule::in([PaymentStatus::Paid->value, PaymentStatus::Cancelled->value, PaymentStatus::Expired->value])],
            'network' => ['required', Rule::in(['FLOOZ', 'TMONEY'])],
        ]);

        /** @var DemoPaymentGateway $gateway */
        $gateway->simulate($order, PaymentStatus::from($validated['outcome']), $validated['network']);

        return redirect()->to(PaymentReturnController::urlFor($order));
    }

    private function ensureDemo(Order $order, string $key, PaymentGateway $gateway): void
    {
        abort_unless($gateway instanceof DemoPaymentGateway && $order->hasPaymentKey($key) && $order->paysOnline(), 404);
    }
}
