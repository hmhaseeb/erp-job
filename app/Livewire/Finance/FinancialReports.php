<?php

namespace App\Livewire\Finance;

use App\Models\AccountTransaction;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Income;
use App\Models\PayrollRun;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Financial Reports')]
class FinancialReports extends Component
{
    public string $reportTab = 'pnl'; // pnl | balance_sheet | cash_flow | outstanding
    public string $fromDate = '';
    public string $toDate = '';
    public string $asOfDate = '';

    public function mount(): void
    {
        $this->fromDate = now()->startOfYear()->toDateString();
        $this->toDate = now()->toDateString();
        $this->asOfDate = now()->toDateString();
    }

    public function exportPnlCsv(): StreamedResponse
    {
        $pnl = $this->computeProfitAndLoss();

        return response()->streamDownload(function () use ($pnl) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Statement', "Profit & Loss ({$this->fromDate} to {$this->toDate})"]);
            fputcsv($out, ['Direct Sales Revenue', $pnl['salesRevenue']]);
            fputcsv($out, ['Service / Job Card Revenue', $pnl['serviceRevenue']]);
            fputcsv($out, ['Other Operating Income', $pnl['otherIncome']]);
            fputcsv($out, ['Less: Sales Returns', -$pnl['salesReturns']]);
            fputcsv($out, ['Net Operating Revenue', $pnl['netRevenue']]);
            fputcsv($out, ['Purchases / COGS', $pnl['purchases']]);
            fputcsv($out, ['Less: Purchase Returns', -$pnl['purchaseReturns']]);
            fputcsv($out, ['Gross Profit', $pnl['grossProfit']]);
            foreach ($pnl['expenseBreakdown'] as $eb) {
                fputcsv($out, ['Expense: '.$eb->category_name, $eb->total]);
            }
            fputcsv($out, ['Payroll Salaries Paid', $pnl['payrollExpenses']]);
            fputcsv($out, ['Total Operating Expenses', $pnl['totalExpenses']]);
            fputcsv($out, ['Net Profit / (Loss)', $pnl['netProfit']]);
            fclose($out);
        }, 'profit-and-loss.csv');
    }

    private function computeProfitAndLoss(): array
    {
        $salesRevenue = (float) SalesInvoice::where('status', '!=', 'cancelled')
            ->where('source', 'sales')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->sum('subtotal');

        $serviceRevenue = (float) SalesInvoice::where('status', '!=', 'cancelled')
            ->where('source', 'job_card')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->sum('subtotal');

        $otherIncome = (float) Income::whereBetween('income_date', [$this->fromDate, $this->toDate])->sum('amount');
        $salesReturns = (float) SalesReturn::whereBetween('return_date', [$this->fromDate, $this->toDate])->sum('subtotal');
        $netRevenue = round($salesRevenue + $serviceRevenue + $otherIncome - $salesReturns, 2);

        $purchases = (float) PurchaseInvoice::where('status', '!=', 'cancelled')
            ->whereBetween('invoice_date', [$this->fromDate, $this->toDate])
            ->sum('subtotal');
        $purchaseReturns = (float) PurchaseReturn::whereBetween('return_date', [$this->fromDate, $this->toDate])->sum('subtotal');
        $netCogs = round($purchases - $purchaseReturns, 2);
        $grossProfit = round($netRevenue - $netCogs, 2);

        $expenseBreakdown = Expense::query()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->select('expense_categories.name as category_name', DB::raw('SUM(expenses.amount) as total'))
            ->whereBetween('expenses.expense_date', [$this->fromDate, $this->toDate])
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        $operatingExpenses = (float) $expenseBreakdown->sum('total');
        $payrollExpenses = (float) PayrollRun::where('status', 'paid')
            ->whereBetween('pay_date', [$this->fromDate, $this->toDate])
            ->sum('total_net');

        $totalExpenses = round($operatingExpenses + $payrollExpenses, 2);
        $netProfit = round($grossProfit - $totalExpenses, 2);

        return compact(
            'salesRevenue',
            'serviceRevenue',
            'otherIncome',
            'salesReturns',
            'netRevenue',
            'purchases',
            'purchaseReturns',
            'netCogs',
            'grossProfit',
            'expenseBreakdown',
            'operatingExpenses',
            'payrollExpenses',
            'totalExpenses',
            'netProfit'
        );
    }

    private function computeBalanceSheet(): array
    {
        $cashIn = (float) AccountTransaction::where('book_type', 'cash')->whereDate('transaction_date', '<=', $this->asOfDate)->where('type', 'inflow')->sum('amount');
        $cashOut = (float) AccountTransaction::where('book_type', 'cash')->whereDate('transaction_date', '<=', $this->asOfDate)->where('type', 'outflow')->sum('amount');
        $cashBalance = round($cashIn - $cashOut, 2);

        $bankAccounts = BankAccount::where('status', 'active')->get();
        $bankBalance = (float) $bankAccounts->sum('current_balance');

        $inventoryValuation = (float) Product::where('is_service', false)->get()->sum(fn ($p) => (float) $p->stock_quantity * (float) $p->cost_price);

        $customers = Customer::all();
        $accountsReceivable = round((float) $customers->sum(fn ($c) => max(0, $c->outstanding_balance)), 2);

        $totalAssets = round($cashBalance + $bankBalance + $inventoryValuation + $accountsReceivable, 2);

        $suppliers = Supplier::all();
        $accountsPayable = round((float) $suppliers->sum(fn ($s) => max(0, $s->outstanding_balance)), 2);

        $outputVat = (float) SalesInvoice::where('status', '!=', 'cancelled')->whereDate('invoice_date', '<=', $this->asOfDate)->sum('tax_amount');
        $inputVat = (float) PurchaseInvoice::where('status', '!=', 'cancelled')->whereDate('invoice_date', '<=', $this->asOfDate)->sum('tax_amount');
        $netVatPayable = round($outputVat - $inputVat, 2);

        $totalLiabilities = round($accountsPayable + max(0, $netVatPayable), 2);
        $retainedEarnings = round($totalAssets - $totalLiabilities, 2);

        return compact(
            'cashBalance',
            'bankBalance',
            'bankAccounts',
            'inventoryValuation',
            'accountsReceivable',
            'totalAssets',
            'accountsPayable',
            'netVatPayable',
            'totalLiabilities',
            'retainedEarnings'
        );
    }

    public function render()
    {
        $pnl = $this->computeProfitAndLoss();
        $bs = $this->computeBalanceSheet();

        $inflowsByCategory = AccountTransaction::select('category', DB::raw('SUM(amount) as total'))
            ->where('type', 'inflow')
            ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $outflowsByCategory = AccountTransaction::select('category', DB::raw('SUM(amount) as total'))
            ->where('type', 'outflow')
            ->whereBetween('transaction_date', [$this->fromDate, $this->toDate])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $totalInflow = (float) $inflowsByCategory->sum('total');
        $totalOutflow = (float) $outflowsByCategory->sum('total');

        $openReceivables = SalesInvoice::with('customer')->where('balance_due', '>', 0)->where('status', '!=', 'cancelled')->orderByDesc('balance_due')->get();
        $openPayables = PurchaseInvoice::with('supplier')->where('balance_due', '>', 0)->where('status', '!=', 'cancelled')->orderByDesc('balance_due')->get();

        return view('livewire.finance.financial-reports', [
            'pnl' => $pnl,
            'bs' => $bs,
            'inflowsByCategory' => $inflowsByCategory,
            'outflowsByCategory' => $outflowsByCategory,
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'openReceivables' => $openReceivables,
            'openPayables' => $openPayables,
        ]);
    }
}
