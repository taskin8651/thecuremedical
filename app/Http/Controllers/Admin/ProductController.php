<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ProductImportService;
use App\Support\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends CrudController
{
    protected string $modelClass = Product::class;
    protected string $route = 'admin.products';
    protected string $title = 'Products';
    protected string $singular = 'Product';
    protected array $with = ['category', 'brand', 'media'];
    protected array $search = ['name', 'sku', 'barcode', 'generic_name', 'brand.name'];
    protected int $perPage = 25;

    protected function scopeQuery(Builder $query, Request $request): void
    {
        match ($request->input('stock')) {
            'low' => $query->lowStock(),
            'out' => $query->outOfStock(),
            default => null,
        };
    }

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->imageUrl('thumb')],
            ['label' => 'Product', 'type' => 'html', 'link' => true, 'value' => fn ($r) => '<strong>'.e($r->name).'</strong><div class="small text-muted">'.e($r->sku ?: 'No SKU').'</div>'],
            ['label' => 'Category', 'value' => fn ($r) => $r->category?->name ?? '-'],
            ['label' => 'Brand', 'value' => fn ($r) => $r->brand?->name ?? '-'],
            ['label' => 'MRP', 'value' => fn ($r) => money($r->mrp)],
            ['label' => 'Stock', 'type' => 'html', 'value' => function ($r) {
                $tone = ['ok' => 'success', 'low' => 'warning', 'out' => 'danger', 'untracked' => 'muted'][$r->stockStatus()];

                return $r->track_inventory ? '<span class="pill pill-'.$tone.'">'.(int) $r->stock_quantity.'</span>' : '<span class="text-muted">Not tracked</span>';
            }],
            ['label' => 'Rx', 'key' => 'requires_prescription', 'type' => 'bool', 'yes' => 'Rx', 'no' => '-'],
            ['label' => 'Verified', 'key' => 'is_verified', 'type' => 'bool', 'yes' => 'Verified', 'no' => 'Unverified', 'tones' => ['warning']],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Active', 'no' => 'Inactive'],
        ];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'category_id', 'label' => 'Category', 'options' => Category::query()->whereNull('parent_id')->orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'brand_id', 'label' => 'Brand', 'options' => Brand::query()->orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive']],
            ['name' => 'is_verified', 'label' => 'Verification', 'options' => ['1' => 'Verified', '0' => 'Unverified']],
            ['name' => 'requires_prescription', 'label' => 'Prescription', 'options' => ['1' => 'Rx required', '0' => 'Not required']],
            ['name' => 'stock', 'label' => 'Stock', 'custom' => true, 'options' => ['low' => 'Low stock', 'out' => 'Out of stock']],
        ];
    }

    protected function bulkActions(): array
    {
        return [
            'activate' => ['label' => 'Activate'], 'deactivate' => ['label' => 'Deactivate'],
            'feature' => ['label' => 'Feature'], 'unfeature' => ['label' => 'Unfeature'],
            'verify' => ['label' => 'Mark as verified (pharmacist)'],
            'delete' => ['label' => 'Delete', 'ability' => 'delete'],
        ];
    }

    protected function rowActions(): array
    {
        return [
            ['label' => 'Duplicate', 'icon' => 'bi-copy', 'method' => 'post', 'url' => fn ($r) => route('admin.products.duplicate', $r->id), 'can' => fn ($r) => auth()->user()->can('products.create')],
            ['label' => 'Verify', 'icon' => 'bi-patch-check', 'method' => 'post', 'url' => fn ($r) => route('admin.products.verify', $r->id), 'can' => fn ($r) => ! $r->is_verified && auth()->user()->can('products.verify')],
        ];
    }

    protected function headerActions(): array
    {
        $u = auth()->user();

        return [
            ['label' => 'Import CSV', 'icon' => 'bi-upload', 'url' => route('admin.products.import'), 'can' => $u->can('products.import')],
            ['label' => 'Export CSV', 'icon' => 'bi-download', 'url' => route('admin.products.export', request()->query()), 'can' => $u->can('products.export')],
        ];
    }

    protected function fields(?Model $model = null): array
    {
        $top = Category::query()->whereNull('parent_id')->orderBy('name')->pluck('name', 'id')->all();
        $subs = Category::query()->whereNotNull('parent_id')->with('parent')->orderBy('name')->get()
            ->mapWithKeys(fn ($c) => [$c->id => ($c->parent?->name ? $c->parent->name.' › ' : '').$c->name])->all();
        $brands = Brand::query()->orderBy('name')->pluck('name', 'id')->all();
        $canVerify = auth()->user()?->can('products.verify');
        $isEdit = $model?->exists;

        $fields = [
            ['name' => 'name', 'label' => 'Product name', 'rules' => 'required|string|max:200', 'group' => 'Basic information', 'col' => 8],
            ['name' => 'sku', 'label' => 'SKU', 'col' => 4, 'rules' => fn ($m) => ['nullable', 'string', 'max:80', Rule::unique('products', 'sku')->ignore($m?->id)]],
            ['name' => 'slug', 'col' => 4, 'rules' => fn ($m) => ['nullable', 'alpha_dash', 'max:220', Rule::unique('products', 'slug')->ignore($m?->id)], 'help' => 'Blank = generated from the name.'],
            ['name' => 'barcode', 'col' => 4, 'rules' => 'nullable|string|max:80'],
            ['name' => 'brand_id', 'label' => 'Brand', 'type' => 'select', 'col' => 4, 'options' => $brands, 'rules' => ['nullable', Rule::exists('brands', 'id')]],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'col' => 6, 'options' => $top, 'rules' => ['nullable', Rule::exists('categories', 'id')]],
            ['name' => 'subcategory_id', 'label' => 'Subcategory', 'type' => 'select', 'col' => 6, 'options' => $subs, 'rules' => ['nullable', Rule::exists('categories', 'id')]],
            ['name' => 'short_description', 'type' => 'textarea', 'rows' => 2, 'col' => 12, 'rules' => 'nullable|string|max:400'],
            ['name' => 'description', 'label' => 'Full description', 'type' => 'textarea', 'rows' => 5, 'col' => 12, 'rules' => 'nullable|string|max:10000'],

            ['name' => '_notice1', 'type' => 'notice', 'group' => 'Medical information', 'col' => 12, 'text' => 'This information is shown to the public only after it has been verified by the shop owner or an authorised pharmacist. Do not copy unverified text. Editing any field in this section clears the verified flag.', 'rules' => 'nullable'],
            ['name' => 'generic_name', 'rules' => 'nullable|string|max:200'],
            ['name' => 'composition', 'type' => 'textarea', 'rows' => 2, 'rules' => 'nullable|string|max:2000'],
            ['name' => 'strength', 'col' => 3, 'rules' => 'nullable|string|max:100'],
            ['name' => 'dosage_form', 'col' => 3, 'rules' => 'nullable|string|max:100'],
            ['name' => 'pack_size', 'col' => 3, 'rules' => 'nullable|string|max:100'],
            ['name' => 'pack_type', 'col' => 3, 'rules' => 'nullable|string|max:100'],
            ['name' => 'manufacturer', 'rules' => 'nullable|string|max:150'],
            ['name' => 'shelf_life', 'rules' => 'nullable|string|max:100'],
            ['name' => 'storage_conditions', 'type' => 'textarea', 'rows' => 2, 'col' => 12, 'rules' => 'nullable|string|max:1000'],
            ['name' => 'side_effects', 'type' => 'textarea', 'rows' => 3, 'col' => 6, 'rules' => 'nullable|string|max:3000'],
            ['name' => 'contraindications', 'type' => 'textarea', 'rows' => 3, 'col' => 6, 'rules' => 'nullable|string|max:3000'],
            ['name' => 'requires_prescription', 'label' => 'Prescription required', 'type' => 'checkbox', 'col' => 12, 'rules' => 'boolean'],

            ['name' => 'hsn_code', 'label' => 'HSN code', 'group' => 'Pricing & tax', 'col' => 4, 'rules' => 'nullable|string|max:20'],
            ['name' => 'gst_rate', 'label' => 'GST rate (%)', 'type' => 'money', 'col' => 4, 'rules' => 'nullable|numeric|min:0|max:100'],
            ['name' => 'mrp', 'label' => 'MRP', 'type' => 'money', 'col' => 4, 'rules' => 'nullable|numeric|min:0|max:10000000'],
            ['name' => 'sale_price', 'type' => 'money', 'col' => 4, 'rules' => 'nullable|numeric|min:0|max:10000000'],
            ['name' => 'ptr', 'label' => 'PTR', 'type' => 'money', 'col' => 4, 'rules' => 'nullable|numeric|min:0|max:10000000'],
            ['name' => 'pts', 'label' => 'PTS', 'type' => 'money', 'col' => 4, 'rules' => 'nullable|numeric|min:0|max:10000000'],

            ['name' => 'track_inventory', 'label' => 'Track inventory for this product', 'type' => 'checkbox', 'default' => true, 'group' => 'Stock', 'col' => 12, 'rules' => 'boolean'],
            ['name' => 'min_quantity', 'label' => 'Minimum quantity (low-stock alert)', 'type' => 'number', 'default' => 0, 'col' => 4, 'rules' => 'required|integer|min:0|max:1000000'],
            ['name' => 'max_quantity', 'label' => 'Maximum quantity', 'type' => 'number', 'col' => 4, 'rules' => 'nullable|integer|min:0|max:10000000'],
        ];

        if ($isEdit) {
            $fields[] = ['name' => 'stock_quantity', 'label' => 'Current stock', 'type' => 'number', 'col' => 4, 'readonly' => true, 'help' => 'Change stock from Inventory > Stock movement.', 'rules' => 'nullable'];
        } else {
            $fields[] = ['name' => 'opening_stock', 'type' => 'number', 'col' => 4, 'virtual' => true, 'default' => 0, 'rules' => 'nullable|integer|min:0|max:10000000', 'help' => 'Recorded as an "opening" stock movement.'];
        }

        $fields = array_merge($fields, [
            ['name' => 'images', 'label' => 'Product images', 'type' => 'images', 'collection' => 'images', 'primary' => true, 'group' => 'Media', 'col' => 12, 'help' => 'JPG, PNG or WebP, up to 4 MB each. Choose the primary image after uploading.'],
            ['name' => 'documents', 'label' => 'Documents (leaflet, certificates)', 'type' => 'files', 'collection' => 'documents', 'col' => 12],
            ['name' => 'attributes_text', 'label' => 'Extra attributes', 'type' => 'textarea', 'virtual' => true, 'rows' => 3, 'col' => 12, 'group' => 'Additional', 'rules' => 'nullable|string|max:3000',
                'help' => 'One per line as "Name: Value".', 'value' => $isEdit ? $model->attributesList->map(fn ($a) => $a->name.': '.$a->value)->implode("\n") : null],
            ['name' => 'is_featured', 'label' => 'Featured on homepage', 'type' => 'checkbox', 'col' => 4, 'rules' => 'boolean', 'group' => 'Visibility'],
            ['name' => 'is_active', 'label' => 'Active (visible on website)', 'type' => 'checkbox', 'default' => true, 'col' => 4, 'rules' => 'boolean'],
        ]);

        if ($canVerify) {
            $fields[] = ['name' => 'mark_verified', 'label' => 'Medical information verified by pharmacist/owner', 'type' => 'checkbox', 'virtual' => true, 'col' => 4, 'rules' => 'boolean', 'value' => (bool) $model?->is_verified];
        }

        return $fields;
    }

    protected function beforeSave(array &$data, Model $model, Request $request): void
    {
        if (isset($data['mrp'], $data['sale_price']) && $data['mrp'] !== '' && $data['sale_price'] !== '' && (float) $data['sale_price'] > (float) $data['mrp']) {
            throw ValidationException::withMessages(['sale_price' => 'Sale price cannot be higher than MRP.']);
        }

        if ($model->exists) {
            foreach (Product::MEDICAL_FIELDS as $key) {
                if (array_key_exists($key, $data) && (string) ($model->{$key} ?? '') !== (string) ($data[$key] ?? '')) {
                    $data['is_verified'] = false;
                    $data['verified_by'] = null;
                    $data['verified_at'] = null;
                    break;
                }
            }
        }

        if ($request->user()->can('products.verify') && $request->boolean('mark_verified')) {
            $data['is_verified'] = true;
            $data['verified_by'] = $request->user()->id;
            $data['verified_at'] = $model->verified_at && $model->is_verified ? $model->verified_at : now();
        } elseif ($request->user()->can('products.verify')) {
            $data['is_verified'] = false;
            $data['verified_by'] = null;
            $data['verified_at'] = null;
        }
    }

    protected function afterSave(Model $model, Request $request, bool $isNew): void
    {
        // Extra attributes ("Name: Value" per line)
        $lines = preg_split('/\r\n|\r|\n/', (string) $request->input('attributes_text'));
        $model->attributesList()->delete();
        $order = 0;
        $seen = [];
        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode(':', $line, 2));
            if ($name === '' || $value === '' || isset($seen[mb_strtolower($name)])) {
                continue;
            }
            $seen[mb_strtolower($name)] = true;
            $model->attributesList()->create(['name' => mb_substr($name, 0, 100), 'value' => mb_substr($value, 0, 400), 'sort_order' => $order++]);
        }

        $opening = (int) $request->input('opening_stock', 0);
        if ($isNew && $opening > 0 && $model->track_inventory) {
            app(InventoryService::class)->move($model, 'in', $opening, ['reason' => 'opening', 'notes' => 'Opening stock entered when the product was created']);
        }
    }

    protected function applyBulk(string $action, Model $record): bool
    {
        if ($action === 'verify') {
            if (! auth()->user()->can('products.verify')) {
                return false;
            }

            return $record->update(['is_verified' => true, 'verified_by' => auth()->id(), 'verified_at' => now()]);
        }

        return parent::applyBulk($action, $record);
    }

    protected function blockDelete(Model $model): ?string
    {
        return $model->stock_quantity > 0 ? 'This product still has stock. Adjust the stock to zero first, or mark it inactive.' : null;
    }

    // ------------------------------------------------------------------ extra actions

    public function duplicate($record)
    {
        $this->authorize('create', Product::class);
        $source = $this->findRecord($record);

        $copy = DB::transaction(function () use ($source) {
            $copy = $source->replicate(['sku', 'barcode', 'slug', 'stock_quantity', 'views', 'verified_by', 'verified_at']);
            $copy->name = $source->name.' (copy)';
            $copy->slug = null;
            $copy->is_active = false;
            $copy->is_verified = false;
            $copy->stock_quantity = 0;
            $copy->views = 0;
            $copy->save();
            foreach ($source->attributesList as $a) {
                $copy->attributesList()->create($a->only(['name', 'value', 'sort_order']));
            }

            return $copy;
        });

        return redirect()->route('admin.products.edit', $copy->id)->with('success', 'Product duplicated. It is inactive and unverified until you review it.');
    }

    public function verify($record)
    {
        abort_unless(auth()->user()->can('products.verify'), 403);
        $product = $this->findRecord($record);
        $product->update(['is_verified' => true, 'verified_by' => auth()->id(), 'verified_at' => now()]);
        ActivityLog::record('verified', $product);

        return back()->with('success', 'Product marked as verified.');
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('products.export'), 403);
        ActivityLog::record('exported', null, 'Products exported to CSV');

        $headings = array_merge(ProductImportService::COLUMNS, ['stock_quantity', 'is_verified']);
        $rows = (function () use ($request, $headings) {
            foreach ($this->queryFromRequest($request)->with(['category', 'subcategory', 'brand'])->cursor() as $p) {
                $out = [];
                foreach ($headings as $h) {
                    $out[] = match ($h) {
                        'category' => $p->category?->name, 'subcategory' => $p->subcategory?->name, 'brand' => $p->brand?->name,
                        default => $p->{$h},
                    };
                }
                yield $out;
            }
        })();

        return CsvExporter::download('products-'.now()->format('Ymd-His').'.csv', $headings, $rows);
    }

    public function importForm()
    {
        abort_unless(auth()->user()->can('products.import'), 403);

        return view('admin.products.import', ['columns' => ProductImportService::COLUMNS]);
    }

    public function importTemplate()
    {
        abort_unless(auth()->user()->can('products.import'), 403);

        return CsvExporter::download('product-import-template.csv', ProductImportService::COLUMNS, []);
    }

    public function import(Request $request, ProductImportService $service)
    {
        abort_unless($request->user()->can('products.import'), 403);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel', 'max:2048']]);

        $result = $service->import($request->file('file'));
        ActivityLog::record('imported', null, "Products imported: {$result['created']} created, {$result['updated']} updated");

        return redirect()->route('admin.products.index')
            ->with('success', "Import finished: {$result['created']} created, {$result['updated']} updated. All imported products are unverified until a pharmacist verifies them.");
    }
}
