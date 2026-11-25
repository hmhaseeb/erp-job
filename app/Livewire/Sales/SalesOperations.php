<?php

namespace App\Livewire\Sales;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use App\Services\FinanceService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Sales Operations & Reports')]
class SalesOperations extends Component
{
    use WithPagination;

    public string $mode = 'payments'; // payments | balances | returns | reports

    // Receipt / Payment Collection
    public ?int $customer_id = null;
    public ?int $sales_invoice_id = null;
    public float $amount = 0;
    public string $date = '';
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $reference = '';

    // Sales Return
    public ?int $return_product_id = null;
    public ?int $return_warehouse_id = null;
    public float $return_qty = 1;
    public float $return_price = 0;
    public string $return_reason = 'Defective / Customer Return';
    public string $refund_method = 'credit';

    // Report Filters
    public string $fromDate = '';
    public string $toDate = '';

    public function mount(): void
    {
        $this->mode = request()->route()->defaults['mode'] ?? 'payments';
        $this->date = now()->toDateString();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->payment_method_id = PaymentMethod::where('is_active', true)->value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id');
        $this->return_warehouse_id = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
    }

    public function updatedSalesInvoiceId($val): void
    {
        $inv = SalesInvoice::find($val);
        if ($inv) {
            $this->customer_id = $inv->customer_id;
            $this->amount = (float) $inv->balance_due;
        }
    }

    public function updatedReturnProductId($val): void
    {
        $p = Product::find($val);
        if ($p) {
            $this->return_price = (float) $p->selling_price;
        }
    }

    public function collectPayment(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
        ]);

        DB::transaction(function () {
            $cust = Customer::findOrFail($this->customer_id);
            $rcpNo = erp_next_number('receipt_prefix', 'customer_payments', 'receipt_no', 'RCP-', 4);

            $payment = CustomerPayment::create([
                'receipt_no' => $rcpNo,
                'customer_id' => $this->customer_id,
                'sales_invoice_id' => $this->sales_invoice_id ?: null,
                'payment_date' => $this->date,
                'amount' => $this->amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->reference,
                'created_by' => auth()->id(),
            ]);

            if ($this->sales_invoice_id) {
                SalesInvoice::find($this->sales_invoice_id)?->recalculatePaymentStatus();
            }

            FinanceService::recordTransaction(
                date: $this->date,
                type: 'inflow',
                amount: $this->amount,
                category: 'Customer Payment Collection',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Customer::class,
                partyId: $cust->id,
                partyName: $cust->name,
                referenceType: CustomerPayment::class,
                referenceId: $payment->id,
                referenceNo: $rcpNo,
                description: "Customer collection from {$cust->name}"
            );

            log_activity('sales', 'payment_collected', "Recorded customer receipt {$rcpNo} (".format_currency($this->amount).")", $payment);
        });

        $this->reset(['sales_invoice_id', 'amount', 'reference']);
        $this->dispatch('toast', message: 'Customer payment recorded & posted to Finance.', type: 'success');
    }

    public function recordSalesReturn(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'return_product_id' => ['required', 'exists:products,id'],
            'return_qty' => ['required', 'numeric', 'min:0.01'],
            'return_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () {
            $prod = Product::findOrFail($this->return_product_id);
            $cust = Customer::findOrFail($this->customer_id);
            $retNo = erp_next_number('sales_return_prefix', 'sales_returns', 'return_no', 'SR-', 4);
            $lineTotal = round($this->return_qty * $this->return_price, 2);

            $sr = SalesReturn::create([
                'return_no' => $retNo,
                'sales_invoice_id' => $this->sales_invoice_id ?: null,
                'customer_id' => $cust->id,
                'warehouse_id' => $this->return_warehouse_id,
                'return_date' => $this->date ?: now()->toDateString(),
                'reason' => $this->return_reason,
                'status' => 'completed',
                'subtotal' => $lineTotal,
                'tax_amount' => 0,
                'total_amount' => $lineTotal,
                'refund_method' => $this->refund_method,
                'bank_account_id' => $this->refund_method === 'bank' ? $this->bank_account_id : null,
                'created_by' => auth()->id(),
            ]);

            SalesReturnItem::create([
                'sales_return_id' => $sr->id,
                'product_id' => $prod->id,
                'description' => $prod->name,
                'quantity' => $this->return_qty,
                'unit_price' => $this->return_price,
                'line_total' => $lineTotal,
            ]);

            if (! $prod->is_service) {
                InventoryService::adjustStock($prod->id, $this->return_warehouse_id, $this->return_qty, 'sales_return', 0, SalesReturn::class, $sr->id, $retNo, "Sales return {$retNo}");
            }

            if ($this->sales_invoice_id) {
                SalesInvoice::find($this->sales_invoice_id)?->recalculatePaymentStatus();
            }

            if (in_array($this->refund_method, ['cash', 'bank'])) {
                FinanceService::recordTransaction(
                    date: $this->date ?: now()->toDateString(),
                    type: 'outflow',
                    amount: $lineTotal,
                    category: 'Sales Return Refund',
                    paymentMethodId: $this->refund_method === 'cash' ? PaymentMethod::where('type', 'cash')->value('id') : $this->payment_method_id,
                    bankAccountId: $this->refund_method === 'bank' ? $this->bank_account_id : null,
                    partyType: Customer::class,
                    partyId: $cust->id,
                    partyName: $cust->name,
                    referenceType: SalesReturn::class,
                    referenceId: $sr->id,
                    referenceNo: $retNo,
                    description: "Refund for sales return {$retNo}"
                );
            }

            log_activity('sales', 'sales_return', "Recorded Sales Return {$retNo} for {$cust->name}", $sr);
        });

        $this->reset(['return_qty', 'return_price']);
        $this->return_qty = 1;
        $this->dispatch('toast', message: 'Sales return recorded & inventory restored.', type: 'success');
    }

    public function exportSalesReportCsv(): StreamedResponse
    {
        $invoices = SalesInvoice::with('customer')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->get();

        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice #', 'Date', 'Customer', 'Source', 'Subtotal', 'Tax', 'Total', 'Paid', 'Balance Due', 'Status']);
            foreach ($invoices as $inv) {
                fputcsv($out, [
                    $inv->invoice_no,
                    $inv->invoice_date?->toDateString(),
                    $inv->customer?->name,
                    $inv->source,
                    $inv->subtotal,
                    $inv->tax_amount,
                    $inv->total_amount,
                    $inv->paid_amount,
                    $inv->balance_due,
                    $inv->status,
                ]);
            }
            fclose($out);
        }, 'sales-report.csv');
    }

    public function render()
    {
        $customers = Customer::orderBy('name')->get();
        $openInvoices = SalesInvoice::with('customer')->where('balance_due', '>', 0)->where('status', '!=', 'cancelled')->latest()->get();

        $reportInvoices = SalesInvoice::with('customer')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->latest('invoice_date')
            ->get();

        $topProducts = SalesInvoiceItem::select('description', DB::raw('SUM(quantity) as qty_sold'), DB::raw('SUM(line_total) as total_sales'))
            ->groupBy('description')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        return view('livewire.sales.sales-operations', [
            'customers' => $customers,
            'openInvoices' => $openInvoices,
            'payments' => CustomerPayment::with(['customer', 'salesInvoice', 'paymentMethod'])->latest('id')->paginate(12),
            'returns' => SalesReturn::with(['customer', 'salesInvoice', 'items'])->latest('id')->get(),
            'products' => Product::orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
            'reportInvoices' => $reportInvoices,
            'topProducts' => $topProducts,
        ]);
    }
}
