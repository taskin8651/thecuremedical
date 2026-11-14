<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $guarded = [];

    protected $casts = ['expiry_date' => 'date', 'expiry_notified_at' => 'datetime', 'cost_price' => 'float'];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function daysToExpiry(): ?int
    {
        return $this->expiry_date ? (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false) : null;
    }

    public function expiryState(): string
    {
        $d = $this->daysToExpiry();
        if ($d === null) {
            return 'none';
        }

        return $d < 0 ? 'expired' : ($d <= (int) setting('expiry_alert_days', 60) ? 'soon' : 'ok');
    }
}
