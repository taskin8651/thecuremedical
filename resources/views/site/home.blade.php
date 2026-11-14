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
  <div class="col-lg-6"><div class="hero-visual">
    <div class="hero-card-main"><img src="{{ $heroImg ?: asset('assets/images/hero.svg') }}" alt="{{ $heroImg ? setting('business_name') : 'Illustration of a medicine bottle, capsules and a green leaf' }}" width="400" height="400" style="{{ $heroImg ? 'object-fit:cover;width:100%;height:100%;border-radius:inherit' : '' }}"></div>
  </div></div>
</div></div></section>

@foreach ($sections as $s)
  @include('site.sections.'.$s)
@endforeach
@endsection
