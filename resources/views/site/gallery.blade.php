@extends('site.layout')
@section('title', 'Gallery')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Gallery' => null], 'heading' => 'Gallery', 'lead' => 'A look inside the store.'])
<section class="section-sm gallery-page"><div class="container">
  <div class="gallery-filters d-flex flex-wrap gap-2 mb-3" aria-label="Filter gallery by type">
    @foreach ($types as $value => $label)<a class="badge-c {{ $currentType === $value ? 'active' : '' }}" href="{{ route('site.gallery', array_filter(['type' => $value, 'category' => $value === '' || $value === 'gallery' ? $current : null])) }}">{{ $label }}</a>@endforeach
  </div>
  @if ($cats->isNotEmpty() && ($currentType === '' || $currentType === 'gallery'))<div class="d-flex flex-wrap gap-2 mb-4"><a class="badge-c {{ $current === '' ? 'active' : '' }}" href="{{ route('site.gallery', array_filter(['type' => $currentType])) }}">All store photos</a>@foreach ($cats as $c)<a class="badge-c {{ $current === $c ? 'active' : '' }}" href="{{ route('site.gallery', array_filter(['type' => $currentType, 'category' => $c])) }}">{{ $c }}</a>@endforeach</div>@endif
  <div class="row g-3">
    @forelse ($items as $g)
      <div class="col-12 col-lg-4"><article class="gallery-card"><button type="button" class="gallery-modal-trigger g-tile d-block w-100" data-gallery-image="{{ $g['full'] }}" data-gallery-title="{{ $g['title'] }}" data-gallery-type="{{ ucfirst($g['type']) }}" data-bs-toggle="modal" data-bs-target="#galleryImageModal" aria-label="View {{ $g['title'] }} image">
        <img src="{{ $g['thumb'] }}" alt="{{ $g['title'] }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">
        <span class="gallery-image-open"><i class="bi bi-arrows-fullscreen"></i></span>
      </button><div class="gallery-card-caption small text-muted-c"><span class="gallery-image-type">{{ ucfirst($g['type']) }}</span><span>{{ $g['title'] }}</span></div></article></div>
    @empty<div class="col-12"><p class="text-muted-c">Photos will appear here soon.</p></div>@endforelse
  </div>
  <div class="mt-4">{{ $items->links() }}</div>
</div></section>
<div class="modal fade gallery-image-modal" id="galleryImageModal" tabindex="-1" aria-labelledby="galleryImageModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content">
    <div class="modal-header"><div><span class="gallery-image-type" id="galleryImageModalType"></span><h2 class="modal-title fs-5" id="galleryImageModalTitle"></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body gallery-modal-stage">
      <button type="button" class="gallery-modal-nav gallery-modal-prev" data-gallery-nav="prev" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
      <img id="galleryImageModalPhoto" src="" alt="">
      <button type="button" class="gallery-modal-nav gallery-modal-next" data-gallery-nav="next" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
      <span class="gallery-modal-count" id="galleryImageModalCount" aria-live="polite"></span>
    </div>
  </div></div>
</div>
@endsection
