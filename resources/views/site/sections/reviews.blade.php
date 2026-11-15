@if (setting('google_rating') || $testimonials->isNotEmpty())
<section class="section bg-soft" id="testimonials"><div class="container">
  <div class="section-head reveal"><span class="eyebrow">Testimonials</span><h2>{{ setting('home_reviews_title') }}</h2></div>
  <div class="row g-4">
    @if (setting('google_rating'))
    <div class="col-lg-4 reveal"><div class="rating-panel">
      <div class="rating-big">{{ setting('google_rating') }}</div><div class="stars fs-4 my-2" aria-label="{{ setting('google_rating') }} out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
      <p class="text-muted-c mb-3">Based on {{ setting('google_reviews') }} Google reviews</p>
      <a class="btn-cure-outline btn-sm-c" target="_blank" rel="noopener" href="{{ \App\Support\Settings::mapsView() }}">View on Google Maps</a>
    </div></div>
    @endif
    @if ($testimonials->isNotEmpty())
    <div class="col-lg-8"><div class="row g-3">
      @foreach ($testimonials as $t)
        <div class="col-md-6 reveal"><div class="t-card">
          <div class="stars" aria-label="{{ $t->rating }} out of 5 stars">@for ($star = 1; $star <= 5; $star++)<span aria-hidden="true">{{ $star <= $t->rating ? '★' : '☆' }}</span>@endfor</div>
          <p>{{ $t->review }}</p>
          <div class="who"><strong>{{ $t->name }}</strong>@if ($t->source)<span class="small text-muted-c">&middot; {{ $t->source }}</span>@endif</div>
        </div></div>
      @endforeach
    </div></div>
    @endif
  </div>
</div></section>
@endif
