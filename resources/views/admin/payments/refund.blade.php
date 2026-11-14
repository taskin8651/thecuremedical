@extends('admin.layouts.app')
@section('title', 'Record refund')
@section('content')
<div class="page-head">
  <div><h1>Record refund</h1><p class="sub">Order {{ $payment->order->order_number }} &middot; payment of {{ money($payment->amount) }} via {{ $payment->method }}</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.payments.index') }}"><i class="bi bi-arrow-left"></i> Back to payments</a></div>
</div>
<div class="notice mb-3"><i class="bi bi-shield-exclamation"></i><div>This only records a refund that staff have handed back. No money moves from here. The refund counts in reports once it has been verified.</div></div>
<form method="POST" action="{{ route('admin.payments.refund.store', $payment->id) }}" data-once>
  @csrf
  <div class="card-c"><div class="card-body-c"><div class="row g-3">
    <div class="col-md-4"><label class="form-label" for="amount">Refund amount <span class="text-danger">*</span></label><div class="input-group"><span class="input-group-text">₹</span><input id="amount" type="number" step="0.01" min="0.01" max="{{ $refundable }}" name="amount" value="{{ old('amount', $refundable) }}" class="form-control @error('amount') is-invalid @enderror"></div>@error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror<div class="form-text">Up to {{ money($refundable) }}.</div></div>
    <div class="col-md-8"><label class="form-label" for="refund_reason">Reason <span class="text-danger">*</span></label><input id="refund_reason" name="refund_reason" value="{{ old('refund_reason') }}" class="form-control @error('refund_reason') is-invalid @enderror" maxlength="250">@error('refund_reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="transaction_reference">Reference</label><input id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" class="form-control" maxlength="120"></div>
  </div></div></div>
  <div class="d-flex gap-2 py-3"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Record refund</button><a class="btn-c-outline" href="{{ route('admin.payments.index') }}">Cancel</a></div>
</form>
@endsection
