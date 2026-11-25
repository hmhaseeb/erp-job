<?php

namespace App\Livewire\Purchase;

use App\Models\BankAccount;
use App\Models\GoodsReceipt;
use App\Models\PaymentMethod;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\FinanceService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Vendor Profile')]
class VendorProfile extends Component
{
    public Supplier $supplier;
    public string $activeTab = 'bills'; // bills | history | payments | products

    // Supplier Payment Modal
    public bool $showPayModal = false;
    public ?int $pay_bill_id = null;
    public float $pay_amount = 0;
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $pay_date = '';
    public string $pay_reference = '';
    public string $pay_notes = '';

    public function mount(int $id): void
    {
        $this->loadSupplier($id);
        $this->pay_date = now()->toDateString();
        $this->payment_method_id = PaymentMethod::where('is_active', true)->value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id');
    }

    private function loadSupplier(?int $id = null): void
    {
        $sid = $id ?? $this->supplier->id;
        $this->supplier = Supplier::with([
            'purchaseInvoices.purchaseOrder',
            'purchaseInvoices.goodsReceipt',
            'purchaseOrders.warehouse',
            'payments.purchaseInvoice',
            'payments.paymentMethod',
            'payments.bankAccount',
            'returns.items',
            'products.category',
        ])->findOrFail($sid);
    }

    public function openPayModal(?int $billId = null): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $this->pay_bill_id = $billId;
        if ($billId) {
            $bill = PurchaseInvoice::where('supplier_id', $this->supplier->id)->findOrFail($billId);
            $this->pay_amount = (float) $bill->balance_due;
        } else {
            $this->pay_amount = max(0, $this->supplier->outstanding_balance);
        }
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->pay_notes = '';
        $this->showPayModal = true;
    }

    public function updatedPayBillId($val): void
    {
        if ($val) {
            $bill = PurchaseInvoice::where('supplier_id', $this->supplier->id)->find($val);
            if ($bill) {
                $this->pay_amount = (float) $bill->balance_due;
            }
        }
    }

    public function makePayment(): void
    {
        abort_unless(auth()->user()->hasPermission('purchase', 'create'), 403);
        $rules = [
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'pay_date' => ['required', 'date'],
        ];

        $bill = null;
        if ($this->pay_bill_id) {
            $bill = PurchaseInvoice::where('supplier_id', $this->supplier->id)->findOrFail($this->pay_bill_id);
            $rules['pay_amount'][] = 'max:'.max(0.01, (float) $bill->balance_due);
        }

        $this->validate($rules);

        DB::transaction(function () use ($bill) {
            $payNo = erp_next_number('payment_prefix', 'supplier_payments', 'payment_no', 'PAY-', 4);
            $payment = SupplierPayment::create([
                'payment_no' => $payNo,
                'supplier_id' => $this->supplier->id,
                'purchase_invoice_id' => $bill?->id,
                'payment_date' => $this->pay_date,
                'amount' => $this->pay_amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->pay_reference,
                'notes' => $this->pay_notes,
                'created_by' => auth()->id(),
            ]);

            if ($bill) {
                $bill->recalculatePaymentStatus();
            }

            FinanceService::recordTransaction(
                date: $this->pay_date,
                type: 'outflow',
                amount: $this->pay_amount,
                category: 'Supplier Payment',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Supplier::class,
                partyId: $this->supplier->id,
                partyName: $this->supplier->name,
                referenceType: $bill ? PurchaseInvoice::class : SupplierPayment::class,
                referenceId: $bill?->id ?? $payment->id,
                referenceNo: $payNo,
                description: $bill
                    ? "Payment made to {$this->supplier->name} for {$bill->bill_no}"
                    : "Vendor payment to {$this->supplier->name}"
            );

            log_activity('purchase', 'supplier_payment', "Paid ".format_currency($this->pay_amount)." to vendor {$this->supplier->name}", $payment);
        });

        $this->showPayModal = false;
        $this->loadSupplier();
        $this->dispatch('toast', message: 'Vendor payment recorded & posted to Cash/Bank Book.', type: 'success');
    }

    public function render()
    {
        $bills = $this->supplier->purchaseInvoices->sortByDesc('id');
        $openBills = $bills->where('balance_due', '>', 0)->where('status', '!=', 'cancelled');
        $grns = GoodsReceipt::with(['warehouse', 'purchaseOrder', 'items'])
            ->where('supplier_id', $this->supplier->id)
            ->latest('id')
            ->get();

        return view('livewire.purchase.vendor-profile', [
            'bills' => $bills,
            'openBills' => $openBills,
            'orders' => $this->supplier->purchaseOrders->sortByDesc('id'),
            'grns' => $grns,
            'returns' => $this->supplier->returns->sortByDesc('id'),
            'payments' => $this->supplier->payments->sortByDesc('id'),
            'products' => $this->supplier->products,
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
            'totalBilled' => (float) $bills->where('status', '!=', 'cancelled')->sum('total_amount'),
            'totalPaid' => (float) $this->supplier->payments->sum('amount'),
            'totalReturned' => (float) $this->supplier->returns->sum('total_amount'),
        ]);
    }
}
