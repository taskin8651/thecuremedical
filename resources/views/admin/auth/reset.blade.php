@extends('admin.layouts.guest')
@section('title', 'Reset password')
@section('content')
  <h1 class="h3 mb-1">Choose a new password</h1>
  <p class="text-muted mb-4">At least 10 characters with upper and lower case letters and a number.</p>
  <form method="POST" action="{{ route('password.update') }}" data-once>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3"><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="form-control" required></div>
    <div class="mb-3"><label for="password" class="form-label">New password</label><input id="password" type="password" name="password" class="form-control" autocomplete="new-password" required></div>
    <div class="mb-4"><label for="password_confirmation" class="form-label">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
    <button class="btn-c w-100 justify-content-center py-2" type="submit">Reset password</button>
  </form>
@endsection
