<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function __invoke(?Category $category = null): View
    {
        abort_if($category && ! $category->is_active, 404);

        return view('shop.index', ['category' => $category]);
    }
}
