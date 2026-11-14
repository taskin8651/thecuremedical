@extends('site.layout')
@section('title', 'Customer sign in')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Sign in' => null], 'heading' => 'Customer sign in', 'lead' => 'Sign in to manage your account and view store orders.'])
<section class="section-sm"><div class="container"><div class="form-card mx-auto" style="max-width:520px">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ session('status') }}</div></div>@endif
  @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif
  <form method="POST" action="{{ route('customer.login.store') }}">@csrf
    <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
    <div class="mb-3"><label class="form-label" for="password">Password</label><input class="form-control" id="password" type="password" name="password" required autocomplete="current-password"></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"><label class="form-check-label" for="remember">Remember me</label></div>
    <button class="btn-cure" type="submit">Sign in</button>
  </form>
  <p class="mt-3 mb-1"><a href="{{ route('customer.password.request') }}">Forgot password?</a></p>
  <p class="mb-0">New here? <a href="{{ route('customer.register') }}">Create an account</a></p>
</div></div></section>
@endsection
