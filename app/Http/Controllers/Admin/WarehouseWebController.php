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

class WarehouseWebController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $status = $request->query('status');

        $q = Warehouse::query()
            ->withCount(['stockLevels as product_count', 'movements as movements_count'])
            ->withSum('stockLevels as total_units', 'quantity')
            ->latest('id');

        if (is_string($status) && $status !== '') {
            $q->where('is_active', $status === 'active');
        }

        $warehouses = $q->paginate(20)->withQueryString();

        $this->attachProductLinks($warehouses->items());

        return AdminSpa::respond($request, 'Admin/Warehouses/Index', [
            'warehouses' => $warehouses,
            'status' => $status,
            'stats' => $this->stats(),
        ]);
    }

    public function create(Request $request): View|JsonResponse
    {
        return AdminSpa::respond($request, 'Admin/Warehouses/Form', [
            'warehouse' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Warehouse::create($data);

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('admin.flash.warehouse_created'));
    }

    public function edit(Request $request, Warehouse $warehouse): View|JsonResponse
    {
        return AdminSpa::respond($request, 'Admin/Warehouses/Form', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $this->validated($request);
        $warehouse->update($data);

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('admin.flash.warehouse_updated'));
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->stockLevels()->where('quantity', '>', 0)->exists()) {
            return redirect()
                ->route('admin.warehouses.index')
                ->withErrors(__('admin.warehouses.cannot_delete_nonempty'));
        }

        $warehouse->delete();

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('admin.flash.warehouse_deleted'));
    }

    public function linkProducts(Request $request, Warehouse $warehouse): View|JsonResponse
    {
        $products = Product::query()
            ->with(['stockLevels' => fn ($q) => $q->where('warehouse_id', $warehouse->id)])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'name_en' => $p->name_en,
                'sku' => $p->sku,
                'unit' => $p->unit,
                'min_stock' => $p->min_stock,
                'is_active' => $p->is_active,
                'linked' => $p->stockLevels->isNotEmpty(),
                'quantity' => (int) ($p->stockLevels->first()?->quantity ?? 0),
            ]);

        return AdminSpa::respond($request, 'Admin/Warehouses/LinkProducts', [
            'warehouse' => [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'name_en' => $warehouse->name_en,
                'code' => $warehouse->code,
                'location' => $warehouse->location,
                'is_active' => $warehouse->is_active,
                'total_units' => $warehouse->totalUnits(),
            ],
            'products' => $products,
        ]);
    }

    public function updateProductLinks(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validate([
            'products' => ['required', 'array'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.linked' => ['required', 'boolean'],
            'products.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $blockers = collect($data['products'])
            ->filter(fn (array $row) => ! $row['linked'])
            ->filter(fn (array $row) => StockLevel::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $row['id'])
                ->value('quantity') > 0);

        if ($blockers->isNotEmpty()) {
            return back()->withErrors(__('admin.warehouses.unlink_has_stock'));
        }

        $stock = app(\App\Services\StockService::class);

        DB::transaction(function () use ($data, $warehouse, $stock) {
            foreach ($data['products'] as $row) {
                if ($row['linked']) {
                    $stock->link((int) $row['id'], $warehouse->id, (int) $row['quantity']);
                } else {
                    $stock->unlink((int) $row['id'], $warehouse->id);
                }
            }
        });

        return redirect()
            ->route('admin.warehouses.index')
            ->with('status', __('admin.flash.links_saved'));
    }

    /**
     * @param  array<int, \App\Models\Warehouse>  $warehouses
     */
    private function attachProductLinks(array $warehouses): void
    {
        $ids = collect($warehouses)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $links = StockLevel::query()
            ->whereIn('warehouse_id', $ids)
            ->with('product')
            ->get()
            ->groupBy('warehouse_id')
            ->map(fn ($levels) => $levels->map(fn (StockLevel $l) => [
                'id' => $l->product_id,
                'name' => $l->product->name,
                'name_en' => $l->product->name_en,
                'sku' => $l->product->sku,
                'unit' => $l->product->unit,
                'quantity' => (int) $l->quantity,
            ])->values());

        foreach ($warehouses as $warehouse) {
            $warehouse->setAttribute('products', $links->get($warehouse->id, collect())->all());
        }
    }

    /**
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'warehouses' => Warehouse::count(),
            'products' => Product::where('is_active', true)->count(),
            'units' => (int) StockLevel::sum('quantity'),
            'low' => Product::query()
                ->whereRaw($this->totalUnitsRaw().' <= products.min_stock')
                ->count(),
        ];
    }

    private function totalUnitsRaw(): string
    {
        return 'COALESCE((SELECT SUM(quantity) FROM stock_levels WHERE stock_levels.product_id = products.id), 0)';
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('warehouses', 'code')->ignore($request->route('warehouse')?->id),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
