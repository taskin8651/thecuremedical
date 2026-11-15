@section('content')
@php
  $editing = $model->exists;
  $hasFiles = collect($fields)->contains(fn ($f) => in_array($f['type'], ['image', 'images', 'files'], true));
  $groups = [];
  $current = 'Details';
  foreach ($fields as $f) {
      if (! empty($f['group'])) { $current = $f['group']; }
      $groups[$current][] = $f;
  }
@endphp
<div class="page-head">
  <div><h1>{{ $editing ? 'Edit' : 'Add' }} {{ strtolower($singular) }}</h1>@if ($editing && method_exists($model, 'auditLabel'))<p class="sub">{{ $model->auditLabel() }}</p>@endif</div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route($route.'.index') }}"><i class="bi bi-arrow-left"></i> Back to {{ strtolower($title) }}</a></div>
</div>

<form method="POST" action="{{ $editing ? route($route.'.update', $model->id) : route($route.'.store') }}" @if ($hasFiles) enctype="multipart/form-data" @endif data-once novalidate>
  @csrf
  @if ($editing) @method('PUT') @endif

  @foreach ($groups as $groupName => $list)
    <div class="card-c mb-3">
      <div class="card-body-c">
        <div class="section-title">{{ $groupName }}</div>
        <div class="row g-3">
          @foreach ($list as $f)
            @include('admin.crud.field', ['f' => $f, 'model' => $model])
          @endforeach
        </div>
      </div>
    </div>
  @endforeach

  <div class="d-flex gap-2 pb-4">
    <button class="btn-c" type="submit"><i class="bi bi-check2"></i> {{ $editing ? 'Save changes' : 'Create '.strtolower($singular) }}</button>
    <a class="btn-c-outline" href="{{ route($route.'.index') }}">Cancel</a>
  </div>
</form>
