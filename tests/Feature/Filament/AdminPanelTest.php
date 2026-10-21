<?php

use App\Enums\OrderStatus;
use App\Filament\Pages\StoreSettings;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestItem;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

it('sends guests to the admin login', function () {
    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('renders every admin screen for staff', function (string $path) {
    $this->actingAs(User::factory()->create())
        ->get($path)
        ->assertOk();
})->with([
    'dashboard' => '/admin',
    'products' => '/admin/products',
    'new product' => '/admin/products/create',
    'categories' => '/admin/categories',
    'brands' => '/admin/brands',
    'orders' => '/admin/orders',
    'quote requests' => '/admin/quote-requests',
    'delivery zones' => '/admin/delivery-zones',
    'settings' => '/admin/store-settings',
]);

it('shows the admin panel in English by default', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin/products')
        ->assertOk()
        ->assertSee('Needs review')
        ->assertSee('Français');
});

it('keeps the admin in English even though the shop opens in French', function () {
    $this->get('/fr')->assertOk();

    $this->actingAs(User::factory()->create())
        ->get('/admin/products')
        ->assertOk()
        ->assertSee('Needs review');
});

it('remembers the admin language chosen from the user menu', function () {
    $this->actingAs(User::factory()->create())
        ->from('/admin/products')
        ->get('/admin-language/fr')
        ->assertRedirect('/admin/products')
        ->assertSessionHas('admin_locale', 'fr');
});

it('shows the admin panel in French once chosen', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['admin_locale' => 'fr'])
        ->get('/admin/products')
        ->assertOk()
        ->assertSee('À vérifier')
        ->assertSee('English');
});

it('rejects an unsupported admin language', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin-language/de')
        ->assertNotFound();
});

it('shows an order with its items and customer', function () {
    $order = Order::factory()->has(OrderItem::factory()->state(['product_name' => 'Toner HP 26A']), 'items')
        ->create(['customer_name' => 'Ama Koffi']);

    $this->actingAs(User::factory()->create())
        ->get("/admin/orders/{$order->number}")
        ->assertOk()
        ->assertSee('Toner HP 26A')
        ->assertSee('Ama Koffi');
});

it('shows a quote request with its items', function () {
    $quote = QuoteRequest::factory()->has(QuoteRequestItem::factory()->state(['description' => 'Onduleur 3 kVA']), 'items')->create();

    $this->actingAs(User::factory()->create())
        ->get("/admin/quote-requests/{$quote->number}")
        ->assertOk()
        ->assertSee('Onduleur 3 kVA');
});

it('recalculates the order total when staff confirm the delivery fee', function () {
    $this->actingAs(User::factory()->create());
    $order = Order::factory()->create([
        'fulfilment' => 'delivery',
        'delivery_zone_id' => DeliveryZone::factory(),
        'subtotal' => 1250000,
        'delivery_fee' => null,
        'total' => 1250000,
    ]);

    Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
        ->fillForm(['status' => OrderStatus::Confirmed->value, 'delivery_fee' => 15000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($order->refresh())
        ->status->toBe(OrderStatus::Confirmed)
        ->delivery_fee->toBe(15000)
        ->total->toBe(1265000);
});

it('saves product prices and translations from the edit form', function () {
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->priced(50000)->create();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['name' => ['fr' => 'Baie réseau 12U', 'en' => '12U network rack']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh())
        ->translate('name', 'fr')->toBe('Baie réseau 12U')
        ->translate('name', 'en')->toBe('12U network rack')
        ->search_text->toContain('baie réseau 12u');
});

it('stores the WhatsApp number from the settings page', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(StoreSettings::class)
        ->set('data.whatsapp', '+228 90 11 22 33')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::value('whatsapp'))->toBe('+228 90 11 22 33');
});
