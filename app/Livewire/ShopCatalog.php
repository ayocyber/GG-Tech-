<?php

namespace App\Livewire;

use App\Enums\Market;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ShopCatalog extends Component
{
    use WithPagination;

    public const SORTS = ['featured', 'price_asc', 'price_desc', 'name'];

    #[Locked]
    public ?int $categoryId = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $brand = '';

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    #[Url(as: 'stock', except: false)]
    public bool $inStockOnly = false;

    public function mount(?Category $category = null): void
    {
        $this->categoryId = $category?->id;

        if (! in_array($this->sort, self::SORTS, true)) {
            $this->sort = 'featured';
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'brand', 'sort', 'inStockOnly'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'brand', 'inStockOnly');
        $this->sort = 'featured';
        $this->resetPage();
    }

    public function render(): View
    {
        $products = $this->filteredQuery()
            ->withStorefrontData()
            ->tap(fn (Builder $query) => $this->applySort($query))
            ->paginate(24);

        return view('livewire.shop-catalog', [
            'products' => $products,
            'brands' => Brand::query()
                ->whereHas('products', fn (Builder $query) => $this->baseQuery($query))
                ->withCount(['products' => fn (Builder $query) => $this->baseQuery($query)])
                ->orderBy('name')
                ->get(),
            'market' => Market::current(),
            'hasFilters' => $this->search !== '' || $this->brand !== '' || $this->inStockOnly,
        ]);
    }

    /**
     * Published products in the current category, before the visitor's filters.
     */
    private function baseQuery(Builder $query): Builder
    {
        return $query->published()->when($this->categoryId, fn (Builder $query) => $query->where('category_id', $this->categoryId));
    }

    private function filteredQuery(): Builder
    {
        return $this->baseQuery(Product::query())
            ->when($this->search !== '', fn (Builder $query) => $query->search($this->search))
            ->when($this->brand !== '', fn (Builder $query) => $query->whereRelation('brand', 'slug', $this->brand))
            ->when($this->inStockOnly, fn (Builder $query) => $query->whereRelation('variants', 'in_stock', true));
    }

    private function applySort(Builder $query): void
    {
        $lowestPrice = ProductVariant::query()
            ->selectRaw('min(price_xof)')
            ->whereColumn('product_variants.product_id', 'products.id');

        match ($this->sort) {
            'price_asc' => $query->orderByRaw('price_on_request')->orderBy($lowestPrice),
            'price_desc' => $query->orderByRaw('price_on_request')->orderByDesc($lowestPrice),
            'name' => $query->orderBy('name->'.app()->getLocale()),
            default => $query->orderByDesc('is_featured')->orderByRaw('(select count(*) from product_images where product_images.product_id = products.id) = 0'),
        };

        $query->orderBy('products.id');
    }
}
