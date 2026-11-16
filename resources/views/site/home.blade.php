@extends('site.layout')
@section('page', 'home')
@php
  $tel = \App\Support\Settings::tel();
  $heroImg = setting_url('hero_image');
  $b1u = setting('hero_btn1_url') ?: 'tel:'.$tel;
  $b2u = setting('hero_btn2_url') ?: \App\Support\Settings::whatsapp('Hello, I would like to make an enquiry.');
  $firstHour = trim(strtok((string) setting('opening_hours'), "\n"));
@endphp
@section('content')
@if ($heroProducts->isNotEmpty())
<section class="hero-section" aria-label="Featured products">
  <div class="container-fluid px-lg-4">
    <div class="hero-slider" id="heroSlider" aria-roledescription="carousel" aria-label="Featured products">
      @foreach ($heroProducts as $i => $p)
        @php $slideImg = $p->imageUrl(); $slidePrice = public_price($p); $slideTone = [0 => '', 1 => 'slide-two', 2 => 'slide-three'][$i % 3]; @endphp
        <article class="hero-slide {{ $i === 0 ? 'active' : '' }} {{ $slideTone }}" aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ $heroProducts->count() }}">
          <div class="hero-content">
            <span class="hero-tag"><i class="bi bi-heart-pulse-fill"></i>{{ setting('hero_eyebrow') ?: 'FEATURED FOR YOUR WELLBEING' }}</span>
            @if ($p->category)<small class="d-block mt-3 text-muted-c">{{ $p->category->name }}</small>@endif
            <h1>{{ $p->name }}</h1>
            @if ($p->brand)<strong class="hero-product-brand">{{ $p->brand->name }}</strong>@endif
            <p>{{ $p->short_description ? \Illuminate\Support\Str::limit(strip_tags($p->short_description), 150) : (setting('hero_subtitle') ?: 'Discover trusted healthcare essentials selected for your everyday wellbeing.') }}</p>
            @if ($slidePrice)<p class="hero-slide-price"><strong>{{ $slidePrice['now'] }}</strong>@if ($slidePrice['mrp']) <s>{{ $slidePrice['mrp'] }}</s>@endif</p>@endif
            <div class="hero-buttons"><a href="{{ route('site.product', $p->slug) }}" class="btn-primary-custom">Explore Product <i class="bi bi-arrow-right ms-2"></i></a><a href="{{ route('site.products') }}" class="btn-outline-custom">Browse All</a></div>
          </div>
          <a class="hero-image" href="{{ route('site.product', $p->slug) }}" aria-label="View {{ $p->name }}"><span class="image-circle"></span>@if ($slideImg)<img src="{{ $slideImg }}" alt="{{ $p->name }}" @if ($i > 0) loading="lazy" @endif>@else<i class="bi bi-capsule hero-product-placeholder" aria-hidden="true"></i>@endif</a>
        </article>
      @endforeach
      @if ($heroProducts->count() > 1)
        <button class="slider-arrow slider-prev" type="button" aria-label="Previous featured product"><i class="bi bi-chevron-left"></i></button>
        <button class="slider-arrow slider-next" type="button" aria-label="Next featured product"><i class="bi bi-chevron-right"></i></button>
        <div class="slider-dots" aria-label="Choose featured product">
          @foreach ($heroProducts as $i => $p)<button class="slider-dot {{ $i === 0 ? 'active' : '' }}" type="button" aria-label="Show {{ $p->name }}" @if ($i === 0) aria-current="true" @endif></button>@endforeach
        </div>
      @endif
    </div>
  </div>
</section>
@else
<section class="hero" aria-labelledby="heroTitle"><div class="container"><div class="row align-items-center g-5">
  <div class="col-lg-6">
    @if (setting('hero_eyebrow'))<span class="eyebrow">{{ setting('hero_eyebrow') }}</span>@endif
    <h1 id="heroTitle" class="mb-3">{{ setting('hero_title') }} @if (setting('hero_highlight'))<span>{{ setting('hero_highlight') }}</span>@endif</h1>
    <p class="lead">{{ setting('hero_subtitle') }}</p>
    <div class="hero-cta">
      @if (setting('hero_btn1_label'))<a class="btn-cure" href="{{ $b1u }}"><i class="bi bi-telephone"></i> {{ setting('hero_btn1_label') }}</a>@endif
      @if (setting('hero_btn2_label'))<a class="btn-cure-outline" href="{{ $b2u }}" @if (str_starts_with($b2u, 'http')) target="_blank" rel="noopener" @endif><i class="bi bi-whatsapp"></i> {{ setting('hero_btn2_label') }}</a>@endif
    </div>
    <div class="hero-meta">
      @if (setting('google_rating'))<span><i class="bi bi-star-fill" style="color:#E6A700"></i>{{ setting('google_rating') }} on Google ({{ setting('google_reviews') }} reviews)</span>@endif
      @if ($firstHour)<span><i class="bi bi-clock"></i>{{ $firstHour }}</span>@endif
      <span><i class="bi bi-geo-alt"></i>{{ \Illuminate\Support\Str::before(\Illuminate\Support\Str::after((string) setting('address'), ', '), ', Bihar') ?: setting('address') }}</span>
    </div>
  </div>
  <div class="col-lg-6"><div class="hero-visual"><div class="hero-card-main"><img src="{{ $heroImg ?: asset('assets/images/hero.svg') }}" alt="{{ $heroImg ? setting('business_name') : 'Illustration of a medicine bottle, capsules and a green leaf' }}" width="400" height="400" style="{{ $heroImg ? 'object-fit:cover;width:100%;height:100%;border-radius:inherit' : '' }}"></div></div></div>
</div></div></section>
@endif
@foreach ($sections as $s)
  @include('site.sections.'.$s)
@endforeach
@endsection
