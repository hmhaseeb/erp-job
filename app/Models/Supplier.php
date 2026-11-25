<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2'];
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'default_supplier_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $invoiced = (float) $this->purchaseInvoices()->where('status', '!=', 'cancelled')->sum('total_amount');
        $paid = (float) $this->payments()->sum('amount');
        $returned = (float) $this->returns()->sum('total_amount');

        return round((float) $this->opening_balance + $invoiced - $paid - $returned, 2);
    }
}
