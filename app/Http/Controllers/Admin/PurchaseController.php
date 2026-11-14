<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Purchases are a permanent record, so they can be added but never edited or deleted.
 * Saving one receives the stock through InventoryService (new batch + ledger row).
 */
class PurchaseController extends CrudController
{
    protected string $modelClass = Purchase::class;
    protected string $route = 'admin.purchases';
    protected string $title = 'Purchases';
    protected string $singular = 'Purchase';
    protected array $with = ['product', 'supplier'];
    protected array $search = ['invoice_number', 'batch_number', 'product.name', 'supplier.name'];
    protected bool $allowDelete = false;
    protected string $orderBy = 'purchase_date';

    protected function columns(): array
    {
        return [
            ['label' => 'Date', 'key' => 'purchase_date', 'type' => 'date'],
            ['label' => 'Product', 'value' => fn ($r) => $r->product?->name],
            ['label' => 'Supplier', 'value' => fn ($r) => $r->supplier?->name ?? '-'],
            ['label' => 'Invoice', 'key' => 'invoice_number'],
            ['label' => 'Batch', 'key' => 'batch_number'],
            ['label' => 'Expiry', 'key' => 'expiry_date', 'type' => 'date'],
            ['label' => 'Qty', 'key' => 'quantity'],
            ['label' => 'Total', 'value' => fn ($r) => money($r->total_cost)],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'supplier_id', 'label' => 'Supplier', 'options' => Supplier::query()->orderBy('name')->pluck('name', 'id')->all()]];
    }

    protected function fields(?Model $model = null): array
    {
        $products = Product::query()->where('track_inventory', true)->orderBy('name')->get(['id', 'name', 'sku'])
            ->mapWithKeys(fn ($p) => [$p->id => $p->name.($p->sku ? " ({$p->sku})" : '')])->all();

        return [
            ['name' => 'product_id', 'label' => 'Product', 'type' => 'select', 'options' => $products, 'rules' => 'required|exists:products,id'],
            ['name' => 'supplier_id', 'label' => 'Supplier', 'type' => 'select', 'options' => Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:suppliers,id'],
            ['name' => 'invoice_number', 'label' => 'Supplier invoice no.', 'rules' => 'nullable|string|max:80'],
            ['name' => 'purchase_date', 'type' => 'date', 'default' => now()->toDateString(), 'rules' => 'required|date|before_or_equal:today'],
            ['name' => 'batch_number', 'label' => 'Batch number', 'rules' => 'required|string|max:80', 'help' => 'Copy it from the pack.'],
            ['name' => 'expiry_date', 'type' => 'date', 'rules' => 'nullable|date|after:today', 'help' => 'Expired stock cannot be received.'],
            ['name' => 'quantity', 'type' => 'number', 'rules' => 'required|integer|min:1|max:1000000'],
            ['name' => 'unit_cost', 'type' => 'money', 'default' => 0, 'rules' => 'required|numeric|min:0|max:9999999'],
            ['name' => 'notes', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:500'],
        ];
    }

    protected function beforeSave(array &$data, Model $model, Request $request): void
    {
        $data['total_cost'] = round((float) $data['unit_cost'] * (int) $data['quantity'], 2);
        $data['created_by'] = auth()->id();
    }

    protected function afterSave(Model $model, Request $request, bool $isNew): void
    {
        app(InventoryService::class)->move($model->product, 'in', (int) $model->quantity, [
            'batch_number' => $model->batch_number,
            'expiry_date' => $model->expiry_date?->toDateString(),
            'supplier_id' => $model->supplier_id,
            'purchase_id' => $model->id,
            'cost' => $model->unit_cost,
            'reason' => 'purchase',
            'reference' => $model->invoice_number ?: 'Purchase #'.$model->id,
        ]);
    }

    protected function redirectAfterSave(Model $model, bool $isNew): string
    {
        return route('admin.inventory.batches');
    }
}
