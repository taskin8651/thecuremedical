@extends('admin.layouts.app')
@section('title', $title)
@section('content')
@php
  $has = fn ($action) => \Illuminate\Support\Facades\Route::has($route.'.'.$action);
  $user = auth()->user();
  $canCreate = $allowCreate && $has('create') && $user->can('create', $modelClass);
  $canEdit = $has('edit');
  $canDelete = $allowDelete && $has('destroy');
  $visibleBulk = $has('bulk') ? collect($bulk)->filter(fn ($b) => ($b['ability'] ?? 'update') === 'delete' ? $user->can('delete', $modelClass) : $user->can('viewAny', $modelClass) && $user->can('update', new $modelClass))->all() : [];
@endphp
<div class="page-head">
  <div><h1>{{ $title }}</h1><p class="sub">{{ number_format($rows->total()) }} {{ \Illuminate\Support\Str::plural('record', $rows->total()) }}</p></div>
  <div class="page-actions">
    @foreach ($headerActions as $a)
      @if (! empty($a['can']))<a class="btn-c-outline" href="{{ $a['url'] }}"><i class="bi {{ $a['icon'] ?? 'bi-dot' }}"></i>{{ $a['label'] }}</a>@endif
    @endforeach
    @if ($canCreate)<a class="btn-c" href="{{ route($route.'.create') }}"><i class="bi bi-plus-lg"></i> Add {{ strtolower($singular) }}</a>@endif
  </div>
</div>

