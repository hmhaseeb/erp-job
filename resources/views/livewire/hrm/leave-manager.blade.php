<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Employee Leave Management</h1>
            <p class="text-sm text-default mb-0">Apply, approve/reject leave requests and manage paid/unpaid leave policies.</p>
        </div>
        <button type="button" wire:click="$set('showLeaveModal', true)" class="btn-sm bg-dark text-white hover:bg-primary-hover">+ Apply Leave</button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 bg-white border border-border-color rounded-md p-4 overflow-x-auto">
            <h2 class="text-base font-bold text-title mb-3">Leave Requests</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2 px-3">Employee</th>
                        <th class="text-left py-2 px-3">Leave Type</th>
                        <th class="text-left py-2 px-3">Dates</th>
                        <th class="text-left py-2 px-3">Days</th>
                        <th class="text-left py-2 px-3">Status</th>
                        <th class="text-right py-2 px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaves as $lv)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-semibold text-title">{{ $lv->employee?->full_name }}</td>
                            <td class="py-2.5 px-3 text-default">{{ $lv->leaveType?->name }}</td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ format_date($lv->start_date) }} → {{ format_date($lv->end_date) }}</td>
                            <td class="py-2.5 px-3 text-default">{{ $lv->total_days }}</td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded {{ $lv->status === 'approved' ? 'bg-success-transparent text-success' : ($lv->status === 'rejected' ? 'bg-danger-transparent text-danger' : 'bg-warning-transparent text-warning') }}">
                                    {{ ucfirst($lv->status) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1">
                                @if ($lv->status === 'pending')
                                    <button type="button" wire:click="updateStatus({{ $lv->id }}, 'approved')" class="btn-xsmall bg-success text-white">Approve</button>
                                    <button type="button" wire:click="updateStatus({{ $lv->id }}, 'rejected')" class="btn-xsmall bg-danger-transparent text-danger">Reject</button>
                                @else
                                    <span class="text-xs text-default">By {{ $lv->approver?->name ?? 'Admin' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-default">No leave requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="lg:col-span-4 bg-white border border-border-color rounded-md p-4 space-y-3">
            <h2 class="text-base font-bold text-title mb-2">Leave Types</h2>
            <div class="space-y-2">
                <input type="text" wire:model="lt_name" placeholder="Leave Type Name" class="w-full px-3 py-1.5 text-sm border border-border-color rounded-md">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" wire:model="lt_code" placeholder="Code (CL)" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                    <input type="number" wire:model="lt_days" placeholder="Days/Yr" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                </div>
                <label class="flex items-center gap-2 text-xs"><input type="checkbox" wire:model="lt_paid" class="size-4"> Paid Leave</label>
                <button type="button" wire:click="addLeaveType" class="btn-sm w-full bg-dark text-white">Add Leave Type</button>
            </div>
            <div class="border-t border-border-color pt-3 space-y-2">
                @foreach ($leaveTypes as $lt)
                    <div class="flex items-center justify-between text-xs p-2 bg-light rounded">
                        <span><strong>{{ $lt->name }}</strong> ({{ $lt->code }})</span>
                        <span>{{ $lt->days_per_year }}d/yr · {{ $lt->is_paid ? 'Paid' : 'Unpaid' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if ($showLeaveModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md p-5 space-y-3">
            <h3 class="text-base font-bold text-title mb-0">Submit Leave Request</h3>
            <div><label class="text-xs font-semibold block mb-1">Employee *</label><select wire:model="employee_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}</option>@endforeach</select></div>
            <div><label class="text-xs font-semibold block mb-1">Leave Type *</label><select wire:model="leave_type_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($leaveTypes as $lt)<option value="{{ $lt->id }}">{{ $lt->name }}</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="text-xs font-semibold block mb-1">Start Date *</label><input type="date" wire:model="start_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">End Date *</label><input type="date" wire:model="end_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
            </div>
            <div><label class="text-xs font-semibold block mb-1">Reason</label><textarea wire:model="reason" rows="2" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></textarea></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" wire:click="$set('showLeaveModal', false)" class="btn-sm bg-white border border-border-color">Cancel</button>
                <button type="button" wire:click="applyLeave" class="btn-sm bg-dark text-white">Submit Request</button>
            </div>
        </div>
    </div>
    @endif
</div>
