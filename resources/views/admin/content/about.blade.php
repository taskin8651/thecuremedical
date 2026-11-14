@extends('admin.layouts.app')
@section('title', 'About us')
@section('content')
@php $meta = $page->meta ?? []; @endphp
<div class="page-head"><div><h1>About us</h1><p class="sub">Shown on the website's About page. Write only what is true.</p></div></div>
<form method="POST" action="{{ route('admin.content.about.update') }}" enctype="multipart/form-data" data-once novalidate>
  @csrf @method('PUT')
  <div class="card-c mb-3"><div class="card-body-c"><div class="section-title">Story</div><div class="row g-3">
    <div class="col-md-8"><label class="form-label" for="title">Page title <span class="text-danger">*</span></label><input id="title" name="title" value="{{ old('title', $page->title) }}" class="form-control @error('title') is-invalid @enderror" maxlength="200">@error('title')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
    <div class="col-md-4"><div class="form-check form-switch pt-md-4"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $page->is_active))><label class="form-check-label" for="is_active">Published</label></div></div>
    <div class="col-12"><label class="form-label" for="content">Our story (Markdown allowed)</label><textarea id="content" name="content" rows="9" class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>@error('content')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
    <div class="col-md-6"><label class="form-label" for="mission">Mission</label><textarea id="mission" name="mission" rows="3" class="form-control" maxlength="600">{{ old('mission', $meta['mission'] ?? '') }}</textarea></div>
    <div class="col-md-6"><label class="form-label" for="vision">Vision</label><textarea id="vision" name="vision" rows="3" class="form-control" maxlength="600">{{ old('vision', $meta['vision'] ?? '') }}</textarea></div>
    <div class="col-12"><label class="form-label" for="values">Values (one per line)</label><textarea id="values" name="values" rows="4" class="form-control" maxlength="1200">{{ old('values', $meta['values'] ?? '') }}</textarea></div>
  </div></div></div>
  <div class="card-c mb-3"><div class="card-body-c"><div class="section-title">Photo &amp; SEO</div><div class="row g-3">
    <div class="col-12">
      @if ($img = $page->getFirstMediaUrl('image', 'thumb'))<div class="media-grid"><div class="media-tile"><img src="{{ $img }}" alt=""><label class="text-danger"><input type="checkbox" name="remove_image" value="1"> Remove</label></div></div>@endif
      <label class="form-label" for="image">Photo</label><input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" class="form-control @error('image') is-invalid @enderror" data-preview="imgPrev">@error('image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror<div class="upload-preview" id="imgPrev"></div>
    </div>
    <div class="col-md-6"><label class="form-label" for="seo_title">SEO title</label><input id="seo_title" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" class="form-control" maxlength="120"></div>
    <div class="col-md-6"><label class="form-label" for="seo_description">SEO description</label><input id="seo_description" name="seo_description" value="{{ old('seo_description', $page->seo_description) }}" class="form-control" maxlength="250"></div>
  </div></div></div>
  @can('content.edit')<div class="pb-4"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Save about page</button></div>@endcan
</form>
@endsection
