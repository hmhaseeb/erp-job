<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'account_name',
        'bank_name',
        'account_number',
        'iban',
        'swift_code',
        'branch',
        'account_type',
        'opening_balance',
        'current_balance',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(AccountTransaction::class);
    }
}
