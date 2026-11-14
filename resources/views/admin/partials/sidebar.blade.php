<aside class="sidebar" id="sidebar" aria-label="Main navigation">
  <a class="sidebar-brand" href="{{ route('admin.dashboard') }}">
    <span class="brand-mark"><i class="bi bi-plus-lg"></i></span>
    <span class="brand-text"><strong>{{ \Illuminate\Support\Str::limit(setting('business_name'), 24) }}</strong><span>Admin panel</span></span>
  </a>
  <nav class="sidebar-nav">
    @foreach (config('cure.nav') as $group)
      @php($visible = collect($group['items'])->filter(fn ($i) => auth()->user()->can($i[3])))
      @if ($visible->isNotEmpty())
        <div class="nav-group-label">{{ $group['label'] }}</div>
        @foreach ($visible as [$label, $icon, $route, $permission, $pattern])
          <a href="{{ route($route) }}" class="nav-item-link {{ request()->routeIs($pattern) ? 'active' : '' }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
            <i class="bi {{ $icon }}"></i><span>{{ $label }}</span>
          </a>
        @endforeach
      @endif
    @endforeach
  </nav>
  <div class="sidebar-foot">
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="text-white-50"><i class="bi bi-box-arrow-up-right me-1"></i>View website</a>
  </div>
</aside>
