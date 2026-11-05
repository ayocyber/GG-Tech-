<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * PayGate Global (Flooz / T-Money), using its hosted payment page ("method 2").
 * The customer chooses their network and confirms on their phone; we then read
 * the result from the status API, never from the unsigned confirmation message.
 */
class PayGateGateway implements PaymentGateway
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://paygateglobal.com',
    ) {}

    public function checkoutUrl(Order $order, string $returnUrl): string
    {
        $query = array_filter([
            'token' => $this->apiKey,
            'amount' => $order->total,
            'description' => __('GG-TECH order :number', ['number' => $order->number]),
            'identifier' => $order->payment_identifier,
            'url' => $returnUrl,
            'phone' => self::localPhoneNumber($order->phone),
        ], fn (mixed $value) => $value !== null);

        return rtrim($this->baseUrl, '/').'/v1/page?'.http_build_query($query);
    }

    public function status(Order $order): PaymentResult
    {
        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(15)
                ->post(rtrim($this->baseUrl, '/').'/api/v2/status', [
                    'auth_token' => $this->apiKey,
                    'identifier' => $order->payment_identifier,
                ]);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('PayGate could not be reached.', previous: $exception);
        }

        if ($response->failed()) {
            throw new PaymentGatewayException("PayGate status check failed with HTTP {$response->status()}.");
        }

        $status = match ((string) $response->json('status')) {
            '0' => PaymentStatus::Paid,
            '4' => PaymentStatus::Expired,
            '6' => PaymentStatus::Cancelled,
            default => PaymentStatus::Pending,
        };

        return new PaymentResult(
            status: $status,
            txReference: self::stringOrNull($response->json('tx_reference')),
            providerReference: self::stringOrNull($response->json('payment_reference')),
            network: self::stringOrNull($response->json('payment_method')),
            paidAt: $status === PaymentStatus::Paid ? self::parseDate($response->json('datetime')) : null,
        );
    }

    /**
     * PayGate expects the 8-digit Togolese number, without the +228 prefix.
     */
    public static function localPhoneNumber(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '228')) {
            $digits = substr($digits, 3);
        }

        return strlen($digits) === 8 ? $digits : null;
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
