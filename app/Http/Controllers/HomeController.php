<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'featuredProducts' => Product::query()
                ->published()
                ->where('is_featured', true)
                ->withStorefrontData()
                ->orderByDesc('id')
                ->take(8)
                ->get(),
            'newProducts' => Product::query()
                ->published()
                ->has('coverImage')
                ->where('is_featured', false)
                ->withStorefrontData()
                ->orderByDesc('catalog_page')
                ->orderByDesc('id')
                ->take(8)
                ->get(),
            'brands' => Brand::query()
                ->whereHas('products', fn (Builder $query) => $query->published())
                ->withCount(['products' => fn (Builder $query) => $query->published()])
                ->orderByDesc('products_count')
                ->take(12)
                ->get(),
        ]);
    }
}
