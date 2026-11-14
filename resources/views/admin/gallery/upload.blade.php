@extends('admin.layouts.app')
@section('title', 'Upload photos')
@section('content')
<div class="page-head">
  <div><h1>Upload photos</h1><p class="sub">Add up to 20 photos at once. JPG, PNG or WebP, 6 MB each.</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.gallery.index') }}"><i class="bi bi-arrow-left"></i> Back to gallery</a></div>
</div>
<form method="POST" action="{{ route('admin.gallery.upload.store') }}" enctype="multipart/form-data" data-once>
  @csrf
  <div class="card-c"><div class="card-body-c">
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label" for="images">Photos</label>
        <input id="images" type="file" name="images[]" multiple required accept=".jpg,.jpeg,.png,.webp" class="form-control @if ($errors->has('images') || $errors->has('images.*')) is-invalid @endif" data-preview="imagesPreview">
        <div class="upload-preview" id="imagesPreview"></div>
        @if ($errors->has('images') || $errors->has('images.*'))<div class="text-danger small mt-1">{{ $errors->first('images') ?: $errors->first('images.*') }}</div>@endif
        <div class="form-text">Only upload photos the shop owns or has permission to use. The file name becomes the title and can be edited afterwards.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="category">Category (optional)</label>
        <input id="category" name="category" value="{{ old('category') }}" class="form-control" list="catList" maxlength="80">
        <datalist id="catList">@foreach ($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
      </div>
      <div class="col-md-6"><div class="form-check form-switch pt-md-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked><label class="form-check-label" for="is_active">Visible on website</label></div></div>
    </div>
  </div></div>
  <div class="d-flex gap-2 py-3"><button class="btn-c" type="submit"><i class="bi bi-cloud-arrow-up"></i> Upload</button><a class="btn-c-outline" href="{{ route('admin.gallery.index') }}">Cancel</a></div>
</form>
@endsection
