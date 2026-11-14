@extends('site.layout')
@section('title', 'FAQ')
@section('content')
@include('site.partials.hero', ['crumbs' => ['FAQ' => null], 'heading' => 'Frequently asked questions', 'lead' => 'Cannot find your answer? Call the store.'])
<section class="section-sm"><div class="container" style="max-width:860px">
  @if ($faqs->isNotEmpty())@include('site.partials.accordion', ['faqs' => $faqs, 'idp' => 'fq'])@else<p class="text-muted-c">Questions will appear here soon.</p>@endif
</div></section>
@push('head')
@if ($faqs->isNotEmpty())<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs->map(fn ($f) => ['@type' => 'Question', 'name' => $f->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f->answer]])->values()], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>@endif
@endpush
@endsection