<div class="card-c">
  @if ($hasSearch || $filters)
  <form method="GET" class="filter-bar" role="search">
    @if ($hasSearch)
      <div class="search"><i class="bi bi-search"></i><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search {{ strtolower($title) }}" aria-label="Search"></div>
    @endif
    @foreach ($filters as $f)
      <select name="{{ $f['name'] }}" class="form-select" aria-label="{{ $f['label'] }}" onchange="this.form.submit()">
        <option value="">{{ $f['label'] }}: all</option>
        @foreach ($f['options'] as $value => $label)
          <option value="{{ $value }}" @selected((string) request($f['name']) === (string) $value)>{{ $label }}</option>
        @endforeach
      </select>
    @endforeach
    <button class="btn-c-outline" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    @if (collect(array_merge(['q'], array_column($filters, 'name')))->contains(fn ($k) => request()->filled($k)))
      <a href="{{ route($route.'.index') }}" class="btn-c-ghost">Clear</a>
    @endif
  </form>
  @endif

  @if ($visibleBulk)
  <form id="bulkForm" method="POST" action="{{ route($route.'.bulk') }}">
    @csrf
    <div class="bulk-bar" id="bulkBar">
      <strong id="bulkCount">0 selected</strong>
      <select name="action" class="form-select form-select-sm" style="width:auto" aria-label="Bulk action">
        <option value="">Choose action...</option>
        @foreach ($visibleBulk as $key => $b)<option value="{{ $key }}">{{ $b['label'] }}</option>@endforeach
      </select>
      <button class="btn-c btn-sm-c" type="submit">Apply</button>
    </div>
  </form>
  @endif

  <div class="table-wrap">
    <table class="table-c">
      <thead>
        <tr>
          @if ($visibleBulk)<th style="width:40px"><input type="checkbox" id="selectAll" class="form-check-input" aria-label="Select all"></th>@endif
          @foreach ($columns as $col)<th>{{ $col['label'] }}</th>@endforeach
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
          <tr>
            @if ($visibleBulk)<td><input type="checkbox" class="form-check-input row-check" value="{{ $row->id }}" aria-label="Select row"></td>@endif
            @foreach ($columns as $col)
              @php
                $type = $col['type'] ?? 'text';
                $val = isset($col['value']) ? ($col['value'])($row) : (isset($col['key']) ? data_get($row, $col['key']) : null);
                $href = null;
                if (! empty($col['link'])) {
                    $href = $col['link'] === 'show' && $has('show') ? route($route.'.show', $row->id) : ($canEdit && $user->can('update', $row) ? route($route.'.edit', $row->id) : null);
                }
              @endphp
              <td>
                @if ($type === 'image')
                  @if ($val)<img class="thumb" src="{{ $val }}" alt="" loading="lazy">@else<span class="thumb-ph"><i class="bi bi-image"></i></span>@endif
                @elseif ($type === 'bool')
                  <span class="pill {{ $val ? 'pill-success' : 'pill-'.($col['tones'][0] ?? 'muted') }}">{{ $val ? ($col['yes'] ?? 'Yes') : ($col['no'] ?? 'No') }}</span>
                @elseif ($type === 'pill')
                  <span class="pill pill-{{ isset($col['tone']) ? ($col['tone'])($row) : 'muted' }}">{{ $val ?? '-' }}</span>
                @elseif ($type === 'date')
                  {{ $val ? \Illuminate\Support\Carbon::parse($val)->format('d M Y') : '-' }}
                @elseif ($type === 'datetime')
                  {{ $val ? \Illuminate\Support\Carbon::parse($val)->format('d M Y, h:i A') : '-' }}
                @elseif ($type === 'html')
                  @if ($href)<a href="{{ $href }}" class="text-reset">{!! $val !!}</a>@else{!! $val !!}@endif
                @else
                  @if ($href)<a href="{{ $href }}" class="fw-semibold">{{ $val ?? '-' }}</a>@else{{ $val === null || $val === '' ? '-' : $val }}@endif
                @endif
              </td>
            @endforeach
            <td>
              <div class="row-actions">
                @foreach ($rowActions as $a)
                  @if (($a['can'])($row))
                    @if (($a['method'] ?? 'get') === 'post')
                      <form method="POST" action="{{ ($a['url'])($row) }}">@csrf<button class="btn-c-outline btn-sm-c" type="submit" title="{{ $a['label'] }}"><i class="bi {{ $a['icon'] ?? 'bi-dot' }}"></i><span class="d-none d-xl-inline">{{ $a['label'] }}</span></button></form>
                    @else
                      <a class="btn-c-outline btn-sm-c" href="{{ ($a['url'])($row) }}" title="{{ $a['label'] }}"><i class="bi {{ $a['icon'] ?? 'bi-dot' }}"></i><span class="d-none d-xl-inline">{{ $a['label'] }}</span></a>
                    @endif
                  @endif
                @endforeach
                @if ($has('show') && $user->can('view', $row))<a class="btn-c-outline btn-sm-c" href="{{ route($route.'.show', $row->id) }}" title="View"><i class="bi bi-eye"></i></a>@endif
                @if ($canEdit && $user->can('update', $row))<a class="btn-c-outline btn-sm-c" href="{{ route($route.'.edit', $row->id) }}" title="Edit"><i class="bi bi-pencil"></i></a>@endif
                @if ($canDelete && $user->can('delete', $row))
                  <form method="POST" action="{{ route($route.'.destroy', $row->id) }}" data-confirm="Delete this {{ strtolower($singular) }}? This cannot be undone.">@csrf @method('DELETE')<button class="btn-c-danger btn-sm-c" type="submit" title="Delete"><i class="bi bi-trash3"></i></button></form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="{{ count($columns) + 2 }}"><div class="empty-state"><i class="bi bi-inbox"></i>No {{ strtolower($title) }} found.@if ($canCreate)<div class="mt-2"><a class="btn-c" href="{{ route($route.'.create') }}">Add the first {{ strtolower($singular) }}</a></div>@endif</div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if ($rows->hasPages())<div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="toolbar-note">Showing {{ $rows->firstItem() }} to {{ $rows->lastItem() }} of {{ $rows->total() }}</span>{{ $rows->links() }}</div>@endif
</div>
@endsection
