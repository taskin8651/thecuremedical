<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes, Auditable;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'discount_value' => 'float', 'min_order' => 'float', 'max_discount' => 'float'];

    public function isUsable(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->expires_at || $this->expires_at->isFuture())
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
}
