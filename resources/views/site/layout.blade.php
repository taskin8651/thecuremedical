@php
  $biz = setting('business_name');
  $title = trim($__env->yieldContent('title'));
  $fullTitle = $title !== '' ? $title.' | '.$biz : (setting('meta_title') ?: $biz);
  $desc = trim($__env->yieldContent('description')) ?: setting('meta_description');
  $primary = preg_match('/^#[0-9a-fA-F]{6}$/', (string) setting('primary_color')) ? setting('primary_color') : '#0D6B4B';
  $dark = preg_match('/^#[0-9a-fA-F]{6}$/', (string) setting('dark_color')) ? setting('dark_color') : '#084B37';
  $logo = setting_url('logo_header');
  $footLogo = setting_url('logo_footer') ?: $logo;
  $fav = setting_url('favicon') ?: asset('assets/icons/favicon.svg');
  $styleVersion = @filemtime(public_path('assets/css/style.css')) ?: 1;
  $componentsVersion = @filemtime(public_path('assets/css/components.css')) ?: 1;
  $responsiveVersion = @filemtime(public_path('assets/css/responsive.css')) ?: 1;
  $scriptVersion = @filemtime(public_path('assets/js/site.js')) ?: 1;
  $og = trim($__env->yieldContent('og_image')) ?: setting_url('og_image');
  $isProductPage = request()->routeIs('site.product');
  $productOgImage = trim($__env->yieldContent('product_og_image')) ?: ($og ?: asset('assets/images/hero.svg'));
  $productOgImage = $productOgImage ? preg_replace('/^http:/i', 'https:', $productOgImage) : null;
  $nav = [['site.home', 'Home', 'bi-house'], ['site.about', 'About', 'bi-info-circle'], ['site.products', 'Products', 'bi-grid'], ['site.categories', 'Categories', 'bi-diagram-3'], ['site.services', 'Services', 'bi-clipboard2-pulse'], ['site.gallery', 'Gallery', 'bi-images'], ['site.blog', 'Articles', 'bi-journal-text'], ['site.contact', 'Contact', 'bi-telephone']];
  $tel = \App\Support\Settings::tel();
  $hours = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) setting('opening_hours')))));
  $firstHour = $hours[0] ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $fullTitle }}</title>
  <meta name="description" content="{{ $desc }}">
  @if (setting('meta_keywords'))<meta name="keywords" content="{{ setting('meta_keywords') }}">@endif
  @if (trim($__env->yieldContent('noindex')) !== '' || ! filter_var(setting('robots_index', true), FILTER_VALIDATE_BOOLEAN))<meta name="robots" content="noindex,nofollow">@endif
  <link rel="canonical" href="{{ url()->current() }}">
  <meta property="og:type" content="{{ $isProductPage ? 'product' : 'website' }}"><meta property="og:site_name" content="{{ $biz }}">
  <meta property="og:title" content="{{ $fullTitle }}"><meta property="og:description" content="{{ $desc }}"><meta property="og:url" content="{{ $isProductPage ? route('site.product', request()->route('product')) : url()->current() }}">
  @if ($isProductPage)
    @if ($productOgImage)
      <meta property="og:image" content="{{ $productOgImage }}">
      <meta property="og:image:secure_url" content="{{ $productOgImage }}">
      <meta property="og:image:alt" content="{{ $title }}">
      <meta property="og:image:width" content="1200">
      <meta property="og:image:height" content="630">
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" content="{{ $title }}">
      <meta name="twitter:description" content="{{ $desc }}">
      <meta name="twitter:image" content="{{ $productOgImage }}">
    @endif
  @elseif ($og)<meta property="og:image" content="{{ $og }}">@endif
  <link rel="icon" href="{{ $fav }}">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('assets/css/style.css') }}?v={{ $styleVersion }}" rel="stylesheet">
  <link href="{{ asset('assets/css/components.css') }}?v={{ $componentsVersion }}" rel="stylesheet">
  <link href="{{ asset('assets/css/responsive.css') }}?v={{ $responsiveVersion }}" rel="stylesheet">
  <style>:root{--primary:{{ $primary }};--primary-dark:{{ $dark }}}
  .pagination{justify-content:center;flex-wrap:wrap;gap:.25rem}.page-link{border-radius:12px!important;color:var(--primary);border-color:var(--line)}.page-item.active .page-link{background:var(--primary);border-color:var(--primary)}
  .brand-logo{height:44px;width:auto}.hp{position:absolute!important;left:-9999px;height:0;overflow:hidden}
  .prose p,.prose li{color:#34463e}.prose h2{font-size:1.4rem;margin-top:1.6rem}.prose h3{font-size:1.15rem;margin-top:1.2rem}
  @if (setting('custom_css')){!! str_ireplace('</style', '', (string) setting('custom_css')) !!}@endif</style>
  @stack('head')
  @if (setting('header_scripts')){!! setting('header_scripts') !!}@endif
</head>
<body data-page="@yield('page', 'inner')">
<a class="skip-link" href="#main">Skip to content</a>

@if (filter_var(setting('announcement_enabled', true), FILTER_VALIDATE_BOOLEAN) && setting('announcement_text'))
<div class="announce"><div class="container">
  <span><i class="bi bi-megaphone me-1"></i>@if (setting('announcement_link'))<a href="{{ setting('announcement_link') }}">{{ setting('announcement_text') }}</a>@else{{ setting('announcement_text') }}@endif</span>
  <span class="hide-md"><a href="tel:{{ $tel }}"><i class="bi bi-telephone me-1"></i>{{ setting('phone') }}</a>@if ($firstHour)<span class="sep">|</span><span>{{ $firstHour }}</span>@endif</span>
</div></div>
@endif

<header class="site-nav"><div class="container">
  <a class="brand" href="{{ route('site.home') }}" aria-label="{{ $biz }} home">
    @if ($logo)<img class="brand-logo" src="{{ $logo }}" alt="{{ $biz }}">@else
    <span class="brand-mark"><i class="bi bi-plus-lg"></i></span>
    <span class="brand-text"><strong>{{ \Illuminate\Support\Str::before($biz, ' Medicall') }}</strong><span>{{ str_contains($biz, 'Medicall') ? 'Medicall Hall' : setting('tagline') }}</span></span>@endif
  </a>
  <nav aria-label="Primary"><ul class="nav-links">
    @foreach ($nav as [$r, $l])<li><a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'active' : '' }}" @if (request()->routeIs($r)) aria-current="page" @endif>{{ $l }}</a></li>@endforeach
  </ul></nav>
  <div class="nav-actions">
    <button class="btn-icon" data-action="open-search" aria-label="Search products"><i class="bi bi-search"></i></button>
    <a class="btn-icon cart-nav-link" href="{{ route('cart.index') }}" aria-label="Cart, {{ $cartCount }} items"><i class="bi bi-bag"></i><span class="cart-nav-count">{{ $cartCount }}</span></a>
    @if (auth()->guard('customer')->check())<a class="btn-icon" href="{{ route('customer.profile') }}" aria-label="My account"><i class="bi bi-person-circle"></i></a>@else<a class="btn-icon" href="{{ route('customer.login') }}" aria-label="Customer sign in"><i class="bi bi-person-circle"></i></a>@endif
    <a class="btn-cure btn-sm-c hide-md ms-1" href="tel:{{ $tel }}"><i class="bi bi-telephone"></i> Call Now</a>
    <button class="btn-icon menu-toggle" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
  </div>
