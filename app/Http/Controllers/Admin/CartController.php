<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Model;

/** Read-only view of saved carts. Carts only exist if online ordering is enabled. */
class CartController extends CrudController
{
    protected string $modelClass = Cart::class;
    protected string $route = 'admin.carts';
    protected string $title = 'Carts';
    protected string $singular = 'Cart';
    protected array $with = ['customer'];
    protected array $search = ['customer.name', 'customer.phone'];
    protected bool $allowCreate = false;
    protected bool $allowDelete = false;

    protected function columns(): array
    {
        return [
            ['label' => 'Cart', 'value' => fn ($r) => '#'.$r->id, 'link' => 'show'],
            ['label' => 'Customer', 'value' => fn ($r) => $r->customer?->name ?? 'Guest'],
            ['label' => 'Items', 'value' => fn ($r) => $r->items()->count()],
            ['label' => 'Status', 'key' => 'status'],
            ['label' => 'Updated', 'key' => 'updated_at', 'type' => 'datetime'],
        ];
    }

    protected function fields(?Model $model = null): array
    {
        return [];
    }

    public function show($record)
    {
        $cart = Cart::query()->with(['customer', 'items.product'])->findOrFail($record);
        $this->authorize('view', $cart);

        return view('admin.carts.show', ['cart' => $cart]);
    }
}
