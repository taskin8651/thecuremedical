@extends('site.layout')
@section('title', 'Choose a new password')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Sign in' => route('customer.login'), 'Reset password' => null], 'heading' => 'Choose a new password', 'lead' => 'Set a strong password for your account.'])
<section class="section-sm"><div class="container"><div class="form-card mx-auto" style="max-width:520px">
  @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif
  <form method="POST" action="{{ route('customer.password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></div>
    <div class="mb-3"><label class="form-label" for="password">New password</label><input class="form-control" id="password" type="password" name="password" minlength="10" required autocomplete="new-password"><div class="form-text">At least 10 characters with uppercase, lowercase, and a number.</div></div>
    <div class="mb-3"><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" minlength="10" required autocomplete="new-password"></div>
    <button class="btn-cure" type="submit">Reset password</button>
  </form>
</div></div></section>
@endsection