</div></header>

<div class="offcanvas offcanvas-end mobile-menu" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
  <div class="offcanvas-header"><h2 class="h5 m-0" id="mobileMenuLabel">Menu</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button></div>
  <div class="offcanvas-body">
    @foreach ($nav as [$r, $l, $ic])<a class="m-link {{ request()->routeIs($r) ? 'active' : '' }}" href="{{ route($r) }}"><i class="bi {{ $ic }}"></i>{{ $l }}</a>@endforeach
    <hr>
    <a class="m-link" href="{{ route('cart.index') }}"><i class="bi bi-bag"></i>Cart ({{ $cartCount }})</a>
    @if (auth()->guard('customer')->check())<a class="m-link" href="{{ route('customer.profile') }}"><i class="bi bi-person-circle"></i>My account</a><a class="m-link" href="{{ route('customer.orders.index') }}"><i class="bi bi-receipt"></i>My orders</a>@else<a class="m-link" href="{{ route('customer.login') }}"><i class="bi bi-person-circle"></i>Customer sign in</a><a class="m-link" href="{{ route('customer.register') }}"><i class="bi bi-person-plus"></i>Create account</a>@endif
    <hr>
    <a class="btn-cure w-100 mb-2" href="tel:{{ $tel }}"><i class="bi bi-telephone"></i> Call Now</a>
    @if (setting('whatsapp_number'))<a class="btn-cure-outline w-100" href="{{ \App\Support\Settings::whatsapp('Hello, I would like to make an enquiry.') }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
  </div>
</div>

<div class="search-overlay" id="searchOverlay" role="dialog" aria-modal="true" aria-label="Search products">
  <div class="search-box">
    <form class="search-input-wrap" action="{{ route('site.products') }}" method="GET" role="search">
      <i class="bi bi-search text-muted-c"></i>
      <label for="searchInput" class="visually-hidden">Search products</label>
      <input id="searchInput" type="search" name="q" maxlength="100" placeholder="Search products, brands, categories..." autocomplete="off">
      <button type="button" class="btn-icon" data-action="close-search" aria-label="Close search"><i class="bi bi-x-lg"></i></button>
    </form>
  </div>
