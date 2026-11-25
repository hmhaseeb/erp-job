<?php

namespace App\Livewire\Purchase;

use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
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
#[Title('Purchase Operations & Reports')]
class PurchaseOperations extends Component
{
    use WithPagination;

    public string $mode = 'payments'; // payments | balances | returns | reports

    // Supplier Payment Form
    public ?int $supplier_id = null;
    public ?int $purchase_invoice_id = null;
    public float $amount = 0;
    public string $date = '';
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $reference = '';

    // Purchase Return Form
    public ?int $return_product_id = null;
    public ?int $return_warehouse_id = null;
    public float $return_qty = 1;
    public float $return_cost = 0;
    public string $return_reason = 'Damaged / Vendor Return';

    // Reports Filter
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

    public function updatedPurchaseInvoiceId($val): void
    {
        $bill = PurchaseInvoice::find($val);
        if ($bill) {
            $this->supplier_id = $bill->supplier_id;
            $this->amount = (float) $bill->balance_due;
        }
    }

    public function updatedReturnProductId($val): void
    {
        $prod = Product::find($val);
        if ($prod) {
            $this->return_cost = (float) $prod->cost_price;
        }
    }

    public function recordPayment(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
        ]);

        DB::transaction(function () {
            $supplier = Supplier::findOrFail($this->supplier_id);
            $payNo = erp_next_number('payment_prefix', 'supplier_payments', 'payment_no', 'PAY-', 4);

            $payment = SupplierPayment::create([
                'payment_no' => $payNo,
                'supplier_id' => $supplier->id,
                'purchase_invoice_id' => $this->purchase_invoice_id ?: null,
                'payment_date' => $this->date,
                'amount' => $this->amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->reference,
                'created_by' => auth()->id(),
            ]);

            if ($this->purchase_invoice_id) {
                PurchaseInvoice::find($this->purchase_invoice_id)?->recalculatePaymentStatus();
            }

            FinanceService::recordTransaction(
                date: $this->date,
                type: 'outflow',
                amount: $this->amount,
                category: 'Supplier Payment',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Supplier::class,
                partyId: $supplier->id,
                partyName: $supplier->name,
                referenceType: SupplierPayment::class,
                referenceId: $payment->id,
                referenceNo: $payNo,
                description: "Payment to vendor {$supplier->name}"
            );

            log_activity('purchase', 'supplier_payment', "Paid {$supplier->name} (".format_currency($this->amount).")", $payment);
        });

        $this->reset(['purchase_invoice_id', 'amount', 'reference']);
        $this->dispatch('toast', message: 'Supplier payment recorded & posted to Finance.', type: 'success');
    }

    public function recordPurchaseReturn(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'return_product_id' => ['required', 'exists:products,id'],
            'return_qty' => ['required', 'numeric', 'min:0.01'],
            'return_cost' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () {
            $prod = Product::findOrFail($this->return_product_id);
            $supplier = Supplier::findOrFail($this->supplier_id);
            $retNo = erp_next_number('purchase_return_prefix', 'purchase_returns', 'return_no', 'PR-', 4);
            $lineTotal = round($this->return_qty * $this->return_cost, 2);

            $pr = PurchaseReturn::create([
                'return_no' => $retNo,
                'purchase_invoice_id' => $this->purchase_invoice_id ?: null,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $this->return_warehouse_id,
                'return_date' => $this->date ?: now()->toDateString(),
                'reason' => $this->return_reason,
                'status' => 'completed',
                'subtotal' => $lineTotal,
                'tax_amount' => 0,
                'total_amount' => $lineTotal,
                'created_by' => auth()->id(),
            ]);

            PurchaseReturnItem::create([
                'purchase_return_id' => $pr->id,
                'product_id' => $prod->id,
                'description' => $prod->name,
                'quantity' => $this->return_qty,
                'unit_cost' => $this->return_cost,
                'line_total' => $lineTotal,
            ]);

            if (! $prod->is_service) {
                InventoryService::adjustStock($prod->id, $this->return_warehouse_id, -$this->return_qty, 'purchase_return', $this->return_cost, PurchaseReturn::class, $pr->id, $retNo, "Purchase return {$retNo}");
            }

            if ($this->purchase_invoice_id) {
                PurchaseInvoice::find($this->purchase_invoice_id)?->recalculatePaymentStatus();
            }

            log_activity('purchase', 'purchase_return', "Recorded Purchase Return {$retNo} to {$supplier->name}", $pr);
        });

        $this->reset(['return_qty', 'return_cost']);
        $this->return_qty = 1;
        $this->dispatch('toast', message: 'Purchase return recorded & inventory adjusted.', type: 'success');
    }

    public function exportPurchaseReportCsv(): StreamedResponse
    {
        $bills = PurchaseInvoice::with('supplier')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->get();

        return response()->streamDownload(function () use ($bills) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Bill #', 'Vendor Inv #', 'Date', 'Vendor', 'Subtotal', 'Tax', 'Total', 'Paid', 'Balance Due', 'Status']);
            foreach ($bills as $b) {
                fputcsv($out, [
                    $b->bill_no,
                    $b->vendor_invoice_no,
                    $b->invoice_date?->toDateString(),
                    $b->supplier?->name,
                    $b->subtotal,
                    $b->tax_amount,
                    $b->total_amount,
                    $b->paid_amount,
                    $b->balance_due,
                    $b->status,
                ]);
            }
            fclose($out);
        }, 'purchase-report.csv');
    }

    public function render()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $openBills = PurchaseInvoice::with('supplier')->where('balance_due', '>', 0)->where('status', '!=', 'cancelled')->latest()->get();

        $reportBills = PurchaseInvoice::with('supplier')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->latest('invoice_date')
            ->get();

        return view('livewire.purchase.purchase-operations', [
            'suppliers' => $suppliers,
            'openBills' => $openBills,
            'payments' => SupplierPayment::with(['supplier', 'purchaseInvoice', 'paymentMethod'])->latest('id')->paginate(12),
            'returns' => PurchaseReturn::with(['supplier', 'purchaseInvoice', 'items'])->latest('id')->get(),
            'products' => Product::orderBy('name')->get(),
            'warehouses' => Warehouse::orderByDesc('is_default')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
            'reportBills' => $reportBills,
        ]);
    }
}
