@extends('admin.layouts.app')
@section('title', $customer->name)
@section('content')
<div class="page-head">
  <div><h1>{{ $customer->name }}</h1><p class="sub">Customer since {{ $customer->created_at->format('d M Y') }}</p></div>
  <div class="page-actions">
    <a class="btn-c-outline" href="{{ route('admin.customers.index') }}"><i class="bi bi-arrow-left"></i> Back</a>
    @can('update', $customer)<a class="btn-c" href="{{ route('admin.customers.edit', $customer->id) }}"><i class="bi bi-pencil"></i> Edit</a>@endcan
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-5"><div class="card-c h-100"><div class="card-body-c">
    <div class="section-title">Contact</div>
    <dl class="dl">
      <dt>Status</dt><dd><span class="pill {{ $customer->status === 'active' ? 'pill-success' : 'pill-danger' }}">{{ ucfirst($customer->status) }}</span></dd>
      <dt>Phone</dt><dd>@if ($customer->phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $customer->phone) }}">{{ $customer->phone }}</a>@else - @endif</dd>
      <dt>Email</dt><dd>@if ($customer->email)<a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@else - @endif</dd>
      <dt>City</dt><dd>{{ $customer->city ?: '-' }}</dd>
      <dt>Pincode</dt><dd>{{ $customer->pincode ?: '-' }}</dd>
      <dt>Address</dt><dd>{!! nl2br(e($customer->address ?: '-')) !!}</dd>
      <dt>Notes</dt><dd>{!! nl2br(e($customer->notes ?: '-')) !!}</dd>
    </dl>
  </div></div></div>
  <div class="col-lg-7">
    <div class="card-c mb-3"><div class="card-head"><h2>Enquiries</h2></div>
      @forelse ($enquiries as $e)
        @can('enquiries.view')
        <a class="list-row text-decoration-none text-reset" href="{{ route('admin.enquiries.show', $e->id) }}"><div><strong>{{ $e->product_name ?: (\App\Models\Enquiry::TYPES[$e->type] ?? $e->type) }}</strong><div class="small text-muted">{{ $e->created_at->format('d M Y') }}</div></div><span class="pill pill-info">{{ \App\Models\Enquiry::STATUSES[$e->status] ?? $e->status }}</span></a>
        @endcan
      @empty<div class="empty-state"><i class="bi bi-chat-left"></i>No enquiries linked to this customer.</div>@endforelse
    </div>
    @if ($orders->isNotEmpty())
    <div class="card-c"><div class="card-head"><h2>Orders</h2></div>
      @foreach ($orders as $o)
        <a class="list-row text-decoration-none text-reset" href="{{ route('admin.orders.show', $o->id) }}"><div><strong>{{ $o->order_number }}</strong><div class="small text-muted">{{ $o->created_at->format('d M Y') }} &middot; {{ money($o->grand_total) }}</div></div><span class="pill pill-info">{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span></a>
      @endforeach
    </div>
    @endif
  </div>
</div>
@endsection
