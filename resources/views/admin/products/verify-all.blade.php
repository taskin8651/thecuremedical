@extends('admin.layouts.app')
@section('title', 'Verify All Products')
@section('content')
<div class="page-head">
    <h1>Verify All Products</h1>
    <div class="page-actions">
        <a class="btn-c-outline" href="{{ route('admin.products.index', $count ? ['is_verified' => 0] : []) }}">
            <i class="bi bi-arrow-left"></i> {{ $count ? 'View unverified products' : 'Back to products' }}
        </a>
    </div>
</div>

<div class="card-c"><div class="card-body-c">
    @if ($count)
        <p><strong>{{ number_format($count) }} unverified {{ Str::plural('product', $count) }}</strong> across the catalogue, including inactive products.</p>
        <p>Verified medical information can appear on the website. Eligible products can become orderable when online ordering is enabled.</p>
        <form method="POST" action="{{ route('admin.products.verify-all.store', [], false) }}" data-once>
            @csrf
            <div class="form-check mb-3">
                <input class="form-check-input @error('confirmed') is-invalid @enderror" type="checkbox" id="confirmed" name="confirmed" value="1" required>
                <label class="form-check-label" for="confirmed">I have reviewed the medical information for all unverified products.</label>
                @error('confirmed')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn-c" type="submit"><i class="bi bi-patch-check"></i> Confirm and verify all</button>
        </form>
    @else
        <p class="mb-0">All products are already verified.</p>
    @endif
</div></div>
@endsection
