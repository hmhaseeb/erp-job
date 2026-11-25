<?php

namespace App\Livewire\Inventory;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Stock & Inventory Operations')]
class StockOperations extends Component
{
    use WithPagination;

    public string $mode = 'stock'; // stock | adjustments | transfers | history | low-stock | reports

    // Stock In / Out form
    public ?int $product_id = null;
    public ?int $warehouse_id = null;
    public string $stock_action = 'in'; // in | out
    public float $quantity = 1;
    public float $unit_cost = 0;
    public string $reference_no = '';
    public string $notes = '';

    // Stock Adjustment form
    public string $adj_type = 'increase';
    public string $adj_reason = 'Physical Audit Count';
    public string $adj_date = '';

    // Stock Transfer form
    public ?int $from_warehouse_id = null;
    public ?int $to_warehouse_id = null;
    public string $transfer_date = '';

    // Filters
    public string $search = '';
    public string $movementTypeFilter = '';

    public function mount(): void
    {
        $this->mode = request()->route()->defaults['mode'] ?? 'stock';
        $this->warehouse_id = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
        $this->from_warehouse_id = $this->warehouse_id;
        $this->to_warehouse_id = Warehouse::where('id', '!=', $this->warehouse_id)->value('id') ?? $this->warehouse_id;
        $this->adj_date = now()->toDateString();
        $this->transfer_date = now()->toDateString();
    }

    public function updatedProductId($val): void
    {
        $p = Product::find($val);
        if ($p) {
            $this->unit_cost = (float) $p->cost_price;
        }
    }

