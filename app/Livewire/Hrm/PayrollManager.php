<?php

namespace App\Livewire\Hrm;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PaymentMethod;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\FinanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Salary & Payroll Processing')]
class PayrollManager extends Component
{
    public int $month;
    public int $year;
    public string $pay_date = '';
    public ?int $selectedRunId = null;
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;

    public ?int $payslipItemId = null;

    public function mount(): void
    {
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
        $this->pay_date = now()->endOfMonth()->toDateString();
        $this->payment_method_id = PaymentMethod::where('type', 'bank')->value('id') ?? PaymentMethod::value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id') ?? BankAccount::value('id');
        $this->selectedRunId = PayrollRun::latest('year')->latest('month')->value('id');
    }

    public function generatePayrollRun(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'create'), 403);

        if (PayrollRun::where('month', $this->month)->where('year', $this->year)->exists()) {
            $this->dispatch('toast', message: 'Payroll run for this month/year already exists.', type: 'error');
            return;
        }

        $run = DB::transaction(function () {
            $period = Carbon::createFromDate($this->year, $this->month, 1);
            $code = 'PAY-'.$period->format('Y-m');

            $run = PayrollRun::create([
                'run_code' => $code,
                'month' => $this->month,
                'year' => $this->year,
                'period_label' => $period->format('F Y'),
                'pay_date' => $this->pay_date ?: $period->endOfMonth()->toDateString(),
                'status' => 'processed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $totalGross = 0;
            $totalDed = 0;
            $totalNet = 0;

            foreach (Employee::where('status', 'active')->get() as $emp) {
                $unpaidLeaveDays = (float) LeaveRequest::where('employee_id', $emp->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $this->year)
                    ->whereMonth('start_date', $this->month)
                    ->whereHas('leaveType', fn ($q) => $q->where('is_paid', false))
                    ->sum('total_days');

                $dailyRate = $emp->basic_salary > 0 ? ($emp->basic_salary / 30) : 0;
                $leaveDed = round($unpaidLeaveDays * $dailyRate, 2);
                $taxDed = round(((float) $emp->basic_salary + (float) $emp->allowances) * 0.03, 2);
                $gross = (float) $emp->basic_salary + (float) $emp->allowances;
                $net = max(0, round($gross - $leaveDed - $taxDed, 2));

                PayrollItem::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $emp->id,
                    'basic_salary' => $emp->basic_salary,
                    'allowances' => $emp->allowances,
                    'bonus' => 0,
                    'leave_deduction' => $leaveDed,
                    'tax_deduction' => $taxDed,
                    'other_deduction' => 0,
                    'net_salary' => $net,
                    'payment_status' => 'pending',
                ]);

                $totalGross += $gross;
                $totalDed += ($leaveDed + $taxDed);
                $totalNet += $net;
            }

            $run->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDed,
                'total_net' => $totalNet,
            ]);

            return $run;
        });

        $this->selectedRunId = $run->id;
        log_activity('hrm', 'payroll_processed', "Generated payroll run {$run->run_code} ({$run->period_label})", $run);
        $this->dispatch('toast', message: "Payroll run {$run->run_code} processed.", type: 'success');
    }

    public function paySalaryItem(int $itemId): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        $item = PayrollItem::with(['employee', 'payrollRun'])->findOrFail($itemId);

        if ($item->payment_status === 'paid') {
            return;
        }

        DB::transaction(function () use ($item) {
            $ref = 'SAL-'.$item->payrollRun->run_code.'-'.$item->employee->emp_code;

            $item->update([
                'payment_status' => 'paid',
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'paid_at' => now(),
                'payment_reference' => $ref,
            ]);

            FinanceService::recordTransaction(
                date: now()->toDateString(),
                type: 'outflow',
                amount: (float) $item->net_salary,
                category: 'Payroll Salary',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Employee::class,
                partyId: $item->employee_id,
                partyName: $item->employee->full_name,
                referenceType: PayrollRun::class,
                referenceId: $item->payroll_run_id,
                referenceNo: $ref,
                description: "Salary payment for {$item->employee->full_name} ({$item->payrollRun->period_label})"
            );

            $run = $item->payrollRun;
            if (! $run->items()->where('payment_status', 'pending')->exists()) {
                $run->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });

        log_activity('hrm', 'salary_paid', "Paid salary to {$item->employee->full_name} (".format_currency($item->net_salary).")", $item);
        $this->dispatch('toast', message: "Salary paid for {$item->employee->full_name} and recorded in Finance.", type: 'success');
    }

    public function payAllPending(): void
    {
        abort_unless(auth()->user()->hasPermission('hrm', 'edit'), 403);
        if (! $this->selectedRunId) {
            return;
        }
        $pending = PayrollItem::where('payroll_run_id', $this->selectedRunId)->where('payment_status', 'pending')->pluck('id');
        foreach ($pending as $id) {
            $this->paySalaryItem($id);
        }
    }

    public function render()
    {
        $runs = PayrollRun::orderByDesc('year')->orderByDesc('month')->get();
        $selectedRun = $this->selectedRunId
            ? PayrollRun::with(['items.employee.department', 'items.employee.designation'])->find($this->selectedRunId)
            : null;
        $payslipItem = $this->payslipItemId
            ? PayrollItem::with(['employee.department', 'employee.designation', 'payrollRun'])->find($this->payslipItemId)
            : null;

        return view('livewire.hrm.payroll-manager', [
            'runs' => $runs,
            'selectedRun' => $selectedRun,
            'payslipItem' => $payslipItem,
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
        ]);
    }
}
