@extends('admin.layouts.app')
@section('title', 'Stock history')
@section('content')
<div class="page-head">
  <div><h1>Stock history</h1><p class="sub">@if ($product)Showing <strong>{{ $product->name }}</strong>. @endif Entries are permanent and cannot be edited or deleted.</p></div>
  <div class="page-actions">
    @if ($product)<a class="btn-c-outline" href="{{ route('admin.inventory.history') }}">All products</a>@endif
    @can('inventory.create')<a class="btn-c" href="{{ route('admin.inventory.movement', $product ? ['product' => $product->id] : []) }}"><i class="bi bi-arrow-left-right"></i> Record movement</a>@endcan
  </div>
</div>
<div class="card-c">
  <form method="GET" class="filter-bar" role="search">
    @if ($product)<input type="hidden" name="product_id" value="{{ $product->id }}">@endif
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search product, SKU or reference" aria-label="Search"></div>
    <select name="type" class="form-select" aria-label="Type" onchange="this.form.submit()">
      <option value="">Type: all</option>
      @foreach (['in' => 'Stock in', 'out' => 'Stock out', 'adjustment' => 'Adjustment'] as $k => $l)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:auto" aria-label="From date">
    <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:auto" aria-label="To date">
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    @if (request()->hasAny(['q', 'type', 'from', 'to']))<a href="{{ route('admin.inventory.history', $product ? ['product_id' => $product->id] : []) }}" class="btn-c-ghost">Clear</a>@endif
  </form>
  <div class="table-wrap"><table class="table-c">
    <thead><tr><th>Date</th><th>Product</th><th>Type</th><th class="text-end">Change</th><th class="text-end">Balance</th><th>Batch</th><th>Reason</th><th>Reference</th><th>By</th></tr></thead>
    <tbody>
      @forelse ($rows as $t)
        <tr>
          <td class="text-nowrap">{{ $t->created_at->format('d M Y, h:i A') }}</td>
          <td><a href="{{ route('admin.inventory.history', ['product_id' => $t->product_id]) }}">{{ $t->product?->name }}</a></td>
          <td><span class="pill {{ ['in' => 'pill-success', 'out' => 'pill-warning', 'adjustment' => 'pill-info'][$t->type] ?? 'pill-muted' }}">{{ ucfirst($t->type) }}</span></td>
          <td class="text-end fw-semibold {{ $t->quantity < 0 ? 'text-danger' : '' }}">{{ $t->quantity > 0 ? '+' : '' }}{{ $t->quantity }}</td>
          <td class="text-end">{{ $t->balance_after }}</td>
          <td>{{ $t->batch?->batch_number ?? '-' }}</td>
          <td>{{ $reasons[$t->reason] ?? str_replace('_', ' ', (string) $t->reason) ?: '-' }}</td>
          <td>{{ $t->reference ?: '-' }}</td>
          <td>{{ $t->user?->name ?? 'System' }}</td>
        </tr>
      @empty
        <tr><td colspan="9"><div class="empty-state"><i class="bi bi-clock-history"></i>No stock movements yet.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if ($rows->hasPages())<div class="p-3">{{ $rows->links() }}</div>@endif
</div>
@endsection
