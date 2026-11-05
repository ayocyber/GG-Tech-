<?php

namespace App\Http\Controllers;

use App\Actions\SyncOrderPayment;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PayGate's payment confirmation. The message is not signed, so it only tells
 * us which order to look at: the result itself comes from the status API.
 */
class PayGateCallbackController extends Controller
{
    public function __invoke(Request $request, SyncOrderPayment $syncPayment): JsonResponse
    {
        $identifier = $request->input('identifier', $request->input('identify'));

        $order = is_string($identifier) && $identifier !== ''
            ? Order::query()->where('payment_identifier', $identifier)->first()
            : null;

        if ($order === null) {
            return response()->json(['received' => false], 404);
        }

        $syncPayment->handle($order);

        return response()->json(['received' => true]);
    }
}
