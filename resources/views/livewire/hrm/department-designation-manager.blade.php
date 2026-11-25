<div>
    <div class="mb-4">
        <h1 class="text-gray-900 text-xl font-bold mb-1">Departments &amp; Designations</h1>
        <p class="text-sm text-default mb-0">Define organizational departments and staff designations.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Departments -->
        <div class="bg-white border border-border-color rounded-md p-5 space-y-4">
            <h2 class="text-base font-bold text-title mb-0">Departments</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <input type="text" wire:model="dept_name" placeholder="Department Name" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                <input type="text" wire:model="dept_code" placeholder="Code (ENG)" class="px-3 py-1.5 text-sm border border-border-color rounded-md">
                <button type="button" wire:click="saveDepartment" class="btn-sm bg-dark text-white">{{ $editingDeptId ? 'Update' : 'Add Dept' }}</button>
            </div>
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-2">Name</th><th class="text-left py-2 px-2">Code</th><th class="text-left py-2 px-2">Staff</th><th class="text-right py-2 px-2">Actions</th></tr></thead>
                <tbody>
                    @foreach ($departments as $d)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-2 font-medium text-title">{{ $d->name }}</td>
                            <td class="py-2 px-2 text-default">{{ $d->code }}</td>
                            <td class="py-2 px-2 text-default">{{ $d->employees_count }}</td>
                            <td class="py-2 px-2 text-right space-x-1">
                                <button type="button" wire:click="editDepartment({{ $d->id }})" class="btn-xsmall bg-light border border-border-color">Edit</button>
                                <button type="button" wire:click="deleteDepartment({{ $d->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Designations -->
        <div class="bg-white border border-border-color rounded-md p-5 space-y-4">
            <h2 class="text-base font-bold text-title mb-0">Designations</h2>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                <input type="text" wire:model="desig_name" placeholder="Title" class="px-3 py-1.5 text-sm border border-border-color rounded-md sm:col-span-2">
                <select wire:model="desig_dept_id" class="px-3 py-1.5 text-sm border border-border-color rounded-md"><option value="">Dept</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->code }}</option>@endforeach</select>
                <button type="button" wire:click="saveDesignation" class="btn-sm bg-dark text-white">{{ $editingDesigId ? 'Update' : 'Add Title' }}</button>
            </div>
            <table class="w-full text-sm">
                <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-2">Designation</th><th class="text-left py-2 px-2">Department</th><th class="text-left py-2 px-2">Staff</th><th class="text-right py-2 px-2">Actions</th></tr></thead>
                <tbody>
                    @foreach ($designations as $dg)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2 px-2 font-medium text-title">{{ $dg->name }}</td>
                            <td class="py-2 px-2 text-default">{{ $dg->department?->name ?? 'All' }}</td>
                            <td class="py-2 px-2 text-default">{{ $dg->employees_count }}</td>
                            <td class="py-2 px-2 text-right space-x-1">
                                <button type="button" wire:click="editDesignation({{ $dg->id }})" class="btn-xsmall bg-light border border-border-color">Edit</button>
                                <button type="button" wire:click="deleteDesignation({{ $dg->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
