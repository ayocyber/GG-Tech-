<?php

namespace App\Livewire;

use App\Actions\PlaceOrder;
use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Models\DeliveryZone;
use App\Services\Baskets\Cart;
use App\Support\ShopLocation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CheckoutForm extends Component
{
    public string $customer_name = '';

    public string $email = '';

    public string $phone = '';

    public string $fulfilment = '';

    public string $pickup_shop = '';

    public ?int $delivery_zone_id = null;

    public string $address = '';

    public string $city = '';

    public string $landmark = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->fulfilment = FulfilmentMethod::Pickup->value;
        $this->pickup_shop = ShopLocation::listed()->first()?->shop->value ?? '';
        $this->phone = Market::current()->phonePrefix().' ';
    }

    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::query()->activeFor(Market::current())->get();
    }

    #[Computed]
    public function selectedZone(): ?DeliveryZone
    {
        return $this->zones->firstWhere('id', $this->delivery_zone_id);
    }

    public function placeOrder(PlaceOrder $placeOrder): void
    {
        $market = Market::current();
        $validated = $this->validate($this->checkoutRules($market));

        $order = $placeOrder->handle($validated, $market);

        $this->dispatch('basket-updated');
        $this->redirect(URL::signedRoute('orders.thanks', ['order' => $order]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function checkoutRules(Market $market): array
    {
        $isDelivery = $this->fulfilment === FulfilmentMethod::Delivery->value;

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()\-.]{8,}$/'],
            'fulfilment' => ['required', Rule::enum(FulfilmentMethod::class)],
            'pickup_shop' => [
                Rule::requiredIf(! $isDelivery),
                'nullable',
                Rule::in(ShopLocation::listed()->map(fn (ShopLocation $location) => $location->shop->value)),
            ],
            'delivery_zone_id' => [
                Rule::requiredIf($isDelivery),
                'nullable',
                Rule::exists('delivery_zones', 'id')->where(
                    fn (Builder $query) => $query->where('market', $market->value)->where('is_active', true),
                ),
            ],
            'address' => [Rule::requiredIf($isDelivery), 'nullable', 'string', 'max:255'],
            'city' => [Rule::requiredIf($isDelivery), 'nullable', 'string', 'max:120'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'customer_name' => __('full name'),
            'email' => __('email'),
            'phone' => __('phone'),
            'pickup_shop' => __('pickup shop'),
            'delivery_zone_id' => __('delivery zone'),
            'address' => __('address'),
            'city' => __('city'),
        ];
    }

    public function render(Cart $cart): View
    {
        $market = Market::current();
        $lines = $cart->lines($market);
        $subtotal = $cart->subtotal($lines);
        $isPickup = $this->fulfilment === FulfilmentMethod::Pickup->value;
        $zone = $this->selectedZone;

        $deliveryFee = match (true) {
            $isPickup => 0,
            $zone === null => null,
            $zone->isQuoted(), $cart->needsDeliveryQuote($lines) => null,
            default => $zone->fee,
        };

        return view('livewire.checkout-form', [
            'market' => $market,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'deliveryPending' => ! $isPickup && $zone !== null && $deliveryFee === null,
            'total' => $subtotal + ($deliveryFee ?? 0),
            'isPickup' => $isPickup,
            'shopLocations' => ShopLocation::listed(),
        ]);
    }
}
