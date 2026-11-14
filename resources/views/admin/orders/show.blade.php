@extends('admin.layouts.app')
@section('title', $order->order_number)
@section('content')
<div class="page-head">
  <div><h1>{{ $order->order_number }}</h1><p class="sub">Placed {{ ($order->placed_at ?? $order->created_at)->format('d M Y, h:i A') }}</p></div>
  <div class="page-actions">
    <a class="btn-c-outline" href="{{ route('admin.orders.index') }}"><i class="bi bi-arrow-left"></i> Back</a>
    <a class="btn-c-outline" href="{{ route('admin.orders.invoice', $order->id) }}" target="_blank" rel="noopener"><i class="bi bi-printer"></i> Invoice</a>
    @can('delete', $order)@if (in_array($order->status, ['pending', 'cancelled'], true) && $order->payments->isEmpty())
      <form method="POST" action="{{ route('admin.orders.destroy', $order->id) }}" data-confirm="Delete this order?">@csrf @method('DELETE')<button class="btn-c-danger" type="submit"><i class="bi bi-trash3"></i> Delete</button></form>
    @endif @endcan
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-c mb-3"><div class="table-wrap"><table class="table-c">
      <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
      <tbody>@foreach ($order->items as $i)<tr><td><strong>{{ $i->product_name }}</strong>@if ($i->sku)<div class="small text-muted">{{ $i->sku }}</div>@endif</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ money($i->unit_price) }}</td><td class="text-end">{{ money($i->line_total) }}</td></tr>@endforeach</tbody>
      <tfoot>
        <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end">{{ money($order->subtotal) }}</td></tr>
        @if ($order->discount_total > 0)<tr><td colspan="3" class="text-end text-muted">Discount{{ $order->coupon ? ' ('.$order->coupon->code.')' : '' }}</td><td class="text-end">- {{ money($order->discount_total) }}</td></tr>@endif
        <tr><td colspan="3" class="text-end text-muted">Shipping{{ $order->shippingZone ? ' ('.$order->shippingZone->name.')' : '' }}</td><td class="text-end">{{ money($order->shipping_total) }}</td></tr>
        <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold">{{ money($order->grand_total) }}</td></tr>
        <tr><td colspan="4" class="text-end small text-muted">Prices include GST of {{ money($order->tax_total) }}.</td></tr>
      </tfoot>
    </table></div></div>

    <div class="card-c"><div class="card-head"><h2>Payments</h2>@if (feature('payments') && $outstanding > 0)@can('payments.create')<a class="btn-c btn-sm-c" href="{{ route('admin.payments.create') }}"><i class="bi bi-plus"></i> Record payment</a>@endcan @endif</div>
      @forelse ($order->payments as $p)
        <div class="list-row"><div><strong>{{ ucfirst($p->type) }} &middot; {{ money($p->amount) }}</strong><div class="small text-muted">{{ $p->method }} &middot; {{ ($p->paid_at ?? $p->created_at)->format('d M Y') }}{{ $p->transaction_reference ? ' &middot; '.$p->transaction_reference : '' }}</div></div><span class="pill {{ ['verified' => 'pill-success', 'pending' => 'pill-warning', 'failed' => 'pill-danger'][$p->status] ?? 'pill-muted' }}">{{ \App\Models\Payment::STATUSES[$p->status] ?? $p->status }}</span></div>
      @empty<div class="empty-state"><i class="bi bi-credit-card"></i>No payments recorded.</div>@endforelse
      <div class="p-3 small text-muted">Balance due (verified payments only): <strong>{{ money($outstanding) }}</strong></div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-c mb-3"><div class="card-body-c">
      <div class="section-title">Status</div>
      <p class="mb-2"><span class="pill pill-info">{{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</span> <span class="pill {{ $order->payment_status === 'paid' ? 'pill-success' : 'pill-muted' }}">{{ \App\Models\Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}</span></p>
      <p class="small text-muted">Stock: {{ $deducted ? 'deducted for this order' : 'not yet deducted' }}</p>
      @can('update', $order)
      @if ($next || $order->status !== 'delivered')
      <form method="POST" action="{{ route('admin.orders.status', $order->id) }}" data-once>
        @csrf
        <label class="form-label" for="status">Move to</label>
        <select id="status" name="status" class="form-select mb-2">
          <option value="{{ $order->status }}">Keep as {{ \App\Models\Order::STATUSES[$order->status] ?? $order->status }}</option>
          @foreach ($next as $s)<option value="{{ $s }}">{{ \App\Models\Order::STATUSES[$s] }}</option>@endforeach
        </select>
        <label class="form-label" for="delivery_status">Delivery</label>
        <select id="delivery_status" name="delivery_status" class="form-select mb-2">@foreach (\App\Models\Order::DELIVERY_STATUSES as $k => $l)<option value="{{ $k }}" @selected($order->delivery_status === $k)>{{ $l }}</option>@endforeach</select>
        <label class="form-label" for="estimated_delivery">Estimated delivery</label>
        <input id="estimated_delivery" type="date" name="estimated_delivery" value="{{ $order->estimated_delivery?->format('Y-m-d') }}" class="form-control mb-2">
        <label class="form-label" for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2" class="form-control mb-3" maxlength="500">{{ $order->notes }}</textarea>
        <button class="btn-c" type="submit"><i class="bi bi-check2"></i> Update order</button>
      </form>
      @endif
      @endcan
    </div></div>
    <div class="card-c"><div class="card-body-c">
      <div class="section-title">Customer &amp; delivery</div>
      <dl class="dl" style="grid-template-columns:100px 1fr">
        <dt>Customer</dt><dd>@if ($order->customer)@can('customers.view')<a href="{{ route('admin.customers.show', $order->customer_id) }}">{{ $order->customer->name }}</a>@else{{ $order->customer->name }}@endcan @else Walk-in @endif</dd>
        <dt>Payment</dt><dd>{{ $order->payment_method === 'cod' ? 'Cash on delivery' : ($order->payment_method === 'store' ? 'Pay at store' : 'Not specified') }}</dd>
        <dt>Name</dt><dd>{{ $order->shipping_name ?: '-' }}</dd>
        <dt>Phone</dt><dd>{{ $order->shipping_phone ?: '-' }}</dd>
        <dt>Address</dt><dd>{!! nl2br(e($order->shipping_address ?: '-')) !!}{{ $order->shipping_pincode ? ' - '.$order->shipping_pincode : '' }}</dd>
        <dt>Delivery</dt><dd>{{ \App\Models\Order::DELIVERY_STATUSES[$order->delivery_status] ?? $order->delivery_status }}</dd>
      </dl>
    </div></div>
  </div>
</div>
@endsection
