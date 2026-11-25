<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'expected_delivery_date' => 'date',
            'completed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'customer_approved_at' => 'datetime',
            'qc_checked_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
            'labor_charges' => 'decimal:2',
            'parts_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function technician()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function qcChecker()
    {
        return $this->belongsTo(User::class, 'qc_checked_by');
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function parts()
    {
        return $this->hasMany(JobCardPart::class);
    }

    public function images()
    {
        return $this->hasMany(JobCardImage::class)->latest();
    }

    public function logs()
    {
        return $this->hasMany(JobCardLog::class)->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
