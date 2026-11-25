<?php

namespace App\Livewire\Inventory;

use App\Models\JobCardPart;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\PurchaseInvoiceItem;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Product Details')]
class ProductDetail extends Component
{
    use WithFileUploads, WithPagination;

    public Product $product;
    public string $activeTab = 'movements'; // movements | sales | purchases | job_cards
    public string $movementFilter = '';
    public ?string $previewImageUrl = null;

    // Multi-image upload
    public array $photos = [];
    public string $photoCaption = '';

    public function mount(int $id): void
    {
        $this->loadProduct($id);
    }

    private function loadProduct(?int $id = null): void
    {
        $pid = $id ?? $this->product->id;
        $this->product = Product::with([
            'category',
            'subcategory',
            'brand',
            'unit',
            'defaultSupplier',
            'taxRate',
            'images',
        ])->findOrFail($pid);
    }

    public function updatedMovementFilter(): void
    {
        $this->resetPage();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function uploadImages(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'edit'), 403);
        $this->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:4096'],
            'photoCaption' => ['nullable', 'string', 'max:150'],
        ]);

        $hasPrimary = (bool) $this->product->image || $this->product->images()->where('is_primary', true)->exists();
        $nextOrder = (int) $this->product->images()->max('sort_order') + 1;

        foreach ($this->photos as $idx => $file) {
            $path = $file->store('products', 'public');
            $isPrimary = ! $hasPrimary && $idx === 0;

            ProductImage::create([
                'product_id' => $this->product->id,
                'image_path' => $path,
                'caption' => $this->photoCaption ?: null,
                'is_primary' => $isPrimary,
                'sort_order' => $nextOrder + $idx,
                'uploaded_by' => auth()->id(),
            ]);

            if (! $this->product->image) {
                $this->product->update(['image' => $path]);
                $hasPrimary = true;
            }
        }

        $this->reset(['photos', 'photoCaption']);
        $this->loadProduct();
        log_activity('inventory', 'product_images_uploaded', "Uploaded gallery images for {$this->product->name}", $this->product);
        $this->dispatch('toast', message: 'Product images uploaded successfully.', type: 'success');
    }

    public function setPrimaryImage(int $imageId): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'edit'), 403);
        $img = ProductImage::where('product_id', $this->product->id)->findOrFail($imageId);
        $this->product->images()->update(['is_primary' => false]);
        $img->update(['is_primary' => true]);
        $this->product->update(['image' => $img->image_path]);
        $this->loadProduct();
        $this->dispatch('toast', message: 'Primary product image updated.', type: 'success');
    }

    public function deleteGalleryImage(int $imageId): void
    {
        abort_unless(auth()->user()->hasPermission('inventory', 'edit'), 403);
        $img = ProductImage::where('product_id', $this->product->id)->findOrFail($imageId);
        $wasMain = $this->product->image === $img->image_path;
        Storage::disk('public')->delete($img->image_path);
        $img->delete();

        if ($wasMain) {
            $fallback = $this->product->images()->first();
            $this->product->update(['image' => $fallback?->image_path]);
        }

        $this->loadProduct();
        $this->dispatch('toast', message: 'Product image removed.', type: 'success');
    }

    public function render()
    {
        $movements = StockMovement::with(['creator'])
            ->where('product_id', $this->product->id)
            ->when($this->movementFilter, fn ($q) => $q->where('movement_type', $this->movementFilter))
            ->latest('id')
            ->paginate((int) erp_setting('per_page', 10));

        $salesItems = SalesInvoiceItem::with(['salesInvoice.customer'])
            ->where('product_id', $this->product->id)
            ->latest('id')
            ->limit(25)
            ->get();

        $purchaseItems = PurchaseInvoiceItem::with(['purchaseInvoice.supplier'])
            ->where('product_id', $this->product->id)
            ->latest('id')
            ->limit(25)
            ->get();

        $jobCardParts = JobCardPart::with(['jobCard.customer'])
            ->where('product_id', $this->product->id)
            ->latest('id')
            ->limit(25)
            ->get();

        return view('livewire.inventory.product-detail', [
            'movements' => $movements,
            'salesItems' => $salesItems,
            'purchaseItems' => $purchaseItems,
            'jobCardParts' => $jobCardParts,
        ]);
    }
}
