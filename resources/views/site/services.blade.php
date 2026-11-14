@extends('site.layout')
@section('title', 'Services')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Services' => null], 'heading' => 'Our services', 'lead' => 'How the store can help you.'])
<section class="section-sm"><div class="container"><div class="row g-3 g-lg-4">
  @forelse ($services as $s)<div class="col-md-6 col-lg-4"><div class="service"><span class="ic"><i class="bi {{ $s->icon ?: 'bi-heart-pulse' }}"></i></span><div><h2 class="h5">{{ $s->name }}</h2><p>{{ $s->description }}</p></div></div></div>
  @empty<div class="col-12"><p class="text-muted-c">Services will be listed here soon.</p></div>@endforelse
</div>
<div class="text-center mt-4"><a class="btn-cure" href="{{ route('site.contact') }}">Send an enquiry</a></div></div></section>
@endsection
