@extends('site.layout')
@section('title', 'Contact')
@section('description', 'Contact '.setting('business_name').'. Call, WhatsApp, visit or send an enquiry.')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Contact' => null], 'heading' => 'Contact us', 'lead' => 'Call, message or visit the store. We are happy to help.'])
<section class="section-sm"><div class="container"><div class="row g-4">
  <div class="col-lg-5">
    <div class="mb-3">@include('site.partials.contact-card')</div>
    <div class="notice"><i class="bi bi-shield-exclamation"></i><div>Please do not send prescriptions or private health details through this form. Call or visit the store instead.</div></div>
  </div>
  <div class="col-lg-7"><div class="form-card"><h2 class="h4 mb-3">Send an enquiry</h2>@include('site.partials.enquiry-form', ['product' => $product])</div></div>
</div></div></section>
<section class="section-sm"><div class="container"><iframe class="map-frame" style="height:420px" title="Map showing {{ setting('business_name') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="{{ \App\Support\Settings::mapsEmbed() }}"></iframe></div></section>
@endsection
