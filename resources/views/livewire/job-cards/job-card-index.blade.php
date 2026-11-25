<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div>
            <h1 class="text-gray-900 text-xl font-bold mb-0">Job Cards &amp; Service Management</h1>
            <p class="text-sm text-default mb-0">Register customer devices, assign technicians, track progress, QC, delivery &amp; service billing.</p>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <a href="{{ route('job-cards.board') }}" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1.5 hover:bg-light"><i class="ph-duotone ph-kanban"></i> Workshop Board</a>
            @canModule('job_cards', 'create')
            <button type="button" wire:click="openCreate" class="btn-sm bg-dark text-white inline-flex items-center gap-1.5 hover:bg-primary-hover"><i class="ph ph-plus"></i> Register Job Card</button>
            @endcanModule
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Total Job Cards</p><h3 class="text-xl font-bold text-gray-900 mb-0">{{ $counts['total'] }}</h3></div>
        <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Active In Workshop</p><h3 class="text-xl font-bold text-info mb-0">{{ $counts['active'] }}</h3></div>
        <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">In Quality Check</p><h3 class="text-xl font-bold text-warning mb-0">{{ $counts['qc'] }}</h3></div>
        <div class="bg-white border border-border-color rounded-md p-4"><p class="text-xs text-default mb-1">Completed / Ready</p><h3 class="text-xl font-bold text-success mb-0">{{ $counts['ready'] }}</h3></div>
    </div>

    <div class="bg-white border border-border-color rounded-md p-4">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search JC #, serial #, item, customer..." class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white w-72">
            <select wire:model.live="statusFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Statuses</option>
                <option value="registered">Registered</option>
                <option value="diagnosed">Diagnosed</option>
                <option value="awaiting_approval">Awaiting Approval</option>
                <option value="in_progress">In Progress</option>
                <option value="quality_check">Quality Check</option>
                <option value="completed">Completed</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select wire:model.live="priorityFilter" class="px-3 py-1.5 text-sm border border-border-color rounded-md bg-white">
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs bg-light border-b border-border-color">
                        <th class="text-left py-2.5 px-3">Job Card #</th>
                        <th class="text-left py-2.5 px-3">Customer</th>
                        <th class="text-left py-2.5 px-3">Device / Product &amp; Serial</th>
                        <th class="text-left py-2.5 px-3">Technician</th>
                        <th class="text-left py-2.5 px-3">Progress</th>
                        <th class="text-left py-2.5 px-3">Status / Approval</th>
                        <th class="text-right py-2.5 px-3">Total Bill</th>
                        <th class="text-right py-2.5 px-3 no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobCards as $jc)
                        <tr class="border-b border-border-color last:border-0">
                            <td class="py-2.5 px-3">
                                <a href="{{ route('job-cards.show', $jc->id) }}" class="font-semibold text-primary hover:underline">{{ $jc->job_card_no }}</a>
                                <div class="text-[11px] text-default">{{ format_date($jc->received_date) }} · {{ $jc->parts_source === 'site' ? 'On-Site' : 'Direct Shop' }}</div>
                            </td>
                            <td class="py-2.5 px-3">
                                @if ($jc->customer)
                                    <a href="{{ route('sales.customers.show', $jc->customer->id) }}" class="font-medium text-title hover:text-primary hover:underline">{{ $jc->customer->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-medium text-title">{{ $jc->item_name }}</div>
                                <div class="text-xs text-default">{{ $jc->brand_model }} @if($jc->serial_number) · SN: {{ $jc->serial_number }} @endif</div>
                            </td>
                            <td class="py-2.5 px-3 text-xs text-default">{{ $jc->technician?->full_name ?? 'Unassigned' }}</td>
                            <td class="py-2.5 px-3 w-36">
                                <div class="flex items-center gap-2">
                                    <div class="w-full bg-light rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-primary h-1.5 rounded-full" style="width: {{ $jc->progress_percent }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold">{{ $jc->progress_percent }}%</span>
                                </div>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="text-[11px] px-2 py-0.5 rounded capitalize {{ in_array($jc->status, ['completed','delivered']) ? 'bg-success-transparent text-success' : ($jc->status === 'cancelled' ? 'bg-danger-transparent text-danger' : 'bg-info-transparent text-info') }}">
                                    {{ str_replace('_', ' ', $jc->status) }}
                                </span>
                                @if ($jc->qc_status !== 'pending')
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-light border border-border-color ml-1">QC: {{ $jc->qc_status }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right font-semibold text-title">
                                {{ format_currency($jc->total_amount ?: $jc->estimated_cost) }}
                                @if ($jc->salesInvoice)
                                    <div class="text-[11px] text-success">{{ $jc->salesInvoice->invoice_no }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-right space-x-1 no-print">
                                <a href="{{ route('job-cards.show', $jc->id) }}" class="btn-xsmall bg-dark text-white">Manage</a>
                                <a href="{{ route('job-cards.print', $jc->id) }}" target="_blank" class="btn-xsmall bg-light border border-border-color text-title">Slip</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-default">No job cards found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $jobCards->links() }}</div>
    </div>

    @if ($showCreateModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-3xl max-h-[92vh] overflow-y-auto">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Register Service Job Card</h2>
                <button type="button" wire:click="$set('showCreateModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="createJobCard" class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2" x-data="{ open: false }" @click.away="open = false">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold mb-0">Customer *</label>
                        <button type="button" wire:click="$toggle('showQuickCustomer')" class="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1">
                            <i class="ph {{ $showQuickCustomer ? 'ph-x' : 'ph-plus-circle' }}"></i> {{ $showQuickCustomer ? 'Close New Customer' : 'Add New Customer' }}
                        </button>
                    </div>
                    <div class="relative">
                        <div class="flex items-center justify-between px-3 py-2 text-sm border border-border-color rounded-md bg-white cursor-pointer" @click="open = !open">
                            <span class="{{ $selectedCustomer ? 'text-title font-medium' : 'text-default' }} truncate">
                                @if ($selectedCustomer)
                                    {{ $selectedCustomer->name }} ({{ $selectedCustomer->phone ?: $selectedCustomer->code }})
                                @else
                                    Search &amp; Select Customer...
                                @endif
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if ($selectedCustomer)
                                    <button type="button" wire:click.stop="clearCustomer" class="text-default hover:text-danger" title="Clear customer"><i class="ph ph-x"></i></button>
                                @endif
                                <i class="ph ph-caret-down text-default"></i>
                            </div>
                        </div>

                        <div x-show="open" x-cloak class="absolute left-0 right-0 mt-1 bg-white border border-border-color rounded-md shadow-lg z-50 overflow-hidden">
                            <div class="p-2 border-b border-border-color bg-light/50">
                                <input type="text" wire:model.live.debounce.200ms="customerSearch" placeholder="Search customer name, phone, code..." class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded bg-white" @click.stop>
                            </div>
                            <div class="max-h-48 overflow-y-auto divide-y divide-border-color/50">
                                @forelse ($customers as $c)
                                    <button type="button" wire:click="selectCustomer({{ $c->id }})" @click="open = false" class="w-full text-left px-3 py-2 text-xs hover:bg-light flex items-center justify-between {{ $customer_id == $c->id ? 'bg-primary/10 font-semibold text-primary' : 'text-title' }}">
                                        <div>
                                            <div class="font-medium">{{ $c->name }}</div>
                                            @if ($c->company_name)<div class="text-[11px] text-default">{{ $c->company_name }}</div>@endif
                                        </div>
                                        <span class="text-[11px] text-default">{{ $c->phone ?: $c->code }}</span>
                                    </button>
                                @empty
                                    <div class="px-3 py-3 text-xs text-default text-center">No matching customers found.</div>
                                @endforelse
                            </div>
                            <button type="button" wire:click="$set('showQuickCustomer', true)" @click="open = false" class="w-full px-3 py-2 text-xs font-semibold text-primary bg-light border-t border-border-color hover:bg-primary/10 text-left flex items-center gap-1.5">
                                <i class="ph ph-plus-circle"></i> + Add New Customer
                            </button>
                        </div>
                    </div>
                    @error('customer_id')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Assign Technician</label>
                    <select wire:model="assigned_to" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Assign Later</option>
                        @foreach($employees as $emp)<option value="{{ $emp->id }}">{{ $emp->full_name }}</option>@endforeach
                    </select>
                </div>

                @if ($showQuickCustomer)
                <div class="sm:col-span-3 p-3 rounded-md border border-primary/40 bg-light/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-primary uppercase">Quick Add New Customer</span>
                        <button type="button" wire:click="$set('showQuickCustomer', false)" class="text-xs text-default hover:text-title">Close</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <div><input type="text" wire:model="new_cust_name" placeholder="Customer Name *" class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md bg-white">@error('new_cust_name')<span class="text-[11px] text-danger">{{ $message }}</span>@enderror</div>
                        <div><input type="text" wire:model="new_cust_phone" placeholder="Phone Number" class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md bg-white"></div>
                        <div><input type="email" wire:model="new_cust_email" placeholder="Email Address" class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md bg-white">@error('new_cust_email')<span class="text-[11px] text-danger">{{ $message }}</span>@enderror</div>
                        <div><input type="text" wire:model="new_cust_company" placeholder="Company Name" class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md bg-white"></div>
                        <div class="sm:col-span-2 flex gap-2">
                            <input type="text" wire:model="new_cust_address" placeholder="Billing / Service Address" class="flex-1 px-2.5 py-1.5 text-xs border border-border-color rounded-md bg-white">
                            <button type="button" wire:click="saveQuickCustomer" class="btn-xsmall bg-primary text-white shrink-0">Save &amp; Select</button>
                        </div>
                    </div>
                </div>
                @endif

                <div>
                    <label class="text-xs font-semibold block mb-1">Catalog Product (Optional)</label>
                    <select wire:model.live="product_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Custom / Customer Device</option>
                        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Device / Product Name *</label><input type="text" wire:model="item_name" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">@error('item_name')<span class="text-xs text-danger">{{ $message }}</span>@enderror</div>
                <div><label class="text-xs font-semibold block mb-1">Brand / Model</label><input type="text" wire:model="brand_model" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>

                <div><label class="text-xs font-semibold block mb-1">Serial Number / IMEI</label><input type="text" wire:model="serial_number" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Accessories Received</label><input type="text" wire:model="accessories_received" placeholder="Charger, Bag, Cable..." class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Service Mode *</label>
                    <select wire:model.live="parts_source" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="direct">Direct Shop</option>
                        <option value="site">On-Site</option>
                    </select>
                </div>

                @if ($parts_source === 'site')
                <div class="sm:col-span-3">
                    <label class="text-xs font-semibold block mb-1">On-Site Service Location / Address</label>
                    <input type="text" wire:model="service_location" placeholder="Enter customer site address, building, floor, or contact location..." class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                </div>
                @endif

                <div><label class="text-xs font-semibold block mb-1">Received Date *</label><input type="date" wire:model="received_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Expected Delivery</label><input type="date" wire:model="expected_delivery_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Priority &amp; Est. Cost</label>
                    <div class="grid grid-cols-2 gap-1.5">
                        <select wire:model="priority" class="px-2 py-2 text-sm border border-border-color rounded-md">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                        <input type="number" step="0.01" wire:model="estimated_cost" placeholder="Est $" class="px-2 py-2 text-sm border border-border-color rounded-md">
                    </div>
                </div>

                <div class="sm:col-span-3">
                    <label class="text-xs font-semibold block mb-1">Service Description / Customer Complaint *</label>
                    <textarea wire:model="service_description" rows="3" class="w-full px-3 py-2 text-sm border border-border-color rounded-md" placeholder="Describe fault, requested repair, or inspection instructions..."></textarea>
                    @error('service_description')<span class="text-xs text-danger">{{ $message }}</span>@enderror
                </div>

                <div class="sm:col-span-3">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold mb-0">Attach Device Photos / Inspection Images (Multiple)</label>
                        @if (count($photos) > 0)<span class="text-xs font-semibold text-success">{{ count($photos) }} photo(s) ready</span>@endif
                    </div>
                    <label class="block border border-dashed border-border-color rounded-md p-3 bg-light/50 hover:bg-light cursor-pointer text-center">
                        <input type="file" wire:model="newPhotos" multiple accept="image/*" class="hidden">
                        <div wire:loading.remove wire:target="newPhotos" class="text-xs text-default">
                            <i class="ph-duotone ph-images text-lg text-primary block mb-0.5"></i>
                            Click to choose one or multiple photos (PNG, JPG, WEBP up to 5MB each — pick repeatedly to add more)
                        </div>
                        <div wire:loading wire:target="newPhotos" class="text-xs font-semibold text-primary">
                            <i class="ph ph-spinner animate-spin me-1"></i> Uploading selected images...
                        </div>
                    </label>
                    @error('newPhotos.*')<span class="text-xs text-danger block mt-1">{{ $message }}</span>@enderror
                    @error('photos.*')<span class="text-xs text-danger block mt-1">{{ $message }}</span>@enderror

                    @if (count($photos) > 0)
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mt-2">
                            @foreach ($photos as $idx => $photo)
                                <div class="relative border border-border-color rounded p-1 bg-white">
                                    @if (method_exists($photo, 'temporaryUrl'))
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-full h-14 object-cover rounded">
                                    @endif
                                    <button type="button" wire:click="removePhoto({{ $idx }})" class="absolute -top-1.5 -right-1.5 size-5 rounded-full bg-danger text-white text-[10px] flex items-center justify-center"><i class="ph ph-x"></i></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="sm:col-span-3 flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="newPhotos" class="btn-sm bg-dark text-white hover:bg-primary-hover disabled:opacity-50">Register Job Card</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
