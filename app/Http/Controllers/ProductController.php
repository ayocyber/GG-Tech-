<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->status === ProductStatus::Published, 404);

        $product->load([
            'category',
            'brand',
            'variants',
            'images' => fn (Builder $query) => $query->whereNotNull('processed_at'),
        ]);

        return view('products.show', [
            'product' => $product,
            'tonerCounterpart' => $product->tonerCounterpart(),
            'relatedProducts' => Product::query()
                ->published()
                ->whereBelongsTo($product->category)
                ->whereKeyNot($product->id)
                ->withStorefrontData()
                ->orderByDesc('is_featured')
                ->orderBy('id')
                ->take(4)
                ->get(),
        ]);
    }
}
