@extends('admin.layouts.app')
@section('title', 'Enquiry from '.$enquiry->name)
@section('content')
@php $tone = ['new' => 'pill-warning', 'pending' => 'pill-warning', 'contacted' => 'pill-info', 'resolved' => 'pill-success', 'closed' => 'pill-muted']; @endphp
<div class="page-head">
  <div><h1>{{ $enquiry->name }}</h1><p class="sub">Received {{ $enquiry->created_at->format('d M Y, h:i A') }} &middot; <span class="pill {{ $tone[$enquiry->status] ?? 'pill-muted' }}">{{ \App\Models\Enquiry::STATUSES[$enquiry->status] ?? $enquiry->status }}</span></p></div>
  <div class="page-actions">
    <a class="btn-c-outline" href="{{ route('admin.enquiries.index') }}"><i class="bi bi-arrow-left"></i> Back</a>
    @if ($enquiry->telLink())<a class="btn-c-outline" href="{{ $enquiry->telLink() }}"><i class="bi bi-telephone"></i> Call</a>@endif
    @if ($enquiry->whatsappLink())<a class="btn-c-outline" href="{{ $enquiry->whatsappLink() }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>@endif
    @can('update', $enquiry)<a class="btn-c-outline" href="{{ route('admin.enquiries.edit', $enquiry->id) }}"><i class="bi bi-pencil"></i> Edit</a>@endcan
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="card-c h-100"><div class="card-body-c">
    <div class="section-title">Details</div>
    <dl class="dl">
      <dt>Type</dt><dd>{{ \App\Models\Enquiry::TYPES[$enquiry->type] ?? $enquiry->type }}</dd>
      <dt>Product / topic</dt><dd>{{ $enquiry->product_name ?: '-' }}</dd>
      <dt>Phone</dt><dd>{{ $enquiry->phone ?: '-' }}</dd>
      <dt>Email</dt><dd>@if ($enquiry->email)<a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a>@else - @endif</dd>
      <dt>Customer</dt><dd>@if ($enquiry->customer)@can('customers.view')<a href="{{ route('admin.customers.show', $enquiry->customer_id) }}">{{ $enquiry->customer->name }}</a>@endcan @else - @endif</dd>
      <dt>Source</dt><dd>{{ ucfirst($enquiry->source) }}</dd>
      <dt>Handled by</dt><dd>{{ $enquiry->handler?->name ?? '-' }}</dd>
      <dt>First contacted</dt><dd>{{ $enquiry->contacted_at?->format('d M Y, h:i A') ?? '-' }}</dd>
      <dt>Message</dt><dd>{!! nl2br(e($enquiry->message ?: '-')) !!}</dd>
    </dl>
  </div></div></div>
  <div class="col-lg-5"><div class="card-c h-100"><div class="card-body-c">
    <div class="section-title">Follow-up</div>
    @can('update', $enquiry)
    <form method="POST" action="{{ route('admin.enquiries.status', $enquiry->id) }}" data-once>
      @csrf
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select mb-3">
        @foreach (\App\Models\Enquiry::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $enquiry->status) === $k)>{{ $l }}</option>@endforeach
      </select>
      <label class="form-label" for="admin_notes">Staff notes</label>
      <textarea id="admin_notes" name="admin_notes" rows="6" class="form-control @error('admin_notes') is-invalid @enderror" maxlength="2000">{{ old('admin_notes', $enquiry->admin_notes) }}</textarea>
      @error('admin_notes')<div class="text-danger small">{{ $message }}</div>@enderror
      <div class="form-text">Internal only. Do not record medical advice or prescription details.</div>
      <button class="btn-c mt-3" type="submit"><i class="bi bi-check2"></i> Save</button>
    </form>
    @else
      <p class="text-muted mb-0">{!! nl2br(e($enquiry->admin_notes ?: 'No notes.')) !!}</p>
    @endcan
  </div></div></div>
</div>
@endsection
