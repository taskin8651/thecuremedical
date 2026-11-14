@extends('site.layout')
@section('title', 'My account')
@section('content')
@include('site.partials.hero', ['crumbs' => ['My account' => null], 'heading' => 'My account', 'lead' => 'Manage your contact and delivery details.'])
<section class="section-sm"><div class="container"><div class="row g-4">
  <aside class="col-lg-3"><div class="form-card"><a class="d-block mb-2" href="{{ route('customer.profile') }}">Profile</a><a class="d-block mb-2" href="{{ route('customer.orders.index') }}">My orders</a><form method="POST" action="{{ route('customer.logout') }}">@csrf<button class="btn btn-link p-0">Sign out</button></form></div></aside>
  <div class="col-lg-9">
    @if (session('status'))<div class="notice info mb-3"><i class="bi bi-check-circle"></i><div>{{ session('status') }}</div></div>@endif
    @if ($errors->any())<div class="notice danger mb-3"><i class="bi bi-exclamation-circle"></i><div>{{ $errors->first() }}</div></div>@endif
    <div class="form-card mb-3"><h2 class="h5">Contact and default delivery details</h2>
      <form method="POST" action="{{ route('customer.profile.update') }}">@csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label" for="name">Full name</label><input class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" maxlength="150" required></div>
          <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" value="{{ $customer->email }}" readonly><div class="form-text">Contact the store if you need to change your account email.</div></div>
          <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" maxlength="30" required></div>
          <div class="col-md-6"><label class="form-label" for="pincode">PIN code</label><input class="form-control" id="pincode" name="pincode" value="{{ old('pincode', $customer->pincode) }}" maxlength="12"></div>
          <div class="col-md-6"><label class="form-label" for="city">City</label><input class="form-control" id="city" name="city" value="{{ old('city', $customer->city) }}" maxlength="100"></div>
          <div class="col-12"><label class="form-label" for="address">Default address</label><textarea class="form-control" id="address" name="address" rows="3" maxlength="500">{{ old('address', $customer->address) }}</textarea></div>
        </div>
        <button class="btn-cure mt-3" type="submit">Save details</button>
      </form>
    </div>
    <div class="form-card"><h2 class="h5">Change password</h2>
      <form method="POST" action="{{ route('customer.profile.password') }}">@csrf @method('PUT')
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label" for="current_password">Current password</label><input class="form-control" id="current_password" type="password" name="current_password" required autocomplete="current-password"></div>
          <div class="col-md-4"><label class="form-label" for="password">New password</label><input class="form-control" id="password" type="password" name="password" minlength="10" required autocomplete="new-password"></div>
          <div class="col-md-4"><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" minlength="10" required autocomplete="new-password"></div>
        </div>
        <button class="btn-cure-outline mt-3" type="submit">Change password</button>
      </form>
    </div>
  </div>
</div></div></section>
@endsection
