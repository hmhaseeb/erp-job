<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Salary Management &amp; Payroll Processing</h1>
            <p class="text-sm text-default mb-0">Process monthly payroll runs, calculate leave/tax deductions, disburse salaries, and post to Finance.</p>
        </div>
    </div>

    <!-- Generate Payroll Bar -->
    <div class="bg-white border border-border-color rounded-md p-4 mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="text-xs font-semibold block mb-1">Month</label>
            <select wire:model="month" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold block mb-1">Year</label>
            <input type="number" wire:model="year" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-24">
        </div>
        <div>
            <label class="text-xs font-semibold block mb-1">Pay Date</label>
            <input type="date" wire:model="pay_date" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
        </div>
        <button type="button" wire:click="generatePayrollRun" class="btn-sm bg-dark text-white hover:bg-primary-hover">Process Monthly Payroll</button>

        <div class="ms-auto flex flex-wrap items-end gap-2">
            <div>
                <label class="text-xs font-semibold block mb-1">Disbursement Method</label>
                <select wire:model="payment_method_id" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                    @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold block mb-1">Bank Account</label>
                <select wire:model="bank_account_id" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                    <option value="">Cash Drawer</option>
                    @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->account_name }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Payroll Runs List -->
        <div class="lg:col-span-4 bg-white border border-border-color rounded-md p-4 space-y-2">
            <h2 class="text-base font-bold text-title mb-2">Payroll Runs</h2>
            @foreach ($runs as $run)
                <button type="button" wire:click="$set('selectedRunId', {{ $run->id }})" class="w-full text-left border rounded-md p-3 cursor-pointer {{ $selectedRunId === $run->id ? 'border-primary bg-primary-transparent' : 'border-border-color hover:bg-light' }}">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-title">{{ $run->period_label }}</span>
                        <span class="text-[11px] px-2 py-0.5 rounded {{ $run->status === 'paid' ? 'bg-success-transparent text-success' : 'bg-warning-transparent text-warning' }}">{{ ucfirst($run->status) }}</span>
                    </div>
                    <div class="text-xs text-default mt-1">{{ $run->run_code }} · Net: <strong class="text-title">{{ format_currency($run->total_net) }}</strong></div>
                </button>
            @endforeach
        </div>

        <!-- Payroll Items Table -->
        <div class="lg:col-span-8 bg-white border border-border-color rounded-md p-4">
            @if ($selectedRun)
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <h2 class="text-base font-bold text-title mb-0">{{ $selectedRun->period_label }} ({{ $selectedRun->run_code }})</h2>
                        <p class="text-xs text-default mb-0">Gross: {{ format_currency($selectedRun->total_gross) }} · Deductions: {{ format_currency($selectedRun->total_deductions) }} · Net Pay: {{ format_currency($selectedRun->total_net) }}</p>
                    </div>
                    @if ($selectedRun->status !== 'paid')
                        <button type="button" wire:click="payAllPending" class="btn-sm bg-success text-white">Pay All Pending Salaries</button>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-2">Employee</th>
                                <th class="text-right py-2 px-2">Basic + Allow</th>
                                <th class="text-right py-2 px-2">Deductions</th>
                                <th class="text-right py-2 px-2">Net Pay</th>
                                <th class="text-left py-2 px-2">Status</th>
                                <th class="text-right py-2 px-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($selectedRun->items as $item)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-2 font-semibold text-title">{{ $item->employee?->full_name }}</td>
                                    <td class="py-2 px-2 text-right">{{ format_currency($item->basic_salary + $item->allowances) }}</td>
                                    <td class="py-2 px-2 text-right text-danger">{{ format_currency($item->leave_deduction + $item->tax_deduction + $item->other_deduction) }}</td>
                                    <td class="py-2 px-2 text-right font-bold text-title">{{ format_currency($item->net_salary) }}</td>
                                    <td class="py-2 px-2"><span class="text-[11px] px-2 py-0.5 rounded {{ $item->payment_status === 'paid' ? 'bg-success-transparent text-success' : 'bg-warning-transparent text-warning' }}">{{ ucfirst($item->payment_status) }}</span></td>
                                    <td class="py-2 px-2 text-right space-x-1">
                                        @if ($item->payment_status === 'pending')
                                            <button type="button" wire:click="paySalaryItem({{ $item->id }})" class="btn-xsmall bg-dark text-white">Pay Salary</button>
                                        @endif
                                        <button type="button" wire:click="$set('payslipItemId', {{ $item->id }})" class="btn-xsmall bg-light border border-border-color text-title">Payslip</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-default text-center py-8">Select or process a payroll run to view salary details.</p>
            @endif
        </div>
    </div>

    @if ($payslipItem)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-lg p-6">
            <div class="flex justify-between items-start border-b border-border-color pb-3 mb-4">
                <div>
                    <h3 class="text-lg font-bold text-title mb-0">{{ erp_setting('company_name') }}</h3>
                    <p class="text-xs text-default mb-0">Salary Payslip · {{ $payslipItem->payrollRun->period_label }}</p>
                </div>
                <button type="button" wire:click="$set('payslipItemId', null)" class="size-7 rounded border border-border-color flex items-center justify-center no-print"><i class="ph ph-x"></i></button>
            </div>
            <div class="text-xs space-y-1.5 mb-4">
                <div class="flex justify-between"><span>Employee:</span><strong>{{ $payslipItem->employee->full_name }} ({{ $payslipItem->employee->emp_code }})</strong></div>
                <div class="flex justify-between"><span>Department:</span><strong>{{ $payslipItem->employee->department?->name }}</strong></div>
                <div class="flex justify-between"><span>Basic Salary:</span><span>{{ format_currency($payslipItem->basic_salary) }}</span></div>
                <div class="flex justify-between"><span>Allowances:</span><span>{{ format_currency($payslipItem->allowances) }}</span></div>
                <div class="flex justify-between text-danger"><span>Leave &amp; Tax Deductions:</span><span>-{{ format_currency($payslipItem->leave_deduction + $payslipItem->tax_deduction) }}</span></div>
                <div class="flex justify-between border-t border-border-color pt-2 text-sm font-bold text-primary"><span>Net Salary Paid:</span><span>{{ format_currency($payslipItem->net_salary) }}</span></div>
            </div>
            <div class="flex justify-end gap-2 no-print">
                <button type="button" onclick="window.print()" class="btn-sm bg-dark text-white">Print Payslip</button>
            </div>
        </div>
    </div>
    @endif
</div>
