<?php

namespace App\Http\Controllers;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payments\DemoPaymentGateway;
use App\Services\Payments\PaymentSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The fake payment page used by the demo gateway. Orders paid through a real gateway never reach it.
 */
class DemoPaymentController extends Controller
{
    public function __construct(private PaymentSettings $settings) {}

    public function show(Order $order, string $key): View
    {
        $this->ensureDemo($order, $key);

        return view('payments.demo', ['order' => $order, 'key' => $key]);
    }

    public function complete(Request $request, Order $order, string $key, DemoPaymentGateway $gateway): RedirectResponse
    {
        $this->ensureDemo($order, $key);

        $validated = $request->validate([
            'outcome' => ['required', Rule::in([PaymentStatus::Paid->value, PaymentStatus::Cancelled->value, PaymentStatus::Expired->value])],
            'network' => ['required', Rule::in(['FLOOZ', 'TMONEY'])],
        ]);

        $gateway->simulate($order, PaymentStatus::from($validated['outcome']), $validated['network']);

        return redirect()->to(PaymentReturnController::urlFor($order));
    }

    /**
     * Only demo orders can be "paid" here, and only while the demo is the active gateway:
     * once a real gateway is chosen, the fake page disappears.
     */
    private function ensureDemo(Order $order, string $key): void
    {
        abort_unless(
            $this->settings->activeProvider() === PaymentProvider::Demo
                && $order->payment_gateway === PaymentProvider::Demo
                && $order->hasPaymentKey($key)
                && $order->paysOnline(),
            404,
        );
    }
}
