<div>
    <div class="mb-4">
        <h1 class="text-gray-900 text-xl font-bold mb-1">Employee Lifecycle Management</h1>
        <p class="text-sm text-default mb-0">Onboarding, probation, confirmation, department/designation assignment, transfer, promotion, salary revision, resignation, termination &amp; exit.</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
        @foreach (['onboarding' => 'Onboarding', 'probation' => 'Probation', 'confirmed' => 'Confirmed', 'resigned' => 'Resigned', 'exited' => 'Terminated / Exited'] as $k => $lbl)
            <div class="bg-white border border-border-color rounded-md p-3">
                <p class="text-xs text-default mb-1">{{ $lbl }}</p>
                <h3 class="text-xl font-bold text-gray-900 mb-0">{{ $counts[$k] ?? 0 }}</h3>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-5 bg-white border border-border-color rounded-md p-5">
            <h2 class="text-base font-bold text-title mb-3">Record Lifecycle Transition</h2>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-semibold block mb-1">Select Employee *</label>
                    <select wire:model.live="selectedEmployeeId" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Choose employee...</option>
                        @foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->emp_code }} - {{ $e->full_name }} ({{ ucfirst($e->lifecycle_status) }})</option>@endforeach
                    </select>
                    @error('selectedEmployeeId')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Lifecycle Action *</label>
                    <select wire:model.live="event_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="probation">Move to Probation</option>
                        <option value="confirmation">Employee Confirmation</option>
                        <option value="assignment">Department &amp; Designation Assignment</option>
                        <option value="transfer">Employee Transfer</option>
                        <option value="promotion">Promotion</option>
                        <option value="salary_revision">Salary Revision</option>
                        <option value="resignation">Resignation</option>
                        <option value="termination">Termination</option>
                        <option value="exit">Exit Process Complete</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Effective Date *</label>
                    <input type="date" wire:model="effective_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                </div>
                @if (in_array($event_type, ['assignment', 'transfer', 'promotion']))
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="text-xs font-semibold block mb-1">Department</label><select wire:model="department_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                        <div><label class="text-xs font-semibold block mb-1">Designation</label><select wire:model="designation_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@foreach($designations as $dg)<option value="{{ $dg->id }}">{{ $dg->name }}</option>@endforeach</select></div>
                    </div>
                @endif
                @if (in_array($event_type, ['promotion', 'salary_revision']))
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="text-xs font-semibold block mb-1">Basic Salary</label><input type="number" step="0.01" wire:model="basic_salary" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                        <div><label class="text-xs font-semibold block mb-1">Allowances</label><input type="number" step="0.01" wire:model="allowances" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    </div>
                @endif
                <div>
                    <label class="text-xs font-semibold block mb-1">Reason / Remarks</label>
                    <input type="text" wire:model="reason" class="w-full px-3 py-2 text-sm border border-border-color rounded-md" placeholder="Notes...">
                </div>
                <button type="button" wire:click="submitTransition" class="btn-sm w-full bg-dark text-white hover:bg-primary-hover">Apply Lifecycle Event</button>
            </div>
        </div>

        <div class="lg:col-span-7 bg-white border border-border-color rounded-md p-5">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h2 class="text-base font-bold text-title mb-0">Lifecycle Audit Feed</h2>
                <select wire:model.live="stageFilter" class="px-3 py-1.5 text-xs border border-border-color rounded-md">
                    <option value="">All Event Types</option>
                    @foreach (['onboarding', 'probation', 'confirmation', 'assignment', 'transfer', 'promotion', 'salary_revision', 'resignation', 'termination', 'exit'] as $et)
                        <option value="{{ $et }}">{{ ucwords(str_replace('_', ' ', $et)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Date</th><th class="text-left py-2 px-3">Employee</th><th class="text-left py-2 px-3">Event</th><th class="text-left py-2 px-3">Change</th></tr></thead>
                    <tbody>
                        @forelse ($events as $ev)
                            <tr class="border-b border-border-color last:border-0">
                                <td class="py-2 px-3 text-xs text-default">{{ format_date($ev->effective_date) }}</td>
                                <td class="py-2 px-3 font-medium text-title"><a href="{{ route('hrm.employees.show', $ev->employee_id) }}" class="hover:text-primary">{{ $ev->employee?->full_name }}</a></td>
                                <td class="py-2 px-3"><span class="text-[11px] bg-primary-transparent text-primary px-2 py-0.5 rounded">{{ ucwords(str_replace('_', ' ', $ev->event_type)) }}</span></td>
                                <td class="py-2 px-3 text-xs text-default">{{ $ev->old_value ? "$ev->old_value → " : '' }}<strong class="text-title">{{ $ev->new_value }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-xs text-default">No lifecycle events yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
