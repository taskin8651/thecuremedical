@extends('admin.layouts.guest')
@section('title', 'Forgot password')
@section('content')
  <h1 class="h3 mb-1">Forgot your password?</h1>
  <p class="text-muted mb-4">Enter your email and we will send a reset link.</p>
  <form method="POST" action="{{ route('password.email') }}" data-once>
    @csrf
    <div class="mb-3"><label for="email" class="form-label">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus></div>
    <button class="btn-c w-100 justify-content-center py-2" type="submit">Send reset link</button>
  </form>
  <p class="text-center mt-3 mb-0"><a href="{{ route('login') }}"><i class="bi bi-arrow-left"></i> Back to sign in</a></p>
@endsection
