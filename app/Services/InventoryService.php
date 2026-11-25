<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Adjust stock for a product in a warehouse and record an immutable movement entry.
     */
    public static function adjustStock(
        int $productId,
        ?int $warehouseId,
        float $quantityDelta,
        string $movementType,
        float $unitCost = 0,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNo = null,
        ?string $notes = null
    ): StockMovement {
        return DB::transaction(function () use (
            $productId,
            $warehouseId,
            $quantityDelta,
            $movementType,
            $unitCost,
            $referenceType,
            $referenceId,
            $referenceNo,
            $notes
        ) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            if (! $warehouseId) {
                $warehouseId = Warehouse::where('is_default', true)->value('id')
                    ?? Warehouse::query()->value('id');
            }

            if ($warehouseId) {
                $stock = ProductStock::lockForUpdate()->firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $warehouseId],
                    ['quantity' => 0]
                );
                $newWarehouseQty = round((float) $stock->quantity + $quantityDelta, 2);
                $stock->update(['quantity' => $newWarehouseQty]);

                $totalQty = (float) ProductStock::where('product_id', $product->id)->sum('quantity');
                $product->update(['stock_quantity' => $totalQty]);
                $balanceAfter = $totalQty;
            } else {
                $balanceAfter = round((float) $product->stock_quantity + $quantityDelta, 2);
                $product->update(['stock_quantity' => $balanceAfter]);
            }

            return StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'movement_type' => $movementType,
                'quantity_change' => $quantityDelta,
                'balance_after' => $balanceAfter,
                'unit_cost' => $unitCost ?: (float) $product->cost_price,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'notes' => $notes,
                'created_by' => Auth::id(),
            ]);
        });
    }
}
