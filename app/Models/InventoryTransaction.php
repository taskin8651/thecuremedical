<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only stock ledger. History is never changed or removed.
 */
class InventoryTransaction extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Stock history cannot be edited.'));
        static::deleting(fn () => throw new LogicException('Stock history cannot be deleted.'));
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
