<?php

namespace App\Http\Controllers\Admin;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** Customer contact details are personal data. Access is limited by the customers.* permissions. */
class CustomerController extends CrudController
{
    protected string $modelClass = Customer::class;
    protected string $route = 'admin.customers';
    protected string $title = 'Customers';
    protected string $singular = 'Customer';
    protected array $search = ['name', 'phone', 'email', 'city'];

    protected function columns(): array
    {
        return [
            ['label' => 'Name', 'key' => 'name', 'link' => 'show'],
            ['label' => 'Phone', 'key' => 'phone'],
            ['label' => 'Email', 'key' => 'email'],
            ['label' => 'City', 'key' => 'city'],
            ['label' => 'Status', 'key' => 'status', 'type' => 'bool', 'value' => fn ($r) => $r->status === 'active', 'yes' => 'Active', 'no' => 'Blocked', 'tones' => ['danger']],
            ['label' => 'Joined', 'key' => 'created_at', 'type' => 'date'],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'status', 'label' => 'Status', 'options' => ['active' => 'Active', 'blocked' => 'Blocked']]];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Mark active'], 'block' => ['label' => 'Block']];
    }

    protected function applyBulk(string $action, Model $record): bool
    {
        return match ($action) {
            'activate' => $record->update(['status' => 'active']),
            'block' => $record->update(['status' => 'blocked']),
            default => false,
        };
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'name', 'rules' => 'required|string|max:150'],
            ['name' => 'phone', 'type' => 'tel', 'rules' => 'nullable|string|max:30'],
            ['name' => 'email', 'type' => 'email', 'rules' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($model?->id)]],
            ['name' => 'status', 'type' => 'select', 'options' => ['active' => 'Active', 'blocked' => 'Blocked'], 'default' => 'active', 'rules' => 'required|in:active,blocked'],
            ['name' => 'city', 'rules' => 'nullable|string|max:100'],
            ['name' => 'pincode', 'rules' => 'nullable|string|max:12'],
            ['name' => 'address', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:500'],
            ['name' => 'notes', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:1000', 'help' => 'Internal notes. Do not record medical conditions or prescriptions here.'],
        ];
    }

    protected function blockDelete(Model $model): ?string
    {
        return $model->orders()->exists() ? 'This customer has orders. Block the customer instead of deleting.' : null;
    }

    public function show($record)
    {
        $customer = Customer::query()->findOrFail($record);
        $this->authorize('view', $customer);

        return view('admin.customers.show', [
            'customer' => $customer,
            'enquiries' => $customer->enquiries()->latest()->limit(15)->get(),
            'orders' => feature('ordering') && auth()->user()->can('orders.view') ? $customer->orders()->latest()->limit(15)->get() : collect(),
        ]);
    }
}
