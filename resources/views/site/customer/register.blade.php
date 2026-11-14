@extends('site.layout')
@section('title', 'Create customer account')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Create account' => null], 'heading' => 'Create your account', 'lead' => 'Save your details and keep track of store orders.'])
<section class="section-sm"><div class="container"><div class="form-card mx-auto" style="max-width:620px">
  @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif
  <form method="POST" action="{{ route('customer.register.store') }}">@csrf
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="150" required autocomplete="name"></div>
      <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" required autocomplete="tel"></div>
      <div class="col-12"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" maxlength="150" required autocomplete="email"></div>
      <div class="col-md-6"><label class="form-label" for="password">Password</label><input class="form-control" id="password" type="password" name="password" minlength="10" required autocomplete="new-password"><div class="form-text">At least 10 characters with uppercase, lowercase, and a number.</div></div>
      <div class="col-md-6"><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" minlength="10" required autocomplete="new-password"></div>
    </div>
    <button class="btn-cure mt-4" type="submit">Create account</button>
    <p class="small text-muted-c mt-3 mb-0">We will email a verification link before account orders and checkout are available.</p>
  </form>
  <hr><p class="mb-0">Already have an account? <a href="{{ route('customer.login') }}">Sign in</a></p>
</div></div></section>
@endsection
