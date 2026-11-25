<?php

namespace App\Filament\Pages;

use App\Enums\PaymentProvider;
use App\Filament\NavigationGroup;
use App\Models\Setting;
use App\Services\Payments\PaymentSettings;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Online payment: whether customers can pay at checkout, which gateway takes the payment,
 * and each gateway's keys. Keys are stored encrypted and never shown again once saved.
 *
 * @property-read Schema $form
 */
class PaymentSettingsPage extends Page
{
    protected string $view = 'filament.pages.store-settings';

    protected static ?string $slug = 'payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Payments');
    }

    public function getTitle(): string
    {
        return __('Online payments');
    }

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(PaymentSettings $settings): void
    {
        $this->form->fill([
            'online_enabled' => $settings->onlinePaymentsEnabled(),
            'provider' => $settings->activeProvider()->value,
            'fedapay_mode' => $settings->fedaPayMode(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $settings = app(PaymentSettings::class);

        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Online payment'))
                    ->description(__('Prices are always charged in FCFA. Orders keep the gateway they were paid with, so switching gateways does not affect past payments.'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('online_enabled')
                            ->label(__('Offer online payment at checkout'))
                            ->helperText(__('When off, customers pay at pickup or on delivery.'))
                            ->inline(false),
                        Select::make('provider')
                            ->label(__('Active gateway'))
                            ->options(PaymentProvider::class)
                            ->required()
                            ->live()
                            ->helperText(__('New payments go through this gateway.')),
                    ]),
                Section::make('FedaPay')
                    ->description(__('Mobile money (Moov, Mixx by Yas…) and Visa / Mastercard cards. Webhook URL to enter in the FedaPay dashboard (Workbench > Webhooks): :url', [
                        'url' => route('payments.fedapay.webhook'),
                    ]))
                    ->columns(2)
                    ->schema([
                        Select::make('fedapay_mode')
                            ->label(__('Environment'))
                            ->options([
                                PaymentSettings::FedaPaySandbox => __('Sandbox (test payments)'),
                                PaymentSettings::FedaPayLive => __('Live (real money)'),
                            ])
                            ->required(),
                        TextInput::make('fedapay_secret_key')
                            ->label(__('Secret key'))
                            ->password()
                            ->autocomplete('off')
                            ->placeholder(self::secretPlaceholder($settings, 'payments.fedapay.secret_key', filled(config('services.fedapay.secret_key'))))
                            ->required(fn (Get $get) => self::needsKey($get, PaymentProvider::FedaPay) && ! $settings->isConfigured(PaymentProvider::FedaPay))
                            ->rule(fn (Get $get) => self::keyMatchesMode($get('fedapay_mode'))),
                        TextInput::make('fedapay_webhook_secret')
                            ->label(__('Webhook secret'))
                            ->password()
                            ->autocomplete('off')
                            ->placeholder(self::secretPlaceholder($settings, 'payments.fedapay.webhook_secret', filled(config('services.fedapay.webhook_secret'))))
                            ->helperText(__('Shown in FedaPay under Workbench > Webhooks. Payments are still confirmed without it when customers return to the shop.'))
                            ->columnSpanFull(),
                    ]),
                Section::make('PayGate Global')
                    ->description(__('Flooz and T-Money. Callback URL to enter in the PayGate dashboard: :url', [
                        'url' => route('payments.callback'),
                    ]))
                    ->schema([
                        TextInput::make('paygate_api_key')
                            ->label(__('API key'))
                            ->password()
                            ->autocomplete('off')
                            ->placeholder(self::secretPlaceholder($settings, 'payments.paygate.api_key', filled(config('services.paygate.api_key'))))
                            ->required(fn (Get $get) => self::needsKey($get, PaymentProvider::PayGate) && ! $settings->isConfigured(PaymentProvider::PayGate)),
                    ]),
            ]);
    }

    public function save(PaymentSettings $settings): void
    {
        $state = $this->form->getState();

        Setting::store([
            'payments.online_enabled' => (bool) $state['online_enabled'],
            'payments.provider' => $state['provider'] instanceof PaymentProvider ? $state['provider']->value : $state['provider'],
            'payments.fedapay.mode' => $state['fedapay_mode'],
        ]);

        $settings->storeSecret('payments.fedapay.secret_key', $state['fedapay_secret_key'] ?? null);
        $settings->storeSecret('payments.fedapay.webhook_secret', $state['fedapay_webhook_secret'] ?? null);
        $settings->storeSecret('payments.paygate.api_key', $state['paygate_api_key'] ?? null);

        $this->form->fill([
            'online_enabled' => $settings->onlinePaymentsEnabled(),
            'provider' => $settings->activeProvider()->value,
            'fedapay_mode' => $settings->fedaPayMode(),
        ]);

        Notification::make()->title(__('Payment settings saved'))->success()->send();
    }

    private static function needsKey(Get $get, PaymentProvider $provider): bool
    {
        $chosen = $get('provider');
        $chosen = $chosen instanceof PaymentProvider ? $chosen : PaymentProvider::tryFrom((string) $chosen);

        return $get('online_enabled') && $chosen === $provider;
    }

    private static function secretPlaceholder(PaymentSettings $settings, string $key, bool $setInEnv): string
    {
        return match (true) {
            $settings->hasStoredSecret($key) => __('Saved — leave empty to keep it'),
            $setInEnv => __('Set on the server — leave empty to keep it'),
            default => __('Not set'),
        };
    }

    /**
     * A live key in sandbox (or the reverse) would fail every payment, so it is refused here.
     */
    private static function keyMatchesMode(?string $mode): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($mode) {
            if (! is_string($value) || $value === '') {
                return;
            }

            if ($mode === PaymentSettings::FedaPayLive && str_starts_with($value, 'sk_sandbox_')) {
                $fail(__('This is a sandbox key: choose the Sandbox environment or enter the live key.'));
            }

            if ($mode === PaymentSettings::FedaPaySandbox && str_starts_with($value, 'sk_live_')) {
                $fail(__('This is a live key: choose the Live environment or enter the sandbox key.'));
            }
        };
    }
}