    public function recordStockInOut(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'create'), 403);
        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $prod = Product::findOrFail($this->product_id);
        $whId = $this->warehouse_id ?: Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');

        if ($this->stock_action === 'out') {
            $available = (float) $prod->stock_quantity;
            if ($available < $this->quantity) {
                $this->dispatch('toast', message: "Insufficient stock (Available: {$available}).", type: 'error');
                return;
            }
        }

        $delta = $this->stock_action === 'in' ? $this->quantity : -$this->quantity;
        $ref = $this->reference_no ?: strtoupper($this->stock_action).'-'.now()->format('YmdHis');

        InventoryService::adjustStock(
            productId: $this->product_id,
            warehouseId: $whId,
            quantityDelta: $delta,
            movementType: $this->stock_action,
            unitCost: $this->unit_cost,
            referenceNo: $ref,
            notes: $this->notes ?: 'Manual Stock '.strtoupper($this->stock_action)
        );

        log_activity('inventory', 'stock_'.$this->stock_action, "Recorded Stock {$this->stock_action} of {$this->quantity} units ({$ref})");
        $this->reset(['quantity', 'reference_no', 'notes']);
        $this->quantity = 1;
        $this->dispatch('toast', message: 'Stock movement recorded successfully.', type: 'success');
    }

    public function recordAdjustment(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'create'), 403);
        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'adj_reason' => ['required', 'string', 'max:190'],
        ]);

        $whId = $this->warehouse_id ?: Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');

        DB::transaction(function () use ($whId) {
            $adjNo = erp_next_number('adj_prefix', 'stock_adjustments', 'adjustment_no', 'ADJ-', 4);
            $adj = StockAdjustment::create([
                'adjustment_no' => $adjNo,
                'warehouse_id' => $whId,
                'product_id' => $this->product_id,
                'adjustment_type' => $this->adj_type,
                'quantity' => $this->quantity,
                'reason' => $this->adj_reason,
                'adjustment_date' => $this->adj_date ?: now()->toDateString(),
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            $delta = $this->adj_type === 'increase' ? $this->quantity : -$this->quantity;
            InventoryService::adjustStock(
                productId: $this->product_id,
                warehouseId: $whId,
                quantityDelta: $delta,
                movementType: 'adjustment',
                referenceType: StockAdjustment::class,
                referenceId: $adj->id,
                referenceNo: $adjNo,
                notes: $this->adj_reason
            );
            log_activity('inventory', 'stock_adjusted', "Stock adjustment {$adjNo}: {$this->adj_type} {$this->quantity}", $adj);
        });

        $this->reset(['notes']);
        $this->quantity = 1;
        $this->dispatch('toast', message: 'Stock adjustment recorded.', type: 'success');
    }

    public function recordTransfer(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'create'), 403);
        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $available = (float) ProductStock::where('product_id', $this->product_id)->where('warehouse_id', $this->from_warehouse_id)->value('quantity');
        if ($available < $this->quantity) {
            $this->dispatch('toast', message: "Insufficient stock in source warehouse (Available: {$available}).", type: 'error');
            return;
        }

        DB::transaction(function () {
            $trNo = erp_next_number('tr_prefix', 'stock_transfers', 'transfer_no', 'TRF-', 4);
            $transfer = StockTransfer::create([
                'transfer_no' => $trNo,
                'from_warehouse_id' => $this->from_warehouse_id,
                'to_warehouse_id' => $this->to_warehouse_id,
                'product_id' => $this->product_id,
                'quantity' => $this->quantity,
                'transfer_date' => $this->transfer_date ?: now()->toDateString(),
                'status' => 'completed',
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            InventoryService::adjustStock($this->product_id, $this->from_warehouse_id, -$this->quantity, 'transfer_out', 0, StockTransfer::class, $transfer->id, $trNo, "Transferred out to WH #{$this->to_warehouse_id}");
            InventoryService::adjustStock($this->product_id, $this->to_warehouse_id, $this->quantity, 'transfer_in', 0, StockTransfer::class, $transfer->id, $trNo, "Transferred in from WH #{$this->from_warehouse_id}");

            log_activity('inventory', 'stock_transferred', "Stock transfer {$trNo} of {$this->quantity} units", $transfer);
        });

        $this->reset(['notes']);
        $this->quantity = 1;
        $this->dispatch('toast', message: 'Stock transferred between warehouses.', type: 'success');
    }

    public function exportInventoryReportCsv(): StreamedResponse
    {
        $rows = Product::with(['category', 'unit'])->where('is_service', false)->orderBy('name')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Product', 'Category', 'Qty On Hand', 'Reorder Level', 'Unit Cost', 'Stock Valuation (Cost)', 'Retail Value', 'Status']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->sku,
                    $p->name,
                    $p->category?->name,
                    $p->stock_quantity,
                    $p->reorder_level,
                    $p->cost_price,
                    round($p->stock_quantity * $p->cost_price, 2),
                    round($p->stock_quantity * $p->selling_price, 2),
                    $p->stock_status,
                ]);
            }
            fclose($out);
        }, 'inventory-valuation-report.csv');
    }

    public function render()
    {
        $products = Product::where('is_service', false)->orderBy('name')->get();
        $productBalances = Product::with(['category', 'unit'])
            ->where('is_service', false)
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(12);

        $movements = StockMovement::with(['product', 'creator'])
            ->when($this->movementTypeFilter, fn ($q) => $q->where('movement_type', $this->movementTypeFilter))
            ->when($this->search, fn ($q) => $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->latest('id')
            ->paginate(15);

        $lowStockProducts = Product::with(['category', 'defaultSupplier'])
            ->where('is_service', false)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('stock_quantity')
            ->get();

        return view('livewire.inventory.stock-operations', [
            'products' => $products,
            'productBalances' => $productBalances,
            'adjustments' => StockAdjustment::with(['product', 'creator'])->latest()->limit(30)->get(),
            'movements' => $movements,
            'lowStockProducts' => $lowStockProducts,
            'categoryBreakdown' => Category::whereNull('parent_id')->with('products')->get(),
            'totalCostValue' => (float) Product::where('is_service', false)->sum(DB::raw('stock_quantity * cost_price')),
            'totalRetailValue' => (float) Product::where('is_service', false)->sum(DB::raw('stock_quantity * selling_price')),
        ]);
    }
}
