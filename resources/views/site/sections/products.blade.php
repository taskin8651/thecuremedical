@if ($products->isNotEmpty())
<section class="section bg-white-c" id="products"><div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div class="section-head m-0 reveal"><span class="eyebrow">Explore our catalogue</span><h2>{{ setting('home_products_title') }}</h2></div>
    <a class="btn-cure-outline" href="{{ route('site.products') }}">View all products <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="row g-3 g-lg-4">@foreach ($products as $p)<div class="col-6 col-lg-3 reveal">@include('site.partials.product-card', ['p' => $p])</div>@endforeach</div>
</div></section>
@endif
