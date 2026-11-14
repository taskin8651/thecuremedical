@extends('admin.layouts.app')
@section('title', 'My profile')
@section('content')
<div class="page-head"><div><h1>My profile</h1><p class="sub">Signed in as {{ $user->email }} &middot; {{ $user->roles->pluck('name')->implode(', ') ?: 'No role assigned' }}</p></div></div>
<div class="row g-3">
  <div class="col-lg-6">
    <form method="POST" action="{{ route('admin.profile.update') }}" class="card-c" data-once>
      @csrf @method('PUT')
      <div class="card-head"><h2>Profile details</h2></div>
      <div class="card-body-c">
        <div class="mb-3"><label class="form-label" for="name">Full name</label><input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
        <div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
        <div class="mb-3"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}"></div>
        <button class="btn-c" type="submit">Save profile</button>
      </div>
    </form>
  </div>
  <div class="col-lg-6">
    <form method="POST" action="{{ route('admin.profile.password') }}" class="card-c mb-3" data-once>
      @csrf @method('PUT')
      <div class="card-head"><h2>Change password</h2></div>
      <div class="card-body-c">
        <div class="mb-3"><label class="form-label" for="current_password">Current password</label><input id="current_password" type="password" name="current_password" class="form-control" autocomplete="current-password" required></div>
        <div class="mb-3"><label class="form-label" for="password">New password</label><input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required><div class="form-text">At least 10 characters with upper and lower case letters and a number.</div></div>
        <div class="mb-3"><label class="form-label" for="password_confirmation">Confirm new password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
        <button class="btn-c" type="submit">Change password</button>
      </div>
    </form>
    <form method="POST" action="{{ route('admin.profile.logout-others') }}" class="card-c" data-confirm="Sign out of all other devices?">
      @csrf
      <div class="card-head"><h2>Sessions</h2></div>
      <div class="card-body-c">
        <p class="text-muted">Last sign-in: {{ $user->last_login_at?->format('d M Y, h:i A') ?? 'unknown' }}. If you used a shared computer, sign out of other devices.</p>
        <div class="input-group"><input type="password" name="password" class="form-control" placeholder="Confirm your password" autocomplete="current-password" required><button class="btn-c-danger" type="submit">Sign out other devices</button></div>
      </div>
    </form>
  </div>
</div>
@endsection
