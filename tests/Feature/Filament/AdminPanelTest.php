<?php

use App\Enums\OrderStatus;
use App\Enums\Shop;
use App\Enums\TonerType;
use App\Filament\Pages\StoreSettings;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\ShopLocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('moves a toner to the matching category when staff choose its toner type', function () {
    $this->actingAs(User::factory()->create());
    $compatibleToners = Category::factory()->create(['slug' => 'compatible-toners']);
    $product = Product::factory()->priced()->create();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['toner_type' => TonerType::PremiumCompatible->value])
        ->assertSchemaStateSet(['category_id' => $compatibleToners->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->refresh())
        ->toner_type->toBe(TonerType::PremiumCompatible)
        ->category_id->toBe($compatibleToners->id);
});

it('hides photo uploads when the hosting cannot keep uploaded files', function () {
    config(['store.photo_uploads' => false]);
    $product = Product::factory()->priced()->create();

    $this->actingAs(User::factory()->create())
        ->get("/admin/products/{$product->slug}/edit")
        ->assertOk()
        ->assertDontSee('Add a photo');
});

it('stores the WhatsApp number from the settings page', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(StoreSettings::class)
        ->set('data.whatsapp', '+228 90 11 22 33')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::value('whatsapp'))->toBe('+228 90 11 22 33');
});

it('saves the second store and its Google Maps link from the settings page', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(StoreSettings::class)
        ->set('data.shops.second_store.name.fr', 'Bè')
        ->set('data.shops.second_store.address', 'Boulevard du 13 Janvier, Bè, Lomé')
        ->set('data.shops.second_store.maps_url', 'https://maps.app.goo.gl/second-store')
        ->call('save')
        ->assertHasNoErrors();

    expect(ShopLocation::listed())->toHaveCount(2)
        ->and(ShopLocation::for(Shop::SecondStore))
        ->title()->toBe('Bè')
        ->directionsUrl()->toBe('https://maps.app.goo.gl/second-store');
});

it('requires an address for the head office', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(StoreSettings::class)
        ->set('data.shops.head_office.address', '')
        ->call('save')
        ->assertHasErrors(['data.shops.head_office.address' => 'required']);
});

it('uploads several product photos at once after the existing ones', function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->priced()->create();
    Storage::disk('local')->put('products/originals/uploads/cover.jpg', UploadedFile::fake()->image('cover.jpg', 600, 600)->getContent());
    $product->images()->create(['original_path' => 'products/originals/uploads/cover.jpg', 'position' => 1]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('uploadPhotos', data: [
            'photos' => [
                UploadedFile::fake()->image('side.jpg', 600, 600),
                UploadedFile::fake()->image('back.jpg', 600, 600),
            ],
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $images = $product->images()->get();

    expect($images)->toHaveCount(3)
        ->and($images->pluck('position')->all())->toBe([1, 2, 3])
        ->and($images->every(fn (ProductImage $image) => $image->processed_at !== null))->toBeTrue();
});

it('hides the bulk photo upload when the hosting cannot keep uploaded files', function () {
    config(['store.photo_uploads' => false]);
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->priced()->create();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertActionHidden('uploadPhotos');
});
