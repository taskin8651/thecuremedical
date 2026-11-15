<?php

namespace App\Http\Controllers\Admin;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BrandController extends CrudController
{
    protected string $modelClass = Brand::class;
    protected string $route = 'admin.brands';
    protected string $title = 'Brands';
    protected string $singular = 'Brand';
    protected array $with = ['media'];
    protected array $search = ['name', 'manufacturer'];
    protected string $orderBy = 'name';
    protected string $orderDir = 'asc';

    protected function scopeQuery(Builder $query, Request $request): void
    {
        $query->withCount('products');
    }

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->getFirstMediaUrl('logo', 'thumb') ?: null],
            ['label' => 'Brand', 'key' => 'name', 'link' => true],
            ['label' => 'Manufacturer', 'key' => 'manufacturer'],
            ['label' => 'Products', 'type' => 'html', 'value' => fn ($r) => e($r->products_count).' <a class="small ms-1" href="'.e(route('admin.products.index', ['brand_id' => $r->id])).'">view</a>'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Active', 'no' => 'Inactive'],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive']]];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Activate'], 'deactivate' => ['label' => 'Deactivate'], 'delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'name', 'rules' => 'required|string|max:120'],
            ['name' => 'slug', 'rules' => fn ($m) => ['nullable', 'alpha_dash', 'max:160', Rule::unique('brands', 'slug')->ignore($m ? $m->id : null)], 'help' => 'Leave blank to generate from the name.'],
            ['name' => 'manufacturer', 'label' => 'Manufacturer name', 'rules' => 'nullable|string|max:150'],
            ['name' => 'logo', 'label' => 'Upload a new logo', 'type' => 'image', 'collection' => 'logo', 'help' => 'Choose an image from your device. This replaces the current logo.'],
            [
                'name' => 'logo_media_id',
                'label' => 'Or choose an existing logo from Media Library',
                'type' => 'media_image',
                'virtual' => true,
                'options' => Media::query()->where('model_type', (new Brand)->getMorphClass())
                    ->where('collection_name', 'logo')->latest('id')->get()
                    ->map(fn (Media $media) => [
                        'id' => $media->id,
                        'label' => trim(($media->model?->name ?? 'Brand').' / '.$media->name),
                        'url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                    ])->all(),
                'rules' => [
                    'nullable',
                    'integer',
                    Rule::exists('media', 'id')->where('model_type', (new Brand)->getMorphClass())->where('collection_name', 'logo'),
                ],
                'help' => 'The selected image is copied into this Brand logo collection; the original remains in Media Library.',
            ],
            ['name' => 'description', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:1000'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }

    protected function syncMedia(Model $model, array $fields, Request $request): void
    {
        parent::syncMedia($model, $fields, $request);

        // A newly uploaded file takes precedence if both options were submitted.
        if ($request->file('logo') || ! $request->filled('logo_media_id')) {
            return;
        }

        $source = Media::query()
            ->where('model_type', $model->getMorphClass())
            ->where('collection_name', 'logo')
            ->findOrFail($request->integer('logo_media_id'));

        $model->addMediaFromDisk($source->getPathRelativeToRoot(), $source->disk)
            ->usingName($source->name)
            ->usingFileName($source->file_name)
            ->toMediaCollection('logo');
    }

    protected function blockDelete(Model $model): ?string
    {
        return $model->products()->exists() ? 'This brand still has products. Reassign them first.' : null;
    }
}
