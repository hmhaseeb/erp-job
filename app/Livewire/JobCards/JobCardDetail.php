<?php

namespace App\Livewire\JobCards;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Employee;
use App\Models\JobCard;
use App\Models\JobCardImage;
use App\Models\JobCardLog;
use App\Models\JobCardPart;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\Warehouse;
use App\Services\FinanceService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Job Card Workspace')]
class JobCardDetail extends Component
{
    use WithFileUploads;

    public JobCard $jobCard;

    // Assignment & Job Fields
    public ?int $assigned_to = null;
    public string $parts_source = 'direct';
    public string $service_location = '';
    public string $priority = 'medium';
    public ?string $expected_delivery_date = null;
    public string $brand_model = '';
    public string $serial_number = '';
    public string $accessories_received = '';
    public float $estimated_cost = 0;
    public string $status = 'registered';
    public int $progress_percent = 10;
    public string $diagnosis_notes = '';
    public string $progress_log_note = '';

    // Approval & QC & Delivery
    public string $approval_notes = '';
    public string $qc_notes = '';
    public string $delivered_to = '';
    public string $delivery_notes = '';

    // Billing & Parts
    public float $labor_charges = 0;
    public float $discount_amount = 0;
    public ?int $part_product_id = null;
    public string $part_name = '';
    public float $part_qty = 1;
    public float $part_price = 0;

    // Multiple Image Attachments
    public array $photos = [];
    public array $newPhotos = [];
    public string $photoCaption = '';
    public string $photoStage = 'diagnosis';
    public ?string $previewImageUrl = null;

    // Collect Payment Modal
    public bool $showPayModal = false;
    public float $pay_amount = 0;
    public ?int $payment_method_id = null;
    public ?int $bank_account_id = null;
    public string $pay_date = '';
    public string $pay_reference = '';

    public function mount(int $id): void
    {
        $this->loadJobCard($id);
        $this->pay_date = now()->toDateString();
        $this->payment_method_id = PaymentMethod::where('is_active', true)->value('id');
        $this->bank_account_id = BankAccount::where('is_default', true)->value('id');
    }

    private function loadJobCard(?int $id = null): void
    {
        $jcId = $id ?? $this->jobCard->id;
        $this->jobCard = JobCard::with([
            'customer',
            'technician',
            'parts.product',
            'images.uploader',
            'logs.logger',
            'salesInvoice.items',
            'salesInvoice.payments.paymentMethod',
            'salesInvoice.payments.bankAccount',
            'qcChecker',
        ])->findOrFail($jcId);
        $this->assigned_to = $this->jobCard->assigned_to;
        $this->parts_source = $this->jobCard->parts_source === 'site' ? 'site' : 'direct';
        $this->service_location = (string) ($this->jobCard->service_location ?? '');
        $this->priority = (string) ($this->jobCard->priority ?? 'medium');
        $this->expected_delivery_date = $this->jobCard->expected_delivery_date?->toDateString();
        $this->brand_model = (string) ($this->jobCard->brand_model ?? '');
        $this->serial_number = (string) ($this->jobCard->serial_number ?? '');
        $this->accessories_received = (string) ($this->jobCard->accessories_received ?? '');
        $this->estimated_cost = (float) $this->jobCard->estimated_cost;
        $this->status = $this->jobCard->status;
        $this->progress_percent = (int) $this->jobCard->progress_percent;
        $this->diagnosis_notes = (string) ($this->jobCard->diagnosis_notes ?? '');
        $this->approval_notes = (string) ($this->jobCard->approval_notes ?? '');
        $this->qc_notes = (string) ($this->jobCard->qc_notes ?? '');
        $this->delivered_to = (string) ($this->jobCard->delivered_to ?? $this->jobCard->customer?->name ?? '');
        $this->delivery_notes = (string) ($this->jobCard->delivery_notes ?? '');
        $this->labor_charges = (float) $this->jobCard->labor_charges;
        $this->discount_amount = (float) $this->jobCard->discount_amount;
    }

    public function updatedStatus($val): void
    {
        $map = [
            'registered' => 10,
            'diagnosed' => 25,
            'awaiting_approval' => 40,
            'in_progress' => 65,
            'quality_check' => 85,
            'completed' => 100,
            'delivered' => 100,
        ];
        if (isset($map[$val])) {
            $this->progress_percent = $map[$val];
        }
    }

