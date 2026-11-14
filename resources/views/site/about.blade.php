@extends('site.layout')
@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 155))
@php $meta = $page->meta ?? []; $img = $page->getFirstMediaUrl('image', 'thumb'); $values = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($meta['values'] ?? ''))))); @endphp
@section('content')
@include('site.partials.hero', ['crumbs' => [$page->title => null], 'heading' => $page->title, 'lead' => null])
<section class="section-sm"><div class="container"><div class="row g-5 align-items-start">
  <div class="col-lg-7"><div class="prose">{!! markdown_safe($page->content) !!}</div>
    @if (! empty($meta['mission']) || ! empty($meta['vision']))<div class="row g-3 mt-3">
      @if (! empty($meta['mission']))<div class="col-md-6"><div class="feature"><div class="ic"><i class="bi bi-bullseye"></i></div><h3 class="h5">Our mission</h3><p>{{ $meta['mission'] }}</p></div></div>@endif
      @if (! empty($meta['vision']))<div class="col-md-6"><div class="feature"><div class="ic"><i class="bi bi-eye"></i></div><h3 class="h5">Our vision</h3><p>{{ $meta['vision'] }}</p></div></div>@endif
    </div>@endif
    @if ($values)<h2 class="h4 mt-4">What we value</h2><ul class="check-list">@foreach ($values as $v)<li><i class="bi bi-check-circle-fill"></i><span>{{ $v }}</span></li>@endforeach</ul>@endif
  </div>
  <div class="col-lg-5"><div class="about-visual">@if ($img)<img src="{{ $img }}" alt="{{ setting('business_name') }}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">@else<i class="bi bi-shop big-ic" aria-hidden="true"></i>@endif</div>
    <div class="mt-3">@include('site.partials.contact-card')</div></div>
</div></div></section>
@endsection
