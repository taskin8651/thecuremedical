@extends('admin.layouts.app')
@section('title', 'Prescriptions')
@section('content')
<div class="page-head">
  <div><h1>Prescriptions</h1><p class="sub">Restricted register. Every view and download is logged.</p></div>
  <div class="page-actions">@can('prescriptions.create')<a class="btn-c" href="{{ route('admin.prescriptions.create') }}"><i class="bi bi-plus-lg"></i> Register prescription</a>@endcan</div>
</div>
<div class="card-c">
  <form method="GET" class="filter-bar" role="search">
    <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search reference, name or phone" aria-label="Search"></div>
    <select name="status" class="form-select" aria-label="Status" onchange="this.form.submit()"><option value="">Status: all</option>@foreach (\App\Models\PrescriptionEnquiry::STATUSES as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    @if (request()->hasAny(['q', 'status']))<a href="{{ route('admin.prescriptions.index') }}" class="btn-c-ghost">Clear</a>@endif
  </form>
  <div class="table-wrap"><table class="table-c">
    <thead><tr><th>Reference</th><th>Name</th><th>Phone</th><th>Files</th><th>Status</th><th>Received</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
      @forelse ($rows as $r)
        <tr>
          <td><a class="fw-semibold" href="{{ route('admin.prescriptions.show', $r->id) }}">{{ $r->reference }}</a></td>
          <td>{{ $r->name }}</td><td>{{ $r->phone ?: '-' }}</td><td>{{ $r->media_count }}</td>
          <td><span class="pill {{ ['verified' => 'pill-success', 'completed' => 'pill-success', 'rejected' => 'pill-danger', 'received' => 'pill-warning'][$r->status] ?? 'pill-info' }}">{{ \App\Models\PrescriptionEnquiry::STATUSES[$r->status] ?? $r->status }}</span></td>
          <td>{{ $r->created_at->format('d M Y, h:i A') }}</td>
          <td><div class="row-actions"><a class="btn-c-outline btn-sm-c" href="{{ route('admin.prescriptions.show', $r->id) }}" title="Open"><i class="bi bi-eye"></i></a></div></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty-state"><i class="bi bi-file-earmark-medical"></i>No prescriptions registered.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
  @if ($rows->hasPages())<div class="p-3">{{ $rows->links() }}</div>@endif
</div>
@endsection
