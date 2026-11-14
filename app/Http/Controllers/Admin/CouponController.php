<?php

namespace App\Http\Controllers\Admin;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** Coupons are configuration only. Nothing applies them until ordering is enabled. */
class CouponController extends CrudController
{
    protected string $modelClass = Coupon::class;
    protected string $route = 'admin.coupons';
    protected string $title = 'Coupons';
    protected string $singular = 'Coupon';
    protected array $search = ['code', 'description'];

    protected function columns(): array
    {
        return [
            ['label' => 'Code', 'key' => 'code', 'link' => true],
            ['label' => 'Discount', 'value' => fn ($r) => $r->discount_type === 'percent' ? rtrim(rtrim(number_format($r->discount_value, 2), '0'), '.').'%' : money($r->discount_value)],
            ['label' => 'Min order', 'value' => fn ($r) => money($r->min_order)],
            ['label' => 'Used', 'value' => fn ($r) => $r->used_count.($r->usage_limit ? ' / '.$r->usage_limit : '')],
            ['label' => 'Valid until', 'key' => 'expires_at', 'type' => 'date'],
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
            ['name' => 'code', 'label' => 'Coupon code', 'rules' => fn ($m) => ['required', 'alpha_dash', 'max:40', Rule::unique('coupons', 'code')->ignore($m?->id)], 'help' => 'Stored in upper case.'],
            ['name' => 'description', 'rules' => 'nullable|string|max:250'],
            ['name' => 'discount_type', 'type' => 'select', 'options' => ['percent' => 'Percentage', 'fixed' => 'Fixed amount'], 'default' => 'percent', 'rules' => 'required|in:percent,fixed'],
            ['name' => 'discount_value', 'type' => 'money', 'rules' => 'required|numeric|min:0.01|max:1000000'],
            ['name' => 'min_order', 'label' => 'Minimum order', 'type' => 'money', 'rules' => 'nullable|numeric|min:0'],
            ['name' => 'max_discount', 'label' => 'Maximum discount', 'type' => 'money', 'rules' => 'nullable|numeric|min:0'],
            ['name' => 'starts_at', 'label' => 'Valid from', 'type' => 'datetime', 'rules' => 'nullable|date'],
            ['name' => 'expires_at', 'label' => 'Valid until', 'type' => 'datetime', 'rules' => 'nullable|date|after_or_equal:starts_at'],
            ['name' => 'usage_limit', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'default' => true, 'rules' => 'boolean'],
        ];
    }

    protected function beforeSave(array &$data, Model $model, \Illuminate\Http\Request $request): void
    {
        $data['code'] = strtoupper($data['code']);
        if (($data['discount_type'] ?? '') === 'percent' && (float) $data['discount_value'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['discount_value' => 'A percentage discount cannot exceed 100.']);
        }
    }
}
