<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request, CartService $carts)
    {
        $cart = $carts->current($request)->load(['items.product']);

        return view('site.cart', [
            'cart' => $cart,
            'showPrices' => (bool) setting('show_prices'),
            'canOrder' => feature('ordering') && (bool) setting('show_prices'),
        ]);
    }

    public function add(Request $request, CartService $carts, int $product)
    {
        abort_unless(feature('ordering') && (bool) setting('show_prices'), 403, 'Online ordering is currently unavailable.');
        $data = $request->validate(['quantity' => ['nullable', 'integer', 'min:1', 'max:1000']]);
        $item = Product::query()->active()->findOrFail($product);
        $carts->add($request, $item, (int) ($data['quantity'] ?? 1));

        return redirect()->route('cart.index')->with('status', $item->name.' added to your cart.');
    }

    public function update(Request $request, CartService $carts, int $item)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000']]);
        $cart = $carts->current($request);
        $cartItem = CartItem::query()->findOrFail($item);
        $carts->update($cart, $cartItem, (int) $data['quantity']);

        return back()->with('status', 'Cart updated.');
    }

    public function remove(Request $request, CartService $carts, int $item)
    {
        $cart = $carts->current($request);
        $cartItem = CartItem::query()->findOrFail($item);
        abort_unless($cartItem->cart_id === $cart->id, 404);
        $cartItem->delete();

        return back()->with('status', 'Item removed from your cart.');
    }
}
