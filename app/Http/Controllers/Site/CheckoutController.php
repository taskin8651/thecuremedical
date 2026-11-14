<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\CustomerOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function create(Request $request, CartService $carts)
    {
        $cart = $carts->current($request)->load(['items.product']);
        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $customer = $request->user('customer');
        $zones = ShippingZone::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->filter(function (ShippingZone $zone) use ($customer) {
                $pincodes = collect(preg_split('/[,\s]+/', (string) $zone->pincodes))->filter();

                return $pincodes->isEmpty() || ! $customer->pincode || $pincodes->contains($customer->pincode);
            });

        return view('site.checkout', [
            'cart' => $cart,
            'customer' => $customer,
            'zones' => $zones,
            'showPrices' => (bool) setting('show_prices'),
            'canOrder' => feature('ordering') && (bool) setting('show_prices'),
            'shippingPincode' => old('shipping_pincode', $customer->pincode),
        ]);
    }

    public function store(Request $request, CartService $carts, CustomerOrderService $orders)
    {
        abort_unless(feature('ordering') && (bool) setting('show_prices'), 403, 'Online checkout is currently unavailable.');

        $data = $request->validate([
            'cart_id' => ['required', 'integer'],
            'fulfillment' => ['required', Rule::in(['pickup', 'delivery'])],
            'shipping_name' => ['required', 'string', 'max:150'],
            'shipping_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:500'],
            'shipping_city' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:100'],
            'shipping_pincode' => ['required_if:fulfillment,delivery', 'nullable', 'string', 'max:12'],
            'shipping_zone_id' => ['required_if:fulfillment,delivery', 'nullable', 'integer', 'exists:shipping_zones,id'],
            'payment_method' => ['required', Rule::in(['store', 'cod'])],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
            'terms' => ['accepted'],
        ]);

        if ($data['fulfillment'] === 'pickup' && $data['payment_method'] !== 'store') {
            throw ValidationException::withMessages(['payment_method' => 'Store pickup must be paid at the store.']);
        }

        $customer = $request->user('customer');
        $cart = $carts->current($request);
        abort_unless((int) $data['cart_id'] === $cart->id, 409, 'Your cart changed. Please review it again.');
        $order = $orders->place($customer, $cart, $data);

        return redirect()->route('customer.orders.show', $order->order_number)
            ->with('status', 'Your order has been placed. The store will confirm availability and delivery details.');
    }
}
