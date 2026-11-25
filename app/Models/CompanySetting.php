<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tax_enabled' => 'boolean',
            'tax_inclusive' => 'boolean',
            'is_rtl' => 'boolean',
            'show_logo_on_docs' => 'boolean',
            'show_tax_on_docs' => 'boolean',
            'notify_low_stock' => 'boolean',
            'notify_invoice_created' => 'boolean',
            'notify_payroll_processed' => 'boolean',
            'notify_job_card_updates' => 'boolean',
            'maintenance_mode' => 'boolean',
            'default_tax_rate' => 'decimal:2',
            'fiscal_lock_date' => 'date',
        ];
    }
}
