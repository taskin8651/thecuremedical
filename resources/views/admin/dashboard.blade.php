@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
  $cards = [
    ['Total products', $stats['products'], 'bi-capsule', route('admin.products.index'), 'products.view', ''],
    ['Total categories', $stats['categories'], 'bi-diagram-3', route('admin.categories.index'), 'categories.view', ''],
    ['Total enquiries', $stats['enquiries'], 'bi-chat-left-text', route('admin.enquiries.index'), 'enquiries.view', ''],
    ['Pending enquiries', $stats['pending_enquiries'], 'bi-hourglass-split', route('admin.enquiries.index', ['status' => 'new']), 'enquiries.view', $stats['pending_enquiries'] > 0 ? 'warn' : ''],
    ['Total customers', $stats['customers'], 'bi-people', route('admin.customers.index'), 'customers.view', ''],
  ];
@endphp
<div class="page-head">
  <div><h1>Dashboard</h1><p class="sub">Live figures from your database. {{ now()->format('l, d F Y') }}</p></div>
  <div class="page-actions">
    @can('enquiries.view')<a class="btn-c-outline" href="{{ route('admin.enquiries.index') }}"><i class="bi bi-chat-left-text"></i> Enquiries</a>@endcan
    @can('products.create')<a class="btn-c" href="{{ route('admin.products.create') }}"><i class="bi bi-plus-lg"></i> Add product</a>@endcan
  </div>
</div>

<div class="row g-3 mb-3">
  @foreach ($cards as [$label, $num, $icon, $url, $perm, $tone])
    @can($perm)
    <div class="col-6 col-md-4 col-xl-3"><a href="{{ $url }}" class="card-c stat text-decoration-none"><span class="ic {{ $tone }}"><i class="bi {{ $icon }}"></i></span><div><div class="num">{{ number_format($num) }}</div><div class="lbl">{{ $label }}</div></div></a></div>
    @endcan
  @endforeach

  @can('orders.view')
  <div class="col-6 col-md-4 col-xl-3"><div class="card-c stat"><span class="ic {{ $flags['ordering'] ? '' : 'off' }}"><i class="bi bi-receipt"></i></span><div>
    @if ($flags['ordering'])<div class="num">{{ number_format($stats['orders']) }}</div><div class="lbl">Total orders</div>@else<div class="num" style="font-size:1.05rem">Off</div><div class="lbl">Ordering is disabled</div>@endif
  </div></div></div>
  @endcan

  @can('inventory.view')
  <div class="col-6 col-md-4 col-xl-3"><a href="{{ route('admin.inventory.index', ['status' => 'low']) }}" class="card-c stat text-decoration-none"><span class="ic {{ ! $flags['inventory'] ? 'off' : ($stats['low_stock'] ? 'danger' : '') }}"><i class="bi bi-box-seam"></i></span><div>
    @if ($flags['inventory'])<div class="num">{{ number_format($stats['low_stock']) }}</div><div class="lbl">Low / out of stock</div>@else<div class="num" style="font-size:1.05rem">Off</div><div class="lbl">Inventory alerts disabled</div>@endif
  </div></a></div>
  <div class="col-6 col-md-4 col-xl-3"><a href="{{ route('admin.inventory.batches', ['state' => 'soon']) }}" class="card-c stat text-decoration-none"><span class="ic {{ ! $flags['inventory'] ? 'off' : ($stats['expiring'] ? 'warn' : '') }}"><i class="bi bi-calendar-x"></i></span><div>
    @if ($flags['inventory'])<div class="num">{{ number_format($stats['expiring']) }}</div><div class="lbl">Expiring / expired batches</div>@else<div class="num" style="font-size:1.05rem">Off</div><div class="lbl">Inventory alerts disabled</div>@endif
  </div></a></div>
  @endcan

  @can('payments.view')
  <div class="col-6 col-md-4 col-xl-3"><div class="card-c stat"><span class="ic {{ $flags['payments'] ? '' : 'off' }}"><i class="bi bi-currency-rupee"></i></span><div>
    @if ($flags['payments'])<div class="num">{{ money($stats['revenue']) }}</div><div class="lbl">Revenue this month (verified)</div>@else<div class="num" style="font-size:1.05rem">Off</div><div class="lbl">Payments are disabled</div>@endif
  </div></div></div>
  @endcan
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="card-c h-100"><div class="card-head"><h2>Monthly enquiries</h2><span class="toolbar-note">Last 12 months</span></div><div class="card-body-c"><div class="chart-box"><canvas id="chEnquiries" aria-label="Monthly enquiries chart" role="img"></canvas></div></div></div></div>
  <div class="col-lg-4"><div class="card-c h-100"><div class="card-head"><h2>Products by category</h2></div><div class="card-body-c">
    @if (array_sum($categoryChart['values']) > 0)<div class="chart-box"><canvas id="chCategories" aria-label="Product category distribution" role="img"></canvas></div>
    @else<div class="empty-state"><i class="bi bi-pie-chart"></i>Add products to see the distribution.</div>@endif
  </div></div></div>
