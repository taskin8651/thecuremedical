<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingZone;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Orders are staff-entered and only available once the owner switches "Online ordering" on.
 * Prescription-only medicines can never be put on an order. Stock moves when an order is
 * confirmed (and is put back if it is cancelled), always through InventoryService.
 * Prices include GST, so tax is shown for information and is not added on top.
 */
class OrderController extends Controller
{
    /** current status => statuses it may move to */
    private const FLOW = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'shipped', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
        'refunded' => [],
    ];

    private function disabled()
    {
        return view('admin.partials.disabled', [
            'title' => 'Orders', 'what' => 'Online ordering', 'setting' => 'ordering_enabled',
            'text' => 'Ordering, delivery and refunds stay off until the owner confirms the licensing, prescription and delivery rules that apply to the shop.',
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);
        if (! feature('ordering')) {
            return $this->disabled();
        }

        $query = Order::query()->with('customer')->latest('id');
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('order_number', 'like', $like)->orWhere('shipping_name', 'like', $like)->orWhere('shipping_phone', 'like', $like)
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like)));
        }
        foreach (['status' => Order::STATUSES, 'payment_status' => Order::PAYMENT_STATUSES] as $k => $allowed) {
            if (array_key_exists((string) $request->input($k), $allowed)) {
                $query->where($k, $request->input($k));
            }
        }
        if ($request->filled('from') && strtotime((string) $request->input('from'))) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to') && strtotime((string) $request->input('to'))) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        return view('admin.orders.index', ['orders' => $query->paginate(20)->withQueryString()]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('orders.edit'), 403);
        if (! feature('ordering')) {
            return $this->disabled();
        }

        return view('admin.orders.create', [
            'customers' => Customer::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']),
            'products' => Product::query()->active()->where('requires_prescription', false)->whereNotNull('mrp')->orderBy('name')->get(['id', 'name', 'sku', 'mrp', 'sale_price', 'stock_quantity', 'track_inventory']),
            'zones' => ShippingZone::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('orders.edit'), 403);
        abort_unless(feature('ordering'), 403, 'Online ordering is disabled.');

        $data = $request->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'shipping_name' => ['required', 'string', 'max:150'],
            'shipping_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'shipping_pincode' => ['nullable', 'string', 'max:12'],
            'shipping_zone_id' => ['nullable', 'exists:shipping_zones,id'],
            'coupon_code' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Merge duplicate product rows and drop blank ones.
        $lines = [];
        foreach ($data['items'] as $row) {
            if (empty($row['product_id'])) {
                continue;
            }
            $lines[(int) $row['product_id']] = ($lines[(int) $row['product_id']] ?? 0) + (int) ($row['quantity'] ?: 1);
        }
        if (! $lines) {
            throw ValidationException::withMessages(['items' => 'Add at least one product.']);
        }

        $products = Product::query()->whereIn('id', array_keys($lines))->get()->keyBy('id');
        $errors = [];
        foreach ($lines as $pid => $qty) {
            $p = $products->get($pid);
            if (! $p || ! $p->is_active) {
                $errors[] = 'One of the selected products is no longer available.';
            } elseif ($p->requires_prescription) {
                $errors[] = "{$p->name} needs a prescription and cannot be added to an online order.";
            } elseif (($p->sale_price ?? $p->mrp) === null) {
                $errors[] = "{$p->name} has no confirmed price.";
            } elseif ($p->track_inventory && $qty > $p->stock_quantity) {
                $errors[] = "Only {$p->stock_quantity} of {$p->name} in stock.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages(['items' => array_values(array_unique($errors))]);
        }

        $order = DB::transaction(function () use ($data, $lines, $products) {
            $subtotal = 0.0;
            $tax = 0.0;
            $items = [];
            foreach ($lines as $pid => $qty) {
                $p = $products[$pid];
                $price = (float) ($p->sale_price ?? $p->mrp);
                $total = round($price * $qty, 2);
                $rate = (float) $p->gst_rate;
                $subtotal += $total;
                $tax += $rate > 0 ? round($total * $rate / (100 + $rate), 2) : 0;
                $items[] = ['product_id' => $p->id, 'product_name' => $p->name, 'sku' => $p->sku, 'quantity' => $qty, 'unit_price' => $price, 'tax_rate' => $rate, 'line_total' => $total];
            }

            $discount = 0.0;
            $coupon = null;
            if (! empty($data['coupon_code'])) {
                $coupon = Coupon::query()->where('code', Str::upper(trim($data['coupon_code'])))->lockForUpdate()->first();
                if (! $coupon || ! $coupon->isUsable()) {
                    throw ValidationException::withMessages(['coupon_code' => 'This coupon is not valid.']);
                }
                if ($coupon->min_order && $subtotal < $coupon->min_order) {
                    throw ValidationException::withMessages(['coupon_code' => 'Minimum order for this coupon is '.money($coupon->min_order).'.']);
                }
                $discount = $coupon->discount_type === 'percent' ? round($subtotal * $coupon->discount_value / 100, 2) : (float) $coupon->discount_value;
                if ($coupon->max_discount) {
                    $discount = min($discount, (float) $coupon->max_discount);
                }
                $discount = min($discount, $subtotal);
                $coupon->increment('used_count');
            }

            $shipping = 0.0;
            $zone = ! empty($data['shipping_zone_id']) ? ShippingZone::query()->find($data['shipping_zone_id']) : null;
            if ($zone) {
                $shipping = ($zone->free_above !== null && $subtotal - $discount >= $zone->free_above) ? 0.0 : (float) $zone->charge;
            }

            $order = Order::query()->create([
                'order_number' => 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'customer_id' => $data['customer_id'] ?? null,
                'status' => 'pending', 'payment_status' => 'unpaid', 'delivery_status' => 'pending',
                'subtotal' => $subtotal, 'discount_total' => $discount, 'shipping_total' => $shipping, 'tax_total' => $tax,
                'grand_total' => round($subtotal - $discount + $shipping, 2),
                'coupon_id' => $coupon?->id, 'shipping_zone_id' => $zone?->id,
                'shipping_name' => $data['shipping_name'], 'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'], 'shipping_pincode' => $data['shipping_pincode'] ?? null,
                'notes' => $data['notes'] ?? null, 'placed_at' => now(),
            ]);
            $order->items()->createMany($items);

            return $order;
        });

        return redirect()->route('admin.orders.show', $order->id)->with('success', 'Order '.$order->order_number.' created. Stock is deducted when you confirm it.');
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);
        abort_unless(feature('ordering'), 404);
        $order->load(['customer', 'items.product', 'payments.verifier', 'coupon', 'shippingZone']);

        return view('admin.orders.show', [
            'order' => $order,
            'next' => self::FLOW[$order->status] ?? [],
            'deducted' => $this->deducted($order) > 0,
            'outstanding' => PaymentController::outstanding($order),
        ]);
    }

    public function invoice(Order $order)
    {
        $this->authorize('view', $order);
        abort_unless(feature('ordering'), 404);

        return view('admin.orders.invoice', ['order' => $order->load(['customer', 'items'])]);
    }

    public function status(Request $request, Order $order, InventoryService $inventory)
    {
        $this->authorize('update', $order);
        abort_unless(feature('ordering'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
            'delivery_status' => ['nullable', Rule::in(array_keys(Order::DELIVERY_STATUSES))],
            'estimated_delivery' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $from = $order->status;
        $to = $data['status'];
        if ($to !== $from && ! in_array($to, self::FLOW[$from] ?? [], true)) {
            return back()->with('error', 'An order cannot move from '.Order::STATUSES[$from].' to '.Order::STATUSES[$to].'.');
        }

        try {
            DB::transaction(function () use ($order, $data, $from, $to, $inventory) {
                if ($to !== $from) {
                    if (in_array($to, ['confirmed', 'processing', 'shipped', 'delivered'], true) && $this->deducted($order) === 0) {
                        foreach ($order->items as $item) {
                            if ($item->product_id && $item->product && $item->product->track_inventory) {
                                $inventory->move($item->product, 'out', (int) $item->quantity, ['reason' => 'sale', 'reference' => $order->order_number, 'notes' => 'Order confirmed']);
                            }
                        }
                    }
                    if ($to === 'cancelled' && ($units = $this->deducted($order)) > 0) {
                        foreach ($order->items as $item) {
                            if ($item->product_id && $item->product && $item->product->track_inventory) {
                                $inventory->move($item->product, 'in', (int) $item->quantity, ['reason' => 'order_cancelled', 'reference' => $order->order_number, 'notes' => 'Order cancelled, stock returned']);
                            }
                        }
                    }
                }
                $order->forceFill([
                    'status' => $to,
                    'delivery_status' => $to === 'delivered' ? 'delivered' : ($data['delivery_status'] ?? $order->delivery_status),
                    'estimated_delivery' => $data['estimated_delivery'] ?? $order->estimated_delivery,
                    'notes' => $data['notes'] ?? $order->notes,
                ])->save();
            });
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first() ?? 'Stock could not be updated.');
        }

        if ($to !== $from) {
            ActivityLog::record('status_changed', $order, "Order {$order->order_number}: {$from} to {$to}");
        }

        return back()->with('success', 'Order updated.');
    }

    public function destroy(Order $order)
    {
        $this->authorize('delete', $order);
        abort_unless(feature('ordering'), 403);

        if (! in_array($order->status, ['pending', 'cancelled'], true) || $order->payments()->exists()) {
            return back()->with('error', 'Only pending or cancelled orders without payments can be deleted. Cancel the order instead.');
        }
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted.');
    }

    /** Units currently taken out of stock for this order, from the ledger. */
    private function deducted(Order $order): int
    {
        return max(0, -1 * (int) InventoryTransaction::query()->where('reference', $order->order_number)->whereIn('reason', ['sale', 'order_cancelled'])->sum('quantity'));
    }
}