    public function updatedPartProductId($val): void
    {
        $prod = Product::find($val);
        if ($prod) {
            $this->part_name = $prod->name;
            $this->part_price = (float) $prod->selling_price;
        }
    }

    private function recalculateTotals(): void
    {
        $this->jobCard->refresh();
        $partsTotal = (float) $this->jobCard->parts()->sum('line_total');
        $taxable = max(0, $partsTotal + $this->labor_charges - $this->discount_amount);
        $taxRate = erp_setting('tax_enabled', true) ? (float) erp_setting('default_tax_rate', 5) : 0;
        $tax = round($taxable * ($taxRate / 100), 2);
        $total = round($taxable + $tax, 2);

        $this->jobCard->update([
            'parts_total' => $partsTotal,
            'labor_charges' => $this->labor_charges,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $tax,
            'total_amount' => $total,
        ]);
    }

    public function saveWorkflow(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $this->validate([
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'parts_source' => ['required', 'in:direct,site'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'estimated_cost' => ['numeric', 'min:0'],
            'labor_charges' => ['numeric', 'min:0'],
            'discount_amount' => ['numeric', 'min:0'],
            'expected_delivery_date' => ['nullable', 'date'],
        ]);

        $this->jobCard->update([
            'assigned_to' => $this->assigned_to ?: null,
            'parts_source' => $this->parts_source,
            'service_location' => $this->parts_source === 'site' ? ($this->service_location ?: null) : null,
            'priority' => $this->priority,
            'expected_delivery_date' => $this->expected_delivery_date ?: null,
            'brand_model' => $this->brand_model,
            'serial_number' => $this->serial_number,
            'accessories_received' => $this->accessories_received,
            'estimated_cost' => $this->estimated_cost,
            'status' => $this->status,
            'progress_percent' => min(100, max(0, $this->progress_percent)),
            'diagnosis_notes' => $this->diagnosis_notes,
            'completed_at' => in_array($this->status, ['completed', 'delivered']) && ! $this->jobCard->completed_at ? now() : $this->jobCard->completed_at,
        ]);

        $this->recalculateTotals();

        JobCardLog::create([
            'job_card_id' => $this->jobCard->id,
            'stage' => 'Progress & Assignment Update',
            'status' => $this->status,
            'notes' => $this->progress_log_note ?: "Updated status to ".str_replace('_', ' ', $this->status)." ({$this->progress_percent}%)",
            'logged_by' => auth()->id(),
        ]);

        $this->progress_log_note = '';
        $this->loadJobCard();
        log_activity('job_cards', 'job_card_updated', "Updated Job Card {$this->jobCard->job_card_no} to {$this->status}", $this->jobCard);
        $this->dispatch('toast', message: 'Job Card workflow & details updated.', type: 'success');
    }

    public function addSparePart(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $this->validate([
            'part_name' => ['required', 'string', 'max:150'],
            'part_qty' => ['required', 'numeric', 'min:0.01'],
            'part_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () {
            $lineTotal = round($this->part_qty * $this->part_price, 2);
            $defaultWhId = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
            $deducted = false;

            if ($this->part_product_id && $defaultWhId) {
                $prod = Product::find($this->part_product_id);
                if ($prod && ! $prod->is_service) {
                    InventoryService::adjustStock(
                        productId: $prod->id,
                        warehouseId: $defaultWhId,
                        quantityDelta: -$this->part_qty,
                        movementType: 'job_card_use',
                        unitCost: (float) $prod->cost_price,
                        referenceType: JobCard::class,
                        referenceId: $this->jobCard->id,
                        referenceNo: $this->jobCard->job_card_no,
                        notes: "Used on Job Card {$this->jobCard->job_card_no}"
                    );
                    $deducted = true;
                }
            }

            JobCardPart::create([
                'job_card_id' => $this->jobCard->id,
                'product_id' => $this->part_product_id ?: null,
                'part_name' => $this->part_name,
                'quantity' => $this->part_qty,
                'unit_price' => $this->part_price,
                'line_total' => $lineTotal,
                'stock_deducted' => $deducted,
            ]);

            JobCardLog::create([
                'job_card_id' => $this->jobCard->id,
                'stage' => 'Spare Part Added',
                'status' => $this->jobCard->status,
                'notes' => "Added {$this->part_qty}x {$this->part_name}".($deducted ? ' (deducted from inventory)' : ''),
                'logged_by' => auth()->id(),
            ]);

            $this->recalculateTotals();
        });

        $this->reset(['part_product_id', 'part_name', 'part_price']);
        $this->part_qty = 1;
        $this->loadJobCard();
        $this->dispatch('toast', message: 'Spare part added successfully.', type: 'success');
    }

    public function deleteSparePart(int $partId): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        if ($this->jobCard->sales_invoice_id) {
            $this->dispatch('toast', message: 'Cannot remove parts after Service Invoice has been generated.', type: 'error');
            return;
        }

        $part = JobCardPart::where('job_card_id', $this->jobCard->id)->findOrFail($partId);
        DB::transaction(function () use ($part) {
            if ($part->stock_deducted && $part->product_id) {
                $defaultWhId = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
                if ($defaultWhId) {
                    InventoryService::adjustStock(
                        productId: $part->product_id,
                        warehouseId: $defaultWhId,
                        quantityDelta: (float) $part->quantity,
                        movementType: 'adjustment',
                        referenceType: JobCard::class,
                        referenceId: $this->jobCard->id,
                        referenceNo: $this->jobCard->job_card_no,
                        notes: "Reversed part {$part->part_name} from {$this->jobCard->job_card_no}"
                    );
                }
            }
            $name = $part->part_name;
            $part->delete();
            $this->recalculateTotals();
            JobCardLog::create([
                'job_card_id' => $this->jobCard->id,
                'stage' => 'Spare Part Removed',
                'status' => $this->jobCard->status,
                'notes' => "Removed spare part {$name}",
                'logged_by' => auth()->id(),
            ]);
        });

        $this->loadJobCard();
        $this->dispatch('toast', message: 'Spare part removed.', type: 'success');
    }

