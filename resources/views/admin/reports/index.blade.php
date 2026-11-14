@extends('admin.layouts.app')
@section('title', 'Reports')
@section('content')
<div class="page-head"><div><h1>Reports</h1><p class="sub">Filter by date, search, print or export to CSV.</p></div></div>
<div class="row g-3">
  @foreach ($reports as $key => $r)
    <div class="col-md-6 col-xl-4"><a href="{{ route('admin.reports.show', $key) }}" class="card-c stat text-decoration-none h-100"><span class="ic {{ $r['off'] ? 'off' : '' }}"><i class="bi {{ $r['icon'] }}"></i></span><div><div class="fw-bold">{{ $r['label'] }}</div><div class="small text-muted">{{ $r['text'] }}</div>@if ($r['off'])<span class="pill pill-muted mt-1">Feature off</span>@endif</div></a></div>
  @endforeach
</div>
@endsection
