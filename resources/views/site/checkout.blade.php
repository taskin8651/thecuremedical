@extends('site.layout')
@section('title', 'Checkout')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Cart' => route('cart.index'), 'Checkout' => null], 'heading' => 'Checkout', 'lead' => 'Choose store pickup or an available delivery area.'])
<section class="section-sm"><div class="container">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ session('status') }}</div></div>@endif
  @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif
  @if (! $canOrder)
    <div class="form-card"><div class="notice info"><i class="bi bi-shield-check"></i><div>Online checkout is disabled by the store. No order or payment has been taken. Please call the store to confirm availability.</div></div><a class="btn-cure mt-3" href="tel:{{ \App\Support\Settings::tel() }}">Call the store</a> <a class="btn-cure-outline mt-3" href="{{ route('cart.index') }}">Back to cart</a></div>
  @else
  <form method="POST" action="{{ route('checkout.store') }}" class="row g-4">@csrf
    <input type="hidden" name="cart_id" value="{{ $cart->id }}">
    <div class="col-lg-7">
      <div class="form-card mb-3"><h2 class="h5">How would you like to receive your order?</h2>
        <div class="form-check border rounded-3 p-3 ps-5 mb-2"><input class="form-check-input" type="radio" name="fulfillment" id="pickup" value="pickup" @checked(old('fulfillment', 'pickup') === 'pickup')><label class="form-check-label w-100" for="pickup"><strong>Pick up at the store</strong><span class="d-block small text-muted-c">We will contact you when the order is ready.</span></label></div>
        @if ($zones->isNotEmpty())<div class="form-check border rounded-3 p-3 ps-5"><input class="form-check-input" type="radio" name="fulfillment" id="delivery" value="delivery" @checked(old('fulfillment') === 'delivery')><label class="form-check-label w-100" for="delivery"><strong>Home delivery</strong><span class="d-block small text-muted-c">Available only in the listed delivery areas.</span></label></div>@else<div class="small text-muted-c">No delivery areas are configured yet; store pickup is available.</div>@endif
      </div>
      <div class="form-card mb-3"><h2 class="h5">Contact and delivery details</h2>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="shipping_name">Full name</label><input class="form-control" id="shipping_name" name="shipping_name" value="{{ old('shipping_name', $customer->name) }}" required maxlength="150"></div>
          <div class="col-md-6"><label class="form-label" for="shipping_phone">Phone</label><input class="form-control" id="shipping_phone" name="shipping_phone" value="{{ old('shipping_phone', $customer->phone) }}" required maxlength="30" autocomplete="tel"></div>
          <div class="col-12"><label class="form-label" for="shipping_address">Address</label><textarea class="form-control" id="shipping_address" name="shipping_address" rows="2" maxlength="500" autocomplete="street-address">{{ old('shipping_address', $customer->address) }}</textarea></div>
          <div class="col-md-6"><label class="form-label" for="shipping_city">City</label><input class="form-control" id="shipping_city" name="shipping_city" value="{{ old('shipping_city', $customer->city) }}" maxlength="100" autocomplete="address-level2"></div>
          <div class="col-md-6"><label class="form-label" for="shipping_pincode">PIN code</label><input class="form-control" id="shipping_pincode" name="shipping_pincode" value="{{ $shippingPincode }}" maxlength="12" autocomplete="postal-code"></div>
          <div class="col-12"><label class="form-label" for="shipping_zone_id">Delivery area</label><select class="form-select" id="shipping_zone_id" name="shipping_zone_id"><option value="">Choose delivery area (delivery only)</option>@foreach ($zones as $zone)<option value="{{ $zone->id }}" @selected((string) old('shipping_zone_id') === (string) $zone->id)>{{ $zone->name }} — {{ money($zone->charge) }}@if ($zone->free_above) (free over {{ money($zone->free_above) }})@endif</option>@endforeach</select></div>
          <div class="col-12"><label class="form-label" for="coupon_code">Coupon code (optional)</label><input class="form-control" id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" maxlength="40" autocomplete="off"></div>
          <div class="col-12"><label class="form-label" for="notes">Note for the store (optional)</label><textarea class="form-control" id="notes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></div>
        </div>
      </div>
      <div class="form-card"><h2 class="h5">Payment</h2>
        <div class="form-check border rounded-3 p-3 ps-5 mb-2"><input class="form-check-input" type="radio" name="payment_method" id="pay_store" value="store" @checked(old('payment_method', 'store') === 'store')><label class="form-check-label w-100" for="pay_store"><strong>Pay at store</strong><span class="d-block small text-muted-c">Cash or UPI when you collect your order.</span></label></div>
        @if ($zones->isNotEmpty())<div class="form-check border rounded-3 p-3 ps-5"><input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod" @checked(old('payment_method') === 'cod')><label class="form-check-label w-100" for="pay_cod"><strong>Cash on delivery</strong><span class="d-block small text-muted-c">Payment is collected by the store at delivery.</span></label></div>@endif
        <p class="small text-muted-c mt-3 mb-0">No online payment is collected. The store confirms the order before processing it.</p>
      </div>
    </div>
    <aside class="col-lg-5"><div class="form-card"><h2 class="h5">Order summary</h2>
      @foreach ($cart->items as $item)<div class="d-flex justify-content-between gap-2 py-2 border-bottom"><span>{{ $item->product?->name }} <span class="text-muted-c">× {{ $item->quantity }}</span></span><span>{{ money(($item->product?->sale_price ?? $item->product?->mrp) * $item->quantity) }}</span></div>@endforeach
      @php($subtotal = $cart->items->sum(fn ($item) => (($item->product?->sale_price ?? $item->product?->mrp) ?? 0) * $item->quantity))
      <div class="d-flex justify-content-between mt-3"><strong>Subtotal</strong><strong>{{ money($subtotal) }}</strong></div><p class="small text-muted-c">Prices include GST. Delivery charges and discounts are confirmed at checkout.</p>
      <div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="terms" name="terms" value="1" @checked(old('terms')) required><label class="form-check-label small" for="terms">I agree to the <a href="{{ route('site.terms') }}">terms</a>. Product availability and fulfillment are subject to store confirmation.</label></div>
      <button type="submit" class="btn-cure w-100 mt-3">Place order</button>
      <a class="btn-cure-outline w-100 mt-2 text-center" href="{{ route('cart.index') }}">Back to cart</a>
    </div></aside>
  </form>
  @endif
</div></section>
@endsection
