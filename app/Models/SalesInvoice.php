<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_charge' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculatePaymentStatus(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $returned = (float) $this->returns()->sum('total_amount');
        $effectiveTotal = max(0, (float) $this->total_amount - $returned);
        $balance = max(0, round($effectiveTotal - $paid, 2));

        $status = $this->status;
        if ($status !== 'cancelled' && $status !== 'draft') {
            if ($balance <= 0.009) {
                $status = 'paid';
            } elseif ($paid > 0) {
                $status = 'partial';
            } elseif ($this->due_date && $this->due_date->isPast()) {
                $status = 'overdue';
            } else {
                $status = 'unpaid';
            }
        }

        $this->update([
            'paid_amount' => $paid,
            'balance_due' => $balance,
            'status' => $status,
        ]);
    }
}
