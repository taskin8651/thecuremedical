@extends('admin.layouts.app')
@section('title', $role->exists ? 'Edit role' : 'Add role')
@section('content')
<div class="page-head">
  <div><h1>{{ $role->exists ? 'Edit '.$role->name : 'Add role' }}</h1><p class="sub">You can only grant permissions that you have yourself.</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.roles.index') }}"><i class="bi bi-arrow-left"></i> Back</a></div>
</div>
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role->id) : route('admin.roles.store') }}" data-once novalidate>
  @csrf @if ($role->exists) @method('PUT') @endif
  <div class="card-c mb-3"><div class="card-body-c">
    <label class="form-label" for="name">Role name <span class="text-danger">*</span></label>
    <input id="name" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="60" @readonly($isSystem) style="max-width:420px">
    @error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    @if ($isSystem)<div class="form-text">Built-in role: the name is fixed.</div>@endif
  </div></div>
  <div class="card-c mb-3"><div class="card-body-c">
    <div class="section-title">Permissions</div>
    <div class="perm-grid">
      @foreach ($modules as $module => [$label, $actions])
        <div class="perm-box">
          <h4>{{ $label }} <a href="#" data-check-all class="small">Toggle all</a></h4>
          @foreach ($actions as $action)
            @php $perm = $module.'.'.$action; $can = $mine === null || in_array($perm, $mine, true); @endphp
            <div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm }}" id="p_{{ $module }}_{{ $action }}" @checked(in_array($perm, $granted, true)) @disabled(! $can)><label class="form-check-label" for="p_{{ $module }}_{{ $action }}">{{ ucfirst(str_replace('_', ' ', $action)) }}</label></div>
          @endforeach
        </div>
      @endforeach
    </div>
  </div></div>
  <div class="d-flex gap-2 pb-4"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Save role</button><a class="btn-c-outline" href="{{ route('admin.roles.index') }}">Cancel</a></div>
</form>
@endsection
