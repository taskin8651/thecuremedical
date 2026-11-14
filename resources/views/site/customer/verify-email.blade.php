@extends('site.layout')
@section('title', 'Verify your email')
@section('content')
@include('site.partials.hero', ['crumbs' => ['Verify email' => null], 'heading' => 'Verify your email address', 'lead' => 'Confirm you own the email address on your account.'])
<section class="section-sm"><div class="container"><div class="form-card mx-auto" style="max-width:620px">
  @if (session('status'))<div class="notice info mb-3"><i class="bi bi-info-circle"></i><div>{{ session('status') }}</div></div>@endif
  @if (session('mail_error'))<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ session('mail_error') }}</div></div>@endif
  @if ($customer->hasVerifiedEmail())
    <p><strong>{{ $customer->email }}</strong> is already verified. You can continue using your account.</p>
  @else
    <p>Verify <strong>{{ $customer->email }}</strong> before checkout or viewing order details. If you have not received the link, check your spam folder or request a new one below.</p>
    <form method="POST" action="{{ route('customer.verification.resend') }}">@csrf<button class="btn-cure-outline" type="submit">Send a new verification link</button></form>
  @endif
  <form method="POST" action="{{ route('customer.logout') }}" class="mt-3">@csrf<button class="btn btn-link p-0">Sign out</button></form>
</div></div></section>
@endsection
