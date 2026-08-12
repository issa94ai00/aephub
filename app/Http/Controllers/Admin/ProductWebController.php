<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Warehouse;
use App\Support\AdminSpa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductWebController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $category = $request->query('category');
        $low = $request->query('low');

        $q = Product::query()
            ->select('products.*')
            ->selectSub($this->totalUnitsSub(), 'total_units')
            ->latest('id');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.name_en', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        if (is_string($category) && $category !== '') {
            $q->where('products.category', $category);
        }

        if ($low === '1') {
            $q->whereRaw($this->totalUnitsRaw().' <= products.min_stock');
        }

        $products = $q->paginate(20)->withQueryString();

        $this->attachWarehouseLinks($products->items());

        $categories = Product::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return AdminSpa::respond($request, 'Admin/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'category' => $category,
            'low' => $low,
            'stats' => $this->stats(),
        ]);
    }

    public function create(Request $request): View|JsonResponse
    {
        return AdminSpa::respond($request, 'Admin/Products/Form', [
            'product' => null,
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', __('admin.flash.product_created'));
    }

    public function edit(Request $request, Product $product): View|JsonResponse
    {
        return AdminSpa::respond($request, 'Admin/Products/Form', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request);
        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('status', __('admin.flash.product_updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->stockLevels()->where('quantity', '>', 0)->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->withErrors(__('admin.products.cannot_delete_nonempty'));
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', __('admin.flash.product_deleted'));
    }

    public function linkWarehouses(Request $request, Product $product): View|JsonResponse
    {
        $warehouses = Warehouse::query()
            ->with(['stockLevels' => fn ($q) => $q->where('product_id', $product->id)])
            ->orderBy('name')
            ->get()
            ->map(fn (Warehouse $w) => [
                'id' => $w->id,
                'name' => $w->name,
                'name_en' => $w->name_en,
                'code' => $w->code,
                'location' => $w->location,
                'is_active' => $w->is_active,
                'linked' => $w->stockLevels->isNotEmpty(),
                'quantity' => (int) ($w->stockLevels->first()?->quantity ?? 0),
            ]);

        return AdminSpa::respond($request, 'Admin/Products/LinkWarehouses', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'name_en' => $product->name_en,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'min_stock' => $product->min_stock,
                'total_units' => $product->totalUnits(),
            ],
            'warehouses' => $warehouses,
        ]);
    }

    public function updateWarehouseLinks(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'warehouses' => ['required', 'array'],
            'warehouses.*.id' => ['required', 'integer', 'exists:warehouses,id'],
            'warehouses.*.linked' => ['required', 'boolean'],
            'warehouses.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $blockers = collect($data['warehouses'])
            ->filter(fn (array $row) => ! $row['linked'])
            ->filter(fn (array $row) => StockLevel::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $row['id'])
                ->value('quantity') > 0);

        if ($blockers->isNotEmpty()) {
            return back()->withErrors(__('admin.products.unlink_has_stock'));
        }

        $stock = app(\App\Services\StockService::class);

        DB::transaction(function () use ($data, $product, $stock) {
            foreach ($data['warehouses'] as $row) {
                if ($row['linked']) {
                    $stock->link($product->id, (int) $row['id'], (int) $row['quantity']);
                } else {
                    $stock->unlink($product->id, (int) $row['id']);
                }
            }
        });

        return redirect()
            ->route('admin.products.index')
            ->with('status', __('admin.flash.links_saved'));
    }

    /**
     * @param  array<int, \App\Models\Product>  $products
     */
    private function attachWarehouseLinks(array $products): void
    {
        $ids = collect($products)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $links = StockLevel::query()
            ->whereIn('product_id', $ids)
            ->with('warehouse')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($levels) => $levels->map(fn (StockLevel $l) => [
                'id' => $l->warehouse_id,
                'name' => $l->warehouse->name,
                'name_en' => $l->warehouse->name_en,
                'code' => $l->warehouse->code,
                'quantity' => (int) $l->quantity,
            ])->values());

        foreach ($products as $product) {
            $product->setAttribute('warehouses', $links->get($product->id, collect())->all());
        }
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'products' => Product::count(),
            'units' => (int) StockLevel::sum('quantity'),
            'low' => Product::query()
                ->whereRaw($this->totalUnitsRaw().' <= products.min_stock')
                ->count(),
            'warehouses' => Warehouse::count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'sku' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($request->route('product')?->id),
            ],
            'barcode' => ['nullable', 'string', 'max:64'],
            'category' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function totalUnitsSub(): \Closure
    {
        return function ($q) {
            $q->selectRaw('COALESCE(SUM(stock_levels.quantity), 0)')
                ->from('stock_levels')
                ->whereColumn('stock_levels.product_id', 'products.id');
        };
    }

    private function totalUnitsRaw(): string
    {
        return 'COALESCE((SELECT SUM(quantity) FROM stock_levels WHERE stock_levels.product_id = products.id), 0)';
    }

    /**
     * @return array<int, string>
     */
    private function categories(): array
    {
        return Product::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
