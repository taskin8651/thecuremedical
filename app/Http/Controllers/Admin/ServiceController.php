<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use Illuminate\Database\Eloquent\Model;

class ServiceController extends CrudController
{
    protected string $modelClass = Service::class;
    protected string $route = 'admin.services';
    protected string $title = 'Services';
    protected string $singular = 'Service';
    protected array $with = ['media'];
    protected array $search = ['name', 'description'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->getFirstMediaUrl('image', 'thumb') ?: null],
            ['label' => 'Service', 'key' => 'name', 'link' => true],
            ['label' => 'Icon', 'type' => 'html', 'value' => fn ($r) => $r->icon ? '<i class="bi '.e($r->icon).'"></i> <span class="small text-muted">'.e($r->icon).'</span>' : '-'],
            ['label' => 'Order', 'key' => 'sort_order'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Active', 'no' => 'Inactive'],
        ];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Activate'], 'deactivate' => ['label' => 'Deactivate'], 'delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'name', 'label' => 'Service name', 'rules' => 'required|string|max:150'],
            ['name' => 'icon', 'rules' => 'nullable|regex:/^bi-[a-z0-9-]+$/|max:60', 'placeholder' => 'bi-heart-pulse', 'help' => 'A Bootstrap Icons class, for example bi-heart-pulse.'],
            ['name' => 'description', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:1000'],
            ['name' => 'image', 'type' => 'image', 'collection' => 'image'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'col' => 3, 'rules' => 'required|integer|min:0|max:9999'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
        ];
    }
}
