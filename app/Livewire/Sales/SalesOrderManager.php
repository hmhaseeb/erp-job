<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\Concerns\ChecksUniqueLineProducts;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Sales Orders')]
class SalesOrderManager extends Component
{
    use ChecksUniqueLineProducts, WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showModal = false;

    public ?int $customer_id = null;
    public ?int $warehouse_id = null;
    public string $order_date = '';
    public ?string $delivery_date = null;
    public string $status = 'confirmed';
    public float $discount_amount = 0;
    public float $shipping_charge = 0;
    public string $notes = '';
    public array $items = [];

    public function mount(): void
    {
        $this->order_date = now()->toDateString();
        $this->delivery_date = now()->addDays(5)->toDateString();
        $this->warehouse_id = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->reset(['customer_id', 'notes']);
        $this->discount_amount = 0;
        $this->shipping_charge = 0;
        $this->order_date = now()->toDateString();
        $this->delivery_date = now()->addDays(5)->toDateString();
        $this->status = 'confirmed';
        $this->items = [
            ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)],
        ];
        $this->showModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)];
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
                $this->items[$idx]['unit_price'] = (float) $prod->selling_price;
                $this->items[$idx]['tax_rate'] = (float) ($prod->taxRate?->rate ?? erp_setting('default_tax_rate', 5));
            }
        }
    }

    public function getTotalsProperty(): array
    {
        $subtotal = 0;
        $tax = 0;
        foreach ($this->items as $it) {
            $lineSub = (float) ($it['quantity'] ?? 0) * (float) ($it['unit_price'] ?? 0);
            $lineTax = erp_setting('tax_enabled', true) ? round($lineSub * ((float) ($it['tax_rate'] ?? 0) / 100), 2) : 0;
            $subtotal += $lineSub;
            $tax += $lineTax;
        }
        $total = max(0, round($subtotal + $tax - $this->discount_amount + $this->shipping_charge, 2));

        return ['subtotal' => round($subtotal, 2), 'tax' => round($tax, 2), 'total' => $total];
    }

    public function saveOrder(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'order_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);
        $this->ensureUniqueLineProducts();

        $order = DB::transaction(function () {
            $totals = $this->totals;
            $orderNo = erp_next_number('sales_order_prefix', 'sales_orders', 'order_no', 'SO-', 4);

            $so = SalesOrder::create([
                'order_no' => $orderNo,
                'customer_id' => $this->customer_id,
                'warehouse_id' => $this->warehouse_id,
                'order_date' => $this->order_date,
                'delivery_date' => $this->delivery_date ?: null,
                'status' => $this->status,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax'],
                'discount_amount' => $this->discount_amount,
                'shipping_charge' => $this->shipping_charge,
                'total_amount' => $totals['total'],
                'notes' => $this->notes,
                'terms' => erp_setting('invoice_terms'),
                'created_by' => auth()->id(),
            ]);

            foreach ($this->items as $it) {
                $qty = (float) $it['quantity'];
                $price = (float) $it['unit_price'];
                $rate = erp_setting('tax_enabled', true) ? (float) ($it['tax_rate'] ?? 0) : 0;
                $lineSub = $qty * $price;
                $lineTax = round($lineSub * ($rate / 100), 2);
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => ! empty($it['product_id']) ? (int) $it['product_id'] : null,
                    'description' => $it['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $rate,
                    'tax_amount' => $lineTax,
                    'line_total' => round($lineSub + $lineTax, 2),
                ]);
            }

            return $so;
        });

        $this->showModal = false;
        log_activity('sales', 'order_created', "Created Sales Order {$order->order_no}", $order);
        $this->dispatch('toast', message: "Sales Order {$order->order_no} created.", type: 'success');
    }

    public function convertToInvoice(int $orderId): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $so = SalesOrder::with('items')->findOrFail($orderId);

        if ($so->invoices()->exists()) {
            $this->dispatch('toast', message: 'This Sales Order already has an invoice.', type: 'error');
            return;
        }

        $inv = DB::transaction(function () use ($so) {
            $invNo = erp_next_number('sales_invoice_prefix', 'sales_invoices', 'invoice_no', 'INV-', 4);
            $inv = SalesInvoice::create([
                'invoice_no' => $invNo,
                'sales_order_id' => $so->id,
                'source' => 'sales',
                'customer_id' => $so->customer_id,
                'warehouse_id' => $so->warehouse_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'status' => 'unpaid',
                'subtotal' => $so->subtotal,
                'tax_amount' => $so->tax_amount,
                'discount_amount' => $so->discount_amount,
                'shipping_charge' => $so->shipping_charge,
                'total_amount' => $so->total_amount,
                'paid_amount' => 0,
                'balance_due' => $so->total_amount,
                'notes' => $so->notes,
                'terms' => $so->terms,
                'created_by' => auth()->id(),
            ]);

            foreach ($so->items as $item) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $inv->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'line_total' => $item->line_total,
                ]);

                if ($item->product_id && ! $item->product?->is_service) {
                    InventoryService::adjustStock(
                        productId: $item->product_id,
                        warehouseId: $so->warehouse_id,
                        quantityDelta: -(float) $item->quantity,
                        movementType: 'sale',
                        referenceType: SalesInvoice::class,
                        referenceId: $inv->id,
                        referenceNo: $invNo,
                        notes: "Sold via {$invNo}"
                    );
                }
            }

            $so->update(['status' => 'delivered']);

            return $inv;
        });

        log_activity('sales', 'order_invoiced', "Converted {$so->order_no} to Invoice {$inv->invoice_no}", $inv);
        $this->dispatch('toast', message: "Generated Invoice {$inv->invoice_no} and deducted stock.", type: 'success');
    }

    public function updateStatus(int $orderId, string $status): void
    {
        SalesOrder::findOrFail($orderId)->update(['status' => $status]);
        $this->dispatch('toast', message: 'Order status updated.', type: 'success');
    }

    public function render()
    {
        $orders = SalesOrder::with(['customer', 'warehouse', 'invoices'])
            ->when($this->search, fn ($q) => $q->where('order_no', 'like', "%{$this->search}%")->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.sales.sales-order-manager', [
            'orders' => $orders,
            'customers' => Customer::orderBy('name')->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
        ]);
    }
}
