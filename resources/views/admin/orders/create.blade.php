@extends('admin.layouts.app')
@section('title', 'New order')
@section('content')
@php $rows = old('items', [[], [], []]); @endphp
<div class="page-head">
  <div><h1>New order</h1><p class="sub">Prices include GST. Stock is deducted when the order is confirmed.</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.orders.index') }}"><i class="bi bi-arrow-left"></i> Back to orders</a></div>
</div>
<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>Prescription-only medicines are not listed here and cannot be added to an order.</div></div>
@if ($errors->has('items') || $errors->has('items.*'))<div class="notice danger mb-3"><i class="bi bi-exclamation-octagon"></i><div>@foreach (array_unique(array_merge((array) $errors->get('items'), collect($errors->get('items.*'))->flatten()->all())) as $m)<div>{{ $m }}</div>@endforeach</div></div>@endif
<form method="POST" action="{{ route('admin.orders.store') }}" data-once novalidate>
  @csrf
  <div class="card-c mb-3"><div class="card-body-c">
    <div class="section-title">Items</div>
    <div id="itemRows" class="d-grid gap-2">
      @foreach ($rows as $i => $row)
        <div class="row g-2 item-row">
          <div class="col-8 col-md-9">
            <select name="items[{{ $i }}][product_id]" class="form-select" aria-label="Product">
              <option value="">Choose a product...</option>
              @foreach ($products as $p)<option value="{{ $p->id }}" @selected((string) ($row['product_id'] ?? '') === (string) $p->id)>{{ $p->name }}{{ $p->sku ? ' ('.$p->sku.')' : '' }} - {{ money($p->sale_price ?? $p->mrp) }}{{ $p->track_inventory ? ' - '.$p->stock_quantity.' in stock' : '' }}</option>@endforeach
            </select>
          </div>
          <div class="col-4 col-md-3"><input type="number" min="1" max="1000" name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" class="form-control" aria-label="Quantity"></div>
        </div>
      @endforeach
    </div>
    <button type="button" class="btn-c-outline btn-sm-c mt-2" id="addRow"><i class="bi bi-plus"></i> Add another item</button>
  </div></div>

  <div class="card-c mb-3"><div class="card-body-c">
    <div class="section-title">Customer &amp; delivery</div>
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="customer_id">Customer (optional)</label>
        <select id="customer_id" name="customer_id" class="form-select"><option value="">Walk-in / not saved</option>
          @foreach ($customers as $c)<option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}" @selected((string) old('customer_id') === (string) $c->id)>{{ $c->name }}{{ $c->phone ? ' - '.$c->phone : '' }}</option>@endforeach
        </select></div>
      <div class="col-md-6"><label class="form-label" for="shipping_zone_id">Shipping zone</label>
        <select id="shipping_zone_id" name="shipping_zone_id" class="form-select"><option value="">No shipping charge</option>@foreach ($zones as $id => $n)<option value="{{ $id }}" @selected((string) old('shipping_zone_id') === (string) $id)>{{ $n }}</option>@endforeach</select></div>
      @foreach ([['shipping_name', 'Recipient name', 'text', 6], ['shipping_phone', 'Phone', 'tel', 6]] as [$n, $l, $t, $c])
        <div class="col-md-{{ $c }}"><label class="form-label" for="{{ $n }}">{{ $l }} <span class="text-danger">*</span></label><input id="{{ $n }}" type="{{ $t }}" name="{{ $n }}" value="{{ old($n) }}" class="form-control @error($n) is-invalid @enderror">@error($n)<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
      @endforeach
      <div class="col-md-9"><label class="form-label" for="shipping_address">Address <span class="text-danger">*</span></label><textarea id="shipping_address" name="shipping_address" rows="2" class="form-control @error('shipping_address') is-invalid @enderror">{{ old('shipping_address') }}</textarea>@error('shipping_address')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
      <div class="col-md-3"><label class="form-label" for="shipping_pincode">Pincode</label><input id="shipping_pincode" name="shipping_pincode" value="{{ old('shipping_pincode') }}" class="form-control" maxlength="12"></div>
      <div class="col-md-4"><label class="form-label" for="coupon_code">Coupon code</label><input id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" class="form-control @error('coupon_code') is-invalid @enderror" maxlength="40" style="text-transform:uppercase">@error('coupon_code')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
      <div class="col-md-8"><label class="form-label" for="notes">Notes</label><input id="notes" name="notes" value="{{ old('notes') }}" class="form-control" maxlength="500"></div>
    </div>
  </div></div>
  <div class="d-flex gap-2 pb-4"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Create order</button><a class="btn-c-outline" href="{{ route('admin.orders.index') }}">Cancel</a></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var wrap = document.getElementById('itemRows');
  document.getElementById('addRow').addEventListener('click', function () {
    var rows = wrap.querySelectorAll('.item-row');
    if (rows.length >= 50) return;
    var copy = rows[rows.length - 1].cloneNode(true);
    var n = rows.length;
    copy.querySelectorAll('select, input').forEach(function (el) {
      el.name = el.name.replace(/items\[\d+\]/, 'items[' + n + ']');
      if (el.tagName === 'SELECT') el.selectedIndex = 0; else el.value = 1;
    });
    wrap.appendChild(copy);
  });
  var cust = document.getElementById('customer_id');
  cust.addEventListener('change', function () {
    var o = cust.options[cust.selectedIndex];
    var name = document.getElementById('shipping_name'), phone = document.getElementById('shipping_phone');
    if (o.dataset.name && !name.value) name.value = o.dataset.name;
    if (o.dataset.phone && !phone.value) phone.value = o.dataset.phone;
  });
})();
</script>
@endpush
