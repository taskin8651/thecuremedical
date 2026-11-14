@extends('admin.layouts.app')
@section('title', 'Register prescription')
@section('content')
<div class="page-head">
  <div><h1>Register prescription</h1><p class="sub">Staff only. Files are stored privately.</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.prescriptions.index') }}"><i class="bi bi-arrow-left"></i> Back</a></div>
</div>
<div class="notice mb-3"><i class="bi bi-shield-exclamation"></i><div>Registering a prescription does not approve it. A pharmacist must review and verify it. Nothing is dispensed or advised automatically.</div></div>
<form method="POST" action="{{ route('admin.prescriptions.store') }}" enctype="multipart/form-data" data-once novalidate>
  @csrf
  <div class="card-c"><div class="card-body-c"><div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="name">Patient / customer name <span class="text-danger">*</span></label><input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" maxlength="150">@error('name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
    <div class="col-md-3"><label class="form-label" for="phone">Phone</label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="form-control" maxlength="30"></div>
    <div class="col-md-3"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" maxlength="150">@error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
    <div class="col-12"><label class="form-label" for="attachments">Prescription files (PDF, JPG, PNG, up to 5, 8 MB each)</label><input id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="form-control @if ($errors->has('attachments') || $errors->has('attachments.*')) is-invalid @endif">@if ($errors->has('attachments') || $errors->has('attachments.*'))<div class="text-danger small mt-1">{{ $errors->first('attachments') ?: $errors->first('attachments.*') }}</div>@endif</div>
    <div class="col-12"><label class="form-label" for="notes">Notes</label><textarea id="notes" name="notes" rows="3" class="form-control" maxlength="1000">{{ old('notes') }}</textarea></div>
  </div></div></div>
  <div class="d-flex gap-2 py-3"><button class="btn-c" type="submit"><i class="bi bi-check2"></i> Register</button><a class="btn-c-outline" href="{{ route('admin.prescriptions.index') }}">Cancel</a></div>
</form>
@endsection
