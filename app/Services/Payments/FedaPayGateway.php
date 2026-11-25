<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FedaPay (mobile money and cards), using its hosted payment page. FedaPay only
 * charges in FCFA (XOF), which is the currency every order is stored in.
 *
 * The status always comes from the transaction API: the return URL and the
 * webhook only tell us which order to check.
 */
class FedaPayGateway implements PaymentGateway
{
    public function __construct(
        private string $secretKey,
        private string $baseUrl,
    ) {}

    /**
     * Creates a FedaPay transaction for the order's current payment attempt and returns its payment page.
     */
    public function checkoutUrl(Order $order, string $returnUrl): string
    {
        $created = $this->send(fn (PendingRequest $request) => $request->post('/transactions', array_filter([
            'description' => __('GG-TECH order :number', ['number' => $order->number]),
            'amount' => $order->total,
            'currency' => ['iso' => 'XOF'],
            'callback_url' => $returnUrl,
            'merchant_reference' => $order->payment_identifier,
            'custom_metadata' => ['order' => $order->number],
            'customer' => self::customer($order),
        ])));

        $transactionId = self::transaction($created)['id'] ?? null;

        if (! is_scalar($transactionId) || (string) $transactionId === '') {
            throw new PaymentGatewayException('FedaPay did not return a transaction id.');
        }

        $order->update(['payment_transaction_id' => (string) $transactionId]);

        $token = $this->send(fn (PendingRequest $request) => $request->post("/transactions/{$transactionId}/token"));
        $url = $token->json('url');

        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw new PaymentGatewayException('FedaPay did not return a payment link.');
        }

        return $url;
    }

    public function status(Order $order): PaymentResult
    {
        $path = filled($order->payment_transaction_id)
            ? '/transactions/'.rawurlencode($order->payment_transaction_id)
            : '/transactions/merchant/'.rawurlencode((string) $order->payment_identifier);

        $transaction = self::transaction($this->send(fn (PendingRequest $request) => $request->get($path)));

        $status = match ($transaction['status'] ?? null) {
            'approved', 'transferred' => PaymentStatus::Paid,
            'declined', 'canceled', 'refunded' => PaymentStatus::Cancelled,
            'expired' => PaymentStatus::Expired,
            default => PaymentStatus::Pending,
        };

        if ($status === PaymentStatus::Paid && ! self::paysOrderInFull($transaction, $order)) {
            Log::critical('FedaPay approved a transaction that does not match the order; it was not marked paid.', [
                'order' => $order->number,
                'transaction' => $transaction['id'] ?? null,
                'amount' => $transaction['amount'] ?? null,
            ]);

            return new PaymentResult(PaymentStatus::Pending);
        }

        return new PaymentResult(
            status: $status,
            txReference: self::stringOrNull($transaction['reference'] ?? null),
            providerReference: self::stringOrNull($transaction['transaction_key'] ?? $transaction['id'] ?? null),
            network: 'FedaPay'.(self::stringOrNull($transaction['mode'] ?? null) ? ' '.strtoupper($transaction['mode']) : ''),
            paidAt: $status === PaymentStatus::Paid ? self::parseDate($transaction['approved_at'] ?? null) : null,
        );
    }

    /**
     * FedaPay documents flat responses, but its SDKs read them under "v1/transaction": accept both.
     *
     * @return array<string, mixed>
     */
    public static function transaction(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            return [];
        }

        $wrapped = $body['v1/transaction'] ?? $body['transaction'] ?? null;

        return is_array($wrapped) ? $wrapped : $body;
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     *
     * @throws PaymentGatewayException
     */
    private function send(callable $call): Response
    {
        try {
            $response = $call(Http::baseUrl(rtrim($this->baseUrl, '/'))
                ->withToken($this->secretKey)
                ->asJson()
                ->acceptJson()
                ->timeout(15));
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('FedaPay could not be reached.', previous: $exception);
        }

        if ($response->failed()) {
            throw new PaymentGatewayException("FedaPay answered with HTTP {$response->status()}.");
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private static function paysOrderInFull(array $transaction, Order $order): bool
    {
        $id = $transaction['id'] ?? null;
        $sameTransaction = blank($order->payment_transaction_id) || (string) $id === $order->payment_transaction_id;

        return $sameTransaction && (int) ($transaction['amount'] ?? 0) === $order->total;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function customer(Order $order): ?array
    {
        $names = preg_split('/\s+/', trim($order->customer_name), 2) ?: [];
        $digits = preg_replace('/\D/', '', $order->phone);

        return array_filter([
            'firstname' => $names[0] ?? null,
            'lastname' => $names[1] ?? $names[0] ?? null,
            'email' => $order->email,
            // FedaPay needs the phone's country; only Togolese numbers are certain from the prefix.
            'phone_number' => strlen($digits) === 11 && str_starts_with($digits, '228')
                ? ['number' => '+'.$digits, 'country' => 'tg']
                : null,
        ]) ?: null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    private static function parseDate(mixed $value): CarbonImmutable
    {
        try {
            return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : CarbonImmutable::now();
        } catch (Throwable) {
            return CarbonImmutable::now();
        }
    }
}
