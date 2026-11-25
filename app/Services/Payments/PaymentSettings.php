<?php

namespace App\Services\Payments;

use App\Enums\PaymentProvider;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Online payment settings chosen in the admin. Until staff save them, the .env values apply
 * (STORE_ONLINE_PAYMENTS, PAYGATE_MODE, PAYGATE_API_KEY, FEDAPAY_*). API keys are stored encrypted.
 */
class PaymentSettings
{
    public const FedaPaySandbox = 'sandbox';

    public const FedaPayLive = 'live';

    public function onlinePaymentsEnabled(): bool
    {
        return (bool) (Setting::value('payments.online_enabled') ?? config('store.online_payments_enabled'));
    }

    /**
     * Customers only see "pay now" when it is switched on and the active gateway has its keys.
     */
    public function canTakeOnlinePayments(): bool
    {
        return $this->onlinePaymentsEnabled() && $this->isConfigured($this->activeProvider());
    }

    public function activeProvider(): PaymentProvider
    {
        $chosen = PaymentProvider::tryFrom((string) Setting::value('payments.provider'));

        return $chosen ?? (config('services.paygate.mode') === 'live' ? PaymentProvider::PayGate : PaymentProvider::Demo);
    }

    public function isConfigured(PaymentProvider $provider): bool
    {
        return match ($provider) {
            PaymentProvider::Demo => true,
            PaymentProvider::PayGate => filled($this->payGateApiKey()),
            PaymentProvider::FedaPay => filled($this->fedaPaySecretKey()),
        };
    }

    public function payGateApiKey(): ?string
    {
        return $this->secret('payments.paygate.api_key') ?? config('services.paygate.api_key');
    }

    public function fedaPayMode(): string
    {
        $mode = Setting::value('payments.fedapay.mode') ?? config('services.fedapay.mode');

        return $mode === self::FedaPayLive ? self::FedaPayLive : self::FedaPaySandbox;
    }

    public function fedaPaySecretKey(): ?string
    {
        return $this->secret('payments.fedapay.secret_key') ?? config('services.fedapay.secret_key');
    }

    public function fedaPayWebhookSecret(): ?string
    {
        return $this->secret('payments.fedapay.webhook_secret') ?? config('services.fedapay.webhook_secret');
    }

    public function fedaPayBaseUrl(): string
    {
        return $this->fedaPayMode() === self::FedaPayLive
            ? 'https://api.fedapay.com/v1'
            : 'https://sandbox-api.fedapay.com/v1';
    }

    /**
     * Saves an API key encrypted with the app key; an empty value keeps the saved one.
     */
    public function storeSecret(string $key, ?string $value): void
    {
        if (filled($value)) {
            Setting::store([$key => Crypt::encryptString(trim($value))]);
        }
    }

    public function hasStoredSecret(string $key): bool
    {
        return filled(Setting::value($key));
    }

    private function secret(string $key): ?string
    {
        $stored = Setting::value($key);

        if (blank($stored)) {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (DecryptException) {
            Log::error('A saved payment key could not be decrypted; it must be entered again.', ['key' => $key]);

            return null;
        }
    }
}
