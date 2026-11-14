@extends('admin.layouts.app')
@section('title', 'Stock overview')
@section('content')
@php $cards = [['Tracked products', $summary['tracked'], 'bi-boxes', ''], ['Low stock', $summary['low'], 'bi-exclamation-triangle', $summary['low'] ? 'warn' : ''], ['Out of stock', $summary['out'], 'bi-box-seam', $summary['out'] ? 'danger' : ''], ['Expiring soon', $summary['expiring'], 'bi-calendar-event', $summary['expiring'] ? 'warn' : ''], ['Expired batches', $summary['expired'], 'bi-calendar-x', $summary['expired'] ? 'danger' : '']]; @endphp
<div class="page-head">
  <div><h1>Stock overview</h1><p class="sub">Quantities change only through stock movements, so every change is on record.</p></div>
  <div class="page-actions">
    <a class="btn-c-outline" href="{{ route('admin.inventory.history') }}"><i class="bi bi-clock-history"></i> History</a>
    @can('inventory.create')<a class="btn-c" href="{{ route('admin.inventory.movement') }}"><i class="bi bi-arrow-left-right"></i> Record stock movement</a>@endcan
  </div>
</div>
<div class="row g-3 mb-3">
  @foreach ($cards as [$l, $n, $ic, $t])<div class="col-6 col-md-4 col-xl"><div class="card-c stat"><span class="ic {{ $t }}"><i class="bi {{ $ic }}"></i></span><div><div class="num">{{ number_format($n) }}</div><div class="lbl">{{ $l }}</div></div></div></div>@endforeach
</div>
<div class="card-c">
  <form method="GET" class="filter-bar" role="search">
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, SKU or generic name" aria-label="Search"></div>
    <select name="status" class="form-select" aria-label="Stock status" onchange="this.form.submit()">
      <option value="">Stock: all</option>
      @foreach (['ok' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
    </select>
    <select name="category_id" class="form-select" aria-label="Category" onchange="this.form.submit()">
      <option value="">Category: all</option>
      @foreach ($categories as $id => $name)<option value="{{ $id }}" @selected((string) request('category_id') === (string) $id)>{{ $name }}</option>@endforeach
    </select>
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    @if (request()->hasAny(['q', 'status', 'category_id']))<a href="{{ route('admin.inventory.index') }}" class="btn-c-ghost">Clear</a>@endif
  </form>
  <div class="table-wrap"><table class="table-c">
    <thead><tr><th>Product</th><th>SKU</th><th>Category</th><th class="text-end">In stock</th><th class="text-end">Minimum</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      @forelse ($products as $p)
        @php $s = $p->stockStatus(); @endphp
        <tr>
          <td><strong>{{ $p->name }}</strong></td><td>{{ $p->sku ?: '-' }}</td><td>{{ $p->category?->name ?? '-' }}</td>
          <td class="text-end">{{ number_format($p->stock_quantity) }}</td><td class="text-end">{{ number_format($p->min_quantity) }}</td>
          <td><span class="pill {{ ['out' => 'pill-danger', 'low' => 'pill-warning', 'ok' => 'pill-success'][$s] ?? 'pill-muted' }}">{{ ['out' => 'Out of stock', 'low' => 'Low', 'ok' => 'In stock'][$s] ?? $s }}</span></td>
          <td><div class="row-actions">
            <a class="btn-c-outline btn-sm-c" href="{{ route('admin.inventory.history', ['product_id' => $p->id]) }}" title="History"><i class="bi bi-clock-history"></i></a>
            @can('inventory.create')<a class="btn-c-outline btn-sm-c" href="{{ route('admin.inventory.movement', ['product' => $p->id]) }}" title="Record movement"><i class="bi bi-arrow-left-right"></i></a>@endcan
          </div></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-boxes"></i>No tracked products found.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if ($products->hasPages())<div class="p-3">{{ $products->links() }}</div>@endif
</div>
@endsection