    public function recordCustomerApproval(string $decision): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $nextStatus = $decision === 'approved' ? 'in_progress' : 'cancelled';
        $nextProgress = $decision === 'approved' ? max(45, (int) $this->jobCard->progress_percent) : (int) $this->jobCard->progress_percent;

        $this->jobCard->update([
            'customer_approval_status' => $decision,
            'customer_approved_at' => now(),
            'approval_notes' => $this->approval_notes,
            'status' => $nextStatus,
            'progress_percent' => $nextProgress,
        ]);

        JobCardLog::create([
            'job_card_id' => $this->jobCard->id,
            'stage' => 'Customer Approval',
            'status' => $nextStatus,
            'notes' => "Customer {$decision}".($this->approval_notes ? ": {$this->approval_notes}" : ''),
            'logged_by' => auth()->id(),
        ]);

        $this->loadJobCard();
        log_activity('job_cards', 'customer_approval', "Customer {$decision} Job Card {$this->jobCard->job_card_no}", $this->jobCard);
        $this->dispatch('toast', message: "Customer approval marked as {$decision}.", type: 'success');
    }

    public function recordQualityCheck(string $result): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $nextStatus = $result === 'passed' ? 'completed' : 'in_progress';
        $nextProgress = $result === 'passed' ? 100 : 75;

        $this->jobCard->update([
            'qc_status' => $result,
            'qc_checked_by' => auth()->id(),
            'qc_checked_at' => now(),
            'qc_notes' => $this->qc_notes,
            'status' => $nextStatus,
            'progress_percent' => $nextProgress,
            'completed_at' => $result === 'passed' ? now() : null,
        ]);

        JobCardLog::create([
            'job_card_id' => $this->jobCard->id,
            'stage' => 'Quality Checking',
            'status' => $nextStatus,
            'notes' => "QC {$result}".($this->qc_notes ? ": {$this->qc_notes}" : ''),
            'logged_by' => auth()->id(),
        ]);

        $this->loadJobCard();
        log_activity('job_cards', 'quality_check', "QC {$result} for Job Card {$this->jobCard->job_card_no}", $this->jobCard);
        $this->dispatch('toast', message: "Quality Check marked as {$result}.", type: 'success');
    }

    public function markDelivered(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $this->jobCard->update([
            'status' => 'delivered',
            'progress_percent' => 100,
            'completed_at' => $this->jobCard->completed_at ?? now(),
            'delivered_at' => now(),
            'delivered_to' => $this->delivered_to ?: $this->jobCard->customer?->name,
            'delivery_notes' => $this->delivery_notes,
        ]);

        JobCardLog::create([
            'job_card_id' => $this->jobCard->id,
            'stage' => 'Device Delivered',
            'status' => 'delivered',
            'notes' => "Delivered to {$this->delivered_to}".($this->delivery_notes ? " — {$this->delivery_notes}" : ''),
            'logged_by' => auth()->id(),
        ]);

        $this->loadJobCard();
        log_activity('job_cards', 'job_card_delivered', "Delivered Job Card {$this->jobCard->job_card_no}", $this->jobCard);
        $this->dispatch('toast', message: 'Device delivery recorded.', type: 'success');
    }

    public function generateServiceInvoice(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'create'), 403);
        if ($this->jobCard->sales_invoice_id) {
            $this->dispatch('toast', message: 'A Service Invoice has already been generated for this Job Card.', type: 'error');
            return;
        }

        $this->recalculateTotals();
        $this->jobCard->refresh();

        $inv = DB::transaction(function () {
            $invNo = erp_next_number('sales_invoice_prefix', 'sales_invoices', 'invoice_no', 'INV-', 4);
            $taxRate = erp_setting('tax_enabled', true) ? (float) erp_setting('default_tax_rate', 5) : 0;
            $subtotal = (float) $this->jobCard->parts_total + (float) $this->jobCard->labor_charges;
            $tax = (float) $this->jobCard->tax_amount;
            $total = (float) $this->jobCard->total_amount;

            $inv = SalesInvoice::create([
                'invoice_no' => $invNo,
                'job_card_id' => $this->jobCard->id,
                'source' => 'job_card',
                'customer_id' => $this->jobCard->customer_id,
                'warehouse_id' => $this->jobCard->warehouse_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(),
                'status' => 'unpaid',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'discount_amount' => $this->jobCard->discount_amount,
                'shipping_charge' => 0,
                'total_amount' => $total,
                'paid_amount' => 0,
                'balance_due' => $total,
                'notes' => "Service Billing for Job Card {$this->jobCard->job_card_no} ({$this->jobCard->item_name})",
                'terms' => erp_setting('invoice_terms'),
                'created_by' => auth()->id(),
            ]);

            if ($this->jobCard->labor_charges > 0) {
                $laborTax = round((float) $this->jobCard->labor_charges * ($taxRate / 100), 2);
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $inv->id,
                    'product_id' => null,
                    'description' => "Service & Labor Charges ({$this->jobCard->item_name})",
                    'quantity' => 1,
                    'unit_price' => $this->jobCard->labor_charges,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $laborTax,
                    'line_total' => round((float) $this->jobCard->labor_charges + $laborTax, 2),
                ]);
            }

            foreach ($this->jobCard->parts as $part) {
                $partTax = round((float) $part->line_total * ($taxRate / 100), 2);
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $inv->id,
                    'product_id' => $part->product_id,
                    'description' => "Part: {$part->part_name}",
                    'quantity' => $part->quantity,
                    'unit_price' => $part->unit_price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $partTax,
                    'line_total' => round((float) $part->line_total + $partTax, 2),
                ]);
            }

            $this->jobCard->update(['sales_invoice_id' => $inv->id]);

            JobCardLog::create([
                'job_card_id' => $this->jobCard->id,
                'stage' => 'Service Invoice Generated',
                'status' => $this->jobCard->status,
                'notes' => "Generated Service Invoice {$invNo} for ".format_currency($total),
                'logged_by' => auth()->id(),
            ]);

            return $inv;
        });

        $this->loadJobCard();
        log_activity('job_cards', 'service_invoiced', "Generated Service Invoice {$inv->invoice_no} from {$this->jobCard->job_card_no}", $inv);
        $this->dispatch('toast', message: "Service Invoice {$inv->invoice_no} generated.", type: 'success');
    }

    public function updatedNewPhotos(): void
    {
        $this->validate([
            'newPhotos.*' => ['image', 'max:5120'],
        ]);

        foreach ($this->newPhotos as $file) {
            $this->photos[] = $file;
        }
        $this->newPhotos = [];
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function uploadImages(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $this->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:5120'],
            'photoCaption' => ['nullable', 'string', 'max:150'],
            'photoStage' => ['required', 'string', 'max:30'],
        ]);

        $count = count($this->photos);
        foreach ($this->photos as $photo) {
            $path = $photo->store('job-cards', 'public');
            JobCardImage::create([
                'job_card_id' => $this->jobCard->id,
                'image_path' => $path,
                'caption' => $this->photoCaption ?: null,
                'stage' => $this->photoStage,
                'uploaded_by' => auth()->id(),
            ]);
        }

        JobCardLog::create([
            'job_card_id' => $this->jobCard->id,
            'stage' => 'Images Uploaded',
            'status' => $this->jobCard->status,
            'notes' => "Attached {$count} image(s) for {$this->photoStage}".($this->photoCaption ? " ({$this->photoCaption})" : ''),
            'logged_by' => auth()->id(),
        ]);

        $this->reset(['photos', 'newPhotos', 'photoCaption']);
        $this->loadJobCard();
        log_activity('job_cards', 'job_card_images_uploaded', "Uploaded {$count} image(s) to {$this->jobCard->job_card_no}", $this->jobCard);
        $this->dispatch('toast', message: 'Images attached to Job Card.', type: 'success');
    }

    public function deleteImage(int $imageId): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $img = JobCardImage::where('job_card_id', $this->jobCard->id)->findOrFail($imageId);
        Storage::disk('public')->delete($img->image_path);
        $img->delete();
        $this->loadJobCard();
        $this->dispatch('toast', message: 'Image deleted from Job Card.', type: 'success');
    }

    public function openCollectPayment(): void
    {
        if (! $this->jobCard->salesInvoice) {
            return;
        }
        $this->pay_amount = (float) $this->jobCard->salesInvoice->balance_due;
        $this->pay_date = now()->toDateString();
        $this->pay_reference = '';
        $this->showPayModal = true;
    }

    public function collectPayment(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'edit'), 403);
        $inv = $this->jobCard->salesInvoice;
        $this->validate([
            'pay_amount' => ['required', 'numeric', 'min:0.01', 'max:'.max(0.01, (float) $inv->balance_due)],
            'pay_date' => ['required', 'date'],
        ]);

        DB::transaction(function () use ($inv) {
            $rcpNo = erp_next_number('receipt_prefix', 'customer_payments', 'receipt_no', 'RCP-', 4);
            CustomerPayment::create([
                'receipt_no' => $rcpNo,
                'customer_id' => $inv->customer_id,
                'sales_invoice_id' => $inv->id,
                'payment_date' => $this->pay_date,
                'amount' => $this->pay_amount,
                'payment_method_id' => $this->payment_method_id,
                'bank_account_id' => $this->bank_account_id,
                'reference' => $this->pay_reference,
                'created_by' => auth()->id(),
            ]);

            $inv->recalculatePaymentStatus();

            FinanceService::recordTransaction(
                date: $this->pay_date,
                type: 'inflow',
                amount: $this->pay_amount,
                category: 'Service Billing Receipt',
                paymentMethodId: $this->payment_method_id,
                bankAccountId: $this->bank_account_id,
                partyType: Customer::class,
                partyId: $inv->customer_id,
                partyName: $inv->customer?->name,
                referenceType: SalesInvoice::class,
                referenceId: $inv->id,
                referenceNo: $rcpNo,
                description: "Service payment for {$this->jobCard->job_card_no} ({$inv->invoice_no})"
            );

            JobCardLog::create([
                'job_card_id' => $this->jobCard->id,
                'stage' => 'Service Payment Collected',
                'status' => $this->jobCard->status,
                'notes' => "Collected ".format_currency($this->pay_amount)." via {$rcpNo}",
                'logged_by' => auth()->id(),
            ]);
        });

        $this->showPayModal = false;
        $this->loadJobCard();
        $this->dispatch('toast', message: 'Service payment collected & posted to Cash/Bank Book.', type: 'success');
    }

    public function render()
    {
        return view('livewire.job-cards.job-card-detail', [
            'employees' => Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(),
            'products' => Product::where('status', 'active')->where('is_service', false)->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
            'bankAccounts' => BankAccount::where('status', 'active')->get(),
        ]);
    }
}
