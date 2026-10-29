<?php

namespace App\Support;

use App\Enums\TonerType;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Keeps links shared before the October 2026 toner split working.
 *
 * The "toners" category became "original-toners" and "compatible-toners", and each toner's
 * slug gained an "-original" or "-premium-compatible" suffix.
 */
class LegacyLinks
{
    /**
     * Searches from the old toner category run across both toner types; plain visits land on original toners.
     */
    public static function redirectCategory(Request $request): RedirectResponse
    {
        abort_unless($request->route('category') === 'toners', 404);

        $query = $request->query();

        return redirect()->route('shop', [
            'locale' => $request->route('locale'),
            ...($query === [] ? ['category' => TonerType::Original->categorySlug()] : $query),
        ], 301);
    }

    public static function redirectProduct(Request $request): RedirectResponse
    {
        $slug = (string) $request->route('product');

        $product = Product::query()
            ->published()
            ->whereIn('slug', collect(TonerType::cases())->map(fn (TonerType $type) => $slug.$type->slugSuffix()))
            ->orderByRaw('toner_type = ? desc', [TonerType::Original->value])
            ->first();

        abort_unless($product, 404);

        return redirect()->route('products.show', ['locale' => $request->route('locale'), 'product' => $product], 301);
    }
}
