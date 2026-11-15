@if ($brands->isNotEmpty())
<section class="section bg-white-c" id="brands"><div class="container">
  <div class="section-head reveal"><span class="eyebrow">Shop trusted names</span><h2>{{ setting('home_brands_title') }}</h2></div>
  <div class="row g-3 g-lg-4">
    @foreach ($brands as $brand)
      <div class="col-6 col-md-4 col-lg-3 reveal">
        <a class="home-brand-card" href="{{ route('site.products', ['q' => $brand->name]) }}" aria-label="Browse {{ $brand->name }} products">
          <span class="home-brand-logo">
            @if ($brand->hasMedia('logo'))<img src="{{ $brand->getFirstMediaUrl('logo', 'thumb') }}" alt="{{ $brand->name }}" loading="lazy">@else<i class="bi bi-capsule" aria-hidden="true"></i>@endif
          </span>
          <span class="home-brand-name">{{ $brand->name }}</span>
          <span class="home-brand-count">{{ $brand->products_count }} {{ \Illuminate\Support\Str::plural('product', $brand->products_count) }}</span>
          <span class="home-brand-arrow" aria-hidden="true"><i class="bi bi-arrow-up-right"></i></span>
        </a>
      </div>
    @endforeach
  </div>
</div></section>
<style>
  .home-brand-card{position:relative;display:flex;min-height:174px;height:100%;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:20px 16px;border:1px solid #e4ece7;border-radius:16px;background:#fff;color:#183b2d;text-align:center;text-decoration:none;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
  .home-brand-card:hover{transform:translateY(-3px);border-color:#b9d9c8;box-shadow:0 12px 28px #174b3212;color:#12684e}
  .home-brand-logo{display:grid;width:76px;height:76px;place-items:center;overflow:hidden;border-radius:14px;background:#f4f8f5;color:#12684e;font-size:29px}
  .home-brand-logo img{width:100%;height:100%;object-fit:contain;padding:8px}
  .home-brand-name{font-size:14px;font-weight:700;line-height:1.3}
  .home-brand-count{color:#75857c;font-size:11px}
  .home-brand-arrow{position:absolute;top:12px;right:13px;color:#9aac9f;font-size:13px}
</style>
@endif
