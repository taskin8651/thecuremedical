<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use Auditable;

    public const STATUSES = ['pending' => 'Pending', 'verified' => 'Verified', 'failed' => 'Failed'];

    protected $guarded = [];

    protected $casts = ['paid_at' => 'datetime', 'verified_at' => 'datetime', 'amount' => 'float'];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function refunds()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
