@if ($services->isNotEmpty())
<section class="section" id="services"><div class="container">
  <div class="section-head reveal"><span class="eyebrow">Healthcare services</span><h2>{{ setting('home_services_title') }}</h2></div>
  <div class="row g-3 g-lg-4">@foreach ($services as $s)<div class="col-md-6 col-lg-4 reveal"><div class="service"><span class="ic"><i class="bi {{ $s->icon ?: 'bi-heart-pulse' }}"></i></span><div><h3>{{ $s->name }}</h3><p>{{ $s->description }}</p></div></div></div>@endforeach</div>
  <div class="text-center mt-4"><a class="btn-cure-outline" href="{{ route('site.services') }}">All services</a></div>
</div></section>
@endif
