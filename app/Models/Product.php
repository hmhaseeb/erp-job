<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'is_service' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function defaultSupplier()
    {
        return $this->belongsTo(Supplier::class, 'default_supplier_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class)->latest();
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->latest();
    }

    public function salesInvoiceItems()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function purchaseInvoiceItems()
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    public function jobCardParts()
    {
        return $this->hasMany(JobCardPart::class);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->is_service) {
            return 'Service';
        }
        if ((float) $this->stock_quantity <= 0) {
            return 'Out of Stock';
        }
        if ((float) $this->stock_quantity <= (float) $this->reorder_level) {
            return 'Low Stock';
        }

        return 'In Stock';
    }
}
