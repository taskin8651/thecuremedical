@if (setting('promo_title'))
<section class="section-sm"><div class="container reveal"><div class="promo">
  @if (setting('promo_badge'))<span class="badge-c mb-3" style="background:rgba(255,255,255,.15);color:#fff">{{ setting('promo_badge') }}</span>@endif
  <h2 class="mb-3">{{ setting('promo_title') }}</h2>
  <p class="mb-4">{{ setting('promo_text') }}</p>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn-cure-light" href="tel:{{ \App\Support\Settings::tel() }}"><i class="bi bi-telephone"></i> Call {{ setting('phone') }}</a>
    @if (setting('promo_btn_label'))<a class="btn-cure-light" href="{{ setting('promo_btn_url') ?: route('site.contact') }}"><i class="bi bi-envelope"></i> {{ setting('promo_btn_label') }}</a>@endif
  </div>
</div></div></section>
@endif
