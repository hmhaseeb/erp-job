<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Financial Reports</h1>
            <p class="text-sm text-default mb-0">Profit &amp; Loss Statement, Balance Sheet, Cash Flow Report, and Outstanding Receivables/Payables.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1"><i class="ph-duotone ph-printer"></i> Print Statement</button>
            <button type="button" wire:click="exportPnlCsv" class="btn-sm bg-dark text-white inline-flex items-center gap-1"><i class="icon-download"></i> Export P&amp;L CSV</button>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 bg-white border border-border-color rounded-md p-4 mb-4 no-print">
        <div class="flex flex-wrap gap-1.5">
            <button type="button" wire:click="$set('reportTab', 'pnl')" class="btn-sm {{ $reportTab === 'pnl' ? 'bg-primary text-white' : 'bg-light text-title' }}">Profit &amp; Loss</button>
            <button type="button" wire:click="$set('reportTab', 'balance_sheet')" class="btn-sm {{ $reportTab === 'balance_sheet' ? 'bg-primary text-white' : 'bg-light text-title' }}">Balance Sheet</button>
            <button type="button" wire:click="$set('reportTab', 'cash_flow')" class="btn-sm {{ $reportTab === 'cash_flow' ? 'bg-primary text-white' : 'bg-light text-title' }}">Cash Flow</button>
            <button type="button" wire:click="$set('reportTab', 'outstanding')" class="btn-sm {{ $reportTab === 'outstanding' ? 'bg-primary text-white' : 'bg-light text-title' }}">Outstanding Reports</button>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs">
            @if ($reportTab === 'balance_sheet')
                <span class="font-semibold">As of:</span>
                <input type="date" wire:model.live="asOfDate" class="px-2.5 py-1.5 border border-border-color rounded-md">
            @else
                <span class="font-semibold">From:</span>
                <input type="date" wire:model.live="fromDate" class="px-2.5 py-1.5 border border-border-color rounded-md">
                <span class="font-semibold">To:</span>
                <input type="date" wire:model.live="toDate" class="px-2.5 py-1.5 border border-border-color rounded-md">
            @endif
        </div>
    </div>

    @if ($reportTab === 'pnl')
    <div class="bg-white border border-border-color rounded-md p-6 max-w-4xl">
        <div class="flex justify-between items-start border-b border-border-color pb-4 mb-4">
            <div>
                <h2 class="text-lg font-bold text-title mb-0">Income Statement (Profit &amp; Loss)</h2>
                <p class="text-xs text-default mb-0">{{ erp_setting('company_name') }} · Period: {{ format_date($fromDate) }} – {{ format_date($toDate) }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-default mb-0">Net Profit / (Loss)</p>
                <h3 class="text-xl font-bold mb-0 {{ $pnl['netProfit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ format_currency($pnl['netProfit']) }}</h3>
            </div>
        </div>

        <table class="w-full text-sm">
            <tbody>
                <tr class="bg-light font-bold"><td colspan="2" class="py-2 px-3">Operating Revenue</td></tr>
                <tr class="border-b border-border-color"><td class="py-2 px-3 pl-6">Direct Product Sales Revenue</td><td class="py-2 px-3 text-right">{{ format_currency($pnl['salesRevenue']) }}</td></tr>
                <tr class="border-b border-border-color"><td class="py-2 px-3 pl-6">Service / Job Card Revenue</td><td class="py-2 px-3 text-right">{{ format_currency($pnl['serviceRevenue']) }}</td></tr>
                <tr class="border-b border-border-color"><td class="py-2 px-3 pl-6">Other Income</td><td class="py-2 px-3 text-right">{{ format_currency($pnl['otherIncome']) }}</td></tr>
                <tr class="border-b border-border-color text-danger"><td class="py-2 px-3 pl-6">Less: Sales Returns</td><td class="py-2 px-3 text-right">-{{ format_currency($pnl['salesReturns']) }}</td></tr>
                <tr class="border-b border-border-color font-bold bg-light/50"><td class="py-2 px-3">Total Net Revenue</td><td class="py-2 px-3 text-right">{{ format_currency($pnl['netRevenue']) }}</td></tr>

                <tr class="bg-light font-bold"><td colspan="2" class="py-2 px-3">Cost of Goods / Purchases</td></tr>
                <tr class="border-b border-border-color"><td class="py-2 px-3 pl-6">Purchases (Net of Tax)</td><td class="py-2 px-3 text-right">{{ format_currency($pnl['purchases']) }}</td></tr>
                <tr class="border-b border-border-color text-success"><td class="py-2 px-3 pl-6">Less: Purchase Returns</td><td class="py-2 px-3 text-right">-{{ format_currency($pnl['purchaseReturns']) }}</td></tr>
                <tr class="border-b border-border-color font-bold bg-info-transparent/30"><td class="py-2.5 px-3">Gross Profit</td><td class="py-2.5 px-3 text-right text-title">{{ format_currency($pnl['grossProfit']) }}</td></tr>

                <tr class="bg-light font-bold"><td colspan="2" class="py-2 px-3">Operating Expenses</td></tr>
                @foreach ($pnl['expenseBreakdown'] as $eb)
                    <tr class="border-b border-border-color"><td class="py-2 px-3 pl-6">{{ $eb->category_name }}</td><td class="py-2 px-3 text-right">{{ format_currency($eb->total) }}</td></tr>
                @endforeach
                <tr class="border-b border-border-color"><td class="py-2 px-3 pl-6">Payroll &amp; Staff Salaries</td><td class="py-2 px-3 text-right">{{ format_currency($pnl['payrollExpenses']) }}</td></tr>
                <tr class="border-b border-border-color font-bold"><td class="py-2 px-3">Total Operating Expenses</td><td class="py-2 px-3 text-right text-danger">{{ format_currency($pnl['totalExpenses']) }}</td></tr>
            </tbody>
            <tfoot>
                <tr class="{{ $pnl['netProfit'] >= 0 ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }} font-bold text-base">
                    <td class="py-3 px-3">Net Profit / (Loss)</td>
                    <td class="py-3 px-3 text-right">{{ format_currency($pnl['netProfit']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @elseif ($reportTab === 'balance_sheet')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white border border-border-color rounded-md p-5">
            <h2 class="text-base font-bold text-title mb-3 pb-2 border-b border-border-color">Assets (As of {{ format_date($asOfDate) }})</h2>
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-border-color"><td class="py-2.5">Cash in Hand</td><td class="py-2.5 text-right font-semibold">{{ format_currency($bs['cashBalance']) }}</td></tr>
                    <tr class="border-b border-border-color"><td class="py-2.5">Bank Accounts Balance</td><td class="py-2.5 text-right font-semibold">{{ format_currency($bs['bankBalance']) }}</td></tr>
                    <tr class="border-b border-border-color"><td class="py-2.5">Inventory Asset Valuation</td><td class="py-2.5 text-right font-semibold">{{ format_currency($bs['inventoryValuation']) }}</td></tr>
                    <tr class="border-b border-border-color"><td class="py-2.5">Accounts Receivable (Customers)</td><td class="py-2.5 text-right font-semibold">{{ format_currency($bs['accountsReceivable']) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-bold text-base"><td class="py-3 px-2">Total Assets</td><td class="py-3 px-2 text-right text-primary">{{ format_currency($bs['totalAssets']) }}</td></tr>
                </tfoot>
            </table>
        </div>

        <div class="bg-white border border-border-color rounded-md p-5">
            <h2 class="text-base font-bold text-title mb-3 pb-2 border-b border-border-color">Liabilities &amp; Equity</h2>
            <table class="w-full text-sm">
                <tbody>
                    <tr class="border-b border-border-color"><td class="py-2.5">Accounts Payable (Vendors)</td><td class="py-2.5 text-right font-semibold">{{ format_currency($bs['accountsPayable']) }}</td></tr>
                    <tr class="border-b border-border-color"><td class="py-2.5">Net {{ erp_setting('tax_label', 'VAT') }} Payable</td><td class="py-2.5 text-right font-semibold">{{ format_currency(max(0, $bs['netVatPayable'])) }}</td></tr>
                    <tr class="border-b border-border-color bg-light/50 font-semibold"><td class="py-2.5">Total Liabilities</td><td class="py-2.5 text-right text-danger">{{ format_currency($bs['totalLiabilities']) }}</td></tr>
                    <tr class="border-b border-border-color"><td class="py-2.5">Owner Equity &amp; Retained Earnings</td><td class="py-2.5 text-right font-semibold">{{ format_currency($bs['retainedEarnings']) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-bold text-base"><td class="py-3 px-2">Total Liabilities &amp; Equity</td><td class="py-3 px-2 text-right text-primary">{{ format_currency($bs['totalAssets']) }}</td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    @elseif ($reportTab === 'cash_flow')
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Cash/Bank Inflows</p><h3 class="text-xl font-bold text-success mb-0">+{{ format_currency($totalInflow) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Cash/Bank Outflows</p><h3 class="text-xl font-bold text-danger mb-0">-{{ format_currency($totalOutflow) }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Net Cash Flow</p><h3 class="text-xl font-bold text-primary mb-0">{{ format_currency($totalInflow - $totalOutflow) }}</h3></div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Cash Inflows by Category</h2>
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($inflowsByCategory as $in)
                            <tr class="border-b border-border-color last:border-0"><td class="py-2">{{ $in->category }}</td><td class="py-2 text-right font-semibold text-success">{{ format_currency($in->total) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-default">No inflows in period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Cash Outflows by Category</h2>
                <table class="w-full text-sm">
                    <tbody>
                        @forelse ($outflowsByCategory as $out)
                            <tr class="border-b border-border-color last:border-0"><td class="py-2">{{ $out->category }}</td><td class="py-2 text-right font-semibold text-danger">{{ format_currency($out->total) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-center text-default">No outflows in period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Outstanding Customer Invoices (Receivables)</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-2">Invoice #</th><th class="text-left py-2 px-2">Customer</th><th class="text-left py-2 px-2">Due</th><th class="text-right py-2 px-2">Balance Due</th></tr></thead>
                <tbody>
                    @forelse ($openReceivables as $r)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-2 font-semibold text-primary">{{ $r->invoice_no }}</td>
                            <td class="py-2 px-2">{{ $r->customer?->name }}</td>
                            <td class="py-2 px-2 text-xs text-default">{{ format_date($r->due_date) }}</td>
                            <td class="py-2 px-2 text-right font-bold text-danger">{{ format_currency($r->balance_due) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-default">No pending receivables.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="bg-white border border-border-color rounded-md p-4">
            <h2 class="text-base font-bold text-title mb-3">Outstanding Supplier Bills (Payables)</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-2">Bill #</th><th class="text-left py-2 px-2">Vendor</th><th class="text-left py-2 px-2">Due</th><th class="text-right py-2 px-2">Balance Due</th></tr></thead>
                <tbody>
                    @forelse ($openPayables as $p)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-2 font-semibold text-primary">{{ $p->bill_no }}</td>
                            <td class="py-2 px-2">{{ $p->supplier?->name }}</td>
                            <td class="py-2 px-2 text-xs text-default">{{ format_date($p->due_date) }}</td>
                            <td class="py-2 px-2 text-right font-bold text-danger">{{ format_currency($p->balance_due) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-default">No pending payables.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
