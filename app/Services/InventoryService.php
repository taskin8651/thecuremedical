<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place stock quantities change. Every change is one or more rows in the
 * append-only inventory_transactions ledger, inside a DB transaction with a row lock.
 */
class InventoryService
{
    /**
     * @param  string  $type  in | out | adjustment
     * @param  int  $quantity  in/out: units (>0). adjustment: the counted quantity (>=0).
     * @param  array  $opts  batch_number, expiry_date, batch_id, supplier_id, purchase_id, cost, reason, reference, notes
     * @return InventoryTransaction[]
     */
    public function move(Product $product, string $type, int $quantity, array $opts = []): array
    {
        return DB::transaction(function () use ($product, $type, $quantity, $opts) {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = (int) $product->stock_quantity;
            $rows = [];

            if ($type === 'in') {
                $this->assertPositive($quantity);
                $batch = $this->receiveBatch($product, $quantity, $opts);
                $rows[] = $this->record($product, $batch?->id, 'in', $quantity, $before + $quantity, $opts);
                $product->stock_quantity = $before + $quantity;
            } elseif ($type === 'out') {
                $this->assertPositive($quantity);
                if ($quantity > $before) {
                    throw ValidationException::withMessages(['quantity' => "Only {$before} unit(s) in stock."]);
                }
                $balance = $before;
                foreach ($this->allocate($product, $quantity, $opts['batch_id'] ?? null) as [$batchId, $take]) {
                    $balance -= $take;
                    $rows[] = $this->record($product, $batchId, 'out', -$take, $balance, $opts);
                }
                $product->stock_quantity = $before - $quantity;
            } elseif ($type === 'adjustment') {
                if ($quantity < 0) {
                    throw ValidationException::withMessages(['counted' => 'Counted quantity cannot be negative.']);
                }
                $delta = $quantity - $before;
                if ($delta === 0) {
                    throw ValidationException::withMessages(['counted' => 'Counted quantity equals the current stock. Nothing to adjust.']);
                }
                $rows[] = $this->record($product, null, 'adjustment', $delta, $quantity, $opts);
                $product->stock_quantity = $quantity;
            } else {
                throw new \InvalidArgumentException('Unknown stock movement type.');
            }

            $product->save();
            $this->alertIfLow($product, $before);

            return $rows;
        });
    }

    private function assertPositive(int $q): void
    {
        if ($q < 1) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be at least 1.']);
        }
    }

    private function receiveBatch(Product $product, int $quantity, array $opts): ?ProductBatch
    {
        if (empty($opts['batch_number'])) {
            return null;
        }
        $batch = ProductBatch::query()->where('product_id', $product->id)->where('batch_number', $opts['batch_number'])->lockForUpdate()->first();
        if ($batch) {
            $batch->quantity += $quantity;
            if (! empty($opts['expiry_date']) && ! $batch->expiry_date) {
                $batch->expiry_date = $opts['expiry_date'];
            }
            $batch->save();

            return $batch;
        }

        return ProductBatch::query()->create([
            'product_id' => $product->id,
            'supplier_id' => $opts['supplier_id'] ?? null,
            'purchase_id' => $opts['purchase_id'] ?? null,
            'batch_number' => $opts['batch_number'],
            'expiry_date' => $opts['expiry_date'] ?? null,
            'quantity' => $quantity,
            'cost_price' => $opts['cost'] ?? null,
        ]);
    }

    /** @return array<int,array{0:?int,1:int}> list of [batchId|null, units] */
    private function allocate(Product $product, int $quantity, ?int $batchId): array
    {
        if ($batchId) {
            $batch = ProductBatch::query()->where('product_id', $product->id)->lockForUpdate()->find($batchId);
            if (! $batch || $batch->quantity < $quantity) {
                throw ValidationException::withMessages(['batch_id' => 'The selected batch does not have enough units.']);
            }
            $batch->decrement('quantity', $quantity);

            return [[$batch->id, $quantity]];
        }

        // First-expiry-first-out across batches that still have units.
        $plan = [];
        $left = $quantity;
        $batches = ProductBatch::query()->where('product_id', $product->id)->where('quantity', '>', 0)
            ->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->orderBy('id')->lockForUpdate()->get();
        foreach ($batches as $b) {
            if ($left <= 0) {
                break;
            }
            $take = min($left, $b->quantity);
            $b->decrement('quantity', $take);
            $plan[] = [$b->id, $take];
            $left -= $take;
        }
        if ($left > 0) {
            $plan[] = [null, $left]; // stock that was never assigned to a batch
        }

        return $plan;
    }

    private function record(Product $product, ?int $batchId, string $type, int $signed, int $balance, array $opts): InventoryTransaction
    {
        return InventoryTransaction::query()->create([
            'product_id' => $product->id,
            'batch_id' => $batchId,
            'type' => $type,
            'quantity' => $signed,
            'balance_after' => $balance,
            'reason' => $opts['reason'] ?? null,
            'reference' => $opts['reference'] ?? null,
            'notes' => $opts['notes'] ?? null,
            'user_id' => auth()->id(),
        ]);
    }

    private function alertIfLow(Product $product, int $before): void
    {
        if (! $product->track_inventory || ! feature('inventory')) {
            return;
        }
        $after = (int) $product->stock_quantity;
        $min = (int) $product->min_quantity;
        $url = route('admin.inventory.index', ['status' => $after <= 0 ? 'out' : 'low']);

        if ($after <= 0 && $before > 0) {
            Notifier::toPermission('inventory.view', 'Out of stock', $product->name, $url, 'bi-box-seam', 'danger');
        } elseif ($after <= $min && $before > $min) {
            Notifier::toPermission('inventory.view', 'Low stock', "{$product->name} · {$after} left (minimum {$min})", $url, 'bi-exclamation-triangle', 'warning');
        }
    }
}
