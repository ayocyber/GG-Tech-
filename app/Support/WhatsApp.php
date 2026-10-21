<?php

namespace App\Support;

use App\Enums\FulfilmentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Setting;

/**
 * Builds wa.me click-to-chat links with pre-filled messages.
 */
class WhatsApp
{
    public static function url(?string $message = null): string
    {
        $number = preg_replace('/\D/', '', (string) Setting::value('whatsapp'));

        return "https://wa.me/{$number}".($message ? '?text='.rawurlencode($message) : '');
    }

    public static function productQuestion(Product $product): string
    {
        return static::url(__('Hello GG-TECH, I am interested in :product (ref. :ref): :url', [
            'product' => $product->translate('name'),
            'ref' => $product->model ?? $product->slug,
            'url' => route('products.show', $product),
        ]));
    }

    public static function priceRequest(Product $product): string
    {
        return static::url(__('Hello GG-TECH, could you send me the price of :product? :url', [
            'product' => $product->translate('name'),
            'url' => route('products.show', $product),
        ]));
    }

    public static function orderConfirmation(Order $order): string
    {
        $lines = $order->items
            ->map(fn (OrderItem $item) => "• {$item->quantity} × {$item->product_name}"
                .($item->variant_label ? " ({$item->variant_label})" : '')
                .' — '.$order->formatMoney($item->line_total))
            ->implode("\n");

        $fulfilment = $order->fulfilment->getLabel()
            .($order->deliveryZone ? ' — '.$order->deliveryZone->translate('name') : '');

        return static::url(implode("\n", [
            __('Hello GG-TECH, I would like to confirm my order :number.', ['number' => $order->number]),
            '',
            $lines,
            '',
            __('Total: :total', ['total' => $order->formatMoney($order->total)])
                .($order->delivery_fee === null && $order->fulfilment === FulfilmentMethod::Delivery ? ' + '.__('delivery to be confirmed') : ''),
            __('Fulfilment: :method', ['method' => $fulfilment]),
            __('Name: :name', ['name' => $order->customer_name]),
        ]));
    }

    public static function quoteFollowUp(QuoteRequest $quote): string
    {
        return static::url(__('Hello GG-TECH, I just sent quote request :number for :company.', [
            'number' => $quote->number,
            'company' => $quote->company_name,
        ]));
    }
}
