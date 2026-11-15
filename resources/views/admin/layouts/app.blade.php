<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', 'Admin') | {{ setting('business_name') }}</title>
  <link rel="icon" href="{{ setting_url('favicon') ?: asset('admin/favicon.svg') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" onerror="this.onerror=null;this.href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css'">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" onerror="this.onerror=null;this.href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css'">
  <link href="{{ asset('admin/css/admin.css') }}?v={{ filemtime(public_path('admin/css/admin.css')) }}" rel="stylesheet">
  @stack('head')
</head>
<body>
<div class="admin-shell">
  @include('admin.partials.sidebar')
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <div class="main">
    @include('admin.partials.topbar')
    <main class="content" id="main">
      @include('admin.partials.flash')
      @yield('content')
    </main>
  </div>
</div>
<script async src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" onerror="this.onerror=null;this.src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js'"></script>
<script defer src="{{ asset('admin/js/admin.js') }}?v={{ filemtime(public_path('admin/js/admin.js')) }}"></script>
@stack('scripts')
</body>
</html>
