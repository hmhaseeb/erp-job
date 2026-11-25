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
#[Title('Purchase Orders')]
class PurchaseOrderManager extends Component
{
    use ChecksUniqueLineProducts, WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    // Create PO Modal
    public bool $showModal = false;
    public ?int $supplier_id = null;
    public ?int $warehouse_id = null;
    public string $order_date = '';
    public ?string $expected_date = null;
    public string $status = 'ordered';
    public float $discount_amount = 0;
    public float $shipping_charge = 0;
    public string $notes = '';
    public array $items = [];

    // Receive Goods (GRN) Modal
    public bool $showGrnModal = false;
    public ?int $grn_po_id = null;
    public string $grn_received_date = '';
    public string $grn_notes = '';
    public array $grnItems = [];

    public function mount(): void
    {
        $this->order_date = now()->toDateString();
        $this->expected_date = now()->addDays(7)->toDateString();
        $this->grn_received_date = now()->toDateString();
        $this->warehouse_id = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->reset(['supplier_id', 'notes']);
        $this->discount_amount = 0;
        $this->shipping_charge = 0;
        $this->order_date = now()->toDateString();
        $this->expected_date = now()->addDays(7)->toDateString();
        $this->status = 'ordered';
        $this->items = [
            ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)],
        ];
        $this->showModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key): void
    {
        if (str_ends_with($key, '.product_id')) {
            $idx = (int) explode('.', $key)[0];
            if ($this->rejectDuplicateProductOnIndex($idx, $value)) {
                return;
            }
            $prod = Product::with('taxRate')->find($value);
            if ($prod) {
                $this->items[$idx]['description'] = $prod->name;
                $this->items[$idx]['unit_cost'] = (float) $prod->cost_price;
                $this->items[$idx]['tax_rate'] = (float) ($prod->taxRate?->rate ?? erp_setting('default_tax_rate', 5));
            }
        }
    }

    public function getTotalsProperty(): array
    {
        $subtotal = 0;
        $tax = 0;
        foreach ($this->items as $it) {
            $lineSub = (float) ($it['quantity'] ?? 0) * (float) ($it['unit_cost'] ?? 0);
            $lineTax = erp_setting('tax_enabled', true) ? round($lineSub * ((float) ($it['tax_rate'] ?? 0) / 100), 2) : 0;
            $subtotal += $lineSub;
            $tax += $lineTax;
        }
        $total = max(0, round($subtotal + $tax - $this->discount_amount + $this->shipping_charge, 2));

        return ['subtotal' => round($subtotal, 2), 'tax' => round($tax, 2), 'total' => $total];
    }

    public function saveOrder(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);
        $this->ensureUniqueLineProducts();

        $po = DB::transaction(function () {
            $totals = $this->totals;
            $poNo = erp_next_number('purchase_order_prefix', 'purchase_orders', 'po_no', 'PO-', 4);

            $order = PurchaseOrder::create([
                'po_no' => $poNo,
                'supplier_id' => $this->supplier_id,
                'warehouse_id' => $this->warehouse_id,
                'order_date' => $this->order_date,
                'expected_date' => $this->expected_date ?: null,
                'status' => $this->status,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax'],
                'discount_amount' => $this->discount_amount,
                'shipping_charge' => $this->shipping_charge,
                'total_amount' => $totals['total'],
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->items as $it) {
                $qty = (float) $it['quantity'];
                $cost = (float) $it['unit_cost'];
                $rate = erp_setting('tax_enabled', true) ? (float) ($it['tax_rate'] ?? 0) : 0;
                $lineSub = $qty * $cost;
                $lineTax = round($lineSub * ($rate / 100), 2);
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => ! empty($it['product_id']) ? (int) $it['product_id'] : null,
                    'description' => $it['description'],
                    'quantity' => $qty,
                    'received_quantity' => 0,
                    'unit_cost' => $cost,
                    'tax_rate' => $rate,
                    'tax_amount' => $lineTax,
                    'line_total' => round($lineSub + $lineTax, 2),
                ]);
            }

            return $order;
        });

        $this->showModal = false;
        log_activity('purchase', 'po_created', "Created Purchase Order {$po->po_no}", $po);
        $this->dispatch('toast', message: "Purchase Order {$po->po_no} created.", type: 'success');
    }

    public function openReceiveGrn(int $poId): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $po = PurchaseOrder::with('items')->findOrFail($poId);
        $this->grn_po_id = $po->id;
        $this->grn_received_date = now()->toDateString();
        $this->grn_notes = "Received against {$po->po_no}";
        $this->grnItems = [];
        foreach ($po->items as $item) {
            if ($item->product_id) {
                $pending = max(0, (float) $item->quantity - (float) $item->received_quantity);
                $this->grnItems[] = [
                    'po_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'ordered_qty' => (float) $item->quantity,
                    'already_received' => (float) $item->received_quantity,
                    'qty_to_receive' => $pending,
                    'unit_cost' => (float) $item->unit_cost,
                ];
            }
        }
        $this->showGrnModal = true;
    }

    public function receiveGoods(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $po = PurchaseOrder::with('items')->findOrFail($this->grn_po_id);

        $receivingAny = collect($this->grnItems)->sum(fn ($i) => (float) ($i['qty_to_receive'] ?? 0)) > 0;
        if (! $receivingAny) {
            $this->dispatch('toast', message: 'Enter at least one quantity greater than zero to receive.', type: 'error');
            return;
        }

        $grn = DB::transaction(function () use ($po) {
            $grnNo = erp_next_number(null, 'goods_receipts', 'grn_no', 'GRN-', 4);
            $whId = $po->warehouse_id ?? Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');

            $grn = GoodsReceipt::create([
                'grn_no' => $grnNo,
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'warehouse_id' => $whId,
                'received_date' => $this->grn_received_date ?: now()->toDateString(),
                'status' => 'received',
                'notes' => $this->grn_notes,
                'received_by' => auth()->id(),
            ]);

            foreach ($this->grnItems as $gi) {
                $qty = (float) ($gi['qty_to_receive'] ?? 0);
                if ($qty <= 0 || empty($gi['product_id'])) {
                    continue;
                }

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $grn->id,
                    'product_id' => (int) $gi['product_id'],
                    'purchase_order_item_id' => $gi['po_item_id'],
                    'quantity_received' => $qty,
                    'unit_cost' => (float) $gi['unit_cost'],
                ]);

                if (! empty($gi['po_item_id'])) {
                    $poItem = PurchaseOrderItem::find($gi['po_item_id']);
                    if ($poItem) {
                        $poItem->increment('received_quantity', $qty);
                    }
                }

                InventoryService::adjustStock(
                    productId: (int) $gi['product_id'],
                    warehouseId: $whId,
                    quantityDelta: $qty,
                    movementType: 'purchase',
                    unitCost: (float) $gi['unit_cost'],
                    referenceType: GoodsReceipt::class,
                    referenceId: $grn->id,
                    referenceNo: $grnNo,
                    notes: "Received via {$grnNo} ({$po->po_no})"
                );
            }

            $po->refresh();
            $allReceived = $po->items->every(fn ($item) => (float) $item->received_quantity >= (float) $item->quantity);
            $po->update(['status' => $allReceived ? 'received' : 'partially_received']);

            return $grn;
        });

        $this->showGrnModal = false;
        log_activity('purchase', 'grn_created', "Received goods {$grn->grn_no} against {$po->po_no}", $grn);
        $this->dispatch('toast', message: "Goods Receipt {$grn->grn_no} saved & stock added.", type: 'success');
    }

    public function createBillFromPo(int $poId): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $po = PurchaseOrder::with('items')->findOrFail($poId);

        if ($po->purchaseInvoices()->exists()) {
            $this->dispatch('toast', message: 'This Purchase Order already has a Purchase Bill.', type: 'error');
            return;
        }

        $bill = DB::transaction(function () use ($po) {
            $billNo = erp_next_number('purchase_invoice_prefix', 'purchase_invoices', 'bill_no', 'PINV-', 4);
            $latestGrnId = $po->goodsReceipts()->latest('id')->value('id');

            $bill = PurchaseInvoice::create([
                'bill_no' => $billNo,
                'purchase_order_id' => $po->id,
                'goods_receipt_id' => $latestGrnId,
                'supplier_id' => $po->supplier_id,
                'warehouse_id' => $po->warehouse_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'status' => 'unpaid',
                'subtotal' => $po->subtotal,
                'tax_amount' => $po->tax_amount,
                'discount_amount' => $po->discount_amount,
                'shipping_charge' => $po->shipping_charge,
                'total_amount' => $po->total_amount,
                'paid_amount' => 0,
                'balance_due' => $po->total_amount,
                'notes' => "Generated from {$po->po_no}",
                'created_by' => auth()->id(),
            ]);

            foreach ($po->items as $item) {
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $bill->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'discount' => $item->discount,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'line_total' => $item->line_total,
                ]);
            }

            return $bill;
        });

        log_activity('purchase', 'bill_created', "Converted {$po->po_no} to Bill {$bill->bill_no}", $bill);
        $this->dispatch('toast', message: "Generated Purchase Bill {$bill->bill_no}.", type: 'success');
    }

    public function render()
    {
        $orders = PurchaseOrder::with(['supplier', 'warehouse', 'purchaseInvoices', 'goodsReceipts'])
            ->when($this->search, fn ($q) => $q->where('po_no', 'like', "%{$this->search}%")->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.purchase.purchase-order-manager', [
            'orders' => $orders,
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
        ]);
    }
}
