<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends CrudController
{
    protected string $modelClass = Category::class;
    protected string $route = 'admin.categories';
    protected string $title = 'Categories';
    protected string $singular = 'Category';
    protected array $with = ['parent', 'media'];
    protected array $search = ['name', 'slug'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';

    protected function scopeQuery(Builder $query, Request $request): void
    {
        $query->withCount('products');
    }

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->getFirstMediaUrl('image', 'thumb') ?: null],
            ['label' => 'Name', 'key' => 'name', 'link' => true],
            ['label' => 'Parent', 'value' => fn ($r) => $r->parent?->name ?? '-'],
            ['label' => 'Products', 'value' => fn ($r) => $r->products_count],
            ['label' => 'Order', 'key' => 'sort_order'],
            ['label' => 'Featured', 'key' => 'is_featured', 'type' => 'bool'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Active', 'no' => 'Inactive'],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'is_active', 'label' => 'Status', 'options' => ['1' => 'Active', '0' => 'Inactive']]];
    }

    protected function bulkActions(): array
    {
        return [
            'activate' => ['label' => 'Activate'], 'deactivate' => ['label' => 'Deactivate'],
            'feature' => ['label' => 'Feature on homepage'], 'unfeature' => ['label' => 'Remove from homepage'],
            'delete' => ['label' => 'Delete', 'ability' => 'delete'],
        ];
    }

    protected function fields(?Model $model = null): array
    {
        $parents = Category::query()->whereNull('parent_id')
            ->when($model, fn ($q) => $q->whereKeyNot($model->id))->orderBy('name')->pluck('name', 'id')->all();

        return [
            ['name' => 'name', 'rules' => 'required|string|max:120', 'group' => 'Details'],
            ['name' => 'slug', 'rules' => fn ($m) => ['nullable', 'alpha_dash', 'max:160', Rule::unique('categories', 'slug')->ignore($m?->id)], 'help' => 'Leave blank to generate from the name.'],
            ['name' => 'parent_id', 'label' => 'Parent category', 'type' => 'select', 'options' => $parents, 'empty' => 'None (top-level)', 'rules' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id')], 'help' => 'Choose a parent to make this a subcategory.'],
            ['name' => 'icon', 'rules' => 'nullable|regex:/^bi-[a-z0-9-]+$/|max:60', 'placeholder' => 'bi-capsule', 'help' => 'A Bootstrap Icons class, for example bi-capsule.'],
            ['name' => 'description', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:1000'],
            ['name' => 'image', 'label' => 'Category image', 'type' => 'image', 'collection' => 'image', 'group' => 'Display'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'rules' => 'required|integer|min:0|max:9999', 'col' => 3],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
            ['name' => 'is_featured', 'label' => 'Show on homepage', 'type' => 'checkbox', 'col' => 3, 'rules' => 'boolean'],
        ];
    }

    protected function blockDelete(Model $model): ?string
    {
        if ($model->products()->exists() || $model->children()->exists()) {
            return 'This category still has products or subcategories. Move or remove them first.';
        }

        return null;
    }
}
