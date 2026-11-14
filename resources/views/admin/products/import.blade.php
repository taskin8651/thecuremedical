@extends('admin.layouts.app')
@section('title', 'Import products')
@section('content')
<div class="page-head">
  <div><h1>Import products</h1><p class="sub">Add or update products from a CSV file.</p></div>
  <div class="page-actions"><a class="btn-c-outline" href="{{ route('admin.products.index') }}"><i class="bi bi-arrow-left"></i> Back to products</a></div>
</div>
<div class="row g-3">
  <div class="col-lg-6"><div class="card-c"><div class="card-body-c">
    <div class="section-title">Upload file</div>
    <div class="notice mb-3"><i class="bi bi-shield-exclamation"></i><div>Imported products are saved as <strong>unverified</strong>. A pharmacist must verify the medical details of each product before they appear on the website as verified.</div></div>
    <form method="POST" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data" data-once>
      @csrf
      <label class="form-label" for="file">CSV file (max 2 MB)</label>
      <input type="file" id="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
      @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
      <div class="d-flex gap-2 mt-3">
        <button class="btn-c" type="submit"><i class="bi bi-upload"></i> Import</button>
        <a class="btn-c-outline" href="{{ route('admin.products.import.template') }}"><i class="bi bi-download"></i> Download template</a>
      </div>
    </form>
  </div></div></div>
  <div class="col-lg-6"><div class="card-c"><div class="card-body-c">
    <div class="section-title">Columns</div>
    <p class="small text-muted">The first row must contain these headings. A row whose SKU already exists updates that product.</p>
    <div class="d-flex flex-wrap gap-1">@foreach ($columns as $c)<span class="pill pill-muted">{{ $c }}</span>@endforeach</div>
  </div></div></div>
</div>
@endsection
