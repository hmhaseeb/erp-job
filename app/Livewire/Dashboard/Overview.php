<?php

namespace App\Livewire\Dashboard;

use App\Models\AccountTransaction;
use App\Models\Attendance;
use App\Models\BankAccount;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\JobCard;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('ERP Dashboard')]
class Overview extends Component
{
    public function render()
    {
        $totalEmployees = Employee::where('status', 'active')->count();
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->count();
        $todayPresent = Attendance::whereDate('attendance_date', today())
            ->whereIn('status', ['present', 'late', 'remote'])
            ->count();
        $attendanceRate = $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100, 1) : 0;
        $latestPayroll = PayrollRun::latest('year')->latest('month')->first();

        $totalProducts = Product::where('is_service', false)->count();
        $lowStockCount = Product::where('is_service', false)->whereColumn('stock_quantity', '<=', 'reorder_level')->count();
        $inventoryValue = (float) Product::where('is_service', false)->sum(DB::raw('stock_quantity * cost_price'));

        $totalSalesRevenue = (float) SalesInvoice::where('status', '!=', 'cancelled')->sum('total_amount');
        $receivables = (float) SalesInvoice::where('status', '!=', 'cancelled')->sum('balance_due');
        $payables = (float) PurchaseInvoice::where('status', '!=', 'cancelled')->sum('balance_due');

        $cashIn = (float) AccountTransaction::where('book_type', 'cash')->where('type', 'inflow')->sum('amount');
        $cashOut = (float) AccountTransaction::where('book_type', 'cash')->where('type', 'outflow')->sum('amount');
        $cashBalance = $cashIn - $cashOut;
        $bankBalance = (float) BankAccount::where('status', 'active')->sum('current_balance');

        $totalExpenses = (float) Expense::sum('total_amount');
        $openJobCards = JobCard::whereNotIn('status', ['delivered', 'cancelled'])->count();
        $pendingQcJobs = JobCard::where('status', 'quality_check')->count();

        $departments = Department::withCount('employees')->get();
        $recentInvoices = SalesInvoice::with('customer')->latest('invoice_date')->limit(5)->get();
        $recentJobCards = JobCard::with(['customer', 'technician'])->latest()->limit(5)->get();
        $lowStockItems = Product::where('is_service', false)->whereColumn('stock_quantity', '<=', 'reorder_level')->limit(5)->get();

        // Monthly revenue vs expenses (last 6 months)
        $months = [];
        $revenueSeries = [];
        $expenseSeries = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $months[] = $m->format('M Y');
            $revenueSeries[] = round((float) SalesInvoice::where('status', '!=', 'cancelled')
                ->whereYear('invoice_date', $m->year)
                ->whereMonth('invoice_date', $m->month)
                ->sum('total_amount'), 2);
            $expenseSeries[] = round((float) Expense::whereYear('expense_date', $m->year)
                ->whereMonth('expense_date', $m->month)
                ->sum('total_amount') + (float) PurchaseInvoice::where('status', '!=', 'cancelled')
                ->whereYear('invoice_date', $m->year)
                ->whereMonth('invoice_date', $m->month)
                ->sum('total_amount'), 2);
        }

        return view('livewire.dashboard.overview', [
            'totalEmployees' => $totalEmployees,
            'onLeaveToday' => $onLeaveToday,
            'attendanceRate' => $attendanceRate,
            'latestPayroll' => $latestPayroll,
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockCount,
            'inventoryValue' => $inventoryValue,
            'totalSalesRevenue' => $totalSalesRevenue,
            'receivables' => $receivables,
            'payables' => $payables,
            'cashBalance' => $cashBalance,
            'bankBalance' => $bankBalance,
            'totalExpenses' => $totalExpenses,
            'openJobCards' => $openJobCards,
            'pendingQcJobs' => $pendingQcJobs,
            'pendingOrdersCount' => SalesOrder::whereIn('status', ['draft', 'confirmed', 'processing'])->count(),
            'openPoCount' => PurchaseOrder::whereIn('status', ['ordered', 'partially_received'])->count(),
            'departments' => $departments,
            'recentInvoices' => $recentInvoices,
            'recentJobCards' => $recentJobCards,
            'lowStockItems' => $lowStockItems,
            'chartMonths' => $months,
            'revenueSeries' => $revenueSeries,
            'expenseSeries' => $expenseSeries,
        ]);
    }
}
