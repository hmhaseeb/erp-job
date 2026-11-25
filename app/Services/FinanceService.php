<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinanceService
{
    /**
     * Record a Cash Book or Bank Book transaction and update bank balance atomically.
     */
    public static function recordTransaction(
        string $date,
        string $type, // inflow | outflow
        float $amount,
        string $category,
        ?int $paymentMethodId = null,
        ?int $bankAccountId = null,
        ?string $partyType = null,
        ?int $partyId = null,
        ?string $partyName = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $referenceNo = null,
        ?string $description = null
    ): AccountTransaction {
        return DB::transaction(function () use (
            $date,
            $type,
            $amount,
            $category,
            $paymentMethodId,
            $bankAccountId,
            $partyType,
            $partyId,
            $partyName,
            $referenceType,
            $referenceId,
            $referenceNo,
            $description
        ) {
            $method = $paymentMethodId ? PaymentMethod::find($paymentMethodId) : null;
            $resolvedBankId = $bankAccountId ?? $method?->bank_account_id;
            $bookType = ($resolvedBankId || ($method && $method->type !== 'cash')) ? 'bank' : 'cash';

            if ($bookType === 'bank' && ! $resolvedBankId) {
                $resolvedBankId = BankAccount::where('is_default', true)->value('id')
                    ?? BankAccount::query()->value('id');
                if (! $resolvedBankId) {
                    $bookType = 'cash';
                }
            }

            if ($bookType === 'bank' && $resolvedBankId) {
                $bank = BankAccount::lockForUpdate()->find($resolvedBankId);
                if ($bank) {
                    $delta = $type === 'inflow' ? $amount : -$amount;
                    $bank->update(['current_balance' => round((float) $bank->current_balance + $delta, 2)]);
                }
            }

            $txnNo = erp_next_number('txn_prefix', 'account_transactions', 'transaction_no', 'TXN-', 5);

            return AccountTransaction::create([
                'transaction_no' => $txnNo,
                'transaction_date' => $date,
                'book_type' => $bookType,
                'bank_account_id' => $bookType === 'bank' ? $resolvedBankId : null,
                'type' => $type,
                'category' => $category,
                'party_type' => $partyType,
                'party_id' => $partyId,
                'party_name' => $partyName,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'description' => $description,
                'created_by' => Auth::id(),
            ]);
        });
    }
}
