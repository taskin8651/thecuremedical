<?php

namespace App\Models;

use App\Support\Notifier;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes, Auditable;

    public const STATUSES = ['pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
    public const PAYMENT_STATUSES = ['unpaid' => 'Unpaid', 'paid' => 'Paid', 'partially_refunded' => 'Partially refunded', 'refunded' => 'Refunded', 'failed' => 'Failed'];
    public const DELIVERY_STATUSES = ['pending' => 'Pending', 'packed' => 'Packed', 'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered', 'failed' => 'Failed'];

    protected $guarded = [];

    protected $casts = ['placed_at' => 'datetime', 'estimated_delivery' => 'date', 'grand_total' => 'float', 'subtotal' => 'float', 'discount_total' => 'float', 'shipping_total' => 'float', 'tax_total' => 'float'];

    protected static function booted(): void
    {
        static::created(fn (Order $o) => Notifier::toPermission('orders.view', 'New order', $o->order_number, route('admin.orders.show', $o->id), 'bi-receipt'));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function shippingZone()
    {
        return $this->belongsTo(ShippingZone::class);
    }
}