</div>

<main id="main">
  @yield('content')
</main>

<footer class="site-footer"><div class="container">
  <div class="row ">
    <div class="col-lg-4">
      <a class="brand mb-3" href="{{ route('site.home') }}">
        @if ($footLogo)<img class="brand-logo" src="{{ $footLogo }}" alt="{{ $biz }}">@else<span class="brand-mark"><i class="bi bi-plus-lg"></i></span><span class="brand-text"><strong>{{ \Illuminate\Support\Str::before($biz, ' Medicall') }}</strong><span>{{ str_contains($biz, 'Medicall') ? 'Medicall Hall' : setting('tagline') }}</span></span>@endif
      </a>
      <p>{{ setting('footer_text') }}</p>
      <div class="social" aria-label="Contact shortcuts">
        <a href="tel:{{ $tel }}" aria-label="Call"><i class="bi bi-telephone"></i></a>
        @if (setting('whatsapp_number'))<a href="{{ \App\Support\Settings::whatsapp() }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
        <a href="{{ \App\Support\Settings::mapsDirections() }}" target="_blank" rel="noopener" aria-label="Directions"><i class="bi bi-geo-alt"></i></a>
        @foreach (['facebook_url' => 'bi-facebook', 'instagram_url' => 'bi-instagram', 'youtube_url' => 'bi-youtube'] as $k => $ic)@if (setting($k))<a href="{{ setting($k) }}" target="_blank" rel="noopener noreferrer" aria-label="{{ ucfirst(str_replace('_url', '', $k)) }}"><i class="bi {{ $ic }}"></i></a>@endif @endforeach
      </div>
    </div>
    <div class="col-6 col-lg-2"><h4>Explore</h4><ul>
      <li><a href="{{ route('site.about') }}">About Us</a></li><li><a href="{{ route('site.products') }}">Products</a></li><li><a href="{{ route('site.categories') }}">Categories</a></li><li><a href="{{ route('site.services') }}">Services</a></li><li><a href="{{ route('site.gallery') }}">Gallery</a></li><li><a href="{{ route('site.blog') }}">Health Articles</a></li></ul></div>
    <div class="col-6 col-lg-2"><h4>Help</h4><ul>
      <li><a href="{{ route('site.contact') }}">Contact Us</a></li><li><a href="{{ route('site.faq') }}">FAQ</a></li><li><a href="{{ route('site.privacy') }}">Privacy Policy</a></li><li><a href="{{ route('site.terms') }}">Terms &amp; Conditions</a></li><li><a href="{{ route('site.refund') }}">Return / Refund</a></li></ul></div>
    <div class="col-lg-4"><h4>Visit the store</h4><ul>
      <li><i class="bi bi-geo-alt me-2"></i>{{ setting('address') }}</li>
      <li><i class="bi bi-telephone me-2"></i><a href="tel:{{ $tel }}">{{ setting('phone') }}</a></li>
      @foreach ($hours as $h)<li><i class="bi bi-clock me-2"></i>{{ $h }}</li>@endforeach
      @if (setting('google_rating'))<li><i class="bi bi-star-fill me-2" style="color:#F2C14E"></i>{{ setting('google_rating') }} on Google ({{ setting('google_reviews') }} reviews)</li>@endif</ul></div>
  </div>
  <div class="footer-bottom d-flex flex-wrap justify-content-between gap-2">
    <span>&copy; {{ date('Y') }} {{ setting('copyright_text') }}</span>
    <span class="footer-disclaimer">Information on this site is general and is not medical advice. Please ask the pharmacist.</span>
  </div>
</div></footer>
<button class="btn-icon to-top" id="toTop" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>
<nav class="bottom-nav" aria-label="Mobile">
  <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}"><i class="bi bi-house"></i>Home</a>
  <a href="{{ route('site.products') }}" class="{{ request()->routeIs('site.products') ? 'active' : '' }}"><i class="bi bi-grid"></i>Products</a>
  <button type="button" data-action="open-search"><i class="bi bi-search"></i>Search</button>
  <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}"><i class="bi bi-bag"></i>Cart
    @if ($cartCount)
      <span class="cart-bottom-count">{{ $cartCount }}</span>
    @endif
  </a>
  <a href="tel:{{ $tel }}"><i class="bi bi-telephone"></i>Call</a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/site.js') }}?v={{ $scriptVersion }}"></script>
@stack('scripts')
@if (setting('footer_scripts')){!! setting('footer_scripts') !!}@endif
</body>
</html>
