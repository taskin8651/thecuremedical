@if ($popular->isNotEmpty())
<section class="section bg-white-c" id="popular"><div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div class="section-head m-0 reveal"><span class="eyebrow">Popular</span><h2>{{ setting('home_popular_title') }}</h2></div>
    <a class="btn-cure-outline" href="{{ route('site.products', ['sort' => 'popular']) }}">Browse catalogue</a>
  </div>
  <div class="row g-3 g-lg-4">@foreach ($popular as $p)<div class="col-6 col-lg-3 reveal">@include('site.partials.product-card', ['p' => $p])</div>@endforeach</div>
</div></section>
@endif
