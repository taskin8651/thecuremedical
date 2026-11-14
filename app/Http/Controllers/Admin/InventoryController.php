<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Stock screens. All quantity changes go through InventoryService, which writes the
 * append-only ledger. History rows can never be edited or deleted.
 */
class InventoryController extends Controller
{
    public const REASONS = [
        'in' => ['opening_stock' => 'Opening stock', 'purchase' => 'Purchase received', 'customer_return' => 'Customer return', 'supplier_replacement' => 'Supplier replacement', 'other' => 'Other'],
        'out' => ['damaged' => 'Damaged', 'expired' => 'Expired - removed from shelf', 'returned_to_supplier' => 'Returned to supplier', 'store_use' => 'Store use / sample', 'sale' => 'Sold at store', 'other' => 'Other'],
        'adjustment' => ['stock_count' => 'Physical stock count', 'correction' => 'Data correction', 'other' => 'Other'],
    ];

    public function index(Request $request)
    {
        $this->authorize('viewAny', ProductBatch::class);

        $query = Product::query()->with('category')->where('track_inventory', true);
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like)->orWhere('generic_name', 'like', $like));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }
        match ($request->input('status')) {
            'out' => $query->outOfStock(),
            'low' => $query->lowStock(),
            'ok' => $query->whereColumn('stock_quantity', '>', 'min_quantity'),
            default => null,
        };

        $summary = [
            'tracked' => Product::query()->where('track_inventory', true)->count(),
            'low' => Product::query()->lowStock()->count(),
            'out' => Product::query()->outOfStock()->count(),
            'expiring' => ProductBatch::query()->where('quantity', '>', 0)->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addDays((int) setting('expiry_alert_days', 60)))->count(),
            'expired' => ProductBatch::query()->where('quantity', '>', 0)->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now()->toDateString())->count(),
        ];

        return view('admin.inventory.index', [
            'products' => $query->orderBy('name')->paginate(25)->withQueryString(),
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'summary' => $summary,
        ]);
    }

    public function movement(Request $request)
    {
        abort_unless($request->user()->can('inventory.create'), 403);

        return view('admin.inventory.movement', [
            'products' => Product::query()->where('track_inventory', true)->orderBy('name')->get(['id', 'name', 'sku', 'stock_quantity']),
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'reasons' => self::REASONS,
            'selected' => $request->integer('product') ?: null,
            'type' => in_array($request->input('type'), ['in', 'out', 'adjustment'], true) ? $request->input('type') : 'in',
        ]);
    }

    public function storeMovement(Request $request, InventoryService $inventory)
    {
        abort_unless($request->user()->can('inventory.create'), 403);

        $type = $request->input('type');
        $data = $request->validate([
            'type' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')->where('track_inventory', true)],
            'quantity' => ['required', 'integer', $type === 'adjustment' ? 'min:0' : 'min:1', 'max:1000000'],
            'reason' => ['required', Rule::in(array_keys(self::REASONS[$type] ?? []))],
            'batch_number' => [Rule::requiredIf($type === 'in'), 'nullable', 'string', 'max:80'],
            'expiry_date' => ['nullable', 'date', $type === 'in' ? 'after:today' : 'date'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'batch_number.required' => 'Enter the batch number printed on the pack.',
            'expiry_date.after' => 'Expired stock cannot be received. Check the expiry date on the pack.',
        ], ['quantity' => $type === 'adjustment' ? 'counted quantity' : 'quantity']);

        $product = Product::query()->findOrFail($data['product_id']);
        $rows = $inventory->move($product, $data['type'], (int) $data['quantity'], [
            'batch_number' => $data['batch_number'] ?? null, 'expiry_date' => $data['expiry_date'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null, 'cost' => $data['cost'] ?? null,
            'reason' => $data['reason'], 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null,
        ]);

        $balance = end($rows)->balance_after;

        return redirect()->route('admin.inventory.history', ['product_id' => $product->id])
            ->with('success', "Stock recorded for {$product->name}. Balance is now {$balance}.");
    }

    public function history(Request $request)
    {
        $this->authorize('viewAny', ProductBatch::class);

        $query = InventoryTransaction::query()->with(['product', 'batch', 'user'])->latest('id');
        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }
        if (in_array($request->input('type'), ['in', 'out', 'adjustment'], true)) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('from') && strtotime((string) $request->input('from'))) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to') && strtotime((string) $request->input('to'))) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('reference', 'like', $like)->orWhere('notes', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)->orWhere('sku', 'like', $like)));
        }

        return view('admin.inventory.history', [
            'rows' => $query->paginate(30)->withQueryString(),
            'product' => $request->filled('product_id') ? Product::query()->withTrashed()->find((int) $request->input('product_id')) : null,
            'reasons' => collect(self::REASONS)->flatMap(fn ($r) => $r)->all(),
        ]);
    }

    public function batches(Request $request)
    {
        $this->authorize('viewAny', ProductBatch::class);

        $days = (int) setting('expiry_alert_days', 60);
        $query = ProductBatch::query()->with(['product', 'supplier']);
        if (! $request->boolean('empty')) {
            $query->where('quantity', '>', 0);
        }
        match ($request->input('state')) {
            'expired' => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', now()->toDateString()),
            'soon' => $query->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', now()->toDateString())->whereDate('expiry_date', '<=', now()->addDays($days)),
            'ok' => $query->where(fn ($w) => $w->whereNull('expiry_date')->orWhereDate('expiry_date', '>', now()->addDays($days))),
            default => null,
        };
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('batch_number', 'like', $like)->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)->orWhere('sku', 'like', $like)));
        }

        return view('admin.inventory.batches', [
            'batches' => $query->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->paginate(30)->withQueryString(),
            'days' => $days,
        ]);
    }
}
