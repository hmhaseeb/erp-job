<?php

namespace App\Livewire\Inventory;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categories, Subcategories, Brands & Units')]
class CategoryManager extends Component
{
    public ?int $editingCatId = null;
    public ?int $parent_id = null;
    public string $cat_name = '';
    public string $cat_code = '';
    public string $cat_description = '';

    public string $brand_name = '';
    public string $unit_name = '';
    public string $unit_short = '';

    public function saveCategory(): void
    {
        $this->validate([
            'cat_name' => ['required', 'string', 'max:100'],
            'cat_code' => ['required', 'string', 'max:40', 'unique:categories,code'.($this->editingCatId ? ','.$this->editingCatId : '')],
        ]);

        Category::updateOrCreate(
            ['id' => $this->editingCatId],
            [
                'parent_id' => $this->parent_id ?: null,
                'name' => $this->cat_name,
                'code' => strtoupper($this->cat_code),
                'description' => $this->cat_description,
                'status' => 'active',
            ]
        );

        $this->reset(['editingCatId', 'parent_id', 'cat_name', 'cat_code', 'cat_description']);
        $this->dispatch('toast', message: 'Category saved.', type: 'success');
    }

    public function editCategory(int $id): void
    {
        $c = Category::findOrFail($id);
        $this->editingCatId = $c->id;
        $this->parent_id = $c->parent_id;
        $this->cat_name = $c->name;
        $this->cat_code = $c->code;
        $this->cat_description = (string) ($c->description ?? '');
    }

    public function deleteCategory(int $id): void
    {
        Category::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Category deleted.', type: 'success');
    }

    public function addBrand(): void
    {
        $this->validate(['brand_name' => ['required', 'string', 'max:80', 'unique:brands,name']]);
        Brand::create(['name' => $this->brand_name, 'status' => 'active']);
        $this->brand_name = '';
        $this->dispatch('toast', message: 'Brand added.', type: 'success');
    }

    public function deleteBrand(int $id): void
    {
        Brand::findOrFail($id)->delete();
    }

    public function addUnit(): void
    {
        $this->validate([
            'unit_name' => ['required', 'string', 'max:60', 'unique:units,name'],
            'unit_short' => ['required', 'string', 'max:20'],
        ]);
        Unit::create(['name' => $this->unit_name, 'short_name' => $this->unit_short, 'status' => 'active']);
        $this->reset(['unit_name', 'unit_short']);
        $this->dispatch('toast', message: 'Unit added.', type: 'success');
    }

    public function deleteUnit(int $id): void
    {
        Unit::findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.inventory.category-manager', [
            'parentCategories' => Category::whereNull('parent_id')->with('subcategories')->orderBy('name')->get(),
            'allCategories' => Category::with('parent')->orderBy('parent_id')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }
}
