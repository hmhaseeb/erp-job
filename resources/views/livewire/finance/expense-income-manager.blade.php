<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">
                @if ($mode === 'expenses') Expense Management
                @elseif ($mode === 'categories') Expense Categories
                @else Income Management @endif
            </h1>
            <p class="text-sm text-default mb-0">Track operating expenses, categorize overheads, record non-invoice income, and post automatically to Cash/Bank Books.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            @canModule('finance', 'create')
            @if ($mode === 'categories')
                <button type="button" wire:click="openCreateCategory" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Add Category</button>
            @elseif ($mode === 'incomes')
                <button type="button" wire:click="openCreateIncome" class="btn-sm bg-success text-white inline-flex items-center gap-1.5"><i class="ph ph-plus"></i> Record Income</button>
            @else
                <button type="button" wire:click="openCreateExpense" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Record Expense</button>
            @endif
            @endcanModule
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-4 no-print">
        <button type="button" wire:click="switchTab('expenses')" class="btn-sm {{ $mode === 'expenses' ? 'bg-primary text-white' : 'bg-white border border-border-color text-title' }}">Expenses</button>
        <button type="button" wire:click="switchTab('categories')" class="btn-sm {{ $mode === 'categories' ? 'bg-primary text-white' : 'bg-white border border-border-color text-title' }}">Expense Categories</button>
        <button type="button" wire:click="switchTab('incomes')" class="btn-sm {{ $mode === 'incomes' ? 'bg-primary text-white' : 'bg-white border border-border-color text-title' }}">Other Income</button>
    </div>

    @if ($mode === 'expenses')
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search expense # or title..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="categoryFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Categories</option>
                @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Expense #</th>
                        <th class="text-left py-2.5 px-3">Date</th>
                        <th class="text-left py-2.5 px-3">Title &amp; Category</th>
                        <th class="text-left py-2.5 px-3">Vendor</th>
                        <th class="text-left py-2.5 px-3">Method</th>
                        <th class="text-right py-2.5 px-3">Amount</th>
                        <th class="text-right py-2.5 px-3">Tax</th>
                        <th class="text-right py-2.5 px-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $e)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary text-xs">{{ $e->expense_no }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($e->expense_date) }}</td>
                            <td class="py-2.5 px-3">
                                <div class="font-semibold text-title">{{ $e->title }}</div>
                                <div class="text-xs text-default">{{ $e->category?->name }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $e->supplier?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $e->paymentMethod?->name ?? '—' }}</td>
                            <td class="py-2.5 px-3 text-right">{{ format_currency($e->amount) }}</td>
                            <td class="py-2.5 px-3 text-right text-default">{{ format_currency($e->tax_amount) }}</td>
                            <td class="py-2.5 px-3 text-right font-bold text-danger">{{ format_currency($e->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-default">No expenses recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $expenses->links() }}</div>
    </div>

    @elseif ($mode === 'categories')
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Code</th>
                        <th class="text-left py-2.5 px-3">Category Name</th>
                        <th class="text-left py-2.5 px-3">Description</th>
                        <th class="text-right py-2.5 px-3">Entries</th>
                        <th class="text-right py-2.5 px-3 no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $c)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary text-xs">{{ $c->code }}</td>
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $c->name }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $c->description ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-semibold">{{ $c->expenses_count }}</td>
                            <td class="py-2.5 px-3 text-right no-print">
                                @canModule('finance', 'edit')
                                <button type="button" wire:click="openCreateCategory({{ $c->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                @endcanModule
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @else
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search income # or title..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Income #</th>
                        <th class="text-left py-2.5 px-3">Date</th>
                        <th class="text-left py-2.5 px-3">Category</th>
                        <th class="text-left py-2.5 px-3">Title</th>
                        <th class="text-left py-2.5 px-3">Deposited To</th>
                        <th class="text-right py-2.5 px-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incomes as $inc)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-primary text-xs">{{ $inc->income_no }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($inc->income_date) }}</td>
                            <td class="py-2.5 px-3 text-xs">{{ $inc->category }}</td>
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $inc->title }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $inc->bankAccount?->bank_name ?? 'Cash Counter' }} ({{ $inc->paymentMethod?->name ?? '—' }})</td>
                            <td class="py-2.5 px-3 text-right font-bold text-success">{{ format_currency($inc->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-default">No income entries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $incomes->links() }}</div>
    </div>
    @endif

    @if ($showExpenseModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Record Operating Expense</h2>
                <button type="button" wire:click="$set('showExpenseModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveExpense" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Category *</label>
                    <select wire:model="expense_category_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Vendor (Optional)</label>
                    <select wire:model="supplier_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">None / General Payee</option>
                        @foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Expense Title / Description *</label><input type="text" wire:model="title" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('title')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Date *</label><input type="date" wire:model="date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Amount (Net) *</label><input type="number" step="0.01" wire:model="amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('amount')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Tax / VAT</label><input type="number" step="0.01" wire:model="tax_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Payment Method</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Paid From Bank / Cash</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference / Bill #</label><input type="text" wire:model="reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Receipt / Attachment</label><input type="file" wire:model="attachment" class="text-xs"></div>
                <div class="sm:col-span-2 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showExpenseModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($showCategoryModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">{{ $editingCategoryId ? 'Edit Category' : 'Add Expense Category' }}</h2>
                <button type="button" wire:click="$set('showCategoryModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveCategory" class="p-4 space-y-3">
                <div><label class="text-xs font-semibold block mb-1">Code *</label><input type="text" wire:model="cat_code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Category Name *</label><input type="text" wire:model="cat_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Description</label><input type="text" wire:model="cat_description" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showCategoryModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white">Save Category</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($showIncomeModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-xl">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Record Other Income</h2>
                <button type="button" wire:click="$set('showIncomeModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="saveIncome" class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Category *</label>
                    <select wire:model="income_category" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="Consulting">Consulting</option>
                        <option value="Commission">Commission</option>
                        <option value="Rental Income">Rental Income</option>
                        <option value="Interest">Interest</option>
                        <option value="Other Income">Other Income</option>
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Date *</label><input type="date" wire:model="income_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">Title / Description *</label><input type="text" wire:model="income_title" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="income_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Tax</label><input type="number" step="0.01" wire:model="income_tax" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Payment Method</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Deposit To Account</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showIncomeModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-success text-white">Save Income</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
