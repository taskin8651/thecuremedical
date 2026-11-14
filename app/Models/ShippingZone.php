<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class ShippingZone extends Model
{
    use Auditable;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'charge' => 'float', 'free_above' => 'float'];
}
