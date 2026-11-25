<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">User Activity Logs</h1>
            <p class="text-sm text-default mb-0">Audit trail of authentication, settings changes, transactions, and workflow actions.</p>
        </div>
        <button type="button" wire:click="exportCsv" class="btn-sm bg-white border border-border-color text-gray-900 inline-flex items-center gap-1.5 hover:bg-light"><i class="icon-download"></i> Export CSV</button>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search logs..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="moduleFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Modules</option>
                @foreach ($modules as $mod)
                    <option value="{{ $mod }}">{{ ucfirst(str_replace('_', ' ', $mod)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="userFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-default bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Date &amp; Time</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">User</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Module</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Action</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">Description</th>
                        <th class="text-left py-2.5 px-3 font-semibold text-gray-900">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($log->created_at, true) }}</td>
                            <td class="py-2.5 px-3 font-medium text-title">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="py-2.5 px-3"><span class="text-[11px] bg-primary-transparent text-primary px-2 py-0.5 rounded">{{ $log->module }}</span></td>
                            <td class="py-2.5 px-3 text-xs font-semibold text-gray-900">{{ $log->action }}</td>
                            <td class="py-2.5 px-3 text-default">{{ $log->description }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-default">No activity logs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
