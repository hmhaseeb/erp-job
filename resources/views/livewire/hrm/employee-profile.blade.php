<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-default mb-1">
                <a href="{{ route('hrm.employees') }}" class="hover:text-primary">Employees</a>
                <i class="ph ph-caret-right"></i>
                <span>{{ $employee->emp_code }}</span>
            </div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">{{ $employee->full_name }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs px-2.5 py-1 rounded bg-primary-transparent text-primary font-semibold">
                Stage: {{ ucwords(str_replace('_', ' ', $employee->lifecycle_status)) }}
            </span>
            <a href="{{ route('hrm.employees') }}" class="btn-sm bg-white border border-border-color text-title">Back to Directory</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <!-- Left Profile Card -->
        <div class="lg:col-span-4 space-y-4">
            <div class="bg-white border border-border-color rounded-md p-5">
                <div class="flex items-center gap-3 mb-4">
                    <div class="size-14 rounded-full bg-primary-transparent text-primary font-bold text-lg flex items-center justify-center overflow-hidden">
                        @if ($employee->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->avatar))
                            <img src="{{ asset('storage/'.$employee->avatar) }}" class="size-full object-cover" alt="{{ $employee->full_name }}">
                        @else
                            {{ strtoupper(substr($employee->first_name, 0, 1).substr($employee->last_name, 0, 1)) }}
                        @endif
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-title mb-0">{{ $employee->full_name }}</h2>
                        <p class="text-xs text-default mb-0">{{ $employee->designation?->name ?? 'Unassigned' }} · {{ $employee->department?->name ?? '—' }}</p>
                    </div>
                </div>
                <div class="space-y-2 text-xs border-t border-border-color pt-3">
                    <div class="flex justify-between"><span class="text-default">Emp ID:</span><span class="font-semibold text-title">{{ $employee->emp_code }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Email:</span><span class="font-semibold text-title">{{ $employee->email }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Phone:</span><span class="font-semibold text-title">{{ $employee->phone ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Joined:</span><span class="font-semibold text-title">{{ format_date($employee->join_date) }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Basic Salary:</span><span class="font-semibold text-title">{{ format_currency($employee->basic_salary) }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Allowances:</span><span class="font-semibold text-title">{{ format_currency($employee->allowances) }}</span></div>
                    <div class="flex justify-between border-t border-border-color pt-2"><span class="text-default font-semibold">Gross Monthly Pay:</span><span class="font-bold text-primary">{{ format_currency($employee->gross_salary) }}</span></div>
                </div>
            </div>

            <!-- User Login & Account Management -->
            <div class="bg-white border border-border-color rounded-md p-5">
                <h3 class="text-sm font-bold text-title mb-3">User Login &amp; Account</h3>
                @if ($employee->user)
                    <div class="text-xs space-y-1 mb-3">
                        <div>Linked Email: <strong>{{ $employee->user->email }}</strong></div>
                        <div>Role: <strong>{{ $employee->user->role?->name }}</strong></div>
                        <div>Status: <span class="px-1.5 py-0.5 rounded {{ $employee->user->status === 'active' ? 'bg-success-transparent text-success' : 'bg-danger-transparent text-danger' }}">{{ ucfirst($employee->user->status) }}</span></div>
                    </div>
                @endif
                <div class="space-y-2">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Role</label>
                        <select wire:model="user_role_id" class="w-full px-3 py-1.5 text-sm border border-border-color rounded-md">
                            @foreach ($roles as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">{{ $employee->user ? 'Reset Password (optional)' : 'Initial Password' }}</label>
                        <input type="password" wire:model="user_password" placeholder="••••••••" class="w-full px-3 py-1.5 text-sm border border-border-color rounded-md">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" wire:click="syncUserAccount" class="btn-sm bg-dark text-white flex-1">{{ $employee->user ? 'Update Login' : 'Create Login' }}</button>
                        @if ($employee->user)
                            <button type="button" wire:click="toggleUserLoginStatus" class="btn-sm bg-light border border-border-color text-title">{{ $employee->user->status === 'active' ? 'Disable' : 'Enable' }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Tabs -->
        <div class="lg:col-span-8 space-y-4">
            <div class="inline-flex items-center gap-1.5 border border-border-color rounded-md p-1 bg-white flex-wrap">
                @foreach (['overview' => 'Lifecycle Action & Timeline', 'documents' => 'Employee Documents', 'payroll' => 'Payroll & Attendance'] as $tabKey => $tabLabel)
                    <button type="button" wire:click="$set('activeTab', '{{ $tabKey }}')" class="px-3 py-1.5 text-xs font-semibold rounded-md cursor-pointer {{ $activeTab === $tabKey ? 'bg-dark text-white' : 'text-title hover:bg-light' }}">{{ $tabLabel }}</button>
                @endforeach
            </div>

            @if ($activeTab === 'overview')
            <div class="bg-white border border-border-color rounded-md p-5 space-y-4">
                <h3 class="text-base font-bold text-title mb-2">Execute Employee Lifecycle Action</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold block mb-1">Lifecycle Action</label>
                        <select wire:model.live="event_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                            <option value="onboarding">Onboarding</option>
                            <option value="probation">Probation Period</option>
                            <option value="confirmation">Employee Confirmation</option>
                            <option value="assignment">Department &amp; Designation Assignment</option>
                            <option value="transfer">Employee Transfer</option>
                            <option value="promotion">Promotion</option>
                            <option value="salary_revision">Salary Revision</option>
                            <option value="resignation">Employee Resignation</option>
                            <option value="termination">Employee Termination</option>
                            <option value="exit">Employee Exit Process</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Effective Date</label>
                        <input type="date" wire:model="effective_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                    </div>

                    @if (in_array($event_type, ['assignment', 'transfer', 'promotion']))
                    <div>
                        <label class="text-xs font-semibold block mb-1">Department</label>
                        <select wire:model="new_department_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">Designation</label>
                        <select wire:model="new_designation_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@foreach($designations as $dg)<option value="{{ $dg->id }}">{{ $dg->name }}</option>@endforeach</select>
                    </div>
                    @endif

                    @if (in_array($event_type, ['promotion', 'salary_revision']))
                    <div>
                        <label class="text-xs font-semibold block mb-1">New Basic Salary</label>
                        <input type="number" step="0.01" wire:model="new_basic_salary" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-1">New Allowances</label>
                        <input type="number" step="0.01" wire:model="new_allowances" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                    </div>
                    @endif

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold block mb-1">Reason / Summary</label>
                        <input type="text" wire:model="event_reason" placeholder="Reason for transition or revision..." class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="button" wire:click="applyLifecycleAction" class="btn-sm bg-dark text-white hover:bg-primary-hover">Apply Lifecycle Update</button>
                </div>

                <div class="pt-4 border-t border-border-color">
                    <h4 class="text-sm font-bold text-title mb-3">Lifecycle History Timeline</h4>
                    <div class="space-y-2.5">
                        @forelse ($employee->lifecycleEvents as $ev)
                            <div class="border border-border-color rounded p-3 bg-light/50 flex flex-wrap justify-between gap-2 text-xs">
                                <div>
                                    <span class="font-bold text-primary uppercase">{{ str_replace('_', ' ', $ev->event_type) }}</span>
                                    <span class="text-default ms-2">{{ $ev->old_value ? "$ev->old_value → " : '' }}<strong>{{ $ev->new_value }}</strong></span>
                                    @if ($ev->reason)<p class="text-default mt-1 mb-0">{{ $ev->reason }}</p>@endif
                                </div>
                                <div class="text-right text-default">
                                    <div>{{ format_date($ev->effective_date) }}</div>
                                    <div>By {{ $ev->approver?->name ?? 'System' }}</div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-default">No lifecycle events recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif

            @if ($activeTab === 'documents')
            <div class="bg-white border border-border-color rounded-md p-5 space-y-4">
                <h3 class="text-base font-bold text-title mb-2">Upload Employee Document</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="text-xs font-semibold block mb-1">Document Title *</label><input type="text" wire:model="doc_title" placeholder="Employment Contract" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('doc_title')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                    <div><label class="text-xs font-semibold block mb-1">Document Type</label><select wire:model="doc_type" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"><option>ID / Passport</option><option>Employment Contract</option><option>Offer Letter</option><option>Academic Certificate</option><option>Relieving Letter</option></select></div>
                    <div><label class="text-xs font-semibold block mb-1">Issue Date</label><input type="date" wire:model="doc_issue_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div><label class="text-xs font-semibold block mb-1">Expiry Date</label><input type="date" wire:model="doc_expiry_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    <div class="sm:col-span-2"><label class="text-xs font-semibold block mb-1">File (PDF/Image, max 5MB) *</label><input type="file" wire:model="doc_file" class="text-xs">@error('doc_file')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                </div>
                <div class="flex justify-end"><button type="button" wire:click="uploadDocument" class="btn-sm bg-dark text-white">Upload Document</button></div>

                <div class="border-t border-border-color pt-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Title</th><th class="text-left py-2 px-3">Type</th><th class="text-left py-2 px-3">Expiry</th><th class="text-right py-2 px-3">Actions</th></tr></thead>
                        <tbody>
                            @forelse ($employee->documents as $doc)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-3 font-medium text-title">{{ $doc->title }}</td>
                                    <td class="py-2 px-3 text-default">{{ $doc->document_type }}</td>
                                    <td class="py-2 px-3 text-default">{{ format_date($doc->expiry_date) }}</td>
                                    <td class="py-2 px-3 text-right space-x-1">
                                        <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="btn-xsmall bg-light border border-border-color text-title">View</a>
                                        <button type="button" wire:click="deleteDocument({{ $doc->id }})" class="btn-xsmall bg-danger-transparent text-danger">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-xs text-default">No documents uploaded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if ($activeTab === 'payroll')
            <div class="bg-white border border-border-color rounded-md p-5 space-y-4">
                <h3 class="text-base font-bold text-title mb-2">Salary Payment History</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Period</th><th class="text-right py-2 px-3">Basic + Allow</th><th class="text-right py-2 px-3">Deductions</th><th class="text-right py-2 px-3">Net Pay</th><th class="text-left py-2 px-3">Status</th></tr></thead>
                        <tbody>
                            @forelse ($employee->payrollItems as $pi)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-3 font-medium text-title">{{ $pi->payrollRun?->period_label }}</td>
                                    <td class="py-2 px-3 text-right">{{ format_currency($pi->basic_salary + $pi->allowances) }}</td>
                                    <td class="py-2 px-3 text-right text-danger">{{ format_currency($pi->leave_deduction + $pi->tax_deduction + $pi->other_deduction) }}</td>
                                    <td class="py-2 px-3 text-right font-bold text-title">{{ format_currency($pi->net_salary) }}</td>
                                    <td class="py-2 px-3"><span class="text-[11px] px-2 py-0.5 rounded {{ $pi->payment_status === 'paid' ? 'bg-success-transparent text-success' : 'bg-warning-transparent text-warning' }}">{{ ucfirst($pi->payment_status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-xs text-default">No payroll entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h4 class="text-sm font-bold text-title pt-3 border-t border-border-color">Recent Attendance</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs bg-light border-b border-border-color"><th class="text-left py-2 px-3">Date</th><th class="text-left py-2 px-3">Check In</th><th class="text-left py-2 px-3">Check Out</th><th class="text-left py-2 px-3">Hours</th><th class="text-left py-2 px-3">Status</th></tr></thead>
                        <tbody>
                            @forelse ($employee->attendances->take(10) as $att)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-3">{{ format_date($att->attendance_date) }}</td>
                                    <td class="py-2 px-3">{{ $att->check_in ?? '—' }}</td>
                                    <td class="py-2 px-3">{{ $att->check_out ?? '—' }}</td>
                                    <td class="py-2 px-3">{{ $att->work_hours }}h</td>
                                    <td class="py-2 px-3"><span class="text-[11px] bg-info-transparent text-info px-2 py-0.5 rounded">{{ ucfirst($att->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-xs text-default">No attendance records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
