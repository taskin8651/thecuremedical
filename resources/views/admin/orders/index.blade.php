@extends('admin.layouts.app')
@section('title', 'Orders')
@section('content')
<div class="page-head">
  <div><h1>Orders</h1><p class="sub">{{ number_format($orders->total()) }} {{ \Illuminate\Support\Str::plural('order', $orders->total()) }}</p></div>
  <div class="page-actions">@can('orders.edit')<a class="btn-c" href="{{ route('admin.orders.create') }}"><i class="bi bi-plus-lg"></i> New order</a>@endcan</div>
</div>
<div class="card-c">
  <form method="GET" class="filter-bar" role="search">
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search order no., name or phone" aria-label="Search"></div>
    <select name="status" class="form-select" aria-label="Status" onchange="this.form.submit()"><option value="">Status: all</option>@foreach (\App\Models\Order::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
    <select name="payment_status" class="form-select" aria-label="Payment" onchange="this.form.submit()"><option value="">Payment: all</option>@foreach (\App\Models\Order::PAYMENT_STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('payment_status') === $k)>{{ $l }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:auto" aria-label="From date">
    <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:auto" aria-label="To date">
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    @if (request()->hasAny(['q', 'status', 'payment_status', 'from', 'to']))<a href="{{ route('admin.orders.index') }}" class="btn-c-ghost">Clear</a>@endif
  </form>
  <div class="table-wrap"><table class="table-c">
    <thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th class="text-end">Total</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      @forelse ($orders as $o)
        <tr>
          <td><a class="fw-semibold" href="{{ route('admin.orders.show', $o->id) }}">{{ $o->order_number }}</a></td>
          <td>{{ $o->created_at->format('d M Y') }}</td>
          <td>{{ $o->customer?->name ?? $o->shipping_name ?? 'Guest' }}</td>
          <td><span class="pill {{ ['delivered' => 'pill-success', 'cancelled' => 'pill-danger', 'refunded' => 'pill-danger', 'pending' => 'pill-warning'][$o->status] ?? 'pill-info' }}">{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span></td>
          <td><span class="pill {{ ['paid' => 'pill-success', 'unpaid' => 'pill-muted', 'failed' => 'pill-danger'][$o->payment_status] ?? 'pill-warning' }}">{{ \App\Models\Order::PAYMENT_STATUSES[$o->payment_status] ?? $o->payment_status }}</span></td>
          <td class="text-end">{{ money($o->grand_total) }}</td>
          <td><div class="row-actions"><a class="btn-c-outline btn-sm-c" href="{{ route('admin.orders.show', $o->id) }}" title="View"><i class="bi bi-eye"></i></a></div></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-receipt"></i>No orders found.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if ($orders->hasPages())<div class="p-3">{{ $orders->links() }}</div>@endif
</div>
@endsection
