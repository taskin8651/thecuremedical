<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductImageImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductImageImportController extends Controller
{
    public const PERMISSIONS = ['products.view', 'products.edit', 'products.import'];

    public function create()
    {
        return $this->page();
    }

    public function store(Request $request, ProductImageImportService $service)
    {
        $request->validate(['archive' => ['required', 'file', 'mimes:zip', 'max:'.ProductImageImportService::MAX_ZIP_KB]]);
        $upload = $service->stage($request->file('archive'), $request->user()->id);

        return redirect()->route('admin.products.image-import.show', $upload);
    }

    public function show(Request $request, ProductImageImportService $service, string $upload)
    {
        return $this->page($upload, $service->get($upload, $request->user()->id));
    }

    public function advance(Request $request, ProductImageImportService $service, string $upload)
    {
        $data = $request->validate(['action' => ['required', Rule::in(['check', 'confirm', 'import'])]]);
        $state = $service->advance($upload, $request->user()->id, $data['action']);
        if ($request->expectsJson()) {
            return response()->json([
                'phase' => $state['phase'],
                'processed' => $state['cursor'],
                'total' => count($state['rows']),
            ]);
        }

        return redirect()->route('admin.products.image-import.show', $upload);
    }

    private function page(?string $upload = null, ?array $state = null)
    {
        $counts = array_count_values(array_column($state['rows'] ?? [], 'status'));

        return view('admin.products.image-import', [
            'upload' => $upload,
            'state' => $state,
            'ready' => $counts['ready'] ?? 0,
            'summary' => [
                'uploaded' => $counts['uploaded'] ?? 0,
                'failed' => ($counts['failed'] ?? 0) + ($counts['invalid'] ?? 0) + ($counts['oversized'] ?? 0)
                    + ($counts['dimensions'] ?? 0) + ($counts['unsafe'] ?? 0),
                'skipped' => ($counts['unmatched'] ?? 0) + ($counts['duplicate'] ?? 0)
                    + ($counts['existing'] ?? 0) + ($counts['changed'] ?? 0),
            ],
        ]);
    }
}
