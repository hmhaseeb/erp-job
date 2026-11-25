<?php

namespace App\Livewire\Inventory;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Suppliers & Vendors')]
class SupplierManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $name = '';
    public string $company_name = '';
    public string $contact_person = '';
    public string $email = '';
    public string $phone = '';
    public string $tax_number = '';
    public string $address = '';
    public string $city = '';
    public string $country = '';
    public float $opening_balance = 0;
    public string $status = 'active';

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'company_name', 'contact_person', 'email', 'phone', 'tax_number', 'address', 'city', 'country']);
        $this->opening_balance = 0;
        $this->status = 'active';
        $this->code = erp_next_number('supplier_prefix', 'suppliers', 'code', 'SUP-', 4);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $s = Supplier::findOrFail($id);
        $this->editingId = $s->id;
        $this->code = $s->code;
        $this->name = $s->name;
        $this->company_name = (string) ($s->company_name ?? '');
        $this->contact_person = (string) ($s->contact_person ?? '');
        $this->email = (string) ($s->email ?? '');
        $this->phone = (string) ($s->phone ?? '');
        $this->tax_number = (string) ($s->tax_number ?? '');
        $this->address = (string) ($s->address ?? '');
        $this->city = (string) ($s->city ?? '');
        $this->country = (string) ($s->country ?? '');
        $this->opening_balance = (float) $s->opening_balance;
        $this->status = $s->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:40', 'unique:suppliers,code'.($this->editingId ? ','.$this->editingId : '')],
            'name' => ['required', 'string', 'max:150'],
        ]);

        Supplier::updateOrCreate(
            ['id' => $this->editingId],
            [
                'code' => $this->code,
                'name' => $this->name,
                'company_name' => $this->company_name,
                'contact_person' => $this->contact_person,
                'email' => $this->email,
                'phone' => $this->phone,
                'tax_number' => $this->tax_number,
                'address' => $this->address,
                'city' => $this->city,
                'country' => $this->country,
                'opening_balance' => $this->opening_balance,
                'status' => $this->status,
            ]
        );

        $this->showModal = false;
        log_activity('inventory', 'supplier_saved', "Saved supplier/vendor {$this->name}");
        $this->dispatch('toast', message: 'Supplier / Vendor saved.', type: 'success');
    }

    public function deleteSupplier(int $id): void
    {
        Supplier::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Supplier removed.', type: 'success');
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('company_name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.inventory.supplier-manager', ['suppliers' => $suppliers]);
    }
}
