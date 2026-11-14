<?php

namespace App\Http\Controllers\Admin;

use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Model;

/** Delivery zones are configuration only until delivery is confirmed by the owner. */
class ShippingZoneController extends CrudController
{
    protected string $modelClass = ShippingZone::class;
    protected string $route = 'admin.shipping';
    protected string $title = 'Shipping zones';
    protected string $singular = 'Shipping zone';
    protected array $search = ['name', 'pincodes'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'Zone', 'key' => 'name', 'link' => true],
            ['label' => 'Charge', 'value' => fn ($r) => money($r->charge)],
            ['label' => 'Free above', 'value' => fn ($r) => money($r->free_above)],
            ['label' => 'Estimated delivery', 'key' => 'estimated_days'],
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
            ['name' => 'name', 'label' => 'Zone name', 'rules' => 'required|string|max:120'],
            ['name' => 'estimated_days', 'label' => 'Estimated delivery', 'placeholder' => '1-2 days', 'rules' => 'nullable|string|max:60'],
            ['name' => 'charge', 'label' => 'Shipping charge', 'type' => 'money', 'default' => 0, 'rules' => 'required|numeric|min:0'],
            ['name' => 'free_above', 'label' => 'Free shipping above', 'type' => 'money', 'rules' => 'nullable|numeric|min:0'],
            ['name' => 'pincodes', 'label' => 'PIN codes', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:5000', 'help' => 'Comma separated.'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'rules' => 'required|integer|min:0'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }
}
