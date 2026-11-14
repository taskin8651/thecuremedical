<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function current(Request $request): Cart
    {
        $customer = Auth::guard('customer')->user();
        $token = $request->session()->get('cart_token');
        $guestCart = $token ? Cart::query()->where('session_token', $token)->where('status', 'active')->first() : null;

        if (! $customer) {
            if (! $token) {
                $token = Str::random(60);
                $request->session()->put('cart_token', $token);
            }

            return Cart::query()->firstOrCreate(
                ['session_token' => $token, 'status' => 'active'],
                ['customer_id' => null]
            );
        }

        return DB::transaction(function () use ($customer, $guestCart, $request) {
            $cart = Cart::query()->where('customer_id', $customer->id)->where('status', 'active')->latest('id')->lockForUpdate()->first();

            if (! $cart && $guestCart) {
                $guestCart->forceFill(['customer_id' => $customer->id, 'session_token' => null])->save();
                $cart = $guestCart;
            } else {
                $cart ??= Cart::query()->create(['customer_id' => $customer->id, 'status' => 'active']);
                if ($guestCart && $guestCart->id !== $cart->id) {
                    $this->merge($cart, $guestCart);
                    $guestCart->update(['status' => 'merged']);
                }
            }

            $request->session()->forget('cart_token');

            return $cart;
        });
    }

    public function count(Request $request): int
    {
        $customerId = Auth::guard('customer')->id();
        $token = $request->session()->get('cart_token');
        $cart = Cart::query()->where('status', 'active')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when(! $customerId && $token, fn ($q) => $q->where('session_token', $token))
            ->when(! $customerId && ! $token, fn ($q) => $q->whereRaw('1 = 0'))
            ->latest('id')->first();

        return $cart?->items()->sum('quantity') ?? 0;
    }

    public function add(Request $request, Product $product, int $quantity): Cart
    {
        $this->assertOrderable($product, $quantity);
        $cart = $this->current($request);
        $item = CartItem::query()->where('cart_id', $cart->id)->where('product_id', $product->id)->first();
        $next = ($item?->quantity ?? 0) + $quantity;
        $this->assertOrderable($product, $next);

        if ($item) {
            $item->update(['quantity' => $next, 'unit_price' => $product->sale_price ?? $product->mrp]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->sale_price ?? $product->mrp,
            ]);
        }

        return $cart->fresh(['items.product']);
    }

    public function update(Cart $cart, CartItem $item, int $quantity): void
    {
        abort_unless($item->cart_id === $cart->id, 404);
        $this->assertOrderable($item->product, $quantity);
        $item->update(['quantity' => $quantity, 'unit_price' => $item->product->sale_price ?? $item->product->mrp]);
    }

    public function assertOrderable(?Product $product, int $quantity): void
    {
        if (! $product || ! $product->is_active || ! $product->is_verified || $product->requires_prescription) {
            throw ValidationException::withMessages(['product' => 'This product is not available for online orders. Please contact the pharmacist.']);
        }
        if (($product->sale_price ?? $product->mrp) === null) {
            throw ValidationException::withMessages(['product' => 'This product has no confirmed price. Please contact the store.']);
        }
        if ($quantity < 1 || $quantity > 1000) {
            throw ValidationException::withMessages(['quantity' => 'Choose a quantity between 1 and 1000.']);
        }
        if ($product->track_inventory && $quantity > $product->stock_quantity) {
            throw ValidationException::withMessages(['quantity' => "Only {$product->stock_quantity} unit(s) of {$product->name} are currently available."]);
        }
    }

    private function merge(Cart $target, Cart $source): void
    {
        foreach ($source->items()->with('product')->get() as $item) {
            $product = $item->product;
            if (! $product || ! $product->is_active || ! $product->is_verified || $product->requires_prescription) {
                continue;
            }

            $current = $target->items()->where('product_id', $item->product_id)->first();
            $quantity = ($current?->quantity ?? 0) + $item->quantity;
            $quantity = min($quantity, 1000);
            if ($product->track_inventory) {
                $quantity = min($quantity, (int) $product->stock_quantity);
            }
            if ($quantity < 1) {
                continue;
            }

            $target->items()->updateOrCreate(
                ['product_id' => $item->product_id],
                ['quantity' => $quantity, 'unit_price' => $product->sale_price ?? $product->mrp]
            );
        }
    }
}
