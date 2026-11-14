@if ($faqs->isNotEmpty())
<section class="section bg-white-c" id="faq"><div class="container"><div class="row g-5">
  <div class="col-lg-4"><div class="section-head reveal"><span class="eyebrow">FAQ</span><h2>{{ setting('home_faq_title') }}</h2><p>Cannot find your answer? Call the store.</p><a class="btn-cure" href="{{ route('site.faq') }}">See all FAQs</a></div></div>
  <div class="col-lg-8">@include('site.partials.accordion', ['faqs' => $faqs, 'idp' => 'hf'])</div>
</div></div></section>
@endif
