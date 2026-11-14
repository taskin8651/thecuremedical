@extends('admin.layouts.guest')
@section('title', 'Sign in')
@section('content')
  <h1 class="h3 mb-1">Welcome back</h1>
  <p class="text-muted mb-4">Sign in to manage {{ setting('business_name') }}.</p>
  <form method="POST" action="{{ route('login.attempt') }}" data-once novalidate>
    @csrf
    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" autocomplete="username" required autofocus>
    </div>
    <div class="mb-3">
      <div class="d-flex justify-content-between"><label for="password" class="form-label">Password</label><a href="{{ route('password.request') }}" class="small">Forgot password?</a></div>
      <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
    </div>
    <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"><label class="form-check-label" for="remember">Keep me signed in on this device</label></div>
    <button class="btn-c w-100 justify-content-center py-2" type="submit">Sign in</button>
  </form>
@endsection
