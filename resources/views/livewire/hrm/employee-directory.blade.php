<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Employees Directory</h1>
            <p class="text-sm text-default mb-0">Employee registration, profile management, compensation, and user login linking.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2">
            <div class="inline-flex bg-white border border-border-color rounded-md p-1">
                <button type="button" wire:click="$set('viewMode', 'list')" class="btn-sm {{ $viewMode === 'list' ? 'bg-dark text-white' : 'bg-white text-gray-900' }}"><i class="ph-duotone ph-rows"></i></button>
                <button type="button" wire:click="$set('viewMode', 'grid')" class="btn-sm {{ $viewMode === 'grid' ? 'bg-dark text-white' : 'bg-white text-gray-900' }}"><i class="ph-duotone ph-grid-four"></i></button>
            </div>
            <button type="button" wire:click="exportCsv" class="btn-sm bg-white border border-border-color text-gray-900 inline-flex items-center gap-1.5 hover:bg-light"><i class="icon-download"></i> Export CSV</button>
            @canModule('hrm', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white border border-dark inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Register Employee</button>
            @endcanModule
        </div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search code, name, email..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-64">
            <select wire:model.live="departmentFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Departments</option>
                @foreach ($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="lifecycleFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Lifecycle Stages</option>
                @foreach (['onboarding', 'probation', 'confirmed', 'resigned', 'terminated', 'exited'] as $st)
                    <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>

        @if ($viewMode === 'grid')
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
            @foreach ($employees as $emp)
                <div class="border border-border-color rounded-md p-4 bg-light/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="size-10 rounded-full bg-primary-transparent text-primary font-bold flex items-center justify-center overflow-hidden">
                                    @if ($emp->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($emp->avatar))
                                        <img src="{{ asset('storage/'.$emp->avatar) }}" class="size-full object-cover" alt="{{ $emp->full_name }}">
                                    @else
                                        {{ strtoupper(substr($emp->first_name, 0, 1).substr($emp->last_name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('hrm.employees.show', $emp->id) }}" class="text-sm font-bold text-title hover:text-primary">{{ $emp->full_name }}</a>
                                    <p class="text-xs text-default mb-0">{{ $emp->emp_code }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] px-2 py-0.5 rounded {{ $emp->lifecycle_status === 'confirmed' ? 'bg-success-transparent text-success' : 'bg-info-transparent text-info' }}">
                                {{ ucfirst($emp->lifecycle_status) }}
                            </span>
                        </div>
                        <div class="text-xs text-default space-y-1 mb-3">
                            <div><strong>Dept:</strong> {{ $emp->department?->name ?? '—' }}</div>
                            <div><strong>Role:</strong> {{ $emp->designation?->name ?? '—' }}</div>
                            <div><strong>Gross Pay:</strong> {{ format_currency($emp->gross_salary) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-border-color">
                        <a href="{{ route('hrm.employees.show', $emp->id) }}" class="btn-xsmall bg-dark text-white">Profile &amp; Lifecycle</a>
                        <button type="button" wire:click="openEdit({{ $emp->id }})" class="btn-xsmall bg-white border border-border-color text-title">Edit</button>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-default border-b border-border-color bg-light">
                        <th class="text-left py-2.5 px-3">Emp ID</th>
                        <th class="text-left py-2.5 px-3">Employee</th>
                        <th class="text-left py-2.5 px-3">Department / Designation</th>
                        <th class="text-left py-2.5 px-3">Lifecycle</th>
                        <th class="text-right py-2.5 px-3">Gross Salary</th>
                        <th class="text-left py-2.5 px-3">Login</th>
                        <th class="text-right py-2.5 px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $emp)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3 font-medium text-primary">
                                <a href="{{ route('hrm.employees.show', $emp->id) }}">{{ $emp->emp_code }}</a>
                            </td>
                            <td class="py-2.5 px-3">
                                <a href="{{ route('hrm.employees.show', $emp->id) }}" class="font-semibold text-title hover:text-primary">{{ $emp->full_name }}</a>
                                <div class="text-xs text-default">{{ $emp->email }}</div>
                            </td>
                            <td class="py-2.5 px-3 text-xs">
                                <div class="font-medium text-title">{{ $emp->department?->name ?? '—' }}</div>
                                <div class="text-default">{{ $emp->designation?->name ?? '—' }}</div>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded {{ $emp->lifecycle_status === 'confirmed' ? 'bg-success-transparent text-success' : ($emp->lifecycle_status === 'probation' ? 'bg-warning-transparent text-warning' : 'bg-info-transparent text-info') }}">
                                    {{ ucwords(str_replace('_', ' ', $emp->lifecycle_status)) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">{{ format_currency($emp->gross_salary) }}</td>
                            <td class="py-2.5 px-3 text-xs">
                                @if ($emp->user)
                                    <span class="bg-success-transparent text-success px-2 py-0.5 rounded">Linked</span>
                                @else
                                    <span class="bg-light text-default px-2 py-0.5 rounded">No Login</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1">
                                <a href="{{ route('hrm.employees.show', $emp->id) }}" class="btn-xsmall bg-primary text-white">Profile</a>
                                @canModule('hrm', 'edit')
                                <button type="button" wire:click="openEdit({{ $emp->id }})" class="btn-xsmall bg-light border border-border-color text-title">Edit</button>
                                @endcanModule
                                @canModule('hrm', 'delete')
                                <button type="button" wire:click="deleteEmployee({{ $emp->id }})" wire:confirm="Delete employee {{ $emp->emp_code }}?" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-default">No employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <div class="mt-4">{{ $employees->links() }}</div>
    </div>

    @if ($showModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-3xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">{{ $editingId ? 'Edit Employee' : 'Register New Employee' }}</h2>
                <button type="button" wire:click="$set('showModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="save" class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div><label class="text-xs font-semibold block mb-1">Employee Code *</label><input type="text" wire:model="emp_code" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">First Name *</label><input type="text" wire:model="first_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('first_name')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Last Name *</label><input type="text" wire:model="last_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('last_name')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>

                <div><label class="text-xs font-semibold block mb-1">Email *</label><input type="email" wire:model="email" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('email')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Phone</label><input type="text" wire:model="phone" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Gender</label><select wire:model="gender" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option>Male</option><option>Female</option><option>Other</option></select></div>

                <div><label class="text-xs font-semibold block mb-1">Department</label><select wire:model="department_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
                <div><label class="text-xs font-semibold block mb-1">Designation</label><select wire:model="designation_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="">Select</option>@foreach($designations as $dg)<option value="{{ $dg->id }}">{{ $dg->name }}</option>@endforeach</select></div>
                <div><label class="text-xs font-semibold block mb-1">Lifecycle Stage</label><select wire:model="lifecycle_status" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="onboarding">Onboarding</option><option value="probation">Probation</option><option value="confirmed">Confirmed</option></select></div>

                <div><label class="text-xs font-semibold block mb-1">Join Date</label><input type="date" wire:model="join_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Probation End Date</label><input type="date" wire:model="probation_end_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Shift</label><select wire:model="shift" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option>Day</option><option>Night</option></select></div>

                <div><label class="text-xs font-semibold block mb-1">Basic Salary *</label><input type="number" step="0.01" wire:model="basic_salary" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Allowances</label><input type="number" step="0.01" wire:model="allowances" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Status</label><select wire:model="status" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>

                <div><label class="text-xs font-semibold block mb-1">Bank Name</label><input type="text" wire:model="bank_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Bank Account No.</label><input type="text" wire:model="bank_account_no" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Photo</label><input type="file" wire:model="avatarUpload" class="text-xs" accept="image/*"></div>

                @if (! $editingId)
                <div class="sm:col-span-3 border-t border-border-color pt-3">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-title mb-2">
                        <input type="checkbox" wire:model.live="createUserLogin" class="size-4"> Create ERP User Login Account
                    </label>
                    @if ($createUserLogin)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                            <div><label class="text-xs font-semibold block mb-1">Assign Role</label><select wire:model="login_role_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@foreach($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
                            <div><label class="text-xs font-semibold block mb-1">Initial Password</label><input type="text" wire:model="login_password" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                        </div>
                    @endif
                </div>
                @endif

                <div class="sm:col-span-3 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-dark text-white border border-dark hover:bg-primary-hover">Save Employee</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
