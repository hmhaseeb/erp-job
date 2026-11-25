<?php

namespace App\Livewire\Purchase;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\Concerns\ChecksUniqueLineProducts;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Goods Received (GRN)')]
class GoodsReceiptManager extends Component
{
    use ChecksUniqueLineProducts, WithPagination;

    public string $search = '';
    public bool $showModal = false;

    public ?int $purchase_order_id = null;
    public ?int $supplier_id = null;
    public ?int $warehouse_id = null;
    public string $received_date = '';
    public string $notes = '';
    public array $items = [];

    public function mount(): void
    {
        $this->received_date = now()->toDateString();
        $this->warehouse_id = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->reset(['purchase_order_id', 'supplier_id', 'notes']);
        $this->received_date = now()->toDateString();
        $this->items = [
            ['purchase_order_item_id' => null, 'product_id' => '', 'quantity_received' => 1, 'unit_cost' => 0],
        ];
        $this->showModal = true;
    }

    public function updatedPurchaseOrderId($poId): void
    {
        $po = PurchaseOrder::with('items')->find($poId);
        if (! $po) {
            return;
        }
        $this->supplier_id = $po->supplier_id;
        $this->warehouse_id = $po->warehouse_id ?? $this->warehouse_id;
        $this->items = [];
        foreach ($po->items as $it) {
            if ($it->product_id) {
                $pending = max(0, (float) $it->quantity - (float) $it->received_quantity);
                $this->items[] = [
                    'purchase_order_item_id' => $it->id,
                    'product_id' => $it->product_id,
                    'quantity_received' => $pending,
                    'unit_cost' => (float) $it->unit_cost,
                ];
            }
        }
        if (empty($this->items)) {
            $this->addItem();
        }
    }

    public function updatedItems($value, $key): void
    {
        if (str_ends_with($key, '.product_id')) {
            $idx = (int) explode('.', $key)[0];
            if ($this->rejectDuplicateProductOnIndex($idx, $value)) {
                return;
            }
            $prod = Product::find($value);
            if ($prod) {
                $this->items[$idx]['unit_cost'] = (float) $prod->cost_price;
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = ['purchase_order_item_id' => null, 'product_id' => '', 'quantity_received' => 1, 'unit_cost' => 0];
    }

    public function removeItem(int $idx): void
    {
        unset($this->items[$idx]);
        $this->items = array_values($this->items);
    }

    public function saveGrn(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'received_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0.01'],
        ]);
        $this->ensureUniqueLineProducts();

        $whId = $this->warehouse_id ?: Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');

        $grn = DB::transaction(function () use ($whId) {
            $grnNo = erp_next_number(null, 'goods_receipts', 'grn_no', 'GRN-', 4);

            $grn = GoodsReceipt::create([
                'grn_no' => $grnNo,
                'purchase_order_id' => $this->purchase_order_id ?: null,
                'supplier_id' => $this->supplier_id,
                'warehouse_id' => $whId,
                'received_date' => $this->received_date,
                'status' => 'received',
                'notes' => $this->notes,
                'received_by' => auth()->id(),
            ]);

            foreach ($this->items as $it) {
                $qty = (float) $it['quantity_received'];
                $cost = (float) ($it['unit_cost'] ?? 0);

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $grn->id,
                    'product_id' => (int) $it['product_id'],
                    'purchase_order_item_id' => $it['purchase_order_item_id'] ?: null,
                    'quantity_received' => $qty,
                    'unit_cost' => $cost,
                ]);

                if (! empty($it['purchase_order_item_id'])) {
                    PurchaseOrderItem::find($it['purchase_order_item_id'])?->increment('received_quantity', $qty);
                }

                InventoryService::adjustStock(
                    productId: (int) $it['product_id'],
                    warehouseId: $whId,
                    quantityDelta: $qty,
                    movementType: 'purchase',
                    unitCost: $cost,
                    referenceType: GoodsReceipt::class,
                    referenceId: $grn->id,
                    referenceNo: $grnNo,
                    notes: "Goods received via {$grnNo}"
                );
            }

            if ($this->purchase_order_id) {
                $po = PurchaseOrder::with('items')->find($this->purchase_order_id);
                if ($po) {
                    $allReceived = $po->items->every(fn ($pi) => (float) $pi->received_quantity >= (float) $pi->quantity);
                    $po->update(['status' => $allReceived ? 'received' : 'partially_received']);
                }
            }

            return $grn;
        });

        $this->showModal = false;
        log_activity('purchase', 'grn_created', "Recorded Goods Receipt {$grn->grn_no}", $grn);
        $this->dispatch('toast', message: "Goods Receipt {$grn->grn_no} saved & inventory incremented.", type: 'success');
    }

    public function generateBill(int $grnId): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $grn = GoodsReceipt::with('items.product')->findOrFail($grnId);

        if (PurchaseInvoice::where('goods_receipt_id', $grn->id)->exists()) {
            $this->dispatch('toast', message: 'A purchase bill already exists for this GRN.', type: 'error');
            return;
        }

        $bill = DB::transaction(function () use ($grn) {
            $billNo = erp_next_number('purchase_invoice_prefix', 'purchase_invoices', 'bill_no', 'PINV-', 4);
            $taxRate = erp_setting('tax_enabled', true) ? (float) erp_setting('default_tax_rate', 5) : 0;
            $subtotal = 0;
            $taxTotal = 0;
            foreach ($grn->items as $gi) {
                $lineSub = (float) $gi->quantity_received * (float) $gi->unit_cost;
                $subtotal += $lineSub;
                $taxTotal += round($lineSub * ($taxRate / 100), 2);
            }
            $total = round($subtotal + $taxTotal, 2);

            $bill = PurchaseInvoice::create([
                'bill_no' => $billNo,
                'purchase_order_id' => $grn->purchase_order_id,
                'goods_receipt_id' => $grn->id,
                'supplier_id' => $grn->supplier_id,
                'warehouse_id' => $grn->warehouse_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'status' => 'unpaid',
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total_amount' => $total,
                'paid_amount' => 0,
                'balance_due' => $total,
                'notes' => "Generated from GRN {$grn->grn_no}",
                'created_by' => auth()->id(),
            ]);

            foreach ($grn->items as $gi) {
                $lineSub = (float) $gi->quantity_received * (float) $gi->unit_cost;
                $lineTax = round($lineSub * ($taxRate / 100), 2);
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $bill->id,
                    'product_id' => $gi->product_id,
                    'description' => $gi->product?->name ?? 'Received Item',
                    'quantity' => $gi->quantity_received,
                    'unit_cost' => $gi->unit_cost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'line_total' => round($lineSub + $lineTax, 2),
                ]);
            }

            return $bill;
        });

        log_activity('purchase', 'bill_created', "Created bill {$bill->bill_no} from GRN {$grn->grn_no}", $bill);
        $this->dispatch('toast', message: "Purchase Bill {$bill->bill_no} created from {$grn->grn_no}.", type: 'success');
    }

    public function render()
    {
        $receipts = GoodsReceipt::with(['supplier', 'warehouse', 'purchaseOrder', 'items'])
            ->when($this->search, fn ($q) => $q->where('grn_no', 'like', "%{$this->search}%")->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.purchase.goods-receipt-manager', [
            'receipts' => $receipts,
            'openPos' => PurchaseOrder::with('supplier')->whereIn('status', ['ordered', 'partially_received'])->latest()->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
        ]);
    }
}
