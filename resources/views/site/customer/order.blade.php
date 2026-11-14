@extends('site.layout')
@section('title', 'Order '.$order->order_number)
@section('content')
@include('site.partials.hero', ['crumbs' => ['My orders' => route('customer.orders.index'), $order->order_number => null], 'heading' => 'Order '.$order->order_number, 'lead' => 'Placed '.$order->placed_at?->format('d M Y, h:i A')])
<section class="section-sm"><div class="container">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ session('status') }}</div></div>@endif
  <div class="row g-4">
    <div class="col-lg-8"><div class="form-card mb-3"><div class="d-flex flex-wrap justify-content-between gap-2"><h2 class="h5 mb-0">Items</h2><span class="badge-c">{{ \App\Models\Order::STATUSES[$order->status] ?? ucfirst($order->status) }}</span></div>
      @foreach ($order->items as $item)<div class="d-flex justify-content-between gap-3 py-3 border-bottom"><span>{{ $item->product_name }} <span class="text-muted-c">× {{ $item->quantity }}</span></span><strong>{{ money($item->line_total) }}</strong></div>@endforeach
      <div class="d-flex justify-content-between mt-3"><span>Subtotal</span><span>{{ money($order->subtotal) }}</span></div>
      @if ($order->discount_total > 0)<div class="d-flex justify-content-between"><span>Discount</span><span>-{{ money($order->discount_total) }}</span></div>@endif
      <div class="d-flex justify-content-between"><span>Delivery</span><span>{{ money($order->shipping_total) }}</span></div>
      <div class="d-flex justify-content-between border-top mt-2 pt-2"><strong>Total</strong><strong>{{ money($order->grand_total) }}</strong></div>
    </div></div>
    <aside class="col-lg-4"><div class="form-card mb-3"><h2 class="h5">Fulfillment</h2><p class="mb-1"><strong>{{ $order->shipping_address === 'Store pickup' ? 'Store pickup' : 'Home delivery' }}</strong></p>@if ($order->shipping_address !== 'Store pickup')<p class="mb-1">{{ $order->shipping_name }}<br>{{ $order->shipping_phone }}<br>{{ $order->shipping_address }}<br>{{ $order->shipping_pincode }}</p>@if ($order->shippingZone)<p class="small text-muted-c">{{ $order->shippingZone->name }} @if ($order->shippingZone->estimated_days) · {{ $order->shippingZone->estimated_days }}@endif</p>@endif @endif
      <hr><h3 class="h6">Payment</h3><p class="mb-0">{{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Pay at store' }} · {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</p>
    </div>
    @if ($order->status === 'pending' && $order->payment_status === 'unpaid' && $order->payments()->doesntExist())<div class="form-card"><p class="small text-muted-c">You can cancel while the store has not confirmed your order.</p><form method="POST" action="{{ route('customer.orders.cancel', $order->order_number) }}" data-confirm="Cancel this pending order?">@csrf<button class="btn-cure-outline text-danger" type="submit">Cancel order</button></form></div>@endif
    <a class="btn-cure-outline text-center" href="{{ route('customer.orders.index') }}">Back to my orders</a></aside>
  </div>
</div></section>
@endsection
