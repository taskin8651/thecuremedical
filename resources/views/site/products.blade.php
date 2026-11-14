@extends('site.layout')
@section('title', $current ? $current->name : 'Products')
@section('description', 'Browse products at '.setting('business_name').'. Ask the store to confirm availability.')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Products' => null], 'heading' => $current ? $current->name : 'Products', 'lead' => 'Browse the catalogue. Please confirm availability with the store before you visit.'])
<section class="section-sm"><div class="container"><div class="row g-4">
  <aside class="col-lg-3"><div class="filter-card">
    <form method="GET" action="{{ route('site.products') }}" role="search" class="mb-3">
      <label class="form-label" for="pq">Search</label>
      <div class="d-flex gap-2"><input id="pq" type="search" name="q" value="{{ $q }}" class="form-control" maxlength="100" placeholder="Name, brand, category">@if ($current)<input type="hidden" name="category" value="{{ $current->slug }}">@endif<button class="btn-cure btn-sm-c" type="submit" aria-label="Search"><i class="bi bi-search"></i></button></div>
    </form>
    <h2 class="h6">Categories</h2>
    <ul class="filter-list">
      <li><a class="d-block" href="{{ route('site.products', array_filter(['q' => $q])) }}"><button type="button" class="{{ ! $current ? 'active' : '' }}">All products</button></a></li>
      @foreach ($categories as $c)<li><a class="d-block" href="{{ route('site.products', array_filter(['category' => $c->slug, 'q' => $q])) }}"><button type="button" class="{{ $current && $current->id === $c->id ? 'active' : '' }}">{{ $c->name }} <small>({{ $c->products_count }})</small></button></a></li>@endforeach
    </ul>
  </div></aside>
  <div class="col-lg-9">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <span class="text-muted-c">{{ number_format($products->total()) }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</span>
      <form method="GET" class="d-flex gap-2 align-items-center">
        @foreach (['q' => $q, 'category' => $current?->slug] as $k => $v)@if ($v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
        <label for="sort" class="small text-muted-c">Sort</label>
        <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto"><option value="name" @selected(request('sort') === 'name' || ! request('sort'))>Name</option><option value="new" @selected(request('sort') === 'new')>Newest</option><option value="popular" @selected(request('sort') === 'popular')>Most viewed</option></select>
      </form>
    </div>
    <div class="row g-3 g-lg-4">
      @forelse ($products as $p)<div class="col-6 col-xl-4">@include('site.partials.product-card', ['p' => $p])</div>
      @empty<div class="col-12"><div class="form-card text-center"><i class="bi bi-search fs-1 text-muted-c"></i><h2 class="h5 mt-2">No products found</h2><p class="text-muted-c">Try another search, or ask the store about the product you need.</p><a class="btn-cure" href="{{ route('site.contact') }}">Send an enquiry</a></div></div>@endforelse
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
  </div>
</div></div></section>
@endsection
