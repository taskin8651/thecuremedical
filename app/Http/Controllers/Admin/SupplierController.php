<?php

namespace App\Http\Controllers\Admin;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class SupplierController extends CrudController
{
    protected string $modelClass = Supplier::class;
    protected string $route = 'admin.suppliers';
    protected string $title = 'Suppliers';
    protected string $singular = 'Supplier';
    protected array $search = ['name', 'contact_person', 'phone', 'email'];
    protected string $orderBy = 'name';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'Supplier', 'key' => 'name', 'link' => true],
            ['label' => 'Contact', 'key' => 'contact_person'],
            ['label' => 'Phone', 'key' => 'phone'],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Active', 'no' => 'Inactive'],
        ];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Activate'], 'deactivate' => ['label' => 'Deactivate']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'name', 'label' => 'Supplier name', 'rules' => 'required|string|max:150'],
            ['name' => 'contact_person', 'rules' => 'nullable|string|max:120'],
            ['name' => 'phone', 'type' => 'tel', 'rules' => 'nullable|string|max:30'],
            ['name' => 'email', 'type' => 'email', 'rules' => 'nullable|email|max:150'],
            ['name' => 'gstin', 'label' => 'GSTIN', 'rules' => 'nullable|string|max:20'],
            ['name' => 'address', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:500'],
            ['name' => 'notes', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:1000'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }

    protected function blockDelete(Model $model): ?string
    {
        return $model->purchases()->exists() ? 'This supplier has purchase records. Mark it inactive instead.' : null;
    }
}
