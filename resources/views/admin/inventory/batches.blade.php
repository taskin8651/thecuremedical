@extends('admin.layouts.app')
@section('title', 'Batches & expiry')
@section('content')
<div class="page-head">
  <div><h1>Batches &amp; expiry</h1><p class="sub">Expiry alert window: {{ $days }} days. Change it in Settings.</p></div>
  <div class="page-actions">
    @can('inventory.create')<a class="btn-c" href="{{ route('admin.purchases.create') }}"><i class="bi bi-cart-plus"></i> Add purchase</a>@endcan
  </div>
</div>
<div class="card-c">
  <form method="GET" class="filter-bar" role="search">
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search product or batch" aria-label="Search"></div>
    <select name="state" class="form-select" aria-label="Expiry state" onchange="this.form.submit()">
      <option value="">Expiry: all</option>
      @foreach (['expired' => 'Expired', 'soon' => 'Expiring soon', 'ok' => 'Not near expiry'] as $k => $l)<option value="{{ $k }}" @selected(request('state') === $k)>{{ $l }}</option>@endforeach
    </select>
    <div class="form-check"><input class="form-check-input" type="checkbox" name="empty" value="1" id="empty" @checked(request()->boolean('empty')) onchange="this.form.submit()"><label class="form-check-label" for="empty">Include empty batches</label></div>
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Filter</button>
  </form>
  <div class="table-wrap"><table class="table-c">
    <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Status</th><th class="text-end">Units left</th><th>Supplier</th></tr></thead>
    <tbody>
      @forelse ($batches as $b)
        @php $st = $b->expiryState(); $d = $b->daysToExpiry(); @endphp
        <tr>
          <td><strong>{{ $b->product?->name }}</strong></td><td>{{ $b->batch_number }}</td>
          <td>{{ $b->expiry_date?->format('d M Y') ?? '-' }}</td>
          <td>@if ($st === 'expired')<span class="pill pill-danger">Expired {{ abs($d) }} day(s) ago</span>@elseif ($st === 'soon')<span class="pill pill-warning">{{ $d }} day(s) left</span>@elseif ($st === 'ok')<span class="pill pill-success">OK</span>@else<span class="pill pill-muted">No expiry set</span>@endif</td>
          <td class="text-end">{{ number_format($b->quantity) }}</td><td>{{ $b->supplier?->name ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-check"></i>No batches to show.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if ($batches->hasPages())<div class="p-3">{{ $batches->links() }}</div>@endif
</div>
@endsection
