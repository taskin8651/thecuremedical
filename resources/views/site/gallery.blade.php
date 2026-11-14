@extends('site.layout')
@section('title', 'Gallery')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Gallery' => null], 'heading' => 'Gallery', 'lead' => 'A look inside the store.'])
<section class="section-sm"><div class="container">
  @if ($cats->isNotEmpty())<div class="d-flex flex-wrap gap-2 mb-4"><a class="badge-c {{ $current === '' ? 'active' : '' }}" href="{{ route('site.gallery') }}">All</a>@foreach ($cats as $c)<a class="badge-c" href="{{ route('site.gallery', ['category' => $c]) }}" @if ($current === $c) style="background:var(--primary);color:#fff" @endif>{{ $c }}</a>@endforeach</div>@endif
  <div class="row g-3">
    @forelse ($items as $g)@php $u = $g->getFirstMediaUrl('image', 'thumb'); $full = $g->getFirstMediaUrl('image'); @endphp
      <div class="col-6 col-lg-4"><a href="{{ $full ?: '#' }}" target="_blank" rel="noopener" class="g-tile d-block" style="min-height:240px;padding:0;position:relative" title="{{ $g->title }}">@if ($u)<img src="{{ $u }}" alt="{{ $g->title }}" loading="lazy" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0">@endif</a><div class="small text-muted-c mt-1">{{ $g->title }}</div></div>
    @empty<div class="col-12"><p class="text-muted-c">Photos will appear here soon.</p></div>@endforelse
  </div>
  <div class="mt-4">{{ $items->links() }}</div>
</div></section>
@endsection
