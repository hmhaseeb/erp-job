<?php

namespace App\Livewire\Inventory;

use App\Models\Warehouse;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Warehouse Management')]
class WarehouseManager extends Component
{
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public string $location = '';
    public string $manager_name = '';
    public string $phone = '';
    public bool $is_default = false;

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:40', 'unique:warehouses,code'.($this->editingId ? ','.$this->editingId : '')],
        ]);

        if ($this->is_default) {
            Warehouse::query()->update(['is_default' => false]);
        }

        Warehouse::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'location' => $this->location,
                'manager_name' => $this->manager_name,
                'phone' => $this->phone,
                'is_default' => $this->is_default,
                'status' => 'active',
            ]
        );

        $this->reset(['editingId', 'name', 'code', 'location', 'manager_name', 'phone', 'is_default']);
        $this->dispatch('toast', message: 'Warehouse saved.', type: 'success');
    }

    public function edit(int $id): void
    {
        $w = Warehouse::findOrFail($id);
        $this->editingId = $w->id;
        $this->name = $w->name;
        $this->code = $w->code;
        $this->location = (string) ($w->location ?? '');
        $this->manager_name = (string) ($w->manager_name ?? '');
        $this->phone = (string) ($w->phone ?? '');
        $this->is_default = (bool) $w->is_default;
    }

    public function render()
    {
        return view('livewire.inventory.warehouse-manager', [
            'warehouses' => Warehouse::withSum('stocks', 'quantity')->orderByDesc('is_default')->get(),
        ]);
    }
}
