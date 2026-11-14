@extends('site.layout')
@section('title', $title)
@section('noindex', '1')
@section('content')
@include('site.partials.hero', ['crumbs' => [$title => null], 'heading' => $title, 'lead' => null])
<section class="section"><div class="container" style="max-width:640px"><div class="form-card text-center">
  <i class="bi bi-info-circle fs-1 text-muted-c"></i>
  <p class="lead mt-2">{{ $text }}</p>
  <div class="d-flex flex-wrap justify-content-center gap-2"><a class="btn-cure" href="tel:{{ \App\Support\Settings::tel() }}"><i class="bi bi-telephone"></i> Call the store</a><a class="btn-cure-outline" href="{{ route('site.products') }}">Browse products</a><a class="btn-cure-outline" href="{{ route('site.contact') }}">Send an enquiry</a></div>
</div></div></section>
@endsection
