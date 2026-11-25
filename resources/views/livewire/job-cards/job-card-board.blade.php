<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">{{ $mode === 'board' ? 'Workshop Progress & QC Board' : 'Job Card & Service Reports' }}</h1>
            <p class="text-sm text-default mb-0">Live service pipeline stages, technician workload, turnaround metrics, and billing totals.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <a href="{{ route('job-cards.index') }}" class="btn-sm bg-white border border-border-color text-title">Registry</a>
            <a href="{{ route('job-cards.board') }}" class="btn-sm {{ $mode === 'board' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Workshop Board</a>
            <a href="{{ route('job-cards.reports') }}" class="btn-sm {{ $mode === 'reports' ? 'bg-dark text-white' : 'bg-white border border-border-color text-title' }}">Service Reports</a>
        </div>
    </div>

    @if ($mode === 'board')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ($stages as $stageKey => $stageLabel)
            @php $cards = $boardCards->get($stageKey, collect()); @endphp
            <div class="bg-light border border-border-color rounded-md p-3">
                <div class="flex justify-between items-center mb-3 pb-2 border-b border-border-color">
                    <h2 class="text-xs font-bold uppercase text-title mb-0">{{ $stageLabel }}</h2>
                    <span class="text-xs px-2 py-0.5 rounded bg-white border border-border-color font-bold">{{ $cards->count() }}</span>
                </div>
                <div class="space-y-2.5">
                    @forelse ($cards as $card)
                        <div class="bg-white border border-border-color rounded-md p-3 shadow-xs">
                            <div class="flex justify-between items-center mb-1">
                                <a href="{{ route('job-cards.show', $card->id) }}" class="text-xs font-bold text-primary hover:underline">{{ $card->job_card_no }}</a>
                                <span class="text-[10px] px-1.5 py-0.5 rounded capitalize bg-info-transparent text-info">{{ $card->priority }}</span>
                            </div>
                            <h3 class="text-sm font-semibold text-title mb-0.5">{{ $card->item_name }}</h3>
                            <p class="text-xs text-default mb-1.5">{{ $card->customer?->name }} · {{ $card->technician?->full_name ?? 'Unassigned' }}</p>
                            <div class="flex items-center justify-between gap-2 pt-2 border-t border-border-color">
                                <span class="text-[11px] font-semibold">{{ $card->progress_percent }}%</span>
                                <select onchange="@this.moveStage({{ $card->id }}, this.value)" class="text-[11px] px-1.5 py-1 border border-border-color rounded bg-white">
                                    @foreach($stages as $sk => $sl)
                                        <option value="{{ $sk }}" @selected($card->status === $sk)>{{ $sl }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-default text-center py-4 mb-0">Empty stage</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    @else
    <div class="space-y-4">
        <div class="bg-white border border-border-color rounded-md p-4 flex flex-wrap items-end justify-between gap-3 no-print">
            <div class="flex flex-wrap items-end gap-3">
                <div><label class="text-xs font-semibold block mb-1">From Date</label><input type="date" wire:model.live="fromDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">To Date</label><input type="date" wire:model.live="toDate" class="px-3 py-1.5 text-sm border border-border-color rounded-md"></div>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="window.print()" class="btn-sm bg-white border border-border-color text-title"><i class="ph-duotone ph-printer me-1"></i> Print</button>
                <button type="button" wire:click="exportReportsCsv" class="btn-sm bg-dark text-white"><i class="icon-download me-1"></i> Export CSV</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Jobs Received</p><h3 class="text-xl font-bold text-gray-900 mb-0">{{ $reportCards->count() }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Completed / Delivered</p><h3 class="text-xl font-bold text-success mb-0">{{ $reportCards->whereIn('status', ['completed','delivered'])->count() }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">QC Passed</p><h3 class="text-xl font-bold text-info mb-0">{{ $reportCards->where('qc_status', 'passed')->count() }}</h3></div>
            <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Service Billing Revenue</p><h3 class="text-xl font-bold text-primary mb-0">{{ format_currency($serviceRevenue) }}</h3></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Job Card History Register</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-2">Job Card #</th>
                                <th class="text-left py-2 px-2">Customer / Device</th>
                                <th class="text-left py-2 px-2">Technician</th>
                                <th class="text-left py-2 px-2">Status</th>
                                <th class="text-right py-2 px-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($reportCards as $jc)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-2 font-semibold text-primary">{{ $jc->job_card_no }}</td>
                                    <td class="py-2 px-2"><div>{{ $jc->customer?->name }}</div><div class="text-xs text-default">{{ $jc->item_name }}</div></td>
                                    <td class="py-2 px-2 text-xs">{{ $jc->technician?->full_name ?? 'Unassigned' }}</td>
                                    <td class="py-2 px-2 text-xs capitalize">{{ str_replace('_', ' ', $jc->status) }}</td>
                                    <td class="py-2 px-2 text-right font-semibold">{{ format_currency($jc->total_amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-default">No job cards in period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Technician Performance</h2>
                <div class="space-y-2">
                    @forelse ($techStats as $ts)
                        <div class="flex justify-between items-center border-b border-border-color pb-2 last:border-0">
                            <div><div class="font-semibold text-title text-sm">{{ $ts->tech_name }}</div><div class="text-xs text-default">{{ $ts->total_jobs }} jobs</div></div>
                            <div class="font-bold text-primary text-sm">{{ format_currency($ts->total_billed) }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-default">No technician activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
