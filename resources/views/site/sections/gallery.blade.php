@if ($gallery->isNotEmpty())
<section class="section" id="gallery"><div class="container">
  <div class="section-head reveal"><span class="eyebrow">Shop gallery</span><h2>{{ setting('home_gallery_title') }}</h2></div>
  <div class="row g-3">
    @foreach ($gallery as $g)@php $u = $g->getFirstMediaUrl('image', 'thumb'); @endphp
      <div class="col-6 col-lg-4 reveal"><div class="g-tile" style="min-height:220px;padding:0">@if ($u)<img src="{{ $u }}" alt="{{ $g->title }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">@endif</div></div>
    @endforeach
  </div>
  <div class="text-center mt-4"><a class="btn-cure-outline" href="{{ route('site.gallery') }}">Open gallery</a></div>
</div></section>
@endif
