@if ($why->isNotEmpty())
<section class="section bg-soft" id="why"><div class="container">
  <div class="section-head center reveal"><span class="eyebrow">Why choose us</span><h2>{{ setting('home_why_title') }}</h2></div>
  <div class="row g-3 g-lg-4">@foreach ($why as $w)<div class="col-md-6 col-lg-3 reveal"><div class="feature"><div class="ic"><i class="bi {{ $w->icon ?: 'bi-check2-circle' }}"></i></div><h3 class="h5">{{ $w->title }}</h3><p>{{ $w->text }}</p></div></div>@endforeach</div>
</div></section>
@endif
