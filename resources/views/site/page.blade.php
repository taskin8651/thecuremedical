@extends('site.layout')
@section('title', $page->seo_title ?: $page->title)
@section('description', $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->content), 155))
@section('content')
@include('site.partials.hero', ['crumbs' => [$page->title => null], 'heading' => $page->title, 'lead' => null])
<section class="section-sm"><div class="container" style="max-width:860px"><div class="prose">{!! markdown_safe($page->content) !!}</div><p class="small text-muted-c mt-4">Last updated {{ $page->updated_at->format('d M Y') }}</p></div></section>
@endsection
