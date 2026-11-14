@extends('admin.layouts.app')
@section('title', 'Cart #'.$cart->id)
@section('content')
<div class="page-head">
  <div><h1>Cart #{{ $cart->id }}</h1><p class="sub">{{ $cart->customer?->name ?? 'Guest' }} &middot; updated {{ $cart->updated_at->diffForHumans() }}</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.carts.index') }}"><i class="bi bi-arrow-left"></i> Back to carts</a></div>
</div>
<div class="card-c"><div class="table-wrap"><table class="table-c">
  <thead><tr><th>Product</th><th>Qty</th><th>Unit price</th><th class="text-end">Line total</th></tr></thead>
  <tbody>
    @forelse ($cart->items as $i)
      <tr><td>{{ $i->product?->name ?? 'Removed product' }}</td><td>{{ $i->quantity }}</td><td>{{ money($i->unit_price) }}</td><td class="text-end">{{ money($i->unit_price * $i->quantity) }}</td></tr>
    @empty
      <tr><td colspan="4"><div class="empty-state">This cart is empty.</div></td></tr>
    @endforelse
  </tbody>
</table></div></div>
@endsection
