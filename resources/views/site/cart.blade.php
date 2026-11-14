@extends('site.layout')
@section('title', 'Your cart')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Cart' => null], 'heading' => 'Your cart', 'lead' => 'Review your selected products before checkout.'])
<section class="section-sm"><div class="container">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-check-circle"></i><div>{{ session('status') }}</div></div>@endif
  @if (session('error'))<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ session('error') }}</div></div>@endif
  @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif

  @if ($cart->items->isEmpty())
    <div class="form-card text-center py-5"><i class="bi bi-bag fs-1 text-muted-c"></i><h2 class="h4 mt-2">Your cart is empty</h2><p class="text-muted-c">Browse the catalogue and add eligible products to your cart.</p><a class="btn-cure" href="{{ route('site.products') }}">Browse products</a></div>
  @else
    <div class="row g-4 align-items-start">
      <div class="col-lg-8"><div class="form-card p-0 overflow-hidden">
        @foreach ($cart->items as $item)
          @php($product = $item->product)
          <div class="cart-row d-flex gap-3 align-items-center p-3 border-bottom">
            <div class="cart-thumb">@if ($product && $product->imageUrl('thumb'))<img src="{{ $product->imageUrl('thumb') }}" alt="">@else<i class="bi bi-capsule"></i>@endif</div>
            <div class="flex-grow-1 min-w-0">
              <strong>{{ $product?->name ?? 'Product no longer available' }}</strong>
              @if ($product?->brand)<div class="small text-muted-c">{{ $product->brand->name }}</div>@endif
              @if (! $product || ! $product->is_active || ! $product->is_verified || $product->requires_prescription)<div class="small text-danger">This item can no longer be ordered online. Remove it to continue.</div>@endif
              @if ($showPrices && $product)<div class="small text-muted-c">{{ money($product->sale_price ?? $product->mrp) }} each</div>@endif
            </div>
            @if ($product && $product->is_active && $product->is_verified && ! $product->requires_prescription)
              <form method="POST" action="{{ route('cart.items.update', $item->id) }}" class="d-flex gap-2 align-items-center">@csrf @method('PATCH')<label class="visually-hidden" for="qty{{ $item->id }}">Quantity</label><input id="qty{{ $item->id }}" class="form-control form-control-sm" type="number" name="quantity" min="1" max="1000" value="{{ $item->quantity }}" style="width:78px"><button class="btn-cure-outline btn-sm-c" type="submit">Update</button></form>
            @else<span class="small">Qty: {{ $item->quantity }}</span>@endif
            <form method="POST" action="{{ route('cart.items.destroy', $item->id) }}">@csrf @method('DELETE')<button class="btn-icon text-danger" type="submit" aria-label="Remove {{ $product?->name ?? 'item' }}"><i class="bi bi-trash"></i></button></form>
          </div>
        @endforeach
      </div></div>
      <aside class="col-lg-4"><div class="form-card">
        <h2 class="h5">Order summary</h2>
        <div class="d-flex justify-content-between"><span>Items</span><span>{{ $cart->items->sum('quantity') }}</span></div>
        @if ($showPrices)<div class="d-flex justify-content-between mt-2"><strong>Subtotal</strong><strong>{{ money($cart->items->sum(fn ($item) => (($item->product?->sale_price ?? $item->product?->mrp) ?? 0) * $item->quantity)) }}</strong></div><p class="small text-muted-c mt-2">Delivery and eligible discounts are calculated at checkout. Prices include GST.</p>@else<p class="small text-muted-c mt-2">Prices are hidden. Contact the store for current prices.</p>@endif
        @if ($canOrder)
          <a class="btn-cure w-100 mt-3 text-center" href="{{ route('checkout.create') }}">Continue to checkout</a>
        @else
          <div class="notice info mt-3"><i class="bi bi-info-circle"></i><div>Online checkout is currently unavailable. Please call the store to confirm products and availability.</div></div>
          <a class="btn-cure-outline w-100 mt-2 text-center" href="tel:{{ \App\Support\Settings::tel() }}">Call the store</a>
        @endif
        <a class="btn-cure-outline w-100 mt-2 text-center" href="{{ route('site.products') }}">Continue shopping</a>
      </div></aside>
    </div>
  @endif
</div></section>
@endsection
