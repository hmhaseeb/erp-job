<!-- Search Modal -->
<div id="search_modal" class="hs-overlay hs-overlay-open:opacity-100 hs-overlay-open:duration-500 hidden size-full fixed top-0 start-0 z-80 opacity-0 overflow-x-hidden transition-all overflow-y-auto pointer-events-none no-print" role="dialog" tabindex="-1">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-[630px] w-full mx-auto">
            <div class="flex flex-col bg-white border p-6 border-border-color rounded-lg pointer-events-auto m-5">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-lg font-bold text-title mb-0">Quick Jump</p>
                    <button type="button" class="size-8 inline-flex justify-center items-center rounded-md border border-border-color bg-white text-gray-900 text-base hover:bg-danger hover:border-danger hover:text-white cursor-pointer" aria-label="Close" data-hs-overlay="#search_modal">
                        <i class="icon-x"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase text-default mb-2">ERP Modules</p>
                    <div class="flex items-center flex-wrap gap-2">
                        @canModule('hrm', 'view')<a href="{{ route('hrm.employees') }}" class="py-1.5 px-3 bg-light border border-border-color rounded-md text-xs font-medium text-title hover:bg-primary hover:border-primary hover:text-white">Employees</a>@endcanModule
                        @canModule('inventory', 'view')<a href="{{ route('inventory.products') }}" class="py-1.5 px-3 bg-light border border-border-color rounded-md text-xs font-medium text-title hover:bg-primary hover:border-primary hover:text-white">Products & Stock</a>@endcanModule
                        @canModule('sales', 'view')<a href="{{ route('sales.invoices') }}" class="py-1.5 px-3 bg-light border border-border-color rounded-md text-xs font-medium text-title hover:bg-primary hover:border-primary hover:text-white">Sales Invoices</a>@endcanModule
                        @canModule('purchase', 'view')<a href="{{ route('purchase.orders') }}" class="py-1.5 px-3 bg-light border border-border-color rounded-md text-xs font-medium text-title hover:bg-primary hover:border-primary hover:text-white">Purchase Orders</a>@endcanModule
                        @canModule('finance', 'view')<a href="{{ route('finance.reports') }}" class="py-1.5 px-3 bg-light border border-border-color rounded-md text-xs font-medium text-title hover:bg-primary hover:border-primary hover:text-white">P&amp;L / Balance Sheet</a>@endcanModule
                        @canModule('job_cards', 'view')<a href="{{ route('job-cards.index') }}" class="py-1.5 px-3 bg-light border border-border-color rounded-md text-xs font-medium text-title hover:bg-primary hover:border-primary hover:text-white">Job Cards</a>@endcanModule
                    </div>
                </div>
                <p class="text-xs font-semibold uppercase text-default mb-2">Direct Workflows</p>
                <div class="space-y-1">
                    @canModule('settings', 'view')<a href="{{ route('settings.index') }}" class="flex items-center gap-2 rounded-md text-title text-sm py-1.5 px-2.5 hover:bg-light"><i class="icon icon-cog text-base text-default"></i>Company &amp; Currency Settings</a>@endcanModule
                    @canModule('hrm', 'view')<a href="{{ route('hrm.payroll') }}" class="flex items-center gap-2 rounded-md text-title text-sm py-1.5 px-2.5 hover:bg-light"><i class="ph-duotone ph-money-wavy text-base text-default"></i>Process Monthly Payroll</a>@endcanModule
                    @canModule('inventory', 'view')<a href="{{ route('inventory.low-stock') }}" class="flex items-center gap-2 rounded-md text-title text-sm py-1.5 px-2.5 hover:bg-light"><i class="ph-duotone ph-warning text-base text-warning"></i>View Low Stock Alerts</a>@endcanModule
                    @canModule('finance', 'view')<a href="{{ route('finance.daybook') }}" class="flex items-center gap-2 rounded-md text-title text-sm py-1.5 px-2.5 hover:bg-light"><i class="ph-duotone ph-book-open text-base text-default"></i>Daybook, Cash &amp; Bank Book</a>@endcanModule
                </div>
            </div>
        </div>
    </div>
</div>
