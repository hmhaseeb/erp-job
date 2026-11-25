<?php

namespace App\Support\Concerns;

use Illuminate\Validation\ValidationException;

trait ChecksUniqueLineProducts
{
    /**
     * Determine whether a product_id already exists on another line item index.
     */
    protected function isDuplicateLineProduct(int $currentIdx, $productId): bool
    {
        if (empty($productId)) {
            return false;
        }

        foreach ($this->items as $idx => $item) {
            if ((int) $idx !== $currentIdx && ! empty($item['product_id']) && (int) $item['product_id'] === (int) $productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reject duplicate product selection during interactive item editing.
     */
    protected function rejectDuplicateProductOnIndex(int $idx, $value): bool
    {
        if ($this->isDuplicateLineProduct($idx, $value)) {
            $this->items[$idx]['product_id'] = '';
            $this->addError("items.{$idx}.product_id", 'Already selected on another line.');
            $this->dispatch('toast', message: 'Same product cannot be added twice in bill items.', type: 'error');

            return true;
        }

        $this->resetErrorBag("items.{$idx}.product_id");

        return false;
    }

    /**
     * Ensure no two line items reference the same catalog product.
     */
    protected function ensureUniqueLineProducts(): void
    {
        $seen = [];
        foreach ($this->items as $idx => $item) {
            $pid = $item['product_id'] ?? null;
            if (empty($pid)) {
                continue;
            }
            $pid = (int) $pid;
            if (isset($seen[$pid])) {
                throw ValidationException::withMessages([
                    'items' => ['Bill items cannot contain the same product multiple times.'],
                    "items.{$idx}.product_id" => ['Already selected on another line.'],
                ]);
            }
            $seen[$pid] = true;
        }
    }
}
