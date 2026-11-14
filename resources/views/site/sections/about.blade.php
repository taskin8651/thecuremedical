@php $img = setting_url('home_about_image'); @endphp
<section class="section" id="about"><div class="container"><div class="row align-items-center g-5">
  <div class="col-lg-5 reveal"><div class="about-visual">@if ($img)<img src="{{ $img }}" alt="{{ setting('business_name') }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">@else<i class="bi bi-shop big-ic" aria-hidden="true"></i>@endif</div></div>
  <div class="col-lg-7 reveal">
    <span class="eyebrow">{{ setting('home_about_eyebrow') }}</span>
    <h2 class="mb-3">{{ setting('home_about_title') }}</h2>
    <p class="text-muted-c">{{ setting('home_about_text') }}</p>
    <a class="btn-cure" href="{{ route('site.about') }}">Learn more about us</a>
  </div>
</div></div></section>
