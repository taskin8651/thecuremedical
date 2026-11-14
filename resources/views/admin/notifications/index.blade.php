@extends('admin.layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="page-head">
  <div><h1>Notifications</h1><p class="sub">{{ $unreadCount }} unread</p></div>
  <div class="page-actions">
    <a class="btn-c-outline" href="{{ route('admin.notifications.index', ['filter' => $filter === 'unread' ? 'all' : 'unread']) }}">{{ $filter === 'unread' ? 'Show all' : 'Show unread only' }}</a>
    @if ($unreadCount > 0)<form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="btn-c" type="submit"><i class="bi bi-check2-all"></i> Mark all read</button></form>@endif
  </div>
</div>
<div class="card-c">
  @forelse ($notifications as $n)
    <a href="{{ route('admin.notifications.open', $n->id) }}" class="list-row text-decoration-none text-reset {{ $n->read_at ? '' : 'bg-light' }}">
      <div class="d-flex gap-3 align-items-center">
        <span class="activity-item" style="padding:0;border:0"><span class="ic"><i class="bi {{ $n->data['icon'] ?? 'bi-bell' }}"></i></span></span>
        <div><strong>{{ $n->data['title'] ?? 'Notification' }}</strong>@unless ($n->read_at)<span class="pill pill-info ms-2">New</span>@endunless<div class="small text-muted">{{ $n->data['body'] ?? '' }}</div></div>
      </div>
      <span class="small text-muted text-nowrap">{{ $n->created_at->diffForHumans() }}</span>
    </a>
  @empty
    <div class="empty-state"><i class="bi bi-bell"></i>No notifications.</div>
  @endforelse
  @if ($notifications->hasPages())<div class="p-3">{{ $notifications->links() }}</div>@endif
</div>
@endsection
