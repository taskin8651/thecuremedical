<header class="topbar">
  <button type="button" class="icon-btn menu-toggle" data-toggle-sidebar aria-label="Open menu"><i class="bi bi-list fs-5"></i></button>
  <div class="spacer"></div>

  <div class="dropdown">
    <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="Notifications" aria-expanded="false">
      <i class="bi bi-bell"></i>
      @if ($unreadCount > 0)<span class="dot">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0 shadow" style="width:340px;max-width:92vw;border-radius:14px;overflow:hidden">
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
        <strong>Notifications</strong>
        @if ($unreadCount > 0)
          <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="btn btn-link btn-sm p-0">Mark all read</button></form>
        @endif
      </div>
      @forelse ($latestNotifications as $n)
        <a href="{{ route('admin.notifications.open', $n->id) }}" class="d-flex gap-2 px-3 py-2 text-decoration-none text-reset border-bottom {{ $n->read_at ? '' : 'bg-light' }}">
          <span class="activity-item" style="padding:0;border:0"><span class="ic"><i class="bi {{ $n->data['icon'] ?? 'bi-bell' }}"></i></span></span>
          <span class="small"><strong>{{ $n->data['title'] ?? 'Notification' }}</strong><br><span class="text-muted">{{ \Illuminate\Support\Str::limit($n->data['body'] ?? '', 70) }}</span><br><span class="text-muted">{{ $n->created_at->diffForHumans() }}</span></span>
        </a>
      @empty
        <div class="p-4 text-center text-muted small">You are all caught up.</div>
      @endforelse
      <a href="{{ route('admin.notifications.index') }}" class="d-block text-center py-2 small fw-bold">View all</a>
    </div>
  </div>

  <div class="dropdown">
    <button class="user-btn" data-bs-toggle="dropdown" aria-expanded="false">
      <span class="avatar">{{ auth()->user()->initials() }}</span>
      <span class="d-none d-md-block text-start lh-sm"><strong class="d-block" style="font-size:.85rem">{{ auth()->user()->name }}</strong><small class="text-muted">{{ auth()->user()->roles->pluck('name')->first() ?? 'No role' }}</small></span>
      <i class="bi bi-chevron-down small text-muted"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow" style="border-radius:12px">
      <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="bi bi-person me-2"></i>My profile</a></li>
      <li><a class="dropdown-item" href="{{ url('/') }}" target="_blank" rel="noopener"><i class="bi bi-globe me-2"></i>View website</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
    </ul>
  </div>
</header>
