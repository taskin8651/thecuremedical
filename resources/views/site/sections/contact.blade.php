<section class="section" id="contact"><div class="container">
  <div class="section-head reveal"><span class="eyebrow">Visit us</span><h2>{{ setting('home_contact_title') }}</h2></div>
  <div class="row g-4">
    <div class="col-lg-5">@include('site.partials.contact-card')</div>
    <div class="col-lg-7"><iframe class="map-frame" title="Map showing {{ setting('business_name') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="{{ \App\Support\Settings::mapsEmbed() }}"></iframe></div>
  </div>
</div></section>
