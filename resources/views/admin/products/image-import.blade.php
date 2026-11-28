@extends('admin.layouts.app')
@section('title', 'Bulk Image Upload')
@section('content')
@php
    $phase = $state['phase'] ?? null;
    $statuses = [
        'checking' => ['Checking', 'muted'],
        'ready' => ['Ready to upload', 'success'],
        'unmatched' => ['Unmatched SKU', 'warning'],
        'duplicate' => ['Duplicate SKU', 'warning'],
        'existing' => ['Product already has an image', 'warning'],
        'changed' => ['Product was removed or its SKU changed', 'warning'],
        'unsafe' => ['Unsafe ZIP entry', 'danger'],
        'invalid' => ['Invalid image', 'danger'],
        'oversized' => ['Over 4 MB', 'danger'],
        'dimensions' => ['Over 24 megapixels', 'danger'],
        'uploaded' => ['Uploaded', 'success'],
        'failed' => ['Could not save image', 'danger'],
    ];
@endphp
<div class="page-head">
    <div><h1>Bulk Image Upload</h1></div>
    <div class="page-actions">
        @if ($upload)
            <a class="btn-c-outline" href="{{ route('admin.products.image-import.create') }}">Start another upload</a>
        @endif
        <a class="btn-c-outline" href="{{ route('admin.products.index') }}"><i class="bi bi-arrow-left"></i> Back to products</a>
    </div>
</div>

@if (! $upload)
    <div class="card-c"><div class="card-body-c">
        <p>Use one JPG, PNG or WebP per product. Name each photo with the exact SKU, including letter case, such as <code>SMASK-003.webp</code>.</p>
        <p class="small text-muted">Up to 4 MB and 24 megapixels per image. ZIP limit is 128 MB and 1,000 entries. Split larger sets into multiple ZIPs.</p>
        <form method="POST" action="{{ route('admin.products.image-import.store') }}" enctype="multipart/form-data" data-once>
            @csrf
            <label class="form-label" for="archive">ZIP file</label>
            <input type="file" id="archive" name="archive" accept=".zip,application/zip" class="form-control @error('archive') is-invalid @enderror" required>
            @error('archive')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            <button class="btn-c mt-3" type="submit"><i class="bi bi-upload"></i> Preview images</button>
        </form>
    </div></div>
@else
    <div class="card-c mb-3"><div class="card-body-c">
        @error('archive')<p class="text-danger" role="alert">{{ $message }}</p>@enderror
        @if ($phase === 'complete')
            <h2 class="section-title">Import complete</h2>
            <p class="mb-0"><strong>{{ $summary['uploaded'] }} uploaded</strong> · {{ $summary['skipped'] }} skipped · {{ $summary['failed'] }} failed</p>
        @elseif ($phase === 'preview')
            <h2 class="section-title">Preview</h2>
            <p>{{ $ready }} ready to upload · {{ $summary['skipped'] }} skipped · {{ $summary['failed'] }} failed</p>
            <form method="POST" action="{{ route('admin.products.image-import.advance', $upload) }}" data-once>
                @csrf
                <input type="hidden" name="action" value="confirm">
                <button class="btn-c" type="submit">{{ $ready ? 'Confirm upload' : 'Finish review' }}</button>
            </form>
        @else
            <h2 class="section-title">{{ $phase === 'checking' ? 'Validating ZIP images' : 'Uploading images' }}</h2>
            <p aria-live="polite" id="image-import-progress">{{ $state['cursor'] }} of {{ count($state['rows']) }} files processed</p>
            <p class="small text-muted">Reopen this page within 24 hours to resume.</p>
            <form id="image-import-batch" method="POST" action="{{ route('admin.products.image-import.advance', $upload) }}" data-phase="{{ $phase }}">
                @csrf
                <input type="hidden" name="action" value="{{ $phase === 'checking' ? 'check' : 'import' }}">
                <button class="btn-c-outline" type="submit">Continue</button>
            </form>
        @endif
    </div></div>
    <div class="card-c"><div class="table-responsive">
        <table class="table table-c mb-0">
            <thead><tr><th scope="col">File</th><th scope="col">SKU</th><th scope="col">Matched product</th><th scope="col">Status</th></tr></thead>
            <tbody>
            @foreach ($state['rows'] as $row)
                @php [$label, $tone] = $statuses[$row['status']]; @endphp
                <tr>
                    <td>{{ $row['file'] }}</td>
                    <td>{{ $row['sku'] ?? '—' }}</td>
                    <td>{{ $row['product'] ?? '—' }}</td>
                    <td>
                        <span class="pill pill-{{ $tone }}">{{ $label }}</span>
                        @if ($row['duplicate'] && $row['status'] !== 'duplicate')
                            <span class="pill pill-warning">Duplicate SKU</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
@endif
@endsection

@if (in_array($phase, ['checking', 'importing'], true))
    @push('scripts')
        <script>
            (() => {
                const form = document.getElementById('image-import-batch');
                const progress = document.getElementById('image-import-progress');
                const button = form.querySelector('button');

                async function processBatches(event) {
                    event?.preventDefault();
                    button.disabled = true;
                    try {
                        const data = new FormData(form);
                        while (true) {
                            const response = await fetch(form.action, {
                                method: 'POST',
                                headers: { Accept: 'application/json' },
                                body: data,
                            });
                            if (!response.ok || response.redirected) {
                                throw new Error('Batch interrupted');
                            }
                            const result = await response.json();
                            if (result.phase !== form.dataset.phase) {
                                window.location.reload();
                                return;
                            }
                            progress.textContent = result.processed + ' of ' + result.total + ' files processed';
                        }
                    } catch {
                        progress.textContent = 'Processing paused. Select Continue to retry, or reload the page if your session expired.';
                        button.disabled = false;
                    }
                }

                form.addEventListener('submit', processBatches);
                processBatches();
            })();
        </script>
    @endpush
@endif
