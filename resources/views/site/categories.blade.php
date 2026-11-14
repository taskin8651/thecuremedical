@extends('site.layout')
@section('title', 'Categories')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Categories' => null], 'heading' => 'Product categories', 'lead' => 'Find what you need by category.'])
<section class="section-sm"><div class="container"><div class="row g-3 g-lg-4">
  @forelse ($categories as $c)
    <div class="col-md-6 col-lg-4"><div class="cat-card h-100">
      <div class="ic"><i class="bi {{ $c->icon ?: 'bi-capsule' }}"></i></div>
      <h3><a class="text-reset" href="{{ route('site.products', ['category' => $c->slug]) }}">{{ $c->name }}</a></h3>
      <p>{{ $c->description }}</p><p class="mt-2 small">{{ $c->products_count }} {{ \Illuminate\Support\Str::plural('product', $c->products_count) }}</p>
      @if ($c->children->isNotEmpty())<div class="d-flex flex-wrap gap-1 mt-2">@foreach ($c->children as $s)<a class="badge-c" href="{{ route('site.products', ['category' => $s->slug]) }}">{{ $s->name }}</a>@endforeach</div>@endif
    </div></div>
  @empty<div class="col-12"><p class="text-muted-c">No categories yet.</p></div>@endforelse
</div></div></section>
@endsection
