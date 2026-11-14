<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderService
{
    public function place(Customer $customer, Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($customer, $cart, $data) {
            $cart = Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
            abort_unless($cart->status === 'active' && (int) $cart->customer_id === (int) $customer->id, 409, 'This cart has already been checked out.');

            $cartItems = $cart->items()->with('product')->lockForUpdate()->get();
            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $subtotal = 0.0;
            $tax = 0.0;
            $lines = [];
            foreach ($cartItems as $cartItem) {
                $product = Product::query()->whereKey($cartItem->product_id)->lockForUpdate()->first();
                app(CartService::class)->assertOrderable($product, (int) $cartItem->quantity);
                $price = (float) ($product->sale_price ?? $product->mrp);
                $total = round($price * (int) $cartItem->quantity, 2);
                $rate = (float) $product->gst_rate;
                $subtotal += $total;
                $tax += $rate > 0 ? round($total * $rate / (100 + $rate), 2) : 0;
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => (int) $cartItem->quantity,
                    'unit_price' => $price,
                    'tax_rate' => $rate,
                    'line_total' => $total,
                ];
            }

            $zone = null;
            $shipping = 0.0;
            if ($data['fulfillment'] === 'delivery') {
                $zone = ShippingZone::query()->where('is_active', true)->lockForUpdate()->find($data['shipping_zone_id'] ?? null);
                if (! $zone) {
                    throw ValidationException::withMessages(['shipping_zone_id' => 'Choose an available delivery area.']);
                }
                $allowedPincodes = collect(preg_split('/[,\s]+/', (string) $zone->pincodes))->filter();
                if ($allowedPincodes->isNotEmpty() && ! $allowedPincodes->contains($data['shipping_pincode'])) {
                    throw ValidationException::withMessages(['shipping_pincode' => 'Delivery is not available for this PIN code.']);
                }
                $shipping = $zone->free_above !== null && $subtotal >= $zone->free_above ? 0.0 : (float) $zone->charge;
            }

            $coupon = null;
            $discount = 0.0;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()->where('code', Str::upper(trim($data['coupon_code'])))->lockForUpdate()->first();
                if (! $coupon || ! $coupon->isUsable()) {
                    throw ValidationException::withMessages(['coupon_code' => 'This coupon is not valid.']);
                }
                if ($coupon->min_order !== null && $subtotal < $coupon->min_order) {
                    throw ValidationException::withMessages(['coupon_code' => 'This order does not meet the coupon minimum.']);
                }
                $discount = $coupon->discount_type === 'percent'
                    ? round($subtotal * $coupon->discount_value / 100, 2)
                    : (float) $coupon->discount_value;
                if ($coupon->max_discount !== null) {
                    $discount = min($discount, (float) $coupon->max_discount);
                }
                $discount = min($discount, $subtotal);
                $coupon->increment('used_count');
            }

            $address = $data['fulfillment'] === 'delivery'
                ? trim($data['shipping_address'].', '.$data['shipping_city'])
                : 'Store pickup';

            $order = Order::query()->create([
                'order_number' => 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'customer_id' => $customer->id,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'delivery_status' => 'pending',
                'subtotal' => round($subtotal, 2),
                'discount_total' => round($discount, 2),
                'shipping_total' => round($shipping, 2),
                'tax_total' => round($tax, 2),
                'grand_total' => round($subtotal - $discount + $shipping, 2),
                'coupon_id' => $coupon?->id,
                'shipping_zone_id' => $zone?->id,
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $address,
                'shipping_pincode' => $data['fulfillment'] === 'delivery' ? $data['shipping_pincode'] : null,
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ]);
            $order->items()->createMany($lines);

            if ($data['fulfillment'] === 'delivery') {
                $customer->forceFill([
                    'phone' => $data['shipping_phone'],
                    'address' => $data['shipping_address'],
                    'city' => $data['shipping_city'],
                    'pincode' => $data['shipping_pincode'],
                ])->save();
            }

            $cart->update(['status' => 'checked_out']);

            return $order;
        });
    }
}
