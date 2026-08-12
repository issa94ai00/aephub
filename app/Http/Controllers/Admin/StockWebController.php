<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\Stock\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\AdminSpa;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class StockWebController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $warehouseSummaries = Warehouse::query()
            ->withSum('stockLevels as total_units', 'quantity')
            ->withCount('stockLevels as product_count')
            ->orderBy('name')
            ->get();

        $totalProducts = Product::where('is_active', true)->count();
        $totalUnits = (int) StockLevel::sum('quantity');
        $lowStockCount = Product::query()
            ->whereRaw($this->totalUnitsRaw().' <= products.min_stock')
            ->count();

        $lowStock = Product::query()
            ->select('products.*')
            ->selectSub($this->totalUnitsSub(), 'total_units')
            ->whereRaw($this->totalUnitsRaw().' <= products.min_stock')
            ->orderByRaw($this->totalUnitsRaw().' ASC')
            ->limit(10)
            ->get();

        $recentMovements = StockMovement::query()
            ->with(['warehouse', 'product', 'user'])
            ->latest('id')
            ->take(10)
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'sku', 'barcode', 'unit', 'min_stock', 'is_active']);

        $activeWarehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'code', 'is_active']);

        return AdminSpa::respond($request, 'Admin/Stock/Index', [
            'warehouseSummaries' => $warehouseSummaries,
            'totalProducts' => $totalProducts,
            'totalUnits' => $totalUnits,
            'lowStockCount' => $lowStockCount,
            'lowStock' => $lowStock,
            'recentMovements' => $recentMovements,
            'products' => $products,
            'activeWarehouses' => $activeWarehouses,
            'balances' => $this->balanceMap($products->pluck('id')->all()),
        ]);
    }

    public function movements(Request $request): View|JsonResponse
    {
        $type = $request->query('type');
        $warehouseId = $request->query('warehouse_id');
        $productId = $request->query('product_id');
        $from = $request->query('from');
        $to = $request->query('to');

        $movements = $this->movementQuery($request)->paginate(25)->withQueryString();

        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name', 'name_en']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'name_en', 'sku']);

        return AdminSpa::respond($request, 'Admin/Stock/Movements', [
            'movements' => $movements,
            'warehouses' => $warehouses,
            'products' => $products,
            'type' => $type,
            'warehouseId' => $warehouseId,
            'productId' => $productId,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function movementsExport(Request $request): Response
    {
        $headers = ['ID', 'Time', 'Type', 'Product', 'SKU', 'Warehouse', 'Quantity', 'Balance', 'User', 'Note'];

        $rows = $this->movementQuery($request)->get()->map(function (StockMovement $m) {
            $signed = in_array($m->type, [StockMovement::TYPE_OUT, StockMovement::TYPE_TRANSFER_OUT], true)
                ? -$m->quantity
                : $m->quantity;

            return [
                $m->id,
                $m->occurred_at?->format('Y-m-d H:i:s'),
                $m->type,
                $m->product?->name,
                $m->product?->sku,
                $m->warehouse?->name,
                $signed,
                $m->balance_after,
                $m->user?->name,
                $m->note,
            ];
        })->all();

        return $this->csvDownload('stock-movements-'.date('Ymd-His').'.csv', $headers, $rows);
    }

    public function balances(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $lowStock = $request->query('low_stock') === '1';

        $q = Product::query()->with('stockLevels');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.name_en', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        if ($lowStock) {
            $q->whereRaw($this->totalUnitsRaw().' <= products.min_stock');
        }

        $products = $q->orderBy('name')->paginate(20)->withQueryString();

        $activeWarehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'code']);

        $rows = $products->getCollection()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'name_en' => $p->name_en,
            'sku' => $p->sku,
            'unit' => $p->unit,
            'min_stock' => $p->min_stock,
            'is_active' => $p->is_active,
            'by_warehouse' => $p->stockLevels->mapWithKeys(fn (StockLevel $l) => [$l->warehouse_id => (int) $l->quantity]),
            'total' => (int) $p->stockLevels->sum('quantity'),
        ])->values();

        $products->setCollection($rows);

        return AdminSpa::respond($request, 'Admin/Stock/Balances', [
            'products' => $products,
            'warehouses' => $activeWarehouses,
            'search' => $search,
            'lowStock' => $lowStock,
            'stats' => [
                'products' => Product::where('is_active', true)->count(),
                'warehouses' => $activeWarehouses->count(),
                'units' => (int) StockLevel::sum('quantity'),
                'low' => Product::query()
                    ->whereRaw($this->totalUnitsRaw().' <= products.min_stock')
                    ->count(),
            ],
        ]);
    }

    public function balancesExport(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $lowStock = $request->query('low_stock') === '1';

        $q = Product::query()->with('stockLevels');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.name_en', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        if ($lowStock) {
            $q->whereRaw($this->totalUnitsRaw().' <= products.min_stock');
        }

        $products = $q->orderBy('name')->get();

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $headers = array_merge(
            ['SKU', 'Name', 'Name (EN)', 'Unit'],
            $warehouses->pluck('name')->all(),
            ['Total', 'Reorder threshold', 'Status'],
        );

        $rows = $products->map(function (Product $p) use ($warehouses) {
            $map = $p->stockLevels->mapWithKeys(fn (StockLevel $l) => [$l->warehouse_id => (int) $l->quantity]);
            $row = [$p->sku, $p->name, $p->name_en, $p->unit];

            foreach ($warehouses as $w) {
                $row[] = $map->get($w->id, 0);
            }

            $row[] = (int) $p->stockLevels->sum('quantity');
            $row[] = $p->min_stock;
            $row[] = $p->totalUnits() <= $p->min_stock ? __('admin.stock.low') : __('admin.stock.ok');

            return $row;
        })->all();

        return $this->csvDownload('stock-balances-'.date('Ymd-His').'.csv', $headers, $rows);
    }

    public function organize(Request $request): View|JsonResponse
    {
        $search = $request->query('search');

        $products = Product::query()
            ->with('stockLevels')
            ->when(is_string($search) && trim($search) !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.trim($search).'%')
                        ->orWhere('name_en', 'like', '%'.trim($search).'%')
                        ->orWhere('sku', 'like', '%'.trim($search).'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $products->getCollection()->transform(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'name_en' => $p->name_en,
            'sku' => $p->sku,
            'unit' => $p->unit,
            'min_stock' => $p->min_stock,
            'is_active' => $p->is_active,
            'links' => $p->stockLevels->map(fn (StockLevel $l) => [
                'warehouse_id' => $l->warehouse_id,
                'linked' => true,
                'quantity' => (int) $l->quantity,
            ])->values(),
        ]);

        $warehouses = Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_en', 'code']);

        return AdminSpa::respond($request, 'Admin/Stock/Organize', [
            'products' => $products,
            'warehouses' => $warehouses,
            'search' => is_string($search) ? $search : '',
            'stats' => [
                'products' => Product::where('is_active', true)->count(),
                'warehouses' => $warehouses->count(),
                'units' => (int) StockLevel::sum('quantity'),
            ],
        ]);
    }

    public function updateOrganization(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'rows.*.links' => ['array'],
            'rows.*.links.*.warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'rows.*.links.*.linked' => ['required', 'boolean'],
            'rows.*.links.*.quantity' => ['required', 'integer', 'min:0'],
        ]);

        $blocked = collect($data['rows'])->flatMap(function (array $row) {
            return collect($row['links'] ?? [])
                ->filter(fn (array $l) => ! (bool) $l['linked'])
                ->filter(fn (array $l) => StockLevel::query()
                    ->where('warehouse_id', $l['warehouse_id'])
                    ->where('product_id', $row['product_id'])
                    ->where('quantity', '>', 0)
                    ->exists());
        });

        if ($blocked->isNotEmpty()) {
            return back()->withErrors(__('admin.stock.organize_unlink_blocked'));
        }

        $stock = app(StockService::class);

        DB::transaction(function () use ($data, $stock) {
            foreach ($data['rows'] as $row) {
                $productId = (int) $row['product_id'];

                foreach ($row['links'] as $link) {
                    $warehouseId = (int) $link['warehouse_id'];

                    if ($link['linked']) {
                        $stock->link($productId, $warehouseId, (int) $link['quantity']);
                    } else {
                        $stock->unlink($productId, $warehouseId);
                    }
                }
            }
        });

        return back()->with('status', __('admin.flash.organization_saved'));
    }

    public function receive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        app(StockService::class)->receive(
            (int) $data['product_id'],
            (int) $data['warehouse_id'],
            (int) $data['quantity'],
            $data['note'] ?? null,
        );

        return back()->with('status', __('admin.flash.stock_received'));
    }

    public function dispatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(StockService::class)->dispatch(
                (int) $data['product_id'],
                (int) $data['warehouse_id'],
                (int) $data['quantity'],
                $data['note'] ?? null,
            );
        } catch (InsufficientStockException $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('status', __('admin.flash.stock_dispatched'));
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        app(StockService::class)->adjust(
            (int) $data['product_id'],
            (int) $data['warehouse_id'],
            (int) $data['quantity'],
            $data['note'] ?? null,
        );

        return back()->with('status', __('admin.flash.stock_adjusted'));
    }

    public function transfer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'integer', 'different:to_warehouse_id', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            app(StockService::class)->transfer(
                (int) $data['product_id'],
                (int) $data['from_warehouse_id'],
                (int) $data['to_warehouse_id'],
                (int) $data['quantity'],
                $data['note'] ?? null,
            );
        } catch (InsufficientStockException $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('status', __('admin.flash.stock_transferred'));
    }

    private function movementQuery(Request $request): Builder
    {
        return StockMovement::query()
            ->with(['warehouse', 'product', 'user'])
            ->latest('occurred_at')
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->query('type')))
            ->when($request->filled('warehouse_id'), fn (Builder $q) => $q->where('warehouse_id', (int) $request->query('warehouse_id')))
            ->when($request->filled('product_id'), fn (Builder $q) => $q->where('product_id', (int) $request->query('product_id')))
            ->when($request->filled('from'), fn (Builder $q) => $q->whereDate('occurred_at', '>=', $request->query('from')))
            ->when($request->filled('to'), fn (Builder $q) => $q->whereDate('occurred_at', '<=', $request->query('to')));
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array<string, mixed>>
     */
    private function balanceMap(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $levels = StockLevel::query()
            ->whereIn('product_id', $productIds)
            ->get(['product_id', 'warehouse_id', 'quantity']);

        $map = [];

        foreach ($levels as $l) {
            $map[$l->product_id]['by_warehouse'][(string) $l->warehouse_id] = (int) $l->quantity;
            $map[$l->product_id]['total'] = ($map[$l->product_id]['total'] ?? 0) + (int) $l->quantity;
        }

        foreach ($map as $pid => $data) {
            $map[$pid]['by_warehouse'] ??= [];
            $map[$pid]['total'] ??= 0;
        }

        return $map;
    }

    private function csvDownload(string $filename, array $headers, array $rows): Response
    {
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            fputcsv($out, $row);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
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
}
