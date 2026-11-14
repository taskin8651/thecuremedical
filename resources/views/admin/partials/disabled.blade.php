@extends('admin.layouts.app')
@section('title', $title)
@section('content')
<div class="page-head"><div><h1>{{ $title }}</h1></div></div>
<div class="card-c"><div class="card-body-c">
  <div class="empty-state" style="padding:2rem 1rem">
    <i class="bi bi-toggle-off"></i>
    <h2 class="h5 mt-2">{{ $what }} is switched off</h2>
    <p class="text-muted mx-auto" style="max-width:560px">{{ $text }}</p>
    @can('settings.view')
      <a class="btn-c" href="{{ route('admin.settings.show', ['tab' => 'features']) }}"><i class="bi bi-gear"></i> Open settings</a>
    @endcan
  </div>
</div></div>
@endsection
