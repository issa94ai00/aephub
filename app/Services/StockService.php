<?php

namespace App\Services;

use App\Exceptions\Stock\InsufficientStockException;
use App\Models\StockLevel;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for stock domain logic. Every public operation is
 * transactional and records an audit movement so the stock level history is
 * always traceable.
 */
class StockService
{
    public function receive(int $productId, int $warehouseId, int $quantity, ?string $note = null, ?int $userId = null): StockLevel
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $note, $userId) {
            $level = $this->levelFor($productId, $warehouseId);
            $level->increment('quantity', $quantity);
            $level->refresh();

            $this->record($level, StockMovement::TYPE_IN, $quantity, $note, $userId);

            return $level;
        });
    }

    public function dispatch(int $productId, int $warehouseId, int $quantity, ?string $note = null, ?int $userId = null): StockLevel
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $note, $userId) {
            $level = $this->levelFor($productId, $warehouseId);

            if ($level->quantity < $quantity) {
                throw InsufficientStockException::withMessage(__('admin.stock.insufficient_stock'));
            }

            $level->decrement('quantity', $quantity);
            $level->refresh();

            $this->record($level, StockMovement::TYPE_OUT, $quantity, $note, $userId);

            return $level;
        });
    }

    public function adjust(int $productId, int $warehouseId, int $quantity, ?string $note = null, ?int $userId = null): StockLevel
    {
        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $note, $userId) {
            $level = $this->levelFor($productId, $warehouseId);
            $level->update(['quantity' => $quantity]);
            $level->refresh();

            $this->record($level, StockMovement::TYPE_ADJUST, $quantity, $note, $userId);

            return $level;
        });
    }

    public function transfer(int $productId, int $fromWarehouseId, int $toWarehouseId, int $quantity, ?string $note = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $quantity, $note, $userId) {
            $from = $this->levelFor($productId, $fromWarehouseId);

            if ($from->quantity < $quantity) {
                throw InsufficientStockException::withMessage(__('admin.stock.insufficient_stock'));
            }

            $from->decrement('quantity', $quantity);
            $from->refresh();
            $this->record($from, StockMovement::TYPE_TRANSFER_OUT, $quantity, $note, $userId);

            $to = $this->levelFor($productId, $toWarehouseId);
            $to->increment('quantity', $quantity);
            $to->refresh();
            $this->record($to, StockMovement::TYPE_TRANSFER_IN, $quantity, $note, $userId);
        });
    }

    /**
     * Link a product to a warehouse. Existing links keep their balance untouched.
     */
    public function link(int $productId, int $warehouseId, int $quantity = 0, ?string $note = null, ?int $userId = null): StockLevel
    {
        $level = $this->findLevel($productId, $warehouseId);

        if ($level !== null) {
            return $level;
        }

        return DB::transaction(function () use ($productId, $warehouseId, $quantity, $note, $userId) {
            $level = StockLevel::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);

            if ($quantity > 0) {
                $this->record($level, StockMovement::TYPE_IN, $quantity, $note ?? __('admin.products.initial_balance_note'), $userId);
            }

            return $level;
        });
    }

    /**
     * Remove a product from a warehouse. Throws when the link still holds stock.
     */
    public function unlink(int $productId, int $warehouseId): void
    {
        DB::transaction(function () use ($productId, $warehouseId) {
            $level = $this->findLevel($productId, $warehouseId);

            if ($level === null) {
                return;
            }

            if ($level->quantity > 0) {
                throw InsufficientStockException::withMessage(__('admin.stock.organize_unlink_blocked'));
            }

            $level->delete();
        });
    }

    public function balance(int $productId, int $warehouseId): int
    {
        return (int) ($this->findLevel($productId, $warehouseId)?->quantity ?? 0);
    }

    public function totalUnits(int $productId): int
    {
        return (int) StockLevel::query()->where('product_id', $productId)->sum('quantity');
    }

    private function findLevel(int $productId, int $warehouseId): ?StockLevel
    {
        return StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->first();
    }

    private function levelFor(int $productId, int $warehouseId): StockLevel
    {
        return StockLevel::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['quantity' => 0],
        );
    }

    private function record(StockLevel $level, string $type, int $quantity, ?string $note, ?int $userId): void
    {
        StockMovement::create([
            'warehouse_id' => $level->warehouse_id,
            'product_id' => $level->product_id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $level->quantity,
            'note' => $note,
        ]);
    }
}
