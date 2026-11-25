<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'opening_balance' => 'decimal:2',
        ];
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        $invoiced = (float) $this->salesInvoices()->where('status', '!=', 'cancelled')->sum('total_amount');
        $paid = (float) $this->payments()->sum('amount');
        $returned = (float) $this->returns()->sum('total_amount');

        return round((float) $this->opening_balance + $invoiced - $paid - $returned, 2);
    }
}
