<?php

namespace App\Livewire\JobCards;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\JobCard;
use App\Models\JobCardImage;
use App\Models\JobCardLog;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Job Cards & Service Registry')]
class JobCardIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $priorityFilter = '';
    public bool $showCreateModal = false;

    public ?int $customer_id = null;
    public string $customerSearch = '';
    public ?int $product_id = null;
    public string $parts_source = 'direct'; // direct (Direct Shop) | site (On-Site)
    public string $service_location = '';
    public ?int $assigned_to = null;
    public string $item_name = '';
    public string $brand_model = '';
    public string $serial_number = '';
    public string $accessories_received = '';
    public string $service_description = '';
    public string $priority = 'medium';
    public string $received_date = '';
    public ?string $expected_delivery_date = null;
    public float $estimated_cost = 0;

    // Multi-photo upload buffer + previews
    public array $photos = [];
    public array $newPhotos = [];

    // Inline Quick-Add Customer
    public bool $showQuickCustomer = false;
    public string $new_cust_name = '';
    public string $new_cust_phone = '';
    public string $new_cust_email = '';
    public string $new_cust_company = '';
    public string $new_cust_address = '';

    public function mount(): void
    {
        $this->received_date = now()->toDateString();
        $this->expected_delivery_date = now()->addDays(3)->toDateString();
        $this->parts_source = 'direct';
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingPriorityFilter(): void { $this->resetPage(); }

    public function selectCustomer(int $customerId): void
    {
        $c = Customer::find($customerId);
        if (! $c) {
            return;
        }
        $this->customer_id = $c->id;
        $this->customerSearch = '';
        $this->showQuickCustomer = false;
        if ($this->parts_source === 'site' && ! $this->service_location && $c->billing_address) {
            $this->service_location = $c->billing_address;
        }
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
        $this->customerSearch = '';
    }

    public function updatedPartsSource($val): void
    {
        if ($val === 'site' && ! $this->service_location && $this->customer_id) {
            $this->service_location = (string) (Customer::find($this->customer_id)?->billing_address ?? '');
        }
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

    public function saveQuickCustomer(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'create') || auth()->user()->hasPermission('sales', 'create'), 403);
        $this->validate([
            'new_cust_name' => ['required', 'string', 'max:150'],
            'new_cust_email' => ['nullable', 'email', 'max:150'],
        ]);

        $cust = Customer::create([
            'code' => erp_next_number('customer_prefix', 'customers', 'code', 'CUST-', 4),
            'name' => $this->new_cust_name,
            'phone' => $this->new_cust_phone,
            'email' => $this->new_cust_email,
            'company_name' => $this->new_cust_company,
            'billing_address' => $this->new_cust_address,
            'credit_limit' => 10000,
            'opening_balance' => 0,
            'status' => 'active',
        ]);

        $this->customer_id = $cust->id;
        $this->customerSearch = '';
        if ($this->parts_source === 'site' && ! $this->service_location && $cust->billing_address) {
            $this->service_location = $cust->billing_address;
        }
        $this->reset(['new_cust_name', 'new_cust_phone', 'new_cust_email', 'new_cust_company', 'new_cust_address', 'showQuickCustomer']);
        log_activity('sales', 'customer_saved', "Quick-created customer {$cust->name} from Job Card", $cust);
        $this->dispatch('toast', message: "Customer {$cust->name} added & selected.", type: 'success');
    }

    public function updatedProductId($id): void
    {
        $p = Product::with('brand')->find($id);
        if ($p) {
            $this->item_name = $p->name;
            if (! $this->brand_model && $p->brand) {
                $this->brand_model = $p->brand->name;
            }
        }
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'create'), 403);
        $this->reset(['customer_id', 'customerSearch', 'product_id', 'assigned_to', 'item_name', 'brand_model', 'serial_number', 'accessories_received', 'service_description', 'service_location', 'photos', 'newPhotos', 'showQuickCustomer']);
        $this->priority = 'medium';
        $this->estimated_cost = 0;
        $this->parts_source = 'direct';
        $this->received_date = now()->toDateString();
        $this->expected_delivery_date = now()->addDays(3)->toDateString();
        $this->showCreateModal = true;
    }

    public function createJobCard(): void
    {
        abort_unless(auth()->user()->hasPermission('job_cards', 'create'), 403);
        $this->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'item_name' => ['required', 'string', 'max:160'],
            'service_description' => ['required', 'string'],
            'received_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:received_date'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'estimated_cost' => ['numeric', 'min:0'],
            'parts_source' => ['required', 'in:direct,site'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $jc = DB::transaction(function () {
            $jcNo = erp_next_number('job_card_prefix', 'job_cards', 'job_card_no', 'JC-', 4);

            $jc = JobCard::create([
                'job_card_no' => $jcNo,
                'customer_id' => $this->customer_id,
                'product_id' => $this->product_id ?: null,
                'warehouse_id' => null,
                'parts_source' => $this->parts_source,
                'service_location' => $this->parts_source === 'site' ? ($this->service_location ?: null) : null,
                'item_name' => $this->item_name,
                'brand_model' => $this->brand_model,
                'serial_number' => $this->serial_number,
                'accessories_received' => $this->accessories_received,
                'service_description' => $this->service_description,
                'assigned_to' => $this->assigned_to ?: null,
                'priority' => $this->priority,
                'status' => 'registered',
                'progress_percent' => 10,
                'received_date' => $this->received_date,
                'expected_delivery_date' => $this->expected_delivery_date ?: null,
                'estimated_cost' => $this->estimated_cost,
                'created_by' => auth()->id(),
            ]);

            if (! empty($this->photos)) {
                foreach ($this->photos as $photo) {
                    $path = $photo->store('job-cards', 'public');
                    JobCardImage::create([
                        'job_card_id' => $jc->id,
                        'image_path' => $path,
                        'stage' => 'intake',
                        'caption' => 'Intake condition',
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }

            $modeLabel = $this->parts_source === 'site' ? 'On-Site' : 'Direct Shop';
            JobCardLog::create([
                'job_card_id' => $jc->id,
                'stage' => 'Job Card Registered',
                'status' => 'registered',
                'notes' => "Registered device {$this->item_name} (Service Mode: {$modeLabel}).".(! empty($this->photos) ? ' Attached '.count($this->photos).' intake image(s).' : ''),
                'logged_by' => auth()->id(),
            ]);

            return $jc;
        });

        $this->reset(['photos', 'newPhotos']);
        $this->showCreateModal = false;
        log_activity('job_cards', 'job_card_created', "Created Job Card {$jc->job_card_no}", $jc);
        $this->dispatch('toast', message: "Job Card {$jc->job_card_no} registered.", type: 'success');
    }

    public function render()
    {
        $jobCards = JobCard::with(['customer', 'technician', 'salesInvoice'])
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('job_card_no', 'like', "%{$this->search}%")->orWhere('item_name', 'like', "%{$this->search}%")->orWhere('serial_number', 'like', "%{$this->search}%")->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$this->search}%"))))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter, fn ($q) => $q->where('priority', $this->priorityFilter))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        $counts = [
            'total' => JobCard::count(),
            'active' => JobCard::whereNotIn('status', ['completed', 'delivered', 'cancelled'])->count(),
            'qc' => JobCard::where('status', 'quality_check')->count(),
            'ready' => JobCard::where('status', 'completed')->count(),
        ];

        $customers = Customer::query()
            ->when($this->customerSearch, fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$this->customerSearch}%")
                ->orWhere('phone', 'like', "%{$this->customerSearch}%")
                ->orWhere('code', 'like', "%{$this->customerSearch}%")
                ->orWhere('company_name', 'like', "%{$this->customerSearch}%")))
            ->orderBy('name')
            ->limit(40)
            ->get();

        return view('livewire.job-cards.job-card-index', [
            'jobCards' => $jobCards,
            'counts' => $counts,
            'customers' => $customers,
            'selectedCustomer' => $this->customer_id ? Customer::find($this->customer_id) : null,
            'products' => Product::where('is_service', false)->orderBy('name')->get(),
            'employees' => Employee::whereIn('status', ['active', 'probation'])->orderBy('first_name')->get(),
        ]);
    }
}
