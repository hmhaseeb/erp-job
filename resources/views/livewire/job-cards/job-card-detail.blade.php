<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4 lg:mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('job-cards.index') }}" class="btn-sm bg-white border border-border-color text-title"><i class="ph ph-arrow-left"></i></a>
            <div>
                <div class="flex items-center flex-wrap gap-2">
                    <h1 class="text-gray-900 text-xl font-bold mb-0">{{ $jobCard->job_card_no }}</h1>
                    <span class="text-xs px-2 py-0.5 rounded capitalize bg-info-transparent text-info">{{ str_replace('_', ' ', $jobCard->status) }}</span>
                    <span class="text-xs px-2 py-0.5 rounded capitalize bg-warning-transparent text-warning">{{ $jobCard->priority }}</span>
                </div>
                <p class="text-sm text-default mb-0">{{ $jobCard->item_name }} ({{ $jobCard->brand_model ?: 'Service Item' }}) @if($jobCard->serial_number) · SN: <strong>{{ $jobCard->serial_number }}</strong>@endif</p>
            </div>
        </div>
        <div class="flex items-center flex-wrap gap-2 no-print">
            <a href="{{ route('job-cards.print', $jobCard->id) }}" target="_blank" class="btn-sm bg-white border border-border-color text-title inline-flex items-center gap-1"><i class="ph-duotone ph-printer"></i> Print Job Card</a>
            @if ($jobCard->salesInvoice)
                <a href="{{ route('sales.invoices.print', $jobCard->salesInvoice->id) }}" target="_blank" class="btn-sm bg-white border border-border-color text-title">Invoice {{ $jobCard->salesInvoice->invoice_no }}</a>
                @if ($jobCard->salesInvoice->balance_due > 0)
                    <button type="button" wire:click="openCollectPayment" class="btn-sm bg-success text-white">Collect {{ format_currency($jobCard->salesInvoice->balance_due) }}</button>
                @endif
            @else
                @canModule('job_cards', 'create')
                <button type="button" wire:click="generateServiceInvoice" wire:confirm="Generate a Service Invoice for {{ format_currency($jobCard->total_amount ?: $jobCard->estimated_cost) }}?" class="btn-sm bg-primary text-white">Generate Service Invoice</button>
                @endcanModule
            @endif
        </div>
    </div>

    <!-- Top Summary Cards: Job Info & Customer Details -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white border border-border-color rounded-md p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-title mb-0">Complete Job &amp; Device Information</h2>
                <span class="text-xs font-semibold text-primary">Progress: {{ $jobCard->progress_percent }}%</span>
            </div>
            <div class="w-full bg-light rounded-full h-2 overflow-hidden mb-3">
                <div class="bg-primary h-2 rounded-full" style="width: {{ $jobCard->progress_percent }}%"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs mb-3">
                <div class="p-2.5 rounded bg-light/60"><span class="text-default block">Received Date</span><strong class="text-title">{{ format_date($jobCard->received_date) }}</strong></div>
                <div class="p-2.5 rounded bg-light/60"><span class="text-default block">Expected Delivery</span><strong class="text-title">{{ $jobCard->expected_delivery_date ? format_date($jobCard->expected_delivery_date) : '—' }}</strong></div>
                <div class="p-2.5 rounded bg-light/60"><span class="text-default block">Assigned Technician</span><strong class="text-title">{{ $jobCard->technician?->full_name ?? 'Unassigned' }}</strong></div>
                <div class="p-2.5 rounded bg-light/60"><span class="text-default block">Product / Device</span><strong class="text-title">{{ $jobCard->item_name }}</strong></div>
                <div class="p-2.5 rounded bg-light/60"><span class="text-default block">Brand &amp; Serial</span><strong class="text-title">{{ $jobCard->brand_model ?: '—' }} / {{ $jobCard->serial_number ?: 'N/A' }}</strong></div>
                <div class="p-2.5 rounded bg-light/60"><span class="text-default block">Accessories Received</span><strong class="text-title">{{ $jobCard->accessories_received ?: 'None' }}</strong></div>
                <div class="p-2.5 rounded bg-light/60 sm:col-span-3">
                    <span class="text-default block">Service Mode</span>
                    <strong class="text-title">
                        @if ($jobCard->parts_source === 'site')
                            On-Site @if($jobCard->service_location) — {{ $jobCard->service_location }} @endif
                        @else
                            Direct Shop
                        @endif
                    </strong>
                </div>
            </div>
            <div class="p-3 rounded bg-light text-xs text-default">
                <strong class="text-title block mb-0.5">Service Description / Issue Reported:</strong>
                {{ $jobCard->service_description }}
            </div>
            @if ($jobCard->delivered_at || $jobCard->delivered_to)
                <div class="mt-2 p-3 rounded bg-success-transparent/40 text-xs">
                    <strong class="text-success">Delivery Handover:</strong>
                    Delivered to <strong>{{ $jobCard->delivered_to }}</strong> on {{ $jobCard->delivered_at?->format('d M Y h:i A') }}
                    @if ($jobCard->delivery_notes) · {{ $jobCard->delivery_notes }} @endif
                </div>
            @endif
        </div>

        <div class="bg-white border border-border-color rounded-md p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-title mb-0">Customer Details</h2>
                @if ($jobCard->customer)
                    <a href="{{ route('sales.customers.show', $jobCard->customer->id) }}" class="btn-xsmall bg-light border border-border-color text-primary">View Profile</a>
                @endif
            </div>
            @if ($jobCard->customer)
                <div class="space-y-2 text-xs">
                    <div>
                        <a href="{{ route('sales.customers.show', $jobCard->customer->id) }}" class="text-sm font-bold text-title hover:text-primary hover:underline">{{ $jobCard->customer->name }}</a>
                        <div class="text-default">{{ $jobCard->customer->code }} @if($jobCard->customer->company_name)· {{ $jobCard->customer->company_name }}@endif</div>
                    </div>
                    <div class="border-t border-border-color pt-2 space-y-1">
                        <div><span class="text-default">Phone:</span> <strong class="text-title">{{ $jobCard->customer->phone ?: '—' }}</strong></div>
                        <div><span class="text-default">Email:</span> <strong class="text-title">{{ $jobCard->customer->email ?: '—' }}</strong></div>
                        <div><span class="text-default">Address:</span> <strong class="text-title">{{ collect([$jobCard->customer->billing_address, $jobCard->customer->city])->filter()->implode(', ') ?: '—' }}</strong></div>
                        <div><span class="text-default">Customer Balance:</span> <strong class="{{ $jobCard->customer->outstanding_balance > 0 ? 'text-danger' : 'text-success' }}">{{ format_currency($jobCard->customer->outstanding_balance) }}</strong></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <!-- Multiple Attached Images Gallery & Uploader -->
            <div class="bg-white border border-border-color rounded-md p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-bold text-title mb-0">Attached Device &amp; Inspection Images ({{ $jobCard->images->count() }})</h2>
                </div>

                @if ($jobCard->images->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                        @foreach ($jobCard->images as $img)
                            <div class="border border-border-color rounded-md p-1.5 bg-light/40">
                                <img src="{{ $img->url }}" alt="{{ $img->caption ?? 'Job card image' }}" class="w-full h-24 object-cover rounded cursor-pointer" wire:click="$set('previewImageUrl', '{{ $img->url }}')">
                                <div class="mt-1 flex items-center justify-between gap-1">
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-info-transparent text-info capitalize">{{ $img->stage }}</span>
                                    @canModule('job_cards', 'edit')
                                    <button type="button" wire:click="deleteImage({{ $img->id }})" wire:confirm="Remove this image?" class="text-xs text-danger"><i class="ph ph-trash"></i></button>
                                    @endcanModule
                                </div>
                                @if ($img->caption)<div class="text-[11px] text-default truncate mt-0.5">{{ $img->caption }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-default mb-3">No images attached yet. Upload multiple intake, diagnosis, repair, QC, or delivery photos below.</p>
                @endif

                @canModule('job_cards', 'edit')
                <form wire:submit="uploadImages" class="pt-3 border-t border-border-color space-y-2.5 no-print">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-end">
                        <div class="sm:col-span-2">
                            <label class="text-xs font-semibold block mb-1">Attach Multiple Images * @if(count($photos) > 0)<span class="text-success">({{ count($photos) }} selected)</span>@endif</label>
                            <label class="block border border-dashed border-border-color rounded-md px-3 py-2 bg-light/50 hover:bg-light cursor-pointer text-xs text-center">
                                <input type="file" wire:model="newPhotos" multiple accept="image/*" class="hidden">
                                <span wire:loading.remove wire:target="newPhotos"><i class="ph ph-plus-circle me-1 text-primary"></i> Select Photos (add multiple)</span>
                                <span wire:loading wire:target="newPhotos" class="text-primary font-semibold">Uploading...</span>
                            </label>
                            @error('photos')<span class="text-xs text-danger block">{{ $message }}</span>@enderror
                            @error('newPhotos.*')<span class="text-xs text-danger block">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Stage</label>
                            <select wire:model="photoStage" class="w-full px-2.5 py-2 text-xs border border-border-color rounded-md">
                                <option value="intake">Intake</option>
                                <option value="diagnosis">Diagnosis</option>
                                <option value="repair">Repair</option>
                                <option value="qc">Quality Check</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>
                        <div class="flex gap-1.5">
                            <input type="text" wire:model="photoCaption" placeholder="Caption..." class="flex-1 min-w-0 px-2.5 py-2 text-xs border border-border-color rounded-md">
                            <button type="submit" wire:loading.attr="disabled" wire:target="newPhotos" class="btn-sm bg-dark text-white shrink-0">Upload</button>
                        </div>
                    </div>
                    @if (count($photos) > 0)
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
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
                </form>
                @endcanModule
            </div>

            <!-- Assignment, Diagnosis & Progress -->
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">1. Service Assignment, Status &amp; Work Progress</h2>
                <form wire:submit="saveWorkflow" class="space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs font-semibold block mb-1">Technician</label>
                            <select wire:model="assigned_to" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                                <option value="">Unassigned</option>
                                @foreach($employees as $emp)<option value="{{ $emp->id }}">{{ $emp->full_name }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Service Status</label>
                            <select wire:model.live="status" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                                <option value="registered">Registered</option>
                                <option value="diagnosed">Diagnosed</option>
                                <option value="awaiting_approval">Awaiting Approval</option>
                                <option value="in_progress">In Progress</option>
                                <option value="quality_check">Quality Check</option>
                                <option value="completed">Completed</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Work Progress (%)</label>
                            <input type="number" min="0" max="100" wire:model="progress_percent" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Service Mode</label>
                            <select wire:model.live="parts_source" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                                <option value="direct">Direct Shop</option>
                                <option value="site">On-Site</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs font-semibold block mb-1">Priority</label>
                            <select wire:model="priority" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Expected Delivery</label>
                            <input type="date" wire:model="expected_delivery_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Labor Charges</label>
                            <input type="number" step="0.01" min="0" wire:model="labor_charges" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Discount</label>
                            <input type="number" step="0.01" min="0" wire:model="discount_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                        <div><label class="text-xs font-semibold block mb-1">Brand / Model</label><input type="text" wire:model="brand_model" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                        <div><label class="text-xs font-semibold block mb-1">Serial # / IMEI</label><input type="text" wire:model="serial_number" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                        <div><label class="text-xs font-semibold block mb-1">Accessories</label><input type="text" wire:model="accessories_received" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                        <div><label class="text-xs font-semibold block mb-1">Estimated Cost</label><input type="number" step="0.01" min="0" wire:model="estimated_cost" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                    </div>

                    @if ($parts_source === 'site')
                    <div>
                        <label class="text-xs font-semibold block mb-1">On-Site Service Location / Address</label>
                        <input type="text" wire:model="service_location" placeholder="Enter site location..." class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                    </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold block mb-1">Technician Diagnosis Notes</label>
                            <textarea wire:model="diagnosis_notes" rows="2" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-semibold block mb-1">Progress Log Note (optional)</label>
                            <textarea wire:model="progress_log_note" rows="2" placeholder="What work was completed?" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end no-print">
                        <button type="submit" class="btn-sm bg-dark text-white hover:bg-primary-hover">Save Job Details &amp; Progress</button>
                    </div>
                </form>
            </div>

            <!-- Spare Parts Used -->
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">2. Spare Parts &amp; Materials Used</h2>
                @canModule('job_cards', 'edit')
                <form wire:submit="addSparePart" class="grid grid-cols-1 sm:grid-cols-5 gap-2 mb-3 pb-3 border-b border-border-color no-print">
                    <div class="sm:col-span-2">
                        <select wire:model.live="part_product_id" class="w-full px-2.5 py-2 text-xs border border-border-color rounded-md">
                            <option value="">Custom Part (Manual Entry)</option>
                            @foreach($products as $pr)<option value="{{ $pr->id }}">{{ $pr->name }} (Stock: {{ $pr->stock_quantity }})</option>@endforeach
                        </select>
                    </div>
                    <div><input type="text" wire:model="part_name" placeholder="Part name *" class="w-full px-2.5 py-2 text-xs border border-border-color rounded-md"></div>
                    <div class="grid grid-cols-2 gap-1">
                        <input type="number" step="0.01" wire:model="part_qty" placeholder="Qty" class="w-full px-2 py-2 text-xs border border-border-color rounded-md text-right">
                        <input type="number" step="0.01" wire:model="part_price" placeholder="Price" class="w-full px-2 py-2 text-xs border border-border-color rounded-md text-right">
                    </div>
                    <button type="submit" class="btn-sm bg-primary text-white">Add Part</button>
                </form>
                @endcanModule

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs bg-light border-b border-border-color">
                                <th class="text-left py-2 px-2">Part Name</th>
                                <th class="text-right py-2 px-2">Qty</th>
                                <th class="text-right py-2 px-2">Unit Price</th>
                                <th class="text-right py-2 px-2">Line Total</th>
                                <th class="text-center py-2 px-2">Inventory</th>
                                <th class="text-right py-2 px-2 no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobCard->parts as $part)
                                <tr class="border-b border-border-color last:border-0">
                                    <td class="py-2 px-2 font-medium text-title">{{ $part->part_name }}</td>
                                    <td class="py-2 px-2 text-right">{{ $part->quantity }}</td>
                                    <td class="py-2 px-2 text-right">{{ format_currency($part->unit_price) }}</td>
                                    <td class="py-2 px-2 text-right font-semibold">{{ format_currency($part->line_total) }}</td>
                                    <td class="py-2 px-2 text-center text-xs">{{ $part->stock_deducted ? 'Deducted' : 'Custom' }}</td>
                                    <td class="py-2 px-2 text-right no-print">
                                        @if (! $jobCard->sales_invoice_id)
                                            @canModule('job_cards', 'edit')
                                            <button type="button" wire:click="deleteSparePart({{ $part->id }})" wire:confirm="Remove {{ $part->part_name }}?" class="text-xs text-danger hover:underline">Remove</button>
                                            @endcanModule
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-center text-default text-xs">No spare parts added yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Approval, Quality Checking & Delivery -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-border-color rounded-md p-4">
                    <h3 class="text-sm font-bold text-title mb-2">3. Customer Approval</h3>
                    <p class="text-xs mb-2">Status: <strong class="capitalize text-primary">{{ $jobCard->customer_approval_status }}</strong></p>
                    <textarea wire:model="approval_notes" rows="2" placeholder="Customer approval remarks..." class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md mb-2"></textarea>
                    <div class="flex gap-1.5 no-print">
                        <button type="button" wire:click="recordCustomerApproval('approved')" class="flex-1 btn-xsmall bg-success text-white">Approve</button>
                        <button type="button" wire:click="recordCustomerApproval('rejected')" class="flex-1 btn-xsmall bg-danger text-white">Reject</button>
                    </div>
                </div>

                <div class="bg-white border border-border-color rounded-md p-4">
                    <h3 class="text-sm font-bold text-title mb-2">4. Quality Checking (QC)</h3>
                    <p class="text-xs mb-2">Result: <strong class="capitalize text-primary">{{ $jobCard->qc_status }}</strong></p>
                    <textarea wire:model="qc_notes" rows="2" placeholder="Inspection / test notes..." class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md mb-2"></textarea>
                    <div class="flex gap-1.5 no-print">
                        <button type="button" wire:click="recordQualityCheck('passed')" class="flex-1 btn-xsmall bg-success text-white">QC Pass</button>
                        <button type="button" wire:click="recordQualityCheck('failed')" class="flex-1 btn-xsmall bg-warning text-white">QC Fail</button>
                    </div>
                </div>

                <div class="bg-white border border-border-color rounded-md p-4">
                    <h3 class="text-sm font-bold text-title mb-2">5. Delivery Handover</h3>
                    <input type="text" wire:model="delivered_to" placeholder="Delivered to person" class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md mb-2">
                    <textarea wire:model="delivery_notes" rows="2" placeholder="Delivery notes..." class="w-full px-2.5 py-1.5 text-xs border border-border-color rounded-md mb-2"></textarea>
                    <button type="button" wire:click="markDelivered" class="w-full btn-xsmall bg-dark text-white no-print">Confirm Delivery</button>
                </div>
            </div>
        </div>

        <!-- Right Billing, Payment History & Timeline -->
        <div class="space-y-4">
            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Billing Information</h2>
                <div class="text-sm space-y-1.5">
                    <div class="flex justify-between"><span class="text-default">Estimated Cost:</span><span>{{ format_currency($jobCard->estimated_cost) }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Spare Parts:</span><span>{{ format_currency($jobCard->parts_total) }}</span></div>
                    <div class="flex justify-between"><span class="text-default">Labor / Service:</span><span>{{ format_currency($jobCard->labor_charges) }}</span></div>
                    @if ($jobCard->discount_amount > 0)
                        <div class="flex justify-between text-danger"><span>Discount:</span><span>-{{ format_currency($jobCard->discount_amount) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-default">Tax:</span><span>{{ format_currency($jobCard->tax_amount) }}</span></div>
                    <div class="flex justify-between border-t border-border-color pt-2 font-bold text-base text-title"><span>Total Payable:</span><span class="text-primary">{{ format_currency($jobCard->total_amount) }}</span></div>
                    @if ($jobCard->salesInvoice)
                        <div class="p-2.5 mt-2 rounded bg-light text-xs space-y-1">
                            <div class="flex justify-between"><strong>Invoice:</strong> <a href="{{ route('sales.invoices.print', $jobCard->salesInvoice->id) }}" target="_blank" class="text-primary hover:underline">{{ $jobCard->salesInvoice->invoice_no }} ({{ strtoupper($jobCard->salesInvoice->status) }})</a></div>
                            <div class="flex justify-between"><span>Paid:</span> <strong class="text-success">{{ format_currency($jobCard->salesInvoice->paid_amount) }}</strong></div>
                            <div class="flex justify-between"><span>Balance:</span> <strong class="text-danger">{{ format_currency($jobCard->salesInvoice->balance_due) }}</strong></div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-border-color rounded-md p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-bold text-title mb-0">Payment History</h2>
                    @if ($jobCard->salesInvoice && $jobCard->salesInvoice->balance_due > 0)
                        <button type="button" wire:click="openCollectPayment" class="btn-xsmall bg-success text-white no-print">Collect</button>
                    @endif
                </div>
                @if ($jobCard->salesInvoice && $jobCard->salesInvoice->payments->isNotEmpty())
                    <div class="space-y-2 text-xs">
                        @foreach ($jobCard->salesInvoice->payments as $pmt)
                            <div class="p-2.5 rounded border border-border-color bg-light/50 flex justify-between items-center">
                                <div>
                                    <div class="font-semibold text-primary">{{ $pmt->receipt_no }}</div>
                                    <div class="text-default">{{ format_date($pmt->payment_date) }} · {{ $pmt->paymentMethod?->name ?? 'Payment' }}</div>
                                    @if ($pmt->reference)<div class="text-[10px] text-default">Ref: {{ $pmt->reference }}</div>@endif
                                </div>
                                <div class="font-bold text-success text-sm">{{ format_currency($pmt->amount) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-default mb-0">{{ $jobCard->salesInvoice ? 'No payments collected against this invoice yet.' : 'Generate a service invoice to collect and track payments.' }}</p>
                @endif
            </div>

            <div class="bg-white border border-border-color rounded-md p-4">
                <h2 class="text-base font-bold text-title mb-3">Status Updates &amp; Audit Trail</h2>
                <div class="space-y-3">
                    @foreach ($jobCard->logs as $log)
                        <div class="border-l-2 border-primary pl-3 pb-1">
                            <div class="flex items-center justify-between text-xs">
                                <strong class="text-title">{{ $log->stage }}</strong>
                                <span class="text-[10px] text-default">{{ $log->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="text-xs text-default mb-0">{{ $log->notes }}</p>
                            <span class="text-[10px] text-default">By {{ $log->logger?->name ?? 'System' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if ($showPayModal)
    <div class="fixed inset-0 z-[999] bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white border border-border-color rounded-md shadow-lg w-full max-w-md">
            <div class="flex justify-between items-center p-4 border-b border-border-color">
                <h2 class="text-base font-bold text-title mb-0">Collect Service Payment</h2>
                <button type="button" wire:click="$set('showPayModal', false)" class="size-7 rounded border border-border-color flex items-center justify-center"><i class="ph ph-x"></i></button>
            </div>
            <form wire:submit="collectPayment" class="p-4 space-y-3">
                <div><label class="text-xs font-semibold block mb-1">Amount *</label><input type="number" step="0.01" wire:model="pay_amount" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div><label class="text-xs font-semibold block mb-1">Payment Date *</label><input type="date" wire:model="pay_date" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Payment Method</label>
                    <select wire:model="payment_method_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        @foreach($paymentMethods as $pm)<option value="{{ $pm->id }}">{{ $pm->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold block mb-1">Deposit To Bank / Cash</label>
                    <select wire:model="bank_account_id" class="w-full px-3 py-2 text-sm border border-border-color rounded-md">
                        <option value="">Cash Counter</option>
                        @foreach($bankAccounts as $ba)<option value="{{ $ba->id }}">{{ $ba->bank_name }}</option>@endforeach
                    </select>
                </div>
                <div><label class="text-xs font-semibold block mb-1">Reference</label><input type="text" wire:model="pay_reference" class="w-full px-3 py-2 text-sm border border-border-color rounded-md"></div>
                <div class="flex justify-end gap-2 pt-3 border-t border-border-color">
                    <button type="button" wire:click="$set('showPayModal', false)" class="btn-sm bg-white border border-border-color text-title">Cancel</button>
                    <button type="submit" class="btn-sm bg-success text-white">Record Service Receipt</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if ($previewImageUrl)
    <div class="fixed inset-0 z-[999] bg-black/75 flex items-center justify-center p-4" wire:click="$set('previewImageUrl', null)">
        <div class="bg-white rounded-md p-2 max-w-3xl max-h-[90vh] relative" wire:click.stop>
            <button type="button" wire:click="$set('previewImageUrl', null)" class="absolute top-3 right-3 size-8 rounded-full bg-dark text-white flex items-center justify-center"><i class="ph ph-x"></i></button>
            <img src="{{ $previewImageUrl }}" alt="Job card preview" class="max-h-[82vh] max-w-full object-contain rounded">
        </div>
    </div>
    @endif
</div>
