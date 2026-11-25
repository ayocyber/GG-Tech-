<?php

namespace App\Http\Controllers;

use App\Actions\SyncOrderPayment;
use App\Enums\PaymentProvider;
use App\Models\Order;
use App\Services\Payments\PaymentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * FedaPay's webhook. The signature proves the message comes from FedaPay, but the
 * order's status is still read from the transaction API before anything changes.
 */
class FedaPayWebhookController extends Controller
{
    /**
     * How old a signed message may be, in seconds (the FedaPay SDK default).
     */
    public const Tolerance = 300;

    public function __invoke(Request $request, PaymentSettings $settings, SyncOrderPayment $syncPayment): JsonResponse
    {
        $secret = $settings->fedaPayWebhookSecret();

        if (blank($secret)) {
            Log::warning('FedaPay webhook received but no webhook secret is saved.');

            return response()->json(['received' => false], 503);
        }

        if (! self::hasValidSignature($request->getContent(), (string) $request->header('X-FEDAPAY-SIGNATURE'), $secret)) {
            return response()->json(['received' => false], 400);
        }

        $order = $this->findOrder($request->json()->all());

        if ($order === null) {
            // Acknowledge anyway: FedaPay disables a webhook that keeps failing.
            Log::info('FedaPay webhook for an unknown transaction', ['event' => $request->json('name')]);

            return response()->json(['received' => true]);
        }

        $syncPayment->handle($order);

        return response()->json(['received' => true]);
    }

    /**
     * The header looks like "t=1700000000,s=abc123": an HMAC-SHA256 of "{t}.{raw body}" with the endpoint secret.
     */
    public static function hasValidSignature(string $payload, string $header, string $secret, ?int $now = null): bool
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $header) as $item) {
            [$name, $value] = array_pad(explode('=', trim($item), 2), 2, '');

            if ($name === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($name === 's' && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === [] || abs(($now ?? time()) - $timestamp) > self::Tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * FedaPay does not document where the transaction sits in the event, so look in the usual places.
     *
     * @param  array<string, mixed>  $event
     */
    private function findOrder(array $event): ?Order
    {
        $transaction = collect(['entity', 'object', 'data', 'transaction', 'v1/transaction'])
            ->map(fn (string $key) => $event[$key] ?? null)
            ->first(fn (mixed $candidate) => is_array($candidate)) ?? [];

        $transactionId = $transaction['id'] ?? $event['object_id'] ?? null;
        $reference = $transaction['merchant_reference'] ?? null;

        if (! is_scalar($transactionId) && ! is_string($reference)) {
            return null;
        }

        return Order::query()
            ->where('payment_gateway', PaymentProvider::FedaPay)
            ->where(fn ($query) => $query
                ->when(is_scalar($transactionId), fn ($query) => $query->orWhere('payment_transaction_id', (string) $transactionId))
                ->when(is_string($reference), fn ($query) => $query->orWhere('payment_identifier', $reference)))
            ->first();
    }
}
