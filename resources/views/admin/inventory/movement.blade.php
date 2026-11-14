@extends('admin.layouts.app')
@section('title', 'Stock movement')
@section('content')
@php $t = old('type', $type); $sel = old('product_id', $selected); @endphp
<div class="page-head">
  <div><h1>Stock movement</h1><p class="sub">Receive stock, remove stock, or correct a count. Each entry is permanent history.</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.inventory.index') }}"><i class="bi bi-arrow-left"></i> Back to stock</a></div>
</div>
<form method="POST" action="{{ route('admin.inventory.movement.store') }}" data-once novalidate id="moveForm">
  @csrf
  <div class="card-c mb-3"><div class="card-body-c">
    <div class="section-title">Movement</div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" for="type">What happened?</label>
        <select id="type" name="type" class="form-select">
          <option value="in" @selected($t === 'in')>Stock in (received)</option>
          <option value="out" @selected($t === 'out')>Stock out (removed)</option>
          <option value="adjustment" @selected($t === 'adjustment')>Adjustment (physical count)</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label" for="product_id">Product <span class="text-danger">*</span></label>
        <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror">
          <option value="">Choose a product...</option>
          @foreach ($products as $p)<option value="{{ $p->id }}" @selected((string) $sel === (string) $p->id)>{{ $p->name }}{{ $p->sku ? ' ('.$p->sku.')' : '' }} - {{ $p->stock_quantity }} in stock</option>@endforeach
        </select>
        @error('product_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="form-label" for="quantity"><span id="qtyLabel">Quantity</span> <span class="text-danger">*</span></label>
        <input id="quantity" type="number" name="quantity" min="0" step="1" value="{{ old('quantity') }}" class="form-control @error('quantity') is-invalid @enderror">
        @error('quantity')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        <div class="form-text" id="qtyHelp"></div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="reason">Reason <span class="text-danger">*</span></label>
        <select id="reason" name="reason" class="form-select @error('reason') is-invalid @enderror" data-selected="{{ old('reason') }}"></select>
        @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="form-label" for="reference">Reference (invoice / note no.)</label>
        <input id="reference" name="reference" value="{{ old('reference') }}" class="form-control" maxlength="120">
      </div>
    </div>
  </div></div>

  <div class="card-c mb-3" id="receiveBox"><div class="card-body-c">
    <div class="section-title">Batch details (stock in)</div>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label" for="batch_number">Batch number <span class="text-danger">*</span></label><input id="batch_number" name="batch_number" value="{{ old('batch_number') }}" class="form-control @error('batch_number') is-invalid @enderror" maxlength="80">@error('batch_number')<div class="text-danger small mt-1">{{ $message }}</div>@enderror<div class="form-text">Copy it from the pack.</div></div>
      <div class="col-md-4"><label class="form-label" for="expiry_date">Expiry date</label><input id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-control @error('expiry_date') is-invalid @enderror">@error('expiry_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
      <div class="col-md-4"><label class="form-label" for="supplier_id">Supplier</label><select id="supplier_id" name="supplier_id" class="form-select"><option value="">None</option>@foreach ($suppliers as $id => $n)<option value="{{ $id }}" @selected((string) old('supplier_id') === (string) $id)>{{ $n }}</option>@endforeach</select></div>
      <div class="col-md-4"><label class="form-label" for="cost">Cost per unit</label><div class="input-group"><span class="input-group-text">₹</span><input id="cost" type="number" step="0.01" min="0" name="cost" value="{{ old('cost') }}" class="form-control"></div></div>
    </div>
  </div></div>

  <div class="card-c mb-3"><div class="card-body-c">
    <label class="form-label" for="notes">Notes</label>
    <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="500">{{ old('notes') }}</textarea>
  </div></div>

  <div class="d-flex gap-2 pb-4"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Record movement</button><a class="btn-c-outline" href="{{ route('admin.inventory.index') }}">Cancel</a></div>
</form>
@endsection

@push('scripts')
<script>
(function () {
  var reasons = @json($reasons);
  var type = document.getElementById('type');
  var reason = document.getElementById('reason');
  var label = document.getElementById('qtyLabel');
  var help = document.getElementById('qtyHelp');
  var box = document.getElementById('receiveBox');
  var text = { in: ['Quantity received', 'Units added to stock.'], out: ['Quantity removed', 'Taken from the batch that expires first.'], adjustment: ['Counted quantity', 'Enter the total you counted on the shelf. The system works out the difference.'] };
  function sync() {
    var t = type.value;
    var keep = reason.getAttribute('data-selected') || reason.value;
    reason.innerHTML = '<option value="">Choose...</option>';
    Object.keys(reasons[t]).forEach(function (k) {
      var o = document.createElement('option');
      o.value = k; o.textContent = reasons[t][k]; if (k === keep) o.selected = true;
      reason.appendChild(o);
    });
    reason.removeAttribute('data-selected');
    label.textContent = text[t][0]; help.textContent = text[t][1];
    box.style.display = t === 'in' ? '' : 'none';
  }
  type.addEventListener('change', sync);
  sync();
})();
</script>
@endpush
