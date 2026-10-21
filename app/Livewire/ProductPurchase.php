<?php

namespace App\Livewire;

use App\Enums\Market;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Baskets\Cart;
use App\Services\Baskets\QuoteBasket;
use App\Services\Baskets\SessionBasket;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProductPurchase extends Component
{
    #[Locked]
    public Product $product;

    public ?int $variantId = null;

    public int|string $quantity = 1;

    public ?string $added = null;

    public function mount(Product $product): void
    {
        $this->product = $product;

        $firstAvailable = $product->variants->firstWhere('in_stock', true) ?? $product->variants->first();
        $this->variantId = $firstAvailable?->id;
    }

    public function selectVariant(int $variantId): void
    {
        if ($this->product->variants->contains('id', $variantId)) {
            $this->variantId = $variantId;
            $this->added = null;
        }
    }

    public function increment(): void
    {
        $this->quantity = min(SessionBasket::MAX_QUANTITY, (int) $this->quantity + 1);
    }

    public function decrement(): void
    {
        $this->quantity = max(1, (int) $this->quantity - 1);
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        return $this->product->variants->firstWhere('id', $this->variantId);
    }

    public function addToCart(Cart $cart): void
    {
        $variant = $this->variant;

        if (! $variant || ! $variant->in_stock || $variant->price_xof === null || $this->product->price_on_request) {
            $this->addError('cart', __('This option cannot be ordered online. Ask us on WhatsApp or request a quote.'));

            return;
        }

        $cart->add($variant, $this->clampedQuantity());
        $this->added = 'cart';
        $this->dispatch('basket-updated');
    }

    public function addToQuote(QuoteBasket $quote): void
    {
        if (! $this->variant) {
            return;
        }

        $quote->add($this->variant, $this->clampedQuantity());
        $this->added = 'quote';
        $this->dispatch('basket-updated');
    }

    public function render(): View
    {
        $market = Market::current();

        return view('livewire.product-purchase', [
            'market' => $market,
            'price' => $this->variant?->priceFor($market),
            'isBulk' => (int) $this->quantity >= config('store.bulk_quantity'),
        ]);
    }

    private function clampedQuantity(): int
    {
        return max(1, min(SessionBasket::MAX_QUANTITY, (int) $this->quantity));
    }
}
