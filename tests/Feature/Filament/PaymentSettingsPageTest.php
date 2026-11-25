<?php

use App\Enums\PaymentProvider;
use App\Filament\Pages\PaymentSettingsPage;
use App\Livewire\CheckoutForm;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\Baskets\Cart;
use App\Services\Payments\PaymentSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('shows the payment settings with the FedaPay webhook address', function () {
    $this->get('/admin/payments')
        ->assertOk()
        ->assertSee('FedaPay')
        ->assertSee(route('payments.fedapay.webhook'));
});

it('saves the active gateway and stores its keys encrypted', function () {
    Livewire::test(PaymentSettingsPage::class)
        ->fillForm([
            'online_enabled' => true,
            'provider' => 'fedapay',
            'fedapay_mode' => 'sandbox',
            'fedapay_secret_key' => 'sk_sandbox_abc123',
            'fedapay_webhook_secret' => 'wh_sandbox_xyz',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(PaymentSettings::class);

    expect($settings->activeProvider())->toBe(PaymentProvider::FedaPay)
        ->and($settings->canTakeOnlinePayments())->toBeTrue()
        ->and($settings->fedaPaySecretKey())->toBe('sk_sandbox_abc123')
        ->and($settings->fedaPayWebhookSecret())->toBe('wh_sandbox_xyz')
        ->and(Setting::value('payments.fedapay.secret_key'))->not->toContain('sk_sandbox_abc123');
});

it('keeps a saved key when the field is left empty', function () {
    app(PaymentSettings::class)->storeSecret('payments.fedapay.secret_key', 'sk_sandbox_saved');

    Livewire::test(PaymentSettingsPage::class)
        ->fillForm(['online_enabled' => true, 'provider' => 'fedapay', 'fedapay_mode' => 'sandbox', 'fedapay_secret_key' => ''])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(PaymentSettings::class)->fedaPaySecretKey())->toBe('sk_sandbox_saved');
});

it('requires a key before a gateway can take payments', function () {
    Livewire::test(PaymentSettingsPage::class)
        ->fillForm(['online_enabled' => true, 'provider' => 'fedapay', 'fedapay_mode' => 'sandbox'])
        ->call('save')
        ->assertHasFormErrors(['fedapay_secret_key' => 'required']);
});

it('refuses a FedaPay key that does not match the environment', function (string $mode, string $key) {
    Livewire::test(PaymentSettingsPage::class)
        ->fillForm(['online_enabled' => true, 'provider' => 'fedapay', 'fedapay_mode' => $mode, 'fedapay_secret_key' => $key])
        ->call('save')
        ->assertHasFormErrors(['fedapay_secret_key']);
})->with([
    'live key in sandbox' => ['sandbox', 'sk_live_abc'],
    'sandbox key in live' => ['live', 'sk_sandbox_abc'],
]);

it('hides online payment at checkout when staff switch it off', function () {
    Setting::store(['payments.online_enabled' => false, 'payments.provider' => 'demo']);
    $product = Product::factory()->priced()->create();
    app(Cart::class)->add($product->variants->first());

    Livewire::test(CheckoutForm::class)
        ->assertSet('payment_option', CheckoutForm::PayLater)
        ->assertDontSee('Payer maintenant');
});

it('uses the live FedaPay API once staff choose the live environment', function () {
    Setting::store(['payments.fedapay.mode' => 'live']);

    expect(app(PaymentSettings::class)->fedaPayBaseUrl())->toBe('https://api.fedapay.com/v1');
});
