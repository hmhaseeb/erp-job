<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">
                @if ($mode === 'daybook') Daybook Management
                @elseif ($mode === 'cashbook') Cash Book
                @elseif ($mode === 'bankbook') Bank Book
                @elseif ($mode === 'vouchers') Payment &amp; Receipt Vouchers
                @else Account Transactions Ledger @endif
            </h1>
            <p class="text-sm text-default mb-0">Real-time double-book tracking across Cash Counter, Bank Accounts, and financial vouchers.</p>
        </div>
        <div class="flex items-center flex-wrap gap-1.5 no-print">
            <a href="{{ route('finance.daybook') }}" class="btn-sm {{ $mode === 'daybook' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Daybook</a>
            <a href="{{ route('finance.cashbook') }}" class="btn-sm {{ $mode === 'cashbook' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Cash Book</a>
            <a href="{{ route('finance.bankbook') }}" class="btn-sm {{ $mode === 'bankbook' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Bank Book</a>
            <a href="{{ route('finance.transactions') }}" class="btn-sm {{ $mode === 'transactions' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Transactions</a>
            <a href="{{ route('finance.receipts-payments') }}" class="btn-sm {{ $mode === 'vouchers' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Vouchers</a>
        </div>
    </div>

    @if ($mode === 'daybook')
    <div class="space-y-4">
        <div class="bg-white border border-border-color rounded-md p-4 flex flex-wrap items-end justify-between gap-3 no-print">
            <div><label class="text-xs font-semibold block mb-1">Daybook Date</label><input type="date" wire:model.live="daybookDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title"><i class="ph-duotone ph-printer me-1"></i> Print Daybook</button>
        </div>

        @php
            $dayIn = (float) $entries->where('type', 'inflow')->sum('amount');
            $dayOut = (float) $entries->where('type', 'outflow')->sum('amount');
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Opening Cash Balance</p><h3 class="text-xl font-bold text-title mb-0">{{ format_currency($openingCash) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Day Inflows</p><h3 class="text-xl font-bold text-success mb-0">+{{ format_currency($dayIn) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Day Outflows</p><h3 class="text-xl font-bold text-danger mb-0">-{{ format_currency($dayOut) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Net Movement Today</p><h3 class="text-xl font-bold text-primary mb-0">{{ format_currency($dayIn - $dayOut) }}</h3></div>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Transactions on {{ format_date($daybookDate) }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Txn #</th>
                            <th class="text-left py-2.5 px-3">Book / Account</th>
                            <th class="text-left py-2.5 px-3">Category</th>
                            <th class="text-left py-2.5 px-3">Party / Reference</th>
                            <th class="text-left py-2.5 px-3">Description</th>
                            <th class="text-right py-2.5 px-3">Debit (Inflow)</th>
                            <th class="text-right py-2.5 px-3">Credit (Outflow)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $e)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2.5 px-3 font-semibold text-primary text-xs">{{ $e->transaction_no }}</td>
                                <td class="py-2.5 px-3 text-xs">{{ $e->book_type === 'bank' ? ($e->bankAccount?->bank_name ?? 'Bank') : 'Cash Counter' }}</td>
                                <td class="py-2.5 px-3 text-xs">{{ $e->category }}</td>
                                <td class="py-2.5 px-3 text-xs text-default">{{ $e->party_name ?: '—' }} @if($e->reference_no)<span class="text-primary">({{ $e->reference_no }})</span>@endif</td>
                                <td class="py-2.5 px-3 text-title">{{ $e->description }}</td>
                                <td class="py-2.5 px-3 text-right font-semibold text-success">{{ $e->type === 'inflow' ? format_currency($e->amount) : '—' }}</td>
                                <td class="py-2.5 px-3 text-right font-semibold text-danger">{{ $e->type === 'outflow' ? format_currency($e->amount) : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-6 text-center text-default">No transactions recorded on this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @elseif ($mode === 'cashbook' || $mode === 'bankbook')
    <div class="space-y-4">
        @if ($mode === 'bankbook')
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach ($banks as $ba)
                <div class="bg-white border {{ $selectedBankId == $ba->id ? 'border-primary ring-1 ring-primary' : 'border-border-color' }} rounded-md p-4 cursor-pointer" wire:click="$set('selectedBankId', {{ $ba->id }})">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs text-default mb-0.5">{{ $ba->bank_name }}</p>
                            <h3 class="text-sm font-bold text-title mb-1">{{ $ba->account_name }}</h3>
                            <p class="text-[11px] text-default mb-0">Acct # {{ $ba->account_number }}</p>
                        </div>
                        <span class="text-base font-bold text-primary">{{ format_currency($ba->current_balance) }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <div class="bg-white border border-border-color rounded-md p-4 flex flex-wrap items-end justify-between gap-3 no-print">
            <div class="flex flex-wrap items-end gap-3">
                <div><label class="text-xs font-semibold block mb-1">From Date</label><input type="date" wire:model.live="fromDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">To Date</label><input type="date" wire:model.live="toDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
                @if ($mode === 'bankbook')
                <div>
                    <label class="text-xs font-semibold block mb-1">Bank Account</label>
                    <select wire:model.live="selectedBankId" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                        <option value="">All Bank Accounts</option>
                        @foreach($banks as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }} ({{ $ba->account_number }})</option>@endforeach
                    </select>
                </div>
                @endif
            </div>
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title"><i class="ph-duotone ph-printer me-1"></i> Print Book</button>
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2.5 px-3">Date</th>
                            <th class="text-left py-2.5 px-3">Txn #</th>
                            <th class="text-left py-2.5 px-3">Category / Particulars</th>
                            <th class="text-right py-2.5 px-3">Inflow (+)</th>
                            <th class="text-right py-2.5 px-3">Outflow (-)</th>
                            <th class="text-right py-2.5 px-3">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-light/50 border-b border-border-color font-semibold">
                            <td colspan="5" class="py-2 px-3 text-right text-xs uppercase text-default">Opening Balance before {{ $fromDate }}</td>
                            <td class="py-2 px-3 text-right text-title">{{ format_currency($openingBalance) }}</td>
                        </tr>
                        @php $running = $openingBalance; @endphp
                        @forelse ($entries as $e)
                            @php $running += ($e->type === 'inflow' ? (float)$e->amount : -(float)$e->amount); @endphp
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 text-xs text-default">{{ format_date($e->transaction_date) }}</td>
                                <td class="py-2 px-3 text-xs font-semibold text-primary">{{ $e->transaction_no }}</td>
                                <td class="py-2 px-3">
                                    <div class="font-medium text-title">{{ $e->category }} @if($e->party_name) — {{ $e->party_name }} @endif</div>
                                    <div class="text-xs text-default">{{ $e->description }}</div>
                                </td>
                                <td class="py-2 px-3 text-right text-success font-semibold">{{ $e->type === 'inflow' ? format_currency($e->amount) : '—' }}</td>
                                <td class="py-2 px-3 text-right text-danger font-semibold">{{ $e->type === 'outflow' ? format_currency($e->amount) : '—' }}</td>
                                <td class="py-2 px-3 text-right font-bold text-title">{{ format_currency($running) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No entries in selected period.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-light font-bold">
                            <td colspan="5" class="py-2.5 px-3 text-right">Closing Balance:</td>
                            <td class="py-2.5 px-3 text-right text-primary">{{ format_currency($running) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @elseif ($mode === 'vouchers')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">New Receipt / Payment Voucher</h2>
            <form wire:submit="createVoucher" class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Voucher Type</label>
                        <select wire:model="voucher_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="inflow">Receipt Voucher (In)</option>
                            <option value="outflow">Payment Voucher (Out)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Book Type</label>
                        <select wire:model.live="voucher_book" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="cash">Cash Book</option>
                            <option value="bank">Bank Book</option>
                        </select>
                    </div>
                </div>
                @if ($voucher_book === 'bank')
                <div>
                    <label class="text-xs font-semibold block mb-1">Bank Account *</label>
                    <select wire:model="voucher_bank_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($banks as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_number }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-xs font-semibold block mb-1">Date *</label><input type="date" wire:model="voucher_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="voucher_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('voucher_amount')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Category *</label><input type="text" wire:model="voucher_category" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Party / Payee</label><input type="text" wire:model="voucher_party" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Reference #</label><input type="text" wire:model="voucher_ref" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Narration / Notes</label><input type="text" wire:model="voucher_desc" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <button type="submit" class="w-full btn-sm bg-dark text-white hover:bg-primary-hover">Post Voucher</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Recent Payment &amp; Receipt Vouchers</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs bg-light border-b border-border-color">
                            <th class="text-left py-2 px-3">Txn #</th>
                            <th class="text-left py-2 px-3">Date</th>
                            <th class="text-left py-2 px-3">Book</th>
                            <th class="text-left py-2 px-3">Category / Party</th>
                            <th class="text-left py-2 px-3">Type</th>
                            <th class="text-right py-2 px-3">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ledger as $t)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 font-semibold text-primary text-xs">{{ $t->transaction_no }}</td>
                                <td class="py-2 px-3 text-xs">{{ format_date($t->transaction_date) }}</td>
                                <td class="py-2 px-3 text-xs capitalize">{{ $t->book_type }}</td>
                                <td class="py-2 px-3"><div class="font-medium text-title">{{ $t->category }}</div><div class="text-xs text-default">{{ $t->party_name ?: $t->description }}</div></td>
                                <td class="py-2 px-3"><span class="text-[11px] px-2 py-0.5 rounded {{ $t->type === 'inflow' ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">{{ $t->type === 'inflow' ? 'Receipt' : 'Payment' }}</span></td>
                                <td class="py-2 px-3 text-right font-semibold {{ $t->type === 'inflow' ? 'text-success' : 'text-danger' }}">{{ format_currency($t->amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-default">No vouchers recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $ledger->links() }}</div>
        </div>
    </div>

    @else
    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4 no-print">
            <div class="flex flex-wrap items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search txn #, category, party..." class="px-3 py-1.5 text-sm border border-border-color rounded-md w-60">
                <select wire:model.live="bookFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <option value="">All Books</option>
                    <option value="cash">Cash Book</option>
                    <option value="bank">Bank Book</option>
                </select>
                <select wire:model.live="typeFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <option value="">Inflow &amp; Outflow</option>
                    <option value="inflow">Inflow Only</option>
                    <option value="outflow">Outflow Only</option>
                </select>
                <input type="date" wire:model.live="fromDate" class="px-2.5 py-1.5 text-sm border border-border-color rounded-md">
                <input type="date" wire:model.live="toDate" class="px-2.5 py-1.5 text-sm border border-border-color rounded-md">
            </div>
            <button type="button" wire:click="exportLedgerCsv" class="btn-sm bg-dark text-white inline-flex items-center gap-1"><i class="icon-download"></i> Export CSV</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Txn #</th>
                        <th class="text-left py-2.5 px-3">Date</th>
                        <th class="text-left py-2.5 px-3">Account / Book</th>
                        <th class="text-left py-2.5 px-3">Category</th>
                        <th class="text-left py-2.5 px-3">Party</th>
                        <th class="text-left py-2.5 px-3">Description</th>
                        <th class="text-right py-2.5 px-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ledger as $t)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 text-xs font-semibold text-primary">{{ $t->transaction_no }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($t->transaction_date) }}</td>
                            <td class="py-2.5 px-3 text-xs">{{ $t->book_type === 'bank' ? ($t->bankAccount?->bank_name ?? 'Bank') : 'Cash Counter' }}</td>
                            <td class="py-2.5 px-3 text-xs">{{ $t->category }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $t->party_name ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-title">{{ $t->description }}</td>
                            <td class="py-2.5 px-3 text-right font-bold {{ $t->type === 'inflow' ? 'text-success' : 'text-danger' }}">{{ $t->type === 'inflow' ? '+' : '-' }}{{ format_currency($t->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-default">No transactions match filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $ledger->links() }}</div>
    </div>
    @endif
</div>