</div>

@if ($flags['ordering'] || $flags['payments'])
<div class="row g-3 mb-3">
  @if ($orderChart)<div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Monthly orders</h2></div><div class="card-body-c"><div class="chart-box"><canvas id="chOrders" aria-label="Monthly orders chart" role="img"></canvas></div></div></div></div>@endif
  @if ($revenueChart)<div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Revenue analytics</h2><span class="toolbar-note">Verified payments, net of refunds</span></div><div class="card-body-c"><div class="chart-box"><canvas id="chRevenue" aria-label="Revenue chart" role="img"></canvas></div></div></div></div>@endif
</div>
@endif

<div class="row g-3">
  @can('enquiries.view')
  <div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Recent enquiries</h2><a href="{{ route('admin.enquiries.index') }}" class="small">View all</a></div>
    @forelse ($recentEnquiries as $e)
      <a class="list-row text-decoration-none text-reset" href="{{ route('admin.enquiries.show', $e->id) }}"><div><strong>{{ $e->name }}</strong><div class="small text-muted">{{ $e->product_name ?: (\App\Models\Enquiry::TYPES[$e->type] ?? $e->type) }} &middot; {{ $e->created_at->diffForHumans() }}</div></div><span class="pill {{ $e->status === 'new' ? 'pill-warning' : ($e->status === 'resolved' || $e->status === 'closed' ? 'pill-success' : 'pill-info') }}">{{ \App\Models\Enquiry::STATUSES[$e->status] ?? $e->status }}</span></a>
    @empty<div class="empty-state"><i class="bi bi-chat-left"></i>No enquiries yet.</div>@endforelse
  </div></div>
  @endcan

  @if ($flags['ordering'])
  @can('orders.view')
  <div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Recent orders</h2><a href="{{ route('admin.orders.index') }}" class="small">View all</a></div>
    @forelse ($recentOrders as $o)
      <a class="list-row text-decoration-none text-reset" href="{{ route('admin.orders.show', $o->id) }}"><div><strong>{{ $o->order_number }}</strong><div class="small text-muted">{{ $o->customer?->name ?? 'Guest' }} &middot; {{ $o->created_at->diffForHumans() }}</div></div><span class="pill pill-info">{{ \App\Models\Order::STATUSES[$o->status] ?? $o->status }}</span></a>
    @empty<div class="empty-state"><i class="bi bi-receipt"></i>No orders yet.</div>@endforelse
  </div></div>
  @endcan
  @endif

  @can('customers.view')
  <div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Latest customers</h2><a href="{{ route('admin.customers.index') }}" class="small">View all</a></div>
    @forelse ($latestCustomers as $c)
      <a class="list-row text-decoration-none text-reset" href="{{ route('admin.customers.show', $c->id) }}"><div><strong>{{ $c->name }}</strong><div class="small text-muted">{{ $c->phone ?: ($c->email ?: 'No contact') }}</div></div><span class="small text-muted">{{ $c->created_at->format('d M Y') }}</span></a>
    @empty<div class="empty-state"><i class="bi bi-people"></i>No customers yet.</div>@endforelse
  </div></div>
  @endcan

  @can('products.view')
  <div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Popular products</h2><span class="toolbar-note">By page views</span></div>
    @forelse ($popularProducts as $p)
      <a class="list-row text-decoration-none text-reset" href="{{ route('admin.products.edit', $p->id) }}"><div><strong>{{ $p->name }}</strong><div class="small text-muted">{{ $p->sku ?: 'No SKU' }}</div></div><span class="pill pill-info"><i class="bi bi-eye"></i> {{ number_format($p->views) }}</span></a>
    @empty<div class="empty-state"><i class="bi bi-eye"></i>Views appear once visitors open product pages.</div>@endforelse
  </div></div>
  @endcan

  @if ($flags['inventory'])
  @can('inventory.view')
  <div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Low stock alerts</h2><a href="{{ route('admin.inventory.index', ['status' => 'low']) }}" class="small">Stock overview</a></div>
    @forelse ($lowStockProducts as $p)
      <a class="list-row text-decoration-none text-reset" href="{{ route('admin.inventory.movement', ['product' => $p->id]) }}"><div><strong>{{ $p->name }}</strong><div class="small text-muted">Minimum {{ $p->min_quantity }}</div></div><span class="pill {{ $p->stock_quantity <= 0 ? 'pill-danger' : 'pill-warning' }}">{{ $p->stock_quantity <= 0 ? 'Out of stock' : $p->stock_quantity.' left' }}</span></a>
    @empty<div class="empty-state"><i class="bi bi-check2-circle"></i>No stock alerts.</div>@endforelse
  </div></div>
  @endcan
  @endif

  @can('activity.view')
  <div class="col-lg-6"><div class="card-c h-100"><div class="card-head"><h2>Recent admin activity</h2><a href="{{ route('admin.activity.index') }}" class="small">Activity log</a></div><div class="px-3">
    @forelse ($activities as $a)
      <div class="activity-item"><span class="ic"><i class="bi bi-activity"></i></span><div><div>{{ $a->description }}</div><div class="small text-muted">{{ $a->user?->name ?? 'System' }} &middot; {{ $a->created_at->diffForHumans() }}</div></div></div>
    @empty<div class="empty-state"><i class="bi bi-activity"></i>No activity yet.</div>@endforelse
  </div></div></div>
  @endcan
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  var green = '#0D6B4B', soft = 'rgba(13,107,75,.14)';
  cureChart('chEnquiries', 'line', @json($enquiryChart['labels']), [{ label: 'Enquiries', data: @json($enquiryChart['values']), borderColor: green, backgroundColor: soft, fill: true, tension: .35, pointRadius: 3 }]);
  @if (array_sum($categoryChart['values']) > 0)
  cureChart('chCategories', 'doughnut', @json($categoryChart['labels']), [{ data: @json($categoryChart['values']), backgroundColor: ['#0D6B4B', '#2E9A72', '#6CC3A0', '#9BD8BF', '#C7EAD8', '#084B37', '#5C7F70', '#A9BDB3'], borderWidth: 0 }], { cutout: '62%' });
  @endif
  @if ($orderChart)
  cureChart('chOrders', 'bar', @json($orderChart['labels']), [{ label: 'Orders', data: @json($orderChart['values']), backgroundColor: green, borderRadius: 6 }]);
  @endif
  @if ($revenueChart)
  cureChart('chRevenue', 'bar', @json($revenueChart['labels']), [{ label: 'Revenue (₹)', data: @json($revenueChart['values']), backgroundColor: '#2E9A72', borderRadius: 6 }]);
  @endif
</script>
@endpush
