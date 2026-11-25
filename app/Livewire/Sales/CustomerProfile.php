<?php

namespace App\Livewire\Sales;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\PaymentMethod;
use App\Models\SalesInvoice;
use App\Services\FinanceService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Customer Profile')]
class CustomerProfile extends Component
{
    public Customer $customer;
    public string $activeTab = 'invoices'; // invoices | job_cards | payments | orders_returns

    // Payment Collection Modal
    public bool $showPayModal = false;
    public ?int $pay_invoice_id = null;
    public float $pay_amount = 0;
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $pay_date = '';
    public string $pay_reference = '';
    public string $pay_notes = '';

    public function mount(int $id): void
    {
        $this->loadCustomer($id);
        $this->pay_date = now()->toDateString();
        $this->payment_method_id = PaymentMethod::where('is_active', true)->value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id');
    }

    private function loadCustomer(?int $id = null): void
    {
        $cid = $id ?? $this->customer->id;
        $this->customer = Customer::with([
            'salesInvoices.salesOrder',
            'salesInvoices.jobCard',
            'salesOrders',
            'payments.salesInvoice',
            'payments.paymentMethod',
            'payments.bankAccount',
            'returns.items',
            'jobCards.technician',
            'jobCards.salesInvoice',
        ])->findOrFail($cid);
    }

    public function openCollectPayment(?int $invoiceId = null): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->pay_invoice_id = $invoiceId;
        if ($invoiceId) {
            $inv = SalesInvoice::where('customer_id', $this->customer->id)->findOrFail($invoiceId);
            $this->pay_amount = (float) $inv->balance_due;
        } else {
            $this->pay_amount = max(0, $this->customer->outstanding_balance);
        }
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->pay_notes = '';
        $this->showPayModal = true;
    }

    public function updatedPayInvoiceId($val): void
    {
        if ($val) {
            $inv = SalesInvoice::where('customer_id', $this->customer->id)->find($val);
            if ($inv) {
                $this->pay_amount = (float) $inv->balance_due;
            }
        }
    }

    public function collectPayment(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $rules = [
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'pay_date' => ['required', 'date'],
        ];

        $inv = null;
        if ($this->pay_invoice_id) {
            $inv = SalesInvoice::where('customer_id', $this->customer->id)->findOrFail($this->pay_invoice_id);
            $rules['pay_amount'][] = 'max:'.max(0.01, (float) $inv->balance_due);
        }

        $this->validate($rules);

        DB::transaction(function () use ($inv) {
            $rcpNo = erp_next_number('receipt_prefix', 'customer_payments', 'receipt_no', 'RCP-', 4);
            $payment = CustomerPayment::create([
                'receipt_no' => $rcpNo,
                'customer_id' => $this->customer->id,
                'sales_invoice_id' => $inv?->id,
                'payment_date' => $this->pay_date,
                'amount' => $this->pay_amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->pay_reference,
                'notes' => $this->pay_notes,
                'created_by' => auth()->id(),
            ]);

            if ($inv) {
                $inv->recalculatePaymentStatus();
            }

            FinanceService::recordTransaction(
                date: $this->pay_date,
                type: 'inflow',
                amount: $this->pay_amount,
                category: $inv?->source === 'job_card' ? 'Service Billing Receipt' : 'Customer Payment Collection',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Customer::class,
                partyId: $this->customer->id,
                partyName: $this->customer->name,
                referenceType: $inv ? SalesInvoice::class : CustomerPayment::class,
                referenceId: $inv?->id ?? $payment->id,
                referenceNo: $rcpNo,
                description: $inv
                    ? "Payment collected from {$this->customer->name} for {$inv->invoice_no}"
                    : "Customer payment collection from {$this->customer->name}"
            );

            log_activity('sales', 'payment_collected', "Collected ".format_currency($this->pay_amount)." from customer {$this->customer->name}", $payment);
        });

        $this->showPayModal = false;
        $this->loadCustomer();
        $this->dispatch('toast', message: 'Customer payment recorded & posted to Cash/Bank Book.', type: 'success');
    }

    public function render()
    {
        $invoices = $this->customer->salesInvoices->sortByDesc('id');
        $openInvoices = $invoices->where('balance_due', '>', 0)->where('status', '!=', 'cancelled');

        return view('livewire.sales.customer-profile', [
            'invoices' => $invoices,
            'openInvoices' => $openInvoices,
            'jobCards' => $this->customer->jobCards->sortByDesc('id'),
            'payments' => $this->customer->payments->sortByDesc('id'),
            'orders' => $this->customer->salesOrders->sortByDesc('id'),
            'returns' => $this->customer->returns->sortByDesc('id'),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
            'totalInvoiced' => (float) $invoices->where('status', '!=', 'cancelled')->sum('total_amount'),
            'totalPaid' => (float) $this->customer->payments->sum('amount'),
            'totalReturned' => (float) $this->customer->returns->sum('total_amount'),
        ]);
    }
}
