<?php

namespace App\Livewire\Finance;

use App\Models\AccountTransaction;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Services\FinanceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Books, Ledger & Vouchers')]
class BooksManager extends Component
{
    use WithPagination;

    public string $mode = 'daybook'; // daybook | cashbook | bankbook | transactions | vouchers
    public string $daybookDate = '';
    public string $fromDate = '';
    public string $toDate = '';
    public string $search = '';
    public string $typeFilter = '';
    public string $bookFilter = '';
    public ?int $selectedBankId = null;

    // Voucher / Manual Entry Form
    public string $voucher_type = 'inflow'; // inflow = Receipt Voucher, outflow = Payment Voucher
    public string $voucher_book = 'cash'; // cash | bank
    public ?int $voucher_bank_id = null;
    public ?int $voucher_method_id = null;
    public string $voucher_date = '';
    public float $voucher_amount = 0;
    public string $voucher_category = 'Receipt Voucher';
    public string $voucher_party = '';
    public string $voucher_ref = '';
    public string $voucher_desc = '';

    public function mount(): void
    {
        $this->mode = request()->route()->defaults['mode'] ?? 'daybook';
        $this->daybookDate = now()->toDateString();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->voucher_date = now()->toDateString();
        $this->voucher_bank_id = BankAccount::where('is_default', true)->value('id') ?? BankAccount::value('id');
        $this->selectedBankId = $this->voucher_bank_id;
        $this->voucher_method_id = PaymentMethod::where('is_active', true)->value('id');

        if ($this->mode === 'bankbook') {
            $this->voucher_book = 'bank';
        }
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingBookFilter(): void { $this->resetPage(); }

    public function createVoucher(): void
    {
        abort_unless(auth()->user()->hasPermission('finance', 'create'), 403);
        $this->validate([
            'voucher_date' => ['required', 'date'],
            'voucher_amount' => ['required', 'numeric', 'min:0.01'],
            'voucher_category' => ['required', 'string', 'max:80'],
        ]);

        $bankId = $this->voucher_book === 'bank' ? $this->voucher_bank_id : null;
        $methodId = $this->voucher_book === 'cash'
            ? PaymentMethod::where('type', 'cash')->value('id')
            : $this->voucher_method_id;

        $txn = FinanceService::recordTransaction(
            date: $this->voucher_date,
            type: $this->voucher_type,
            amount: $this->voucher_amount,
            category: $this->voucher_category,
            paymentMethodId: $methodId,
            bankAccountId: $bankId,
            partyName: $this->voucher_party ?: null,
            referenceNo: $this->voucher_ref ?: null,
            description: $this->voucher_desc ?: ($this->voucher_type === 'inflow' ? 'Receipt Voucher' : 'Payment Voucher')
        );

        $this->reset(['voucher_amount', 'voucher_party', 'voucher_ref', 'voucher_desc']);
        log_activity('finance', 'voucher_created', "Recorded {$txn->type} voucher {$txn->transaction_no} (".format_currency($txn->amount).")", $txn);
        $this->dispatch('toast', message: "Voucher {$txn->transaction_no} recorded in ".ucfirst($txn->book_type)." Book.", type: 'success');
    }

    public function exportLedgerCsv(): StreamedResponse
    {
        $rows = AccountTransaction::with(['bankAccount', 'paymentMethod'])
            ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->bookFilter, fn ($q) => $q->where('book_type', $this->bookFilter))
            ->orderBy('transaction_date')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Txn #', 'Date', 'Book', 'Bank', 'Type', 'Category', 'Party', 'Ref #', 'Amount', 'Description']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->transaction_no,
                    $r->transaction_date?->toDateString(),
                    strtoupper($r->book_type),
                    $r->bankAccount?->bank_name ?? 'Cash Counter',
                    strtoupper($r->type),
                    $r->category,
                    $r->party_name,
                    $r->reference_no,
                    $r->amount,
                    $r->description,
                ]);
            }
            fclose($out);
        }, 'account-ledger.csv');
    }

    public function render()
    {
        $banks = BankAccount::where('status', 'active')->orderByDesc('is_default')->get();
        $methods = PaymentMethod::where('is_active', true)->get();

        if ($this->mode === 'daybook') {
            $entries = AccountTransaction::with(['bankAccount', 'paymentMethod'])
                ->whereDate('transaction_date', $this->daybookDate)
                ->latest('id')
                ->get();
            $priorCash = AccountTransaction::where('book_type', 'cash')->whereDate('transaction_date', '<', $this->daybookDate);
            $openingCash = (float) (clone $priorCash)->where('type', 'inflow')->sum('amount') - (float) (clone $priorCash)->where('type', 'outflow')->sum('amount');

            return view('livewire.finance.books-manager', [
                'entries' => $entries,
                'openingCash' => $openingCash,
                'banks' => $banks,
                'methods' => $methods,
            ]);
        }

        if ($this->mode === 'cashbook') {
            $baseBefore = AccountTransaction::where('book_type', 'cash')->whereDate('transaction_date', '<', $this->fromDate);
            $opening = (float) (clone $baseBefore)->where('type', 'inflow')->sum('amount') - (float) (clone $baseBefore)->where('type', 'outflow')->sum('amount');

            $entries = AccountTransaction::with(['paymentMethod'])
                ->where('book_type', 'cash')
                ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            return view('livewire.finance.books-manager', [
                'entries' => $entries,
                'openingBalance' => $opening,
                'banks' => $banks,
                'methods' => $methods,
            ]);
        }

        if ($this->mode === 'bankbook') {
            $selectedBank = $this->selectedBankId ? $banks->firstWhere('id', $this->selectedBankId) : $banks->first();
            $bankOpening = $selectedBank ? (float) $selectedBank->opening_balance : (float) $banks->sum('opening_balance');

            $priorBank = AccountTransaction::where('book_type', 'bank')
                ->when($this->selectedBankId, fn ($q) => $q->where('bank_account_id', $this->selectedBankId))
                ->whereDate('transaction_date', '<', $this->fromDate);
            $opening = $bankOpening + (float) (clone $priorBank)->where('type', 'inflow')->sum('amount') - (float) (clone $priorBank)->where('type', 'outflow')->sum('amount');

            $entries = AccountTransaction::with(['bankAccount', 'paymentMethod'])
                ->where('book_type', 'bank')
                ->when($this->selectedBankId, fn ($q) => $q->where('bank_account_id', $this->selectedBankId))
                ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            return view('livewire.finance.books-manager', [
                'entries' => $entries,
                'openingBalance' => $opening,
                'banks' => $banks,
                'methods' => $methods,
            ]);
        }

        $ledger = AccountTransaction::with(['bankAccount', 'paymentMethod'])
            ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->when($this->bookFilter, fn ($q) => $q->where('book_type', $this->bookFilter))
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('transaction_no', 'like', "%{$this->search}%")->orWhere('category', 'like', "%{$this->search}%")->orWhere('party_name', 'like', "%{$this->search}%")->orWhere('description', 'like', "%{$this->search}%")))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 12));

        return view('livewire.finance.books-manager', [
            'ledger' => $ledger,
            'banks' => $banks,
            'methods' => $methods,
        ]);
    }
}
