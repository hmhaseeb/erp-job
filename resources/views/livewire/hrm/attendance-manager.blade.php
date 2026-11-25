<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Attendance Management</h1>
            <p class="text-sm text-default mb-0">Daily staff check-in/out register and work-hour tracking.</p>
        </div>
        <div class="flex items-center gap-2">
            <input type="date" wire:model.live="date" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
            <button type="button" wire:click="markAllPresent" class="btn-sm bg-white border border-border-color text-title hover:bg-light">Mark All Present</button>
            <button type="button" wire:click="saveAttendance" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save Attendance</button>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-3"><p class="text-xs text-default mb-1">Present</p><h3 class="text-xl font-bold text-success mb-0">{{ $summary['present'] }}</h3></div>
        <div class="bg-white border border-border-color rounded-md p-3"><p class="text-xs text-default mb-1">Late</p><h3 class="text-xl font-bold text-warning mb-0">{{ $summary['late'] }}</h3></div>
        <div class="bg-white border border-border-color rounded-md p-3"><p class="text-xs text-default mb-1">Remote</p><h3 class="text-xl font-bold text-info mb-0">{{ $summary['remote'] }}</h3></div>
        <div class="bg-white border border-border-color rounded-md p-3"><p class="text-xs text-default mb-1">Absent</p><h3 class="text-xl font-bold text-danger mb-0">{{ $summary['absent'] }}</h3></div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs bg-light border-b border-border-color">
                    <th class="text-left py-2.5 px-3">Employee</th>
                    <th class="text-left py-2.5 px-3">Department</th>
                    <th class="text-left py-2.5 px-3">Status</th>
                    <th class="text-left py-2.5 px-3">Check In</th>
                    <th class="text-left py-2.5 px-3">Check Out</th>
                    <th class="text-left py-2.5 px-3">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($employees as $emp)
                    <tr class="border-b border-border-color last:border-0">
                        <td class="py-2 px-3 font-semibold text-title">{{ $emp->full_name }} <span class="text-xs text-default font-normal">({{ $emp->emp_code }})</span></td>
                        <td class="py-2 px-3 text-default">{{ $emp->department?->name ?? '—' }}</td>
                        <td class="py-2 px-3">
                            <select wire:model="entries.{{ $emp->id }}.status" class="px-2 py-1 text-xs border border-border-color rounded bg-white">
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="remote">Remote</option>
                                <option value="half_day">Half Day</option>
                                <option value="on_leave">On Leave</option>
                                <option value="absent">Absent</option>
                            </select>
                        </td>
                        <td class="py-2 px-3"><input type="time" wire:model="entries.{{ $emp->id }}.check_in" class="px-2 py-1 text-xs border border-border-color rounded bg-white"></td>
                        <td class="py-2 px-3"><input type="time" wire:model="entries.{{ $emp->id }}.check_out" class="px-2 py-1 text-xs border border-border-color rounded bg-white"></td>
                        <td class="py-2 px-3"><input type="text" wire:model="entries.{{ $emp->id }}.notes" placeholder="Optional note" class="px-2 py-1 text-xs border border-border-color rounded bg-white w-full"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
