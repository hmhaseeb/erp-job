<?php

namespace App\Livewire\Inventory;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TaxRate;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Products Management')]
class ProductManager extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';
    public string $categoryFilter = '';
    public string $stockFilter = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $code = '';
    public string $name = '';
    public string $sku = '';
    public string $barcode = '';
    public ?int $category_id = null;
    public ?int $subcategory_id = null;
    public ?int $brand_id = null;
    public ?int $unit_id = null;
    public ?int $default_supplier_id = null;
    public ?int $tax_rate_id = null;
    public float $cost_price = 0;
    public float $selling_price = 0;
    public float $stock_quantity = 0;
    public float $reorder_level = 5;
    public bool $is_service = false;
    public string $status = 'active';
    public string $description = '';
    public $imageUpload;
    public array $galleryUploads = [];

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingStockFilter(): void { $this->resetPage(); }

    public function updatedCategoryId(): void
    {
        $this->subcategory_id = null;
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'create'), 403);
        $this->resetForm();
        $this->code = erp_next_number('product_prefix', 'products', 'code', 'PRD-', 4);
        $this->reorder_level = (float) erp_setting('low_stock_threshold', 5);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'edit'), 403);
        $p = Product::findOrFail($id);
        $this->editingId = $p->id;
        $this->code = $p->code;
        $this->name = $p->name;
        $this->sku = $p->sku;
        $this->barcode = (string) ($p->barcode ?? '');
        $this->category_id = $p->category_id;
        $this->subcategory_id = $p->subcategory_id;
        $this->brand_id = $p->brand_id;
        $this->unit_id = $p->unit_id;
        $this->default_supplier_id = $p->default_supplier_id;
        $this->tax_rate_id = $p->tax_rate_id;
        $this->cost_price = (float) $p->cost_price;
        $this->selling_price = (float) $p->selling_price;
        $this->stock_quantity = (float) $p->stock_quantity;
        $this->reorder_level = (float) $p->reorder_level;
        $this->is_service = (bool) $p->is_service;
        $this->status = $p->status;
        $this->description = (string) ($p->description ?? '');
        $this->showModal = true;
    }

    public function save(): void
    {
        $action = $this->editingId ? 'edit' : 'create';
        abort_unless(auth()->user()->hasPermission('inventory', $action), 403);

        $this->validate([
            'code' => ['required', 'string', 'max:40', 'unique:products,code'.($this->editingId ? ','.$this->editingId : '')],
            'name' => ['required', 'string', 'max:190'],
            'sku' => ['required', 'string', 'max:80', 'unique:products,sku'.($this->editingId ? ','.$this->editingId : '')],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'imageUpload' => ['nullable', 'image', 'max:2048'],
            'galleryUploads.*' => ['image', 'max:4096'],
        ]);

        DB::transaction(function () {
            $data = [
                'code' => $this->code,
                'name' => $this->name,
                'sku' => $this->sku,
                'barcode' => $this->barcode,
                'category_id' => $this->category_id ?: null,
                'subcategory_id' => $this->subcategory_id ?: null,
                'brand_id' => $this->brand_id ?: null,
                'unit_id' => $this->unit_id ?: null,
                'default_supplier_id' => $this->default_supplier_id ?: null,
                'tax_rate_id' => $this->tax_rate_id ?: null,
                'cost_price' => $this->cost_price,
                'selling_price' => $this->selling_price,
                'reorder_level' => $this->reorder_level,
                'is_service' => $this->is_service,
                'status' => $this->status,
                'description' => $this->description,
            ];

            if ($this->imageUpload) {
                $data['image'] = $this->imageUpload->store('products', 'public');
            }

            if ($this->editingId) {
                $p = Product::findOrFail($this->editingId);
                $p->update($data);
                log_activity('inventory', 'product_updated', "Updated product {$p->sku} - {$p->name}", $p);
            } else {
                $data['stock_quantity'] = $this->is_service ? 0 : $this->stock_quantity;
                $p = Product::create($data);

                if (! $this->is_service && $this->stock_quantity > 0) {
                    $whId = Warehouse::where('is_default', true)->value('id') ?? Warehouse::value('id');
                    if ($whId) {
                        ProductStock::create([
                            'product_id' => $p->id,
                            'warehouse_id' => $whId,
                            'quantity' => $this->stock_quantity,
                        ]);
                    }
                    StockMovement::create([
                        'product_id' => $p->id,
                        'warehouse_id' => $whId,
                        'movement_type' => 'in',
                        'quantity_change' => $this->stock_quantity,
                        'balance_after' => $this->stock_quantity,
                        'unit_cost' => $this->cost_price,
                        'reference_no' => 'OPENING-STOCK',
                        'notes' => 'Opening stock on product creation',
                        'created_by' => auth()->id(),
                    ]);
                }
                log_activity('inventory', 'product_created', "Created product {$p->sku} - {$p->name}", $p);
            }

            if (! empty($data['image'])) {
                $p->images()->update(['is_primary' => false]);
                ProductImage::create([
                    'product_id' => $p->id,
                    'image_path' => $data['image'],
                    'is_primary' => true,
                    'sort_order' => 0,
                    'uploaded_by' => auth()->id(),
                ]);
            }

            if (! empty($this->galleryUploads)) {
                $sort = (int) $p->images()->max('sort_order') + 1;
                foreach ($this->galleryUploads as $idx => $file) {
                    $path = $file->store('products', 'public');
                    $isFirst = ! $p->image && $idx === 0;
                    ProductImage::create([
                        'product_id' => $p->id,
                        'image_path' => $path,
                        'is_primary' => $isFirst,
                        'sort_order' => $sort + $idx,
                        'uploaded_by' => auth()->id(),
                    ]);
                    if ($isFirst) {
                        $p->update(['image' => $path]);
                    }
                }
            }
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Product saved successfully.', type: 'success');
    }

    public function deleteProduct(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'delete'), 403);
        $p = Product::findOrFail($id);
        $sku = $p->sku;
        $p->delete();
        log_activity('inventory', 'product_deleted', "Deleted product {$sku}");
        $this->dispatch('toast', message: 'Product deleted.', type: 'success');
    }

    public function exportCsv(): StreamedResponse
    {
        $rows = Product::with(['category', 'subcategory', 'brand', 'unit'])->orderBy('name')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Code', 'Name', 'SKU', 'Category', 'Subcategory', 'Brand', 'Unit', 'Qty', 'Cost', 'Selling Price', 'Status']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->code,
                    $p->name,
                    $p->sku,
                    $p->category?->name,
                    $p->subcategory?->name,
                    $p->brand?->name,
                    $p->unit?->short_name,
                    $p->stock_quantity,
                    $p->cost_price,
                    $p->selling_price,
                    $p->stock_status,
                ]);
            }
            fclose($out);
        }, 'products.csv');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'code', 'name', 'sku', 'barcode', 'category_id', 'subcategory_id',
            'brand_id', 'unit_id', 'default_supplier_id', 'tax_rate_id', 'description', 'imageUpload', 'galleryUploads',
        ]);
        $this->cost_price = 0;
        $this->selling_price = 0;
        $this->stock_quantity = 0;
        $this->reorder_level = 5;
        $this->is_service = false;
        $this->status = 'active';
    }

    public function render()
    {
        $products = Product::with(['category', 'subcategory', 'brand', 'unit'])
            ->when($this->search, fn ($q) => $q->where(fn ($sq) => $sq->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->stockFilter === 'low', fn ($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_level')->where('stock_quantity', '>', 0))
            ->when($this->stockFilter === 'out', fn ($q) => $q->where('stock_quantity', '<=', 0))
            ->when($this->stockFilter === 'in', fn ($q) => $q->whereColumn('stock_quantity', '>', 'reorder_level'))
            ->latest()
            ->paginate((int) erp_setting('per_page', 10));

        return view('livewire.inventory.product-manager', [
            'products' => $products,
            'categories' => Category::whereNull('parent_id')->orderBy('name')->get(),
            'subcategories' => Category::whereNotNull('parent_id')->when($this->category_id, fn ($q) => $q->where('parent_id', $this->category_id))->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'stats' => [
                'total' => Product::count(),
                'in_stock' => Product::where('is_service', false)->whereColumn('stock_quantity', '>', 'reorder_level')->count(),
                'low_stock' => Product::where('is_service', false)->whereColumn('stock_quantity', '<=', 'reorder_level')->where('stock_quantity', '>', 0)->count(),
                'out_of_stock' => Product::where('is_service', false)->where('stock_quantity', '<=', 0)->count(),
            ],
        ]);
    }
}
