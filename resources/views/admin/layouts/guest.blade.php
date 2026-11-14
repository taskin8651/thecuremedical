<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', 'Sign in') | {{ setting('business_name') }}</title>
  <link rel="icon" href="{{ setting_url('favicon') ?: asset('admin/favicon.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="{{ asset('admin/css/admin.css') }}" rel="stylesheet">
</head>
<body class="auth-body">
  <aside class="auth-aside">
    <div class="d-flex align-items-center gap-3">
      <span class="brand-mark"><i class="bi bi-plus-lg"></i></span>
      <div class="brand-text"><strong class="text-white">{{ setting('business_name') }}</strong><span>Admin panel</span></div>
    </div>
    <div>
      <h2>Run the pharmacy website from one place.</h2>
      <p class="mt-3 mb-0">Products, content, enquiries and stock in one secure workspace.</p>
    </div>
    <small>{{ setting('address') }}</small>
  </aside>
  <section class="auth-panel">
    <div class="auth-card">
      @include('admin.partials.flash')
      @yield('content')
    </div>
  </section>
</body>
</html>
