@extends('site.layout')
@php
  $verified = (bool) $product->is_verified;
  $price = public_price($product);
  $cartUnavailable = public_cart_unavailable_reason($product);
  [$availLabel, $availClass] = public_availability($product);
  $imgs = $product->getMedia('images');
  $first = $imgs->first();
  $facts = [
    'Generic name' => $product->generic_name, 'Composition' => $product->composition, 'Strength' => $product->strength, 'Form' => $product->dosage_form,
    'Pack size' => $product->pack_size, 'Pack type' => $product->pack_type, 'Manufacturer' => $product->manufacturer, 'Shelf life' => $product->shelf_life,
  ];
@endphp
@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description ?: $product->description)), 155))
@if ($first)@section('og_image', $first->getUrl())@endif
@section('content')
@include('site.partials.hero', ['crumbs' => ['Products' => route('site.products'), $product->name => null], 'heading' => $product->name, 'lead' => null])
<section class="section-sm"><div class="container"><div class="row g-5">
  <div class="col-lg-5">
    <div class="pd-media">@if ($first)<img id="mainImg" src="{{ $first->getUrl() }}" alt="{{ $product->name }}" style="width:100%;height:100%;object-fit:contain">@else<i class="bi bi-capsule" style="font-size:6rem;color:var(--primary)"></i>@endif</div>
    @if ($imgs->count() > 1)<div class="d-flex gap-2 mt-2 flex-wrap">@foreach ($imgs as $m)<a href="{{ $m->getUrl() }}" target="_blank" rel="noopener"><img src="{{ $m->hasGeneratedConversion('thumb') ? $m->getUrl('thumb') : $m->getUrl() }}" alt="" width="72" height="72" style="object-fit:cover;border-radius:12px;border:1px solid var(--line)" loading="lazy"></a>@endforeach</div>@endif
  </div>
  <div class="col-lg-7">
    @if ($product->category)<a class="badge-c" href="{{ route('site.products', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a>@endif
    <h2 class="h3 mt-2">{{ $product->name }}</h2>
    @if ($product->brand)<p class="text-muted-c mb-2">by {{ $product->brand->name }}</p>@endif
    <div class="d-flex flex-wrap gap-2 mb-3">
      @if ($product->requires_prescription)<span class="badge-c badge-warn"><i class="bi bi-file-earmark-medical"></i> Prescription required</span>@endif
      @if ($verified)<span class="badge-c"><i class="bi bi-patch-check"></i> Details verified by the store</span>@endif
    </div>
    @if ($price)<p class="fs-3 fw-bold mb-1">{{ $price['now'] }}@if ($price['mrp']) <s class="fs-6 text-muted">{{ $price['mrp'] }}</s>@endif</p><p class="small text-muted-c">Price includes GST. Please confirm with the store.</p>@endif
    <p><span class="avail {{ $availClass }}">{{ $availLabel }}</span></p>
    @if ($product->short_description)<p class="lead" style="font-size:1.05rem">{{ $product->short_description }}</p>@endif
    <div class="d-flex flex-wrap gap-2 my-3">
      @if (! $cartUnavailable)
        <form method="POST" action="{{ route('cart.items.store', $product->id) }}" class="d-flex gap-2">@csrf<label class="visually-hidden" for="productQuantity">Quantity</label><input id="productQuantity" class="form-control" type="number" name="quantity" value="1" min="1" max="{{ $product->track_inventory ? max(1, $product->stock_quantity) : 1000 }}" style="width:84px"><button class="btn-cure" type="submit"><i class="bi bi-bag-plus"></i> Add to cart</button></form>
      @elseif (! $product->requires_prescription)
        <button class="btn-cure" type="button" disabled title="{{ $cartUnavailable }}"><i class="bi bi-bag-plus"></i> Add to cart</button>
      @endif
      <a class="btn-cure" href="{{ route('site.contact', ['product' => $product->name]) }}"><i class="bi bi-chat-left-text"></i> Enquire about this product</a>
      <a class="btn-cure-outline" href="tel:{{ \App\Support\Settings::tel() }}"><i class="bi bi-telephone"></i> Call the store</a>
      @if (setting('whatsapp_number'))<a class="btn-cure-outline" target="_blank" rel="noopener" href="{{ \App\Support\Settings::whatsapp('Hello, is '.$product->name.' available?') }}"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
    </div>
    @if ($cartUnavailable && ! $product->requires_prescription)
      <div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ $cartUnavailable }} You can still contact the store to ask about availability.</div></div>
    @endif
    @if ($product->requires_prescription)<div class="notice mb-3"><i class="bi bi-shield-exclamation"></i><div>This medicine can only be supplied with a valid prescription. Please visit the store with your prescription. Do not send prescriptions through this website.</div></div>@endif
  </div>
</div>

<div class="row g-4 mt-2"><div class="col-lg-8">
  <ul class="nav nav-pills pd-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tDesc" type="button" role="tab">Description</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tInfo" type="button" role="tab">Product information</button></li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="tDesc" role="tabpanel"><div class="prose">@if ($product->description){!! markdown_safe($product->description) !!}@else<p class="text-muted-c">No description yet. Please ask the store for details.</p>@endif</div></div>
    <div class="tab-pane fade" id="tInfo" role="tabpanel">
      @if ($verified)
        <dl class="row">@foreach ($facts as $l => $v)@if ($v)<dt class="col-sm-4 text-muted-c">{{ $l }}</dt><dd class="col-sm-8">{{ $v }}</dd>@endif @endforeach
          @if ($product->storage_conditions)<dt class="col-sm-4 text-muted-c">Storage</dt><dd class="col-sm-8">{{ $product->storage_conditions }}</dd>@endif
          @if ($product->side_effects)<dt class="col-sm-4 text-muted-c">Possible side effects</dt><dd class="col-sm-8">{!! nl2br(e($product->side_effects)) !!}</dd>@endif
          @if ($product->contraindications)<dt class="col-sm-4 text-muted-c">Cautions</dt><dd class="col-sm-8">{!! nl2br(e($product->contraindications)) !!}</dd>@endif</dl>
        <div class="notice info"><i class="bi bi-info-circle"></i><div>General information only. It is not medical advice. Always read the pack and ask the pharmacist or your doctor how to use a medicine.</div></div>
      @else
        <div class="notice"><i class="bi bi-hourglass-split"></i><div>The medical details for this product are still being checked by the store. Please ask the pharmacist.</div></div>
      @endif
    </div>
  </div>
</div></div>

@if ($related->isNotEmpty())
<div class="mt-5"><h2 class="h4 mb-3">More from {{ $product->category?->name ?? 'the catalogue' }}</h2>
  <div class="row g-3 g-lg-4">@foreach ($related as $p)<div class="col-6 col-lg-3">@include('site.partials.product-card', ['p' => $p])</div>@endforeach</div></div>
@endif
</div></section>
@push('head')
<script type="application/ld+json">{!! json_encode(array_filter(['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->name, 'description' => strip_tags((string) $product->short_description), 'image' => $first?->getUrl(), 'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null]), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush
@endsection
