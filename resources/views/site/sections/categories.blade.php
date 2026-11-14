@if ($categories->isNotEmpty())
<section class="section" id="categories"><div class="container">
  <div class="section-head reveal"><span class="eyebrow">Shop by category</span><h2>{{ setting('home_categories_title') }}</h2>@if (setting('home_categories_text'))<p>{{ setting('home_categories_text') }}</p>@endif</div>
  <div class="row g-3 g-lg-4">
    @foreach ($categories as $c)
      <div class="col-6 col-md-4 col-lg-3 reveal"><a class="cat-card" href="{{ route('site.products', ['category' => $c->slug]) }}">
        <i class="bi bi-arrow-up-right arrow"></i><div class="ic"><i class="bi {{ $c->icon ?: 'bi-capsule' }}"></i></div><h3>{{ $c->name }}</h3><p>{{ \Illuminate\Support\Str::limit($c->description, 70) }}</p></a></div>
    @endforeach
  </div>
</div></section>
@endif
