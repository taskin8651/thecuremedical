@extends('admin.layouts.app')
@section('title', $rx->reference)
@section('content')
<div class="page-head">
  <div><h1>{{ $rx->reference }}</h1><p class="sub"><span class="pill {{ ['verified' => 'pill-success', 'completed' => 'pill-success', 'rejected' => 'pill-danger', 'received' => 'pill-warning'][$rx->status] ?? 'pill-info' }}">{{ \App\Models\PrescriptionEnquiry::STATUSES[$rx->status] ?? $rx->status }}</span> &middot; received {{ $rx->created_at->format('d M Y, h:i A') }}</p></div>
  <div class="page-actions">
    <a class="btn-c-outline" href="{{ route('admin.prescriptions.index') }}"><i class="bi bi-arrow-left"></i> Back</a>
    @can('delete', $rx)<form method="POST" action="{{ route('admin.prescriptions.destroy', $rx->id) }}" data-confirm="Remove this prescription from the register?">@csrf @method('DELETE')<button class="btn-c-danger" type="submit"><i class="bi bi-trash3"></i> Remove</button></form>@endcan
  </div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-c mb-3"><div class="card-body-c">
      <div class="section-title">Details</div>
      <dl class="dl">
        <dt>Name</dt><dd>{{ $rx->name }}</dd><dt>Phone</dt><dd>{{ $rx->phone ?: '-' }}</dd><dt>Email</dt><dd>{{ $rx->email ?: '-' }}</dd>
        <dt>Notes</dt><dd>{!! nl2br(e($rx->notes ?: '-')) !!}</dd>
        <dt>Registered by</dt><dd>{{ $rx->creator?->name ?? '-' }}</dd>
        <dt>Verified by</dt><dd>{{ $rx->verifier ? $rx->verifier->name.' on '.$rx->verified_at?->format('d M Y, h:i A') : 'Not verified' }}</dd>
      </dl>
    </div></div>
    <div class="card-c mb-3"><div class="card-head"><h2>Files</h2></div>
      @forelse ($files as $f)
        <div class="list-row"><div><i class="bi bi-file-earmark-medical me-1"></i><strong>{{ $f->file_name ? 'File '.$loop->iteration : 'File' }}</strong> <span class="small text-muted">{{ strtoupper(pathinfo($f->file_name, PATHINFO_EXTENSION)) }} &middot; {{ number_format($f->size / 1024) }} KB</span></div>
          @can('prescriptions.download')<a class="btn-c-outline btn-sm-c" href="{{ route('admin.prescriptions.download', [$rx->id, $f->id]) }}"><i class="bi bi-download"></i> Download</a>@endcan</div>
      @empty<div class="empty-state"><i class="bi bi-file-earmark"></i>No files attached.</div>@endforelse
    </div>
    <div class="card-c"><div class="card-head"><h2>Access log</h2></div><div class="px-3">
      @forelse ($trail as $a)<div class="activity-item"><span class="ic"><i class="bi bi-activity"></i></span><div><div>{{ $a->description }}</div><div class="small text-muted">{{ $a->user?->name ?? 'System' }} &middot; {{ $a->created_at->format('d M Y, h:i A') }}</div></div></div>@empty<div class="empty-state">No activity.</div>@endforelse
    </div></div>
  </div>
  <div class="col-lg-5"><div class="card-c"><div class="card-body-c">
    <div class="section-title">Review</div>
    @can('update', $rx)
    <form method="POST" action="{{ route('admin.prescriptions.update', $rx->id) }}" data-once>
      @csrf @method('PUT')
      <label class="form-label" for="status">Status</label>
      <select id="status" name="status" class="form-select mb-3">
        <option value="{{ $rx->status }}">{{ \App\Models\PrescriptionEnquiry::STATUSES[$rx->status] }} (current)</option>
        @foreach ($next as $s)@if ($s !== 'verified' || auth()->user()->can('prescriptions.verify'))<option value="{{ $s }}">{{ \App\Models\PrescriptionEnquiry::STATUSES[$s] }}</option>@endif @endforeach
      </select>
      <label class="form-label" for="staff_notes">Staff notes</label>
      <textarea id="staff_notes" name="staff_notes" rows="6" class="form-control" maxlength="2000">{{ old('staff_notes', $rx->staff_notes) }}</textarea>
      <div class="form-text">Required when rejecting. Only an authorised pharmacist can mark a prescription verified.</div>
      <button class="btn-c mt-3" type="submit"><i class="bi bi-check2"></i> Save</button>
    </form>
    @else
      <p class="text-muted mb-0">{!! nl2br(e($rx->staff_notes ?: 'No notes.')) !!}</p>
    @endcan
  </div></div></div>
</div>
@endsection
