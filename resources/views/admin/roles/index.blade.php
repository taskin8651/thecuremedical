@extends('admin.layouts.app')
@section('title', 'Roles & permissions')
@section('content')
<div class="page-head">
  <div><h1>Roles &amp; permissions</h1><p class="sub">Choose what each role can see and do.</p></div>
  <div class="page-actions">@can('create', \Spatie\Permission\Models\Role::class)<a class="btn-c" href="{{ route('admin.roles.create') }}"><i class="bi bi-plus-lg"></i> Add role</a>@endcan</div>
</div>
<div class="card-c"><div class="table-wrap"><table class="table-c">
  <thead><tr><th>Role</th><th>Permissions</th><th>Users</th><th class="text-end">Actions</th></tr></thead>
  <tbody>
    @foreach ($roles as $r)
      <tr>
        <td><strong>{{ $r->name }}</strong> @if (in_array($r->name, $system, true))<span class="pill pill-muted">Built-in</span>@endif</td>
        <td>{{ $r->name === 'Super Admin' ? 'Everything' : $r->permissions_count }}</td><td>{{ $r->users_count }}</td>
        <td><div class="row-actions">
          @if ($r->name !== 'Super Admin')@can('update', $r)<a class="btn-c-outline btn-sm-c" href="{{ route('admin.roles.edit', $r->id) }}" title="Edit"><i class="bi bi-pencil"></i></a>@endcan @endif
          @if (! in_array($r->name, $system, true))@can('delete', $r)<form method="POST" action="{{ route('admin.roles.destroy', $r->id) }}" data-confirm="Delete this role?">@csrf @method('DELETE')<button class="btn-c-danger btn-sm-c" type="submit" title="Delete"><i class="bi bi-trash3"></i></button></form>@endcan @endif
        </div></td>
      </tr>
    @endforeach
  </tbody>
</table></div></div>
@endsection
