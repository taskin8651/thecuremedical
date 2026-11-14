@if ($articles->isNotEmpty())
<section class="section" id="articles"><div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div class="section-head m-0 reveal"><span class="eyebrow">Health articles</span><h2>{{ setting('home_articles_title') }}</h2></div>
    <a class="btn-cure-outline" href="{{ route('site.blog') }}">All articles</a>
  </div>
  <div class="row g-3 g-lg-4">@foreach ($articles as $a)<div class="col-md-6 col-lg-4 reveal">@include('site.partials.article-card', ['a' => $a])</div>@endforeach</div>
</div></section>
@endif
