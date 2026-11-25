<?php

namespace App\Livewire\Sales;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
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
#[Title('Sales Invoices / Bills')]
class SalesInvoiceManager extends Component
{
    use ChecksUniqueLineProducts, WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    // Create Invoice Modal
    public bool $showCreateModal = false;
    public ?int $customer_id = null;
    public ?int $warehouse_id = null;
    public string $invoice_date = '';
    public ?string $due_date = null;
    public float $discount_amount = 0;
    public float $shipping_charge = 0;
    public string $notes = '';
    public array $items = [];

    // Collect Payment Modal
    public bool $showPayModal = false;
    public ?int $pay_invoice_id = null;
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

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->reset(['customer_id', 'notes']);
        $this->discount_amount = 0;
        $this->shipping_charge = 0;
        $this->items = [
            ['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0, 'tax_rate' => (float) erp_setting('default_tax_rate', 5)],
        ];
        $this->showCreateModal = true;
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

    public function saveInvoice(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ]);
        $this->ensureUniqueLineProducts();

        $inv = DB::transaction(function () {
            $totals = $this->totals;
            $invNo = erp_next_number('sales_invoice_prefix', 'sales_invoices', 'invoice_no', 'INV-', 4);

            $inv = SalesInvoice::create([
                'invoice_no' => $invNo,
                'source' => 'sales',
                'customer_id' => $this->customer_id,
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
                'terms' => erp_setting('invoice_terms'),
                'created_by' => auth()->id(),
            ]);

            foreach ($this->items as $it) {
                $qty = (float) $it['quantity'];
                $price = (float) $it['unit_price'];
                $rate = erp_setting('tax_enabled', true) ? (float) ($it['tax_rate'] ?? 0) : 0;
                $lineSub = $qty * $price;
                $lineTax = round($lineSub * ($rate / 100), 2);
                $prodId = ! empty($it['product_id']) ? (int) $it['product_id'] : null;

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $inv->id,
                    'product_id' => $prodId,
                    'description' => $it['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $rate,
                    'tax_amount' => $lineTax,
                    'line_total' => round($lineSub + $lineTax, 2),
                ]);

                if ($prodId) {
                    $prod = Product::find($prodId);
                    if ($prod && ! $prod->is_service) {
                        InventoryService::adjustStock($prodId, $this->warehouse_id, -$qty, 'sale', 0, SalesInvoice::class, $inv->id, $invNo, "Sold via {$invNo}");
                    }
                }
            }

            return $inv;
        });

        $this->showCreateModal = false;
        log_activity('sales', 'invoice_created', "Created Sales Invoice {$inv->invoice_no} for ".format_currency($inv->total_amount), $inv);
        $this->dispatch('toast', message: "Sales Invoice {$inv->invoice_no} generated.", type: 'success');
    }

    public function openCollectPayment(int $invoiceId): void
    {
        $inv = SalesInvoice::findOrFail($invoiceId);
        $this->pay_invoice_id = $inv->id;
        $this->pay_amount = (float) $inv->balance_due;
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->showPayModal = true;
    }

    public function collectPayment(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $inv = SalesInvoice::with('customer')->findOrFail($this->pay_invoice_id);
        $this->validate([
            'pay_amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $inv->balance_due)],
            'pay_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($inv) {
            $rcpNo = erp_next_number('receipt_prefix', 'customer_payments', 'receipt_no', 'RCP-', 4);
            $payment = CustomerPayment::create([
                'receipt_no' => $rcpNo,
                'customer_id' => $inv->customer_id,
                'sales_invoice_id' => $inv->id,
                'payment_date' => $this->pay_date,
                'amount' => $this->pay_amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->pay_reference,
                'created_by' => auth()->id(),
            ]);

            $inv->recalculatePaymentStatus();

            FinanceService::recordTransaction(
                date: $this->pay_date,
                type: 'inflow',
                amount: $this->pay_amount,
                category: $inv->source === 'job_card' ? 'Service Billing Receipt' : 'Sales Receipt',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Customer::class,
                partyId: $inv->customer_id,
                partyName: $inv->customer?->name,
                referenceType: SalesInvoice::class,
                referenceId: $inv->id,
                referenceNo: $rcpNo,
                description: "Payment collected for {$inv->invoice_no}"
            );
            log_activity('sales', 'payment_collected', "Collected ".format_currency($this->pay_amount)." against {$inv->invoice_no}", $payment);
        });

        $this->showPayModal = false;
        $this->dispatch('toast', message: 'Payment collected & posted to Cash/Bank Book.', type: 'success');
    }

    public function render()
    {
        $invoices = SalesInvoice::with(['customer', 'salesOrder', 'jobCard'])
            ->when($this->search, fn ($q) => $q->where('invoice_no', 'like', "%{$this->search}%")->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.sales.sales-invoice-manager', [
            'invoices' => $invoices,
            'customers' => Customer::orderBy('name')->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
        ]);
    }
}
