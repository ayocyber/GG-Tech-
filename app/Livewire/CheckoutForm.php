<?php

namespace App\Livewire;

use App\Actions\PlaceOrder;
use App\Enums\FulfilmentMethod;
use App\Enums\Market;
use App\Http\Controllers\PaymentReturnController;
use App\Models\DeliveryZone;
use App\Services\Baskets\Cart;
use App\Services\Payments\PaymentGateway;
use App\Support\ShopLocation;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
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

    public string $payment_option = self::PayLater;

    public const PayOnline = 'online';

    public const PayLater = 'later';

    public function mount(): void
    {
        $this->fulfilment = FulfilmentMethod::Pickup->value;
        $this->pickup_shop = ShopLocation::listed()->first()?->shop->value ?? '';
        $this->phone = Market::current()->phonePrefix().' ';
        $this->payment_option = $this->onlinePaymentsEnabled ? self::PayOnline : self::PayLater;
    }

    /**
     * Switching to a delivery our team still has to price falls back to paying on delivery.
     */
    public function updated(): void
    {
        if ($this->payment_option === self::PayOnline && $this->deliveryFeeIsPending(app(Cart::class))) {
            $this->payment_option = self::PayLater;
        }
    }

    #[Computed]
    public function onlinePaymentsEnabled(): bool
    {
        return (bool) config('store.online_payments_enabled');
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

    /**
     * The cart sidebar can empty the cart while this page is open; totals update through the re-render.
     */
    #[On('basket-updated')]
    public function leaveWhenCartIsEmpty(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            $this->redirectRoute('cart');
        }
    }

    public function placeOrder(PlaceOrder $placeOrder, Cart $cart, PaymentGateway $gateway): void
    {
        if ($cart->isEmpty()) {
            $this->redirectRoute('cart');

            return;
        }

        $market = Market::current();
        $validated = $this->validate($this->checkoutRules($market));

        $validated['pay_online'] = $validated['payment_option'] === self::PayOnline;
        unset($validated['payment_option']);

        $order = $placeOrder->handle($validated, $market);

        if ($order->paysOnline()) {
            $this->redirect($gateway->checkoutUrl($order, PaymentReturnController::urlFor($order)));

            return;
        }

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
            'payment_option' => [
                'required',
                Rule::in($this->onlinePaymentsEnabled ? [self::PayOnline, self::PayLater] : [self::PayLater]),
                function (string $attribute, string $value, Closure $fail) {
                    if ($value === self::PayOnline && $this->deliveryFeeIsPending(app(Cart::class))) {
                        $fail(__('Online payment opens once our team has confirmed the delivery fee. Choose to pay on delivery for now.'));
                    }
                },
            ],
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
            'payment_option' => __('payment method'),
        ];
    }

    /**
     * A delivery that our team still has to price cannot be paid online yet.
     */
    protected function deliveryFeeIsPending(Cart $cart): bool
    {
        if ($this->fulfilment !== FulfilmentMethod::Delivery->value) {
            return false;
        }

        $zone = $this->selectedZone;

        return $zone === null || $zone->isQuoted() || $cart->needsDeliveryQuote($cart->lines(Market::current()));
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
            'canPayOnline' => $this->onlinePaymentsEnabled && ! $this->deliveryFeeIsPending($cart),
            'paysOnline' => $this->payment_option === self::PayOnline,
        ]);
    }
}
