<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkFlowTmpTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_link_flow(): void
    {
        $admin = User::create(['name' => 'Link Tmp', 'email' => 'linkflow-tmp@test.test', 'password' => 'secret123', 'role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::create([
            'name' => 'قلم حبر TMP', 'name_en' => 'Pen TMP', 'sku' => 'SKU-TMP-'.time(),
            'unit' => 'piece', 'cost_price' => 5, 'sale_price' => 10, 'min_stock' => 3, 'is_active' => true,
        ]);
        $wh1 = Warehouse::create(['name' => 'TMP WH1', 'name_en' => 'TMP WH1', 'code' => 'TMP1-'.time(), 'is_active' => true]);
        $wh2 = Warehouse::create(['name' => 'TMP WH2', 'name_en' => 'TMP WH2', 'code' => 'TMP2-'.time(), 'is_active' => true]);
        $tmp = Warehouse::create(['name' => 'TMP LINK', 'name_en' => 'TMP LINK', 'code' => 'TMPL-'.time(), 'is_active' => true]);
        $tmpEmpty = Warehouse::create(['name' => 'TMP EMPTY', 'name_en' => 'TMP EMPTY', 'code' => 'TMPE-'.time(), 'is_active' => true]);

        try {
            StockLevel::create(['product_id' => $product->id, 'warehouse_id' => $wh1->id, 'quantity' => 30]);
            StockLevel::create(['product_id' => $product->id, 'warehouse_id' => $wh2->id, 'quantity' => 20]);

            // 1) GET the link page as the SPA would (JSON payload)
            $resp = $this->withHeader('Accept', 'application/json')
                ->get("/admin/products/{$product->id}/link-warehouses");
            $resp->assertOk();
            $this->assertSame('Admin/Products/LinkWarehouses', $resp->json('name'));
            $this->assertSame($product->id, $resp->json('props.product.id'));
            $this->assertSame(50, $product->totalUnits());
            $this->assertArrayHasKey('adminChrome', $resp->json('props'));
            $this->assertArrayHasKey('translations', $resp->json('props'));
            $warehouses = $resp->json('props.warehouses');
            $this->assertCount(4, $warehouses);
            $this->assertArrayHasKey('linked', $warehouses[0]);
            $this->assertArrayHasKey('quantity', $warehouses[0]);

            // 2) Link tmp (initial balance 5) and tmpEmpty (initial balance 0); keep wh1/wh2 linked
            $payload = collect($warehouses)->map(fn ($w) => [
                'id' => $w['id'],
                'linked' => $w['linked'] || $w['id'] === $tmp->id || $w['id'] === $tmpEmpty->id,
                'quantity' => $w['id'] === $tmp->id ? 5 : ($w['id'] === $tmpEmpty->id ? 0 : 0),
            ])->all();

            $beforeMovements = StockMovement::count();

            $resp = $this->put("/admin/products/{$product->id}/link-warehouses", ['warehouses' => $payload]);
            $resp->assertRedirect(route('admin.products.index'));
            $resp->assertSessionHas('status');

            $this->assertDatabaseHas('stock_levels', [
                'product_id' => $product->id,
                'warehouse_id' => $tmp->id,
                'quantity' => 5,
            ]);
            $this->assertDatabaseHas('stock_levels', [
                'product_id' => $product->id,
                'warehouse_id' => $tmpEmpty->id,
                'quantity' => 0,
            ]);
            $this->assertSame($beforeMovements + 1, StockMovement::count());
            $movement = StockMovement::where('product_id', $product->id)
                ->where('warehouse_id', $tmp->id)
                ->latest('id')
                ->first();
            $this->assertSame('in', $movement->type);
            $this->assertSame(5, $movement->quantity);
            $this->assertSame(5, $movement->balance_after);

            // 3) Re-saving with the same state must NOT create another movement
            $resp = $this->put("/admin/products/{$product->id}/link-warehouses", ['warehouses' => $payload]);
            $resp->assertRedirect(route('admin.products.index'));
            $this->assertSame($beforeMovements + 1, StockMovement::count());

            // 4) Unlinking a warehouse that still holds stock must be rejected
            $payloadExisting = collect($warehouses)->map(fn ($w) => [
                'id' => $w['id'],
                'linked' => $w['id'] !== $wh2->id,
                'quantity' => $w['id'] === $tmp->id ? 5 : 0,
            ])->all();

            $resp = $this->put("/admin/products/{$product->id}/link-warehouses", ['warehouses' => $payloadExisting]);
            $resp->assertSessionHasErrors();
            $this->assertDatabaseHas('stock_levels', [
                'product_id' => $product->id,
                'warehouse_id' => $wh2->id,
                'quantity' => 20,
            ]);

            // 5) Unlinking a link that holds no stock must succeed
            $payloadUnlinkEmpty = collect($warehouses)->map(fn ($w) => [
                'id' => $w['id'],
                'linked' => $w['id'] !== $tmpEmpty->id,
                'quantity' => $w['id'] === $tmp->id ? 5 : 0,
            ])->all();
            $resp = $this->put("/admin/products/{$product->id}/link-warehouses", ['warehouses' => $payloadUnlinkEmpty]);
            $resp->assertRedirect(route('admin.products.index'));
            $this->assertDatabaseMissing('stock_levels', [
                'product_id' => $product->id,
                'warehouse_id' => $tmpEmpty->id,
            ]);

            // 6) Warehouse -> products link page
            $resp = $this->withHeader('Accept', 'application/json')
                ->get("/admin/warehouses/{$wh1->id}/link-products");
            $resp->assertOk();
            $this->assertSame('Admin/Warehouses/LinkProducts', $resp->json('name'));
            $products = $resp->json('props.products');
            $this->assertArrayHasKey('linked', $products[0]);
            $this->assertCount(1, $products);

            // 7) Warehouse link page redirects correctly on save
            $pPayload = collect($products)->map(fn ($p) => [
                'id' => $p['id'],
                'linked' => $p['linked'],
                'quantity' => $p['quantity'],
            ])->all();
            $resp = $this->put("/admin/warehouses/{$wh1->id}/link-products", ['products' => $pPayload]);
            $resp->assertRedirect(route('admin.warehouses.index'));
        } finally {
            StockMovement::where('product_id', $product->id)->delete();
            StockLevel::where('product_id', $product->id)->delete();
            $tmpEmpty->delete();
            $tmp->delete();
            $wh1->delete();
            $wh2->delete();
            $product->delete();
            $admin->delete();
        }
    }

    public function test_stock_organize_flow(): void
    {
        $admin = User::create(['name' => 'Org Tmp', 'email' => 'org-tmp@test.test', 'password' => 'secret123', 'role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::create([
            'name' => 'قلم رصاص TMP', 'name_en' => 'Pencil TMP', 'sku' => 'SKU-ORG-'.time(),
            'unit' => 'piece', 'cost_price' => 2, 'sale_price' => 4, 'min_stock' => 2, 'is_active' => true,
        ]);
        $wh1 = Warehouse::create(['name' => 'ORG WH1', 'name_en' => 'ORG WH1', 'code' => 'ORG1-'.time(), 'is_active' => true]);
        $wh2 = Warehouse::create(['name' => 'ORG WH2', 'name_en' => 'ORG WH2', 'code' => 'ORG2-'.time(), 'is_active' => true]);
        $whEmpty = Warehouse::create(['name' => 'ORG EMPTY', 'name_en' => 'ORG EMPTY', 'code' => 'ORGE-'.time(), 'is_active' => true]);

        try {
            StockLevel::create(['product_id' => $product->id, 'warehouse_id' => $wh1->id, 'quantity' => 10]);
            StockLevel::create(['product_id' => $product->id, 'warehouse_id' => $wh2->id, 'quantity' => 6]);

            // 1) GET the organize page as the SPA would (JSON payload)
            $resp = $this->withHeader('Accept', 'application/json')->get('/admin/stock/organize');
            $resp->assertOk();
            $this->assertSame('Admin/Stock/Organize', $resp->json('name'));
            $this->assertSame($product->id, $resp->json('props.products.data.0.id'));
            $links = $resp->json('props.products.data.0.links');
            $this->assertCount(2, $links);
            $this->assertTrue($links[0]['linked']);
            $this->assertSame(10, $links[0]['quantity']);
            $this->assertCount(3, $resp->json('props.warehouses'));
            $this->assertArrayHasKey('stats', $resp->json('props'));

            // 2) Link the empty warehouse with an initial balance of 7
            $rows = [[
                'product_id' => $product->id,
                'links' => [
                    ['warehouse_id' => $wh1->id, 'linked' => true, 'quantity' => 10],
                    ['warehouse_id' => $wh2->id, 'linked' => true, 'quantity' => 6],
                    ['warehouse_id' => $whEmpty->id, 'linked' => true, 'quantity' => 7],
                ],
            ]];
            $before = StockMovement::count();
            $resp = $this->post('/admin/stock/organize', ['rows' => $rows]);
            $resp->assertRedirect();
            $resp->assertSessionHas('status');
            $this->assertDatabaseHas('stock_levels', [
                'product_id' => $product->id, 'warehouse_id' => $whEmpty->id, 'quantity' => 7,
            ]);
            $this->assertSame($before + 1, StockMovement::count());
            $movement = StockMovement::where('product_id', $product->id)->where('warehouse_id', $whEmpty->id)->latest('id')->first();
            $this->assertSame('in', $movement->type);
            $this->assertSame(7, $movement->quantity);
            $this->assertSame(7, $movement->balance_after);

            // 3) Re-saving the same state must NOT create another movement
            $resp = $this->post('/admin/stock/organize', ['rows' => $rows]);
            $resp->assertRedirect();
            $this->assertSame($before + 1, StockMovement::count());

            // 4) Unlinking a warehouse that still holds stock must be rejected
            $rowsUnlink = [[
                'product_id' => $product->id,
                'links' => [
                    ['warehouse_id' => $wh1->id, 'linked' => true, 'quantity' => 10],
                    ['warehouse_id' => $wh2->id, 'linked' => false, 'quantity' => 6],
                    ['warehouse_id' => $whEmpty->id, 'linked' => true, 'quantity' => 7],
                ],
            ]];
            $resp = $this->post('/admin/stock/organize', ['rows' => $rowsUnlink]);
            $resp->assertSessionHasErrors();
            $this->assertDatabaseHas('stock_levels', [
                'product_id' => $product->id, 'warehouse_id' => $wh2->id, 'quantity' => 6,
            ]);

            // 5) Unlinking a link that holds no stock must succeed
            StockLevel::where('product_id', $product->id)->where('warehouse_id', $whEmpty->id)->update(['quantity' => 0]);
            $rowsUnlinkEmpty = [[
                'product_id' => $product->id,
                'links' => [
                    ['warehouse_id' => $wh1->id, 'linked' => true, 'quantity' => 10],
                    ['warehouse_id' => $wh2->id, 'linked' => true, 'quantity' => 6],
                    ['warehouse_id' => $whEmpty->id, 'linked' => false, 'quantity' => 0],
                ],
            ]];
            $resp = $this->post('/admin/stock/organize', ['rows' => $rowsUnlinkEmpty]);
            $resp->assertRedirect();
            $this->assertDatabaseMissing('stock_levels', [
                'product_id' => $product->id, 'warehouse_id' => $whEmpty->id,
            ]);
        } finally {
            StockMovement::where('product_id', $product->id)->delete();
            StockLevel::where('product_id', $product->id)->delete();
            $whEmpty->delete();
            $wh1->delete();
            $wh2->delete();
            $product->delete();
            $admin->delete();
        }
    }

    public function test_stock_balances_and_exports(): void
    {
        $admin = User::create(['name' => 'Bal Tmp', 'email' => 'bal-tmp@test.test', 'password' => 'secret123', 'role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::create([
            'name' => 'دفتر TMP', 'name_en' => 'Notebook TMP', 'sku' => 'SKU-BAL-'.time(), 'barcode' => 'BAR-BAL-'.time(),
            'unit' => 'piece', 'cost_price' => 3, 'sale_price' => 6, 'min_stock' => 4, 'is_active' => true,
        ]);
        $wh1 = Warehouse::create(['name' => 'BAL WH1', 'name_en' => 'BAL WH1', 'code' => 'BAL1-'.time(), 'is_active' => true]);
        $wh2 = Warehouse::create(['name' => 'BAL WH2', 'name_en' => 'BAL WH2', 'code' => 'BAL2-'.time(), 'is_active' => true]);

        try {
            StockLevel::create(['product_id' => $product->id, 'warehouse_id' => $wh1->id, 'quantity' => 15]);
            StockLevel::create(['product_id' => $product->id, 'warehouse_id' => $wh2->id, 'quantity' => 0]);
            StockMovement::create([
                'product_id' => $product->id, 'warehouse_id' => $wh1->id, 'user_id' => $admin->id,
                'type' => StockMovement::TYPE_IN, 'quantity' => 15, 'balance_after' => 15, 'note' => 'seed',
            ]);

            // 1) Balances page payload (as the SPA would fetch it)
            $resp = $this->withHeader('Accept', 'application/json')->get('/admin/stock/balances');
            $resp->assertOk();
            $this->assertSame('Admin/Stock/Balances', $resp->json('name'));
            $this->assertCount(2, $resp->json('props.warehouses'));
            $row = $resp->json('props.products.data.0');
            $this->assertSame($product->id, $row['id']);
            $this->assertSame(15, $row['by_warehouse'][(string) $wh1->id]);
            $this->assertSame(15, $row['total']);
            $this->assertArrayHasKey('stats', $resp->json('props'));

            // 2) Balances low-stock filter
            $resp = $this->withHeader('Accept', 'application/json')->get('/admin/stock/balances?low_stock=1');
            $resp->assertOk();
            $this->assertSame('Admin/Stock/Balances', $resp->json('name'));

            // 3) Movements CSV export honours filters
            $resp = $this->get("/admin/stock/movements/export?product_id={$product->id}&type=".StockMovement::TYPE_IN);
            $resp->assertOk();
            $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringContainsString('attachment', $resp->headers->get('Content-Disposition'));
            $this->assertStringContainsString('stock-movements-', $resp->headers->get('Content-Disposition'));
            $this->assertStringContainsString($product->sku, $resp->getContent());
            $this->assertStringContainsString($product->name, $resp->getContent());

            // 4) Balances CSV export
            $resp = $this->get('/admin/stock/balances/export');
            $resp->assertOk();
            $resp->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringContainsString('attachment', $resp->headers->get('Content-Disposition'));
            $this->assertStringContainsString('stock-balances-', $resp->headers->get('Content-Disposition'));
            $content = $resp->getContent();
            $this->assertStringContainsString($product->sku, $content);
            $this->assertStringContainsString($wh1->name, $content);
        } finally {
            StockMovement::where('product_id', $product->id)->delete();
            StockLevel::where('product_id', $product->id)->delete();
            $wh2->delete();
            $wh1->delete();
            $product->delete();
            $admin->delete();
        }
    }
}
