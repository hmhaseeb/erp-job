@php
    $user = auth()->user();
    $lowStockProducts = collect();
    $pendingLeaves = collect();
    $openJobCardsCount = 0;

    try {
        $lowStockProducts = \App\Models\Product::query()
            ->where('is_service', false)
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->limit(4)
            ->get();
        $pendingLeaves = \App\Models\LeaveRequest::with('employee')
            ->where('status', 'pending')
            ->latest()
            ->limit(3)
            ->get();
        $openJobCardsCount = \App\Models\JobCard::whereNotIn('status', ['delivered', 'cancelled'])->count();
    } catch (\Throwable $e) {
    }
    $alertCount = $lowStockProducts->count() + $pendingLeaves->count();
@endphp

<!-- Topbar Start -->
<header class="navbar-header flex items-center max-lg:w-full no-print">
    <div class="topbar-menu flex items-center justify-between w-full gap-2">
        <div class="flex items-center gap-3">
            <!-- Sidebar Mobile Button -->
            <a id="mobile_btn" class="mobile-btn lg:hidden" href="#sidebar" aria-label="menu">
                <i class="icon icon-menu"></i>
            </a>

            <!-- Mobile Logo -->
            <a href="{{ route('dashboard') }}" class="logo">
                <span class="logo-light">
                    <span class="logo-lg"><img src="{{ erp_logo('light') }}" alt="logo" class="h-8"></span>
                    <span class="logo-sm"><img src="{{ erp_logo('small') }}" alt="small logo" class="h-7"></span>
                </span>
                <span class="logo-dark">
                    <span class="logo-lg"><img src="{{ erp_logo('dark') }}" alt="dark logo" class="h-8"></span>
                </span>
            </a>

            <button class="sidenav-toggle-btn topbar-link shrink-0 size-9 text-[20px] items-center justify-center rounded-full" id="toggle_btn2">
                <i class="ph-duotone ph-arrow-left"></i>
            </button>
        </div>

        <div class="flex items-center gap-2 lg:w-full">
            <!-- Active Company Pill -->
            <div class="header-item hidden lg:flex relative company-dropdown me-auto">
                <a href="{{ route('settings.index') }}" class="bg-white border border-border-color rounded-md py-[6px] px-2.5 flex items-center justify-between gap-2 hover:bg-light">
                    <div class="flex items-center gap-2">
                        <div class="bg-dark size-5 rounded-md flex items-center justify-center">
                            <img src="{{ asset('assets/img/icons/company.svg') }}" alt="company">
                        </div>
                        <span class="text-[13px] font-medium text-title">{{ erp_setting('trade_name', 'Dreams ERP') }}</span>
                        <span class="text-[11px] bg-primary-transparent text-primary px-1.5 py-0.5 rounded">{{ erp_setting('currency_code', 'USD') }} · FY {{ erp_setting('fiscal_year_label', '2026-2027') }}</span>
                    </div>
                </a>
            </div>

            <!-- Quick New Action -->
            <div class="header-item hidden md:flex gap-1.5">
                @canModule('sales', 'create')
                <a href="{{ route('sales.invoices') }}" class="btn-sm bg-light border border-border-color text-title hover:bg-primary hover:text-white hover:border-primary inline-flex items-center gap-1">
                    <i class="ph ph-receipt"></i><span>New Invoice</span>
                </a>
                @endcanModule
                @canModule('job_cards', 'create')
                <a href="{{ route('job-cards.index') }}" class="btn-sm bg-light border border-border-color text-title hover:bg-primary hover:text-white hover:border-primary inline-flex items-center gap-1">
                    <i class="ph ph-wrench"></i><span>Jobs ({{ $openJobCardsCount }})</span>
                </a>
                @endcanModule
            </div>

            <!-- Light/Dark Mode Button -->
            <div class="header-item">
                <button class="topbar-link items-center justify-center light-dark-mode" type="button" title="Toggle Dark/Light Mode">
                    <i class="ph-duotone ph-moon"></i>
                </button>
            </div>

            <!-- Search Modal Trigger -->
            <div class="header-item">
                <button type="button" class="topbar-link items-center justify-center" data-hs-overlay="#search_modal" aria-label="Search">
                    <i class="ph-duotone ph-magnifying-glass"></i>
                </button>
            </div>

            <!-- Notifications Dropdown -->
            <div class="header-item hs-dropdown [--placement:bottom-right] [--auto-close:inside] hidden relative sm:flex" id="notification-dropdown">
                <button type="button" class="hs-dropdown-toggle bg-light border border-border-color rounded-md size-8 flex items-center justify-center relative cursor-pointer">
                    <i class="ph-duotone ph-bell"></i>
                    @if ($alertCount > 0)
                        <span class="absolute top-1 end-1 size-2 bg-danger-transparent rounded-full flex items-center justify-center">
                            <span class="size-1.5 bg-danger rounded-full"></span>
                        </span>
                    @endif
                </button>
                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-80 sm:min-w-96 p-4 mt-2 bg-white border border-border-color shadow rounded-lg z-50 notification-dropdown" role="menu">
                    <div class="flex items-center justify-between border-b border-border-color pb-3">
                        <p class="text-sm font-bold text-title mb-0">ERP Alerts ({{ $alertCount }})</p>
                        <button type="button" class="size-7 inline-flex justify-center items-center rounded-md border border-border-color bg-white text-gray-900 hover:bg-light cursor-pointer" id="close-notification">
                            <i class="icon-x text-xs"></i>
                        </button>
                    </div>
                    <div class="py-3 space-y-2.5 max-h-80 overflow-y-auto">
                        @foreach ($lowStockProducts as $prod)
                            <a href="{{ route('inventory.low-stock') }}" class="flex items-center justify-between gap-2 p-2 rounded-md hover:bg-light">
                                <div class="flex items-center gap-2.5">
                                    <div class="size-8 shrink-0 bg-warning-transparent text-warning rounded-full flex items-center justify-center">
                                        <i class="ph-duotone ph-warning"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-title mb-0">Low Stock: {{ $prod->name }}</p>
                                        <p class="text-[11px] text-default mb-0">Qty {{ $prod->stock_quantity }} (Reorder ≤ {{ $prod->reorder_level }})</p>
                                    </div>
                                </div>
                                <span class="text-[10px] bg-warning-transparent text-warning px-1.5 py-0.5 rounded">Alert</span>
                            </a>
                        @endforeach

                        @foreach ($pendingLeaves as $leave)
                            <a href="{{ route('hrm.leaves') }}" class="flex items-center justify-between gap-2 p-2 rounded-md hover:bg-light">
                                <div class="flex items-center gap-2.5">
                                    <div class="size-8 shrink-0 bg-info-transparent text-info rounded-full flex items-center justify-center">
                                        <i class="ph-duotone ph-calendar-plus"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-title mb-0">Leave Request: {{ $leave->employee?->full_name }}</p>
                                        <p class="text-[11px] text-default mb-0">{{ $leave->total_days }} day(s) starting {{ format_date($leave->start_date) }}</p>
                                    </div>
                                </div>
                                <span class="text-[10px] bg-info-transparent text-info px-1.5 py-0.5 rounded">Pending</span>
                            </a>
                        @endforeach

                        @if ($alertCount === 0)
                            <p class="text-xs text-default text-center py-4 mb-0">All operations running smoothly — no urgent alerts.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="profile-dropdown hs-dropdown [--placement:bottom-right] [--auto-close:inside] relative">
                <a href="#" class="hs-dropdown-toggle relative flex items-center justify-center rounded-full lg:ms-2" aria-haspopup="menu" aria-expanded="false">
                    <div class="size-8 rounded-full overflow-hidden border border-border-color bg-primary-transparent flex items-center justify-center text-primary font-bold text-xs">
                        @if ($user?->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                            <img src="{{ asset('storage/'.$user->avatar) }}" class="size-full object-cover" alt="{{ $user->name }}">
                        @else
                            {{ strtoupper(substr($user?->name ?? 'A', 0, 2)) }}
                        @endif
                    </div>
                </a>
                <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-52 p-4 bg-white border border-border-color shadow rounded-md mt-2 z-50 divide-y divide-border-color" role="menu">
                    <div class="pb-3">
                        <p class="font-semibold text-title text-sm mb-0.5">{{ $user?->name }}</p>
                        <p class="text-xs text-default mb-0">{{ $user?->role?->name ?? ucfirst($user?->user_type ?? 'User') }}</p>
                    </div>
                    <div class="py-2 space-y-1">
                        <a href="{{ route('profile') }}" class="flex items-center px-2 py-1.5 rounded-md text-xs text-gray-900 hover:bg-light">
                            <i class="icon icon-user-round text-sm me-2"></i>My Profile & Password
                        </a>
                        @canModule('settings', 'view')
                        <a href="{{ route('settings.index') }}" class="flex items-center px-2 py-1.5 rounded-md text-xs text-gray-900 hover:bg-light">
                            <i class="icon icon-cog text-sm me-2"></i>Company Settings
                        </a>
                        @endcanModule
                        @canModule('users', 'view')
                        <a href="{{ route('users.logs') }}" class="flex items-center px-2 py-1.5 rounded-md text-xs text-gray-900 hover:bg-light">
                            <i class="ph-duotone ph-clock-counter-clockwise text-sm me-2"></i>Activity Logs
                        </a>
                        @endcanModule
                    </div>
                    <div class="pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center px-2 py-1.5 rounded-md text-xs text-danger hover:bg-light cursor-pointer">
                                <i class="icon icon-log-out text-sm me-2"></i>Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Topbar End -->
