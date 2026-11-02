<?php

namespace App\Livewire;

use App\Enums\Market;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Baskets\Cart;
use App\Services\Baskets\SessionBasket;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Adds products to the cart from any product card, asking for an option first when there is a choice.
 */
class QuickAdd extends Component
{
    #[Locked]
    public ?int $productId = null;

    public ?int $variantId = null;

    public int|string $quantity = 1;

    #[On('quick-add')]
    public function open(int $productId, Cart $cart): void
    {
        $this->close();
        $this->productId = $productId;
        unset($this->product);

        $product = $this->product;

        if (! $product?->canBeAddedToCart()) {
            $this->close();
            $this->notify(__('This option cannot be ordered online. Ask us on WhatsApp or request a quote.'), wasAdded: false);
        } elseif ($product->hasVariantChoice()) {
            $this->variantId = $product->variants
                ->first(fn (ProductVariant $variant) => $product->canSellOnline($variant))
                ->id;
        } else {
            $cart->add($product->variants->first());
            $this->confirmAddition($product);
        }

        $this->dispatch('quick-add-finished');
    }

    /**
     * Joins the basket refresh so a quick add never runs alongside it: that refresh
     * could finish later and overwrite the session with a cart missing the new item.
     */
    #[On('basket-updated')]
    public function waitForBasketRefresh(): void {}

    public function close(): void
    {
        $this->reset('productId', 'variantId', 'quantity');
        $this->resetErrorBag();
        unset($this->product);
    }

    public function selectVariant(int $variantId): void
    {
        if ($this->product?->variants->contains('id', $variantId)) {
            $this->variantId = $variantId;
            $this->resetErrorBag();
        }
    }

    /**
     * Each tap sends its own $tappedAt because Livewire merges identical calls made while a
     * request is running, which would otherwise lose fast taps. The same applies to decrement().
     */
    public function increment(?float $tappedAt = null): void
    {
        $this->quantity = min(SessionBasket::MAX_QUANTITY, (int) $this->quantity + 1);
    }

    public function decrement(?float $tappedAt = null): void
    {
        $this->quantity = max(1, (int) $this->quantity - 1);
    }

    #[Computed]
    public function product(): ?Product
    {
        if ($this->productId === null) {
            return null;
        }

        return Product::query()->published()->withStorefrontData()->find($this->productId);
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        return $this->product?->variants->firstWhere('id', $this->variantId);
    }

    public function addToCart(Cart $cart): void
    {
        $product = $this->product;
        $variant = $this->variant;

        if (! $product?->canSellOnline($variant)) {
            $this->addError('cart', __('This option cannot be ordered online. Ask us on WhatsApp or request a quote.'));

            return;
        }

        $cart->add($variant, max(1, min(SessionBasket::MAX_QUANTITY, (int) $this->quantity)));
        $this->confirmAddition($product);
    }

    public function render(): View
    {
        return view('livewire.quick-add', ['market' => Market::current()]);
    }

    private function confirmAddition(Product $product): void
    {
        $this->close();
        $this->dispatch('basket-updated');
        $this->notify(__('Added to your cart.').' '.$product->translate('name'), wasAdded: true);
    }

    private function notify(string $message, bool $wasAdded): void
    {
        $this->dispatch('quick-add-notice', message: $message, wasAdded: $wasAdded);
    }
}
