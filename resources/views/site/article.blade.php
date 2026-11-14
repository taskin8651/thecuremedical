@extends('site.layout')
@php $img = $post->getFirstMediaUrl('featured'); @endphp
@section('title', $post->seo_title ?: $post->title)
@section('description', $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->excerpt), 155))
@if ($img)@section('og_image', $img)@endif
@section('content')
@include('site.partials.hero', ['crumbs' => ['Articles' => route('site.blog'), $post->title => null], 'heading' => $post->title, 'lead' => null])
<section class="section-sm"><div class="container" style="max-width:820px">
  <p class="small text-muted-c">@if ($post->category){{ $post->category }} &middot; @endif{{ ($post->published_at ?? $post->created_at)->format('d M Y') }}@if ($post->author) &middot; {{ $post->author->name }}@endif</p>
  @if ($img)<img src="{{ $img }}" alt="" class="img-fluid mb-4" style="border-radius:var(--radius)">@endif
  <div class="prose article-body">{!! markdown_safe($post->content) !!}</div>
  <div class="notice info mt-4"><i class="bi bi-info-circle"></i><div>This article is general information and is not medical advice. Please speak to a doctor or pharmacist about your own health.</div></div>
</div></section>
@if ($more->isNotEmpty())<section class="section-sm bg-white-c"><div class="container"><h2 class="h4 mb-3">More articles</h2><div class="row g-3 g-lg-4">@foreach ($more as $a)<div class="col-md-6 col-lg-4">@include('site.partials.article-card', ['a' => $a])</div>@endforeach</div></div></section>@endif
@endsection
