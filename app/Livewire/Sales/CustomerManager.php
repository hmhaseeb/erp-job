<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Customer Management')]
class CustomerManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $name = '';
    public string $company_name = '';
    public string $email = '';
    public string $phone = '';
    public string $tax_number = '';
    public string $billing_address = '';
    public string $city = '';
    public string $country = '';
    public float $credit_limit = 10000;
    public float $opening_balance = 0;

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $this->reset(['editingId', 'name', 'company_name', 'email', 'phone', 'tax_number', 'billing_address', 'city', 'country']);
        $this->credit_limit = 10000;
        $this->opening_balance = 0;
        $this->code = erp_next_number('customer_prefix', 'customers', 'code', 'CUST-', 4);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'edit'), 403);
        $c = Customer::findOrFail($id);
        $this->editingId = $c->id;
        $this->code = $c->code;
        $this->name = $c->name;
        $this->company_name = (string) ($c->company_name ?? '');
        $this->email = (string) ($c->email ?? '');
        $this->phone = (string) ($c->phone ?? '');
        $this->tax_number = (string) ($c->tax_number ?? '');
        $this->billing_address = (string) ($c->billing_address ?? '');
        $this->city = (string) ($c->city ?? '');
        $this->country = (string) ($c->country ?? '');
        $this->credit_limit = (float) $c->credit_limit;
        $this->opening_balance = (float) $c->opening_balance;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:40', 'unique:customers,code'.($this->editingId ? ','.$this->editingId : '')],
            'name' => ['required', 'string', 'max:150'],
        ]);

        Customer::updateOrCreate(
            ['id' => $this->editingId],
            [
                'code' => $this->code,
                'name' => $this->name,
                'company_name' => $this->company_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'tax_number' => $this->tax_number,
                'billing_address' => $this->billing_address,
                'city' => $this->city,
                'country' => $this->country,
                'credit_limit' => $this->credit_limit,
                'opening_balance' => $this->opening_balance,
                'status' => 'active',
            ]
        );

        $this->showModal = false;
        log_activity('sales', 'customer_saved', "Saved customer {$this->name}");
        $this->dispatch('toast', message: 'Customer saved successfully.', type: 'success');
    }

    public function deleteCustomer(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'delete'), 403);
        Customer::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Customer removed.', type: 'success');
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")->orWhere('company_name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.sales.customer-manager', ['customers' => $customers]);
    }
}
