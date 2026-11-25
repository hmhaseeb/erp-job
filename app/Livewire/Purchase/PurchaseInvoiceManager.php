<?php

namespace App\Livewire\Purchase;

use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Services\FinanceService;
use App\Services\InventoryService;
use App\Support\Concerns\ChecksUniqueLineProducts;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Purchase Invoices / Bills')]
class PurchaseInvoiceManager extends Component
{
    use ChecksUniqueLineProducts, WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    // Create Bill Modal
    public bool $showCreateModal = false;
    public ?int $supplier_id = null;
    public ?int $warehouse_id = null;
    public string $vendor_invoice_no = '';
    public string $invoice_date = '';
    public ?string $due_date = null;
    public float $discount_amount = 0;
    public float $shipping_charge = 0;
    public bool $receive_into_stock = true;
    public string $notes = '';
    public array $items = [];

    // Supplier Payment Modal
    public bool $showPayModal = false;
    public ?int $pay_bill_id = null;
    public float $pay_amount = 0;
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $pay_date = '';
    public string $pay_reference = '';

    public function mount(): void
    {
        $this->invoice_date = now()->toDateString();
        $this->due_date = now()->addDays(15)->toDateString();
        $this->pay_date = now()->toDateString();
        $this->warehouse_id = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
        $this->payment_method_id = PaymentMethod::where('is_active', true)->value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->reset(['supplier_id', 'vendor_invoice_no', 'notes']);
        $this->discount_amount = 0;
        $this->shipping_charge = 0;
        $this->receive_into_stock = true;
        $this->items = [
            ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)],
        ];
        $this->showCreateModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)];
    }

    public function removeItem(int $idx): void
    {
        unset($this->items[$idx]);
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

    public function saveBill(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);
        $this->ensureUniqueLineProducts();

        $bill = DB::transaction(function () {
            $totals = $this->totals;
            $billNo = erp_next_number('purchase_invoice_prefix', 'purchase_invoices', 'bill_no', 'PINV-', 4);

            $bill = PurchaseInvoice::create([
                'bill_no' => $billNo,
                'vendor_invoice_no' => $this->vendor_invoice_no ?: null,
                'supplier_id' => $this->supplier_id,
                'warehouse_id' => $this->warehouse_id,
                'invoice_date' => $this->invoice_date,
                'due_date' => $this->due_date ?: null,
                'status' => 'unpaid',
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax'],
                'discount_amount' => $this->discount_amount,
                'shipping_charge' => $this->shipping_charge,
                'total_amount' => $totals['total'],
                'paid_amount' => 0,
                'balance_due' => $totals['total'],
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($this->items as $it) {
                $qty = (float) $it['quantity'];
                $cost = (float) $it['unit_cost'];
                $rate = erp_setting('tax_enabled', true) ? (float) ($it['tax_rate'] ?? 0) : 0;
                $lineSub = $qty * $cost;
                $lineTax = round($lineSub * ($rate / 100), 2);
                $prodId = ! empty($it['product_id']) ? (int) $it['product_id'] : null;

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $bill->id,
                    'product_id' => $prodId,
                    'description' => $it['description'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'tax_rate' => $rate,
                    'tax_amount' => $lineTax,
                    'line_total' => round($lineSub + $lineTax, 2),
                ]);

                if ($this->receive_into_stock && $prodId) {
                    $prod = Product::find($prodId);
                    if ($prod && ! $prod->is_service) {
                        InventoryService::adjustStock($prodId, $this->warehouse_id, $qty, 'purchase', $cost, PurchaseInvoice::class, $bill->id, $billNo, "Purchased via {$billNo}");
                    }
                }
            }

            return $bill;
        });

        $this->showCreateModal = false;
        log_activity('purchase', 'bill_created', "Created Purchase Bill {$bill->bill_no} for ".format_currency($bill->total_amount), $bill);
        $this->dispatch('toast', message: "Purchase Bill {$bill->bill_no} saved.", type: 'success');
    }

    public function openPayModal(int $billId): void
    {
        $bill = PurchaseInvoice::findOrFail($billId);
        $this->pay_bill_id = $bill->id;
        $this->pay_amount = (float) $bill->balance_due;
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->showPayModal = true;
    }

    public function paySupplier(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $bill = PurchaseInvoice::with('supplier')->findOrFail($this->pay_bill_id);
        $this->validate([
            'pay_amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $bill->balance_due)],
            'pay_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($bill) {
            $payNo = erp_next_number('payment_prefix', 'supplier_payments', 'payment_no', 'PAY-', 4);
            $sp = SupplierPayment::create([
                'payment_no' => $payNo,
                'supplier_id' => $bill->supplier_id,
                'purchase_invoice_id' => $bill->id,
                'payment_date' => $this->pay_date,
                'amount' => $this->pay_amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->pay_reference,
                'created_by' => auth()->id(),
            ]);

            $bill->recalculatePaymentStatus();

            FinanceService::recordTransaction(
                date: $this->pay_date,
                type: 'outflow',
                amount: $this->pay_amount,
                category: 'Supplier Payment',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Supplier::class,
                partyId: $bill->supplier_id,
                partyName: $bill->supplier?->name,
                referenceType: PurchaseInvoice::class,
                referenceId: $bill->id,
                referenceNo: $payNo,
                description: "Payment made for {$bill->bill_no}"
            );

            log_activity('purchase', 'supplier_payment', "Paid ".format_currency($this->pay_amount)." against {$bill->bill_no}", $sp);
        });

        $this->showPayModal = false;
        $this->dispatch('toast', message: 'Supplier payment recorded & posted to Cash/Bank Book.', type: 'success');
    }

    public function render()
    {
        $bills = PurchaseInvoice::with(['supplier', 'purchaseOrder', 'goodsReceipt'])
            ->when($this->search, fn ($q) => $q->where('bill_no', 'like', "%{$this->search}%")->orWhere('vendor_invoice_no', 'like', "%{$this->search}%")->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.purchase.purchase-invoice-manager', [
            'bills' => $bills,
            'suppliers' => Supplier::orderBy('name')->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
        ]);
    }
}
