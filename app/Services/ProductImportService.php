<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * CSV import. All-or-nothing: any invalid row rolls the whole file back.
 * Imported rows are ALWAYS saved as unverified. A pharmacist/owner must verify them.
 * Stock is not imported; use Inventory > Stock movement or Purchases.
 */
class ProductImportService
{
    public const MAX_ROWS = 2000;

    public const COLUMNS = [
        'sku', 'name', 'category', 'subcategory', 'brand', 'barcode', 'short_description', 'description', 'generic_name',
        'composition', 'strength', 'dosage_form', 'pack_size', 'pack_type', 'manufacturer', 'hsn_code', 'gst_rate', 'mrp',
        'sale_price', 'ptr', 'pts', 'min_quantity', 'max_quantity', 'requires_prescription', 'storage_conditions',
        'shelf_life', 'side_effects', 'contraindications', 'is_featured', 'is_active',
    ];

    /** @return array{created:int,updated:int} */
    public function import(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'The file could not be read.']);
        }

        $header = fgetcsv($handle);
        if (! $header) {
            throw ValidationException::withMessages(['file' => 'The file is empty.']);
        }
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);
        foreach (['sku', 'name'] as $required) {
            if (! in_array($required, $header, true)) {
                throw ValidationException::withMessages(['file' => "Missing required column \"{$required}\"."]);
            }
        }

        $errors = [];
        $created = $updated = 0;
        $line = 1;

        DB::beginTransaction();
        try {
            while (($cells = fgetcsv($handle)) !== false) {
                $line++;
                if ($line - 1 > self::MAX_ROWS) {
                    $errors[] = 'Too many rows. The limit is '.self::MAX_ROWS.' per file.';
                    break;
                }
                if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                    continue;
                }
                $row = [];
                foreach ($header as $i => $key) {
                    $row[$key] = isset($cells[$i]) ? trim((string) $cells[$i]) : null;
                }
                $result = $this->importRow($row);
                if (is_string($result)) {
                    $errors[] = "Row {$line}: {$result}";
                } elseif ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
                if (count($errors) >= 25) {
                    $errors[] = 'Stopped after 25 errors.';
                    break;
                }
            }
            fclose($handle);

            if ($errors) {
                DB::rollBack();
                throw ValidationException::withMessages(['file' => $errors]);
            }
            DB::commit();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    /** @return string|string  'created'|'updated' or an error message */
    private function importRow(array $row): string
    {
        $v = Validator::make($row, [
            'sku' => 'required|string|max:80',
            'name' => 'required|string|max:200',
            'barcode' => 'nullable|string|max:80',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'mrp' => 'nullable|numeric|min:0', 'sale_price' => 'nullable|numeric|min:0',
            'ptr' => 'nullable|numeric|min:0', 'pts' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|integer|min:0', 'max_quantity' => 'nullable|integer|min:0',
        ]);
        if ($v->fails()) {
            return $v->errors()->first();
        }
        if ($row['mrp'] !== null && $row['mrp'] !== '' && $row['sale_price'] !== null && $row['sale_price'] !== '' && (float) $row['sale_price'] > (float) $row['mrp']) {
            return 'sale_price cannot be higher than mrp.';
        }

        $category = $this->category($row['category'] ?? null, false);
        if ($category === false) {
            return 'unknown category "'.$row['category'].'". Create it first.';
        }
        $sub = $this->category($row['subcategory'] ?? null, true);
        if ($sub === false) {
            return 'unknown subcategory "'.$row['subcategory'].'". Create it first.';
        }

        $data = [
            'name' => $row['name'],
            'category_id' => $category?->id,
            'subcategory_id' => $sub?->id,
            'is_verified' => false, 'verified_by' => null, 'verified_at' => null,
        ];
        if (! empty($row['brand'])) {
            $data['brand_id'] = Brand::query()->firstOrCreate(['name' => $row['brand']])->id;
        }
        foreach (['barcode', 'short_description', 'description', 'generic_name', 'composition', 'strength', 'dosage_form', 'pack_size', 'pack_type',
            'manufacturer', 'hsn_code', 'storage_conditions', 'shelf_life', 'side_effects', 'contraindications'] as $k) {
            if (array_key_exists($k, $row)) {
                $data[$k] = $row[$k] !== '' ? $row[$k] : null;
            }
        }
        foreach (['gst_rate', 'mrp', 'sale_price', 'ptr', 'pts', 'min_quantity', 'max_quantity'] as $k) {
            if (array_key_exists($k, $row)) {
                $data[$k] = $row[$k] !== '' ? $row[$k] : ($k === 'min_quantity' ? 0 : null);
            }
        }
        foreach (['requires_prescription', 'is_featured', 'is_active'] as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== '') {
                $data[$k] = in_array(strtolower($row[$k]), ['1', 'yes', 'y', 'true'], true);
            }
        }

        $product = Product::withTrashed()->where('sku', $row['sku'])->first();
        if ($product?->trashed()) {
            return 'SKU "'.$row['sku'].'" belongs to a deleted product.';
        }
        if ($product) {
            $product->forceFill($data)->save();

            return 'updated';
        }
        // New products are inactive unless the file explicitly says otherwise.
        $data += ['is_active' => false, 'sku' => $row['sku']];
        $data['sku'] = $row['sku'];
        (new Product)->forceFill($data)->save();

        return 'created';
    }

    /** null = blank, false = not found */
    private function category(?string $name, bool $child): Category|null|false
    {
        if ($name === null || $name === '') {
            return null;
        }

        return Category::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($child, fn ($q) => $q->whereNotNull('parent_id'), fn ($q) => $q->whereNull('parent_id'))->first() ?? false;
    }
}
