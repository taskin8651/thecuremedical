@extends('site.layout')
@section('title', 'Reset customer password')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Sign in' => route('customer.login'), 'Reset password' => null], 'heading' => 'Reset your password', 'lead' => 'We will email a secure reset link if an account matches.'])
<section class="section-sm"><div class="container"><div class="form-card mx-auto" style="max-width:520px">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ session('status') }}</div></div>@endif
  @if (session('mail_error'))<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ session('mail_error') }}</div></div>@endif
  @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif
  <form method="POST" action="{{ route('customer.password.email') }}">@csrf
    <label class="form-label" for="email">Email address</label><input class="form-control mb-3" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
    <button class="btn-cure" type="submit">Send reset link</button>
  </form>
</div></div></section>
@endsection
