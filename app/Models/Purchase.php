<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['purchase_date' => 'date', 'expiry_date' => 'date', 'unit_cost' => 'float', 'total_cost' => 'float'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
