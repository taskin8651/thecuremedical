@extends('site.layout')
@section('title', 'My orders')
@section('content')
@include('site.partials.hero', ['crumbs' => ['My orders' => null], 'heading' => 'My orders', 'lead' => 'Review order status and fulfillment details.'])
<section class="section-sm"><div class="container">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ session('status') }}</div></div>@endif
  <div class="d-flex justify-content-end mb-3"><a class="btn-cure-outline" href="{{ route('customer.profile') }}">My account</a></div>
  @forelse ($orders as $order)
    <a class="form-card d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 text-reset text-decoration-none" href="{{ route('customer.orders.show', $order->order_number) }}">
      <div><strong>{{ $order->order_number }}</strong><div class="small text-muted-c">{{ $order->placed_at?->format('d M Y, h:i A') }} · {{ $order->items_count }} item(s)</div></div>
      <span class="badge-c">{{ \App\Models\Order::STATUSES[$order->status] ?? ucfirst($order->status) }}</span>
      <strong>{{ money($order->grand_total) }}</strong>
      <span class="small">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }} <i class="bi bi-arrow-right"></i></span>
    </a>
  @empty
    <div class="form-card text-center py-5"><i class="bi bi-receipt fs-1 text-muted-c"></i><h2 class="h5 mt-2">No orders yet</h2><p class="text-muted-c">Orders you place with this account will appear here.</p><a class="btn-cure" href="{{ route('site.products') }}">Browse products</a></div>
  @endforelse
  <div class="mt-3">{{ $orders->links() }}</div>
</div></section>
@endsection
