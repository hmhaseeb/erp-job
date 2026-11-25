<?php

namespace App\Livewire\Finance;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\FinanceService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Expenses & Income Management')]
class ExpenseIncomeManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $mode = 'expenses'; // expenses | categories | incomes
    public string $search = '';
    public string $categoryFilter = '';

    // Expense Form
    public bool $showExpenseModal = false;
    public ?int $expense_category_id = null;
    public ?int $supplier_id = null;
    public string $title = '';
    public string $date = '';
    public float $amount = 0;
    public float $tax_amount = 0;
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $reference = '';
    public string $notes = '';
    public $attachment;

    // Expense Category Form
    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public string $cat_code = '';
    public string $cat_name = '';
    public string $cat_description = '';

    // Income Form
    public bool $showIncomeModal = false;
    public string $income_category = 'Consulting';
    public string $income_title = '';
    public string $income_date = '';
    public float $income_amount = 0;
    public float $income_tax = 0;
    public string $income_reference = '';
    public string $income_notes = '';

    public function mount(): void
    {
        $routeMode = request()->route()->defaults['mode'] ?? 'expenses';
        $this->mode = $routeMode === 'incomes' ? 'incomes' : 'expenses';
        $this->date = now()->toDateString();
        $this->income_date = now()->toDateString();
        $this->payment_method_id = PaymentMethod::where('is_active', true)->value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    public function switchTab(string $tab): void
    {
        $this->mode = $tab;
        $this->resetPage();
    }

    public function openCreateExpense(): void
    {
        abort_unless(auth()->user()->hasPermission('finance', 'create'), 403);
        $this->reset(['supplier_id', 'title', 'reference', 'notes', 'attachment']);
        $this->expense_category_id = ExpenseCategory::where('status', 'active')->value('id');
        $this->date = now()->toDateString();
        $this->amount = 0;
        $this->tax_amount = 0;
        $this->showExpenseModal = true;
    }

    public function saveExpense(): void
    {
        abort_unless(auth()->user()->hasPermission('finance', 'create'), 403);
        $this->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'title' => ['required', 'string', 'max:180'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['numeric', 'min:0'],
            'attachment' => ['nullable', 'file', 'max:4096'],
        ]);

        $path = $this->attachment ? $this->attachment->store('expenses', 'public') : null;
        $total = round($this->amount + $this->tax_amount, 2);

        $expense = DB::transaction(function () use ($path, $total) {
            $expNo = erp_next_number(null, 'expenses', 'expense_no', 'EXP-', 4);
            $cat = ExpenseCategory::find($this->expense_category_id);

            $exp = Expense::create([
                'expense_no' => $expNo,
                'expense_category_id' => $this->expense_category_id,
                'supplier_id' => $this->supplier_id ?: null,
                'title' => $this->title,
                'expense_date' => $this->date,
                'amount' => $this->amount,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $total,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->reference,
                'attachment' => $path,
                'status' => 'approved',
                'notes' => $this->notes,
                'created_by' => auth()->id(),
            ]);

            FinanceService::recordTransaction(
                date: $this->date,
                type: 'outflow',
                amount: $total,
                category: $cat?->name ?? 'Operating Expense',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: $this->supplier_id ? Supplier::class : null,
                partyId: $this->supplier_id ?: null,
                partyName: $this->supplier_id ? Supplier::find($this->supplier_id)?->name : null,
                referenceType: Expense::class,
                referenceId: $exp->id,
                referenceNo: $expNo,
                description: $this->title
            );

            return $exp;
        });

        $this->showExpenseModal = false;
        log_activity('finance', 'expense_created', "Recorded Expense {$expense->expense_no} ({$expense->title})", $expense);
        $this->dispatch('toast', message: "Expense {$expense->expense_no} saved & posted to Ledger.", type: 'success');
    }

    public function openCreateCategory(?int $id = null): void
    {
        abort_unless(auth()->user()->hasPermission('finance', $id ? 'edit' : 'create'), 403);
        if ($id) {
            $c = ExpenseCategory::findOrFail($id);
            $this->editingCategoryId = $c->id;
            $this->cat_code = $c->code;
            $this->cat_name = $c->name;
            $this->cat_description = (string) ($c->description ?? '');
        } else {
            $this->reset(['editingCategoryId', 'cat_name', 'cat_description']);
            $this->cat_code = erp_next_number(null, 'expense_categories', 'code', 'EXC-', 3);
        }
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'cat_code' => ['required', 'string', 'max:30', 'unique:expense_categories,code'.($this->editingCategoryId ? ','.$this->editingCategoryId : '')],
            'cat_name' => ['required', 'string', 'max:120', 'unique:expense_categories,name'.($this->editingCategoryId ? ','.$this->editingCategoryId : '')],
        ]);

        ExpenseCategory::updateOrCreate(
            ['id' => $this->editingCategoryId],
            ['code' => $this->cat_code, 'name' => $this->cat_name, 'description' => $this->cat_description, 'status' => 'active']
        );

        $this->showCategoryModal = false;
        log_activity('finance', 'expense_category_saved', "Saved expense category {$this->cat_name}");
        $this->dispatch('toast', message: 'Expense category saved.', type: 'success');
    }

    public function openCreateIncome(): void
    {
        abort_unless(auth()->user()->hasPermission('finance', 'create'), 403);
        $this->reset(['income_title', 'income_reference', 'income_notes']);
        $this->income_category = 'Consulting';
        $this->income_date = now()->toDateString();
        $this->income_amount = 0;
        $this->income_tax = 0;
        $this->showIncomeModal = true;
    }

    public function saveIncome(): void
    {
        abort_unless(auth()->user()->hasPermission('finance', 'create'), 403);
        $this->validate([
            'income_category' => ['required', 'string', 'max:80'],
            'income_title' => ['required', 'string', 'max:180'],
            'income_date' => ['required', 'date'],
            'income_amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $total = round($this->income_amount + $this->income_tax, 2);

        $inc = DB::transaction(function () use ($total) {
            $incNo = erp_next_number(null, 'incomes', 'income_no', 'INC-', 4);

            $inc = Income::create([
                'income_no' => $incNo,
                'category' => $this->income_category,
                'title' => $this->income_title,
                'income_date' => $this->income_date,
                'amount' => $this->income_amount,
                'tax_amount' => $this->income_tax,
                'total_amount' => $total,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->income_reference,
                'status' => 'received',
                'notes' => $this->income_notes,
                'created_by' => auth()->id(),
            ]);

            FinanceService::recordTransaction(
                date: $this->income_date,
                type: 'inflow',
                amount: $total,
                category: $this->income_category,
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                referenceType: Income::class,
                referenceId: $inc->id,
                referenceNo: $incNo,
                description: $this->income_title
            );

            return $inc;
        });

        $this->showIncomeModal = false;
        log_activity('finance', 'income_created', "Recorded Income {$inc->income_no} ({$inc->title})", $inc);
        $this->dispatch('toast', message: "Income {$inc->income_no} recorded & posted to Ledger.", type: 'success');
    }

    public function render()
    {
        $categories = ExpenseCategory::withCount('expenses')->orderBy('name')->get();

        $expenses = Expense::with(['category', 'supplier', 'paymentMethod'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('expense_no', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn ($q) => $q->where('expense_category_id', $this->categoryFilter))
            ->latest('expense_date')
            ->paginate((int) erp_setting('per_page', 10));

        $incomes = Income::with(['paymentMethod', 'bankAccount'])
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('income_no', 'like', "%{$this->search}%"))
            ->latest('income_date')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.finance.expense-income-manager', [
            'categories' => $categories,
            'expenses' => $expenses,
            'incomes' => $incomes,
            'suppliers' => Supplier::orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
        ]);
    }
}
