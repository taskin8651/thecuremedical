@extends('admin.layouts.app')
@section('title', $label)
@section('content')
<div class="page-head">
  <div><h1>{{ $label }}</h1><p class="sub">@if ($ranged){{ $from->format('d M Y') }} to {{ $to->format('d M Y') }} &middot; @endif{{ $rows->total() }} row(s) &middot; generated {{ now()->format('d M Y, h:i A') }}</p></div>
  <div class="page-actions no-print">
    <a class="btn-c-outline" href="{{ route('admin.reports.index') }}"><i class="bi bi-arrow-left"></i> All reports</a>
    <button type="button" class="btn-c-outline" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    @can('reports.export')<a class="btn-c" href="{{ route('admin.reports.show', ['report' => $key] + request()->except('page') + ['export' => 'csv']) }}"><i class="bi bi-download"></i> CSV</a>@endcan
  </div>
</div>
<div class="row g-3 mb-3">
  @foreach ($cards as [$l, $v])<div class="col-6 col-md-3"><div class="card-c stat"><div><div class="num" style="font-size:1.25rem">{{ $v }}</div><div class="lbl">{{ $l }}</div></div></div></div>@endforeach
</div>
@if ($note)<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ $note }}</div></div>@endif
<div class="card-c">
  <form method="GET" class="filter-bar no-print" role="search">
    @if ($ranged)
      <label class="small text-muted">From <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control"></label>
      <label class="small text-muted">To <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control"></label>
    @endif
    @if ($key === 'expiry')<label class="small text-muted">Within days <input type="number" name="days" min="1" max="730" value="{{ $days }}" class="form-control" style="width:100px"></label>@endif
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search" aria-label="Search"></div>
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Apply</button>
    <a href="{{ route('admin.reports.show', $key) }}" class="btn-c-ghost">Reset</a>
  </form>
  @if ($errors->any())<div class="p-3 text-danger small">{{ $errors->first() }}</div>@endif
  <div class="table-wrap"><table class="table-c">
    <thead><tr>@foreach ($headings as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>
      @forelse ($rows as $row)<tr>@foreach ($row as $cell)<td>{{ $cell === null || $cell === '' ? '-' : $cell }}</td>@endforeach</tr>
      @empty<tr><td colspan="{{ count($headings) }}"><div class="empty-state"><i class="bi bi-bar-chart"></i>No data for these filters.</div></td></tr>@endforelse
    </tbody>
  </table></div>
  @if ($rows->hasPages())<div class="p-3 no-print">{{ $rows->links() }}</div>@endif
</div>
@endsection
