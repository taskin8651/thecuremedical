<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Support\CsvExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eight reports. Each returns summary cards plus a table that is shown on screen, printable
 * and exportable to CSV. Revenue is counted from VERIFIED payment records only.
 */
class ReportController extends Controller
{
    /** key => [label, icon, description, feature flag|null, extra permission|null, uses date range] */
    public const REPORTS = [
        'sales' => ['Sales & revenue', 'bi-cash-coin', 'Verified payments and refunds. Revenue is never estimated from orders.', 'payments', 'payments.view', true],
        'orders' => ['Orders', 'bi-receipt', 'Orders placed in the period, with status and payment status.', 'ordering', 'orders.view', true],
        'products' => ['Product catalogue', 'bi-capsule', 'Products per category and how many have been verified by a pharmacist.', null, 'products.view', false],
        'stock' => ['Current stock', 'bi-boxes', 'Stock level of every tracked product.', null, 'inventory.view', false],
        'movements' => ['Stock movements', 'bi-arrow-left-right', 'Every stock in, out and adjustment from the ledger.', null, 'inventory.view', true],
        'expiry' => ['Expiry', 'bi-calendar-x', 'Batches that have expired or will expire soon.', null, 'inventory.view', false],
        'enquiries' => ['Enquiries', 'bi-chat-left-text', 'Enquiries received in the period by status and type.', null, 'enquiries.view', true],
        'customers' => ['Customers', 'bi-people', 'New customers in the period.', null, 'customers.view', true],
    ];

    private function allowed(string $key): bool
    {
        $def = self::REPORTS[$key] ?? null;

        return $def && (! $def[4] || auth()->user()->can($def[4]));
    }

    public function index()
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $list = [];
        foreach (self::REPORTS as $key => $def) {
            if ($this->allowed($key)) {
                $list[$key] = ['label' => $def[0], 'icon' => $def[1], 'text' => $def[2], 'off' => $def[3] && ! feature($def[3]) ? $def[3] : null];
            }
        }

        return view('admin.reports.index', ['reports' => $list]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
        abort_unless($this->allowed($report), 404);
        [$label, $icon, $text, $flag, , $ranged] = self::REPORTS[$report];

        if ($flag && ! feature($flag)) {
            return view('admin.partials.disabled', [
                'title' => $label, 'what' => ['payments' => 'Payment recording', 'ordering' => 'Online ordering'][$flag], 'setting' => $flag.'_enabled',
                'text' => 'This report has no data while the feature is switched off.',
            ]);
        }

        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'q' => ['nullable', 'string', 'max:100'], 'days' => ['nullable', 'integer', 'min:1', 'max:730']]);
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();
        $q = trim((string) $request->input('q'));

        $data = $this->{'build'.ucfirst($report)}($from, $to, $q, $request);
        $rows = collect($data['rows']);

        if ($request->input('export') === 'csv') {
            abort_unless($request->user()->can('reports.export'), 403);
            ActivityLog::record('exported', null, "Exported report: {$label}");

            return CsvExporter::download('report-'.$report.'-'.now()->format('Ymd-His').'.csv', $data['headings'], $rows);
        }

        $page = max(1, (int) $request->input('page', 1));
        $per = $request->boolean('print') ? 5000 : 50;
        $paginator = new LengthAwarePaginator($rows->forPage($page, $per)->values(), $rows->count(), $per, $page, ['path' => url()->current(), 'query' => $request->except('page')]);

        return view('admin.reports.show', [
            'key' => $report, 'label' => $label, 'icon' => $icon, 'text' => $text, 'ranged' => $ranged,
            'from' => $from, 'to' => $to, 'q' => $q, 'days' => $request->integer('days') ?: (int) setting('expiry_alert_days', 60),
            'cards' => $data['cards'], 'headings' => $data['headings'], 'rows' => $paginator, 'note' => $data['note'] ?? null,
            'print' => $request->boolean('print'),
        ]);
    }

    // ------------------------------------------------------------------ builders

    private function buildSales(Carbon $from, Carbon $to, string $q): array
    {
        $query = Payment::query()->with('order')->where('status', 'verified')->whereBetween('paid_at', [$from, $to])->orderBy('paid_at');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('transaction_reference', 'like', "%$q%")->orWhere('method', 'like', "%$q%")->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%$q%")));
        }
        $all = $query->get();
        $in = $all->where('type', 'payment')->sum('amount');
        $out = $all->where('type', 'refund')->sum('amount');

        return [
            'cards' => [['Payments received', money($in)], ['Refunds paid', money($out)], ['Net revenue', money($in - $out)], ['Transactions', number_format($all->count())]],
            'headings' => ['Date', 'Order', 'Type', 'Method', 'Reference', 'Amount'],
            'rows' => $all->map(fn ($p) => [$p->paid_at->format('d M Y H:i'), $p->order?->order_number, ucfirst($p->type), $p->method, $p->transaction_reference, ($p->type === 'refund' ? '-' : '').number_format($p->amount, 2)]),
            'note' => 'Only payments that staff have verified are counted. Pending and failed entries are excluded.',
        ];
    }

    private function buildOrders(Carbon $from, Carbon $to, string $q): array
    {
        $query = Order::query()->with('customer')->withCount('items')->whereBetween('created_at', [$from, $to])->orderBy('created_at');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('order_number', 'like', "%$q%")->orWhere('shipping_name', 'like', "%$q%")->orWhere('shipping_phone', 'like', "%$q%"));
        }
        $all = $query->get();
        $live = $all->whereNotIn('status', ['cancelled', 'refunded']);

        return [
            'cards' => [['Orders', number_format($all->count())], ['Cancelled', number_format($all->where('status', 'cancelled')->count())], ['Order value (not revenue)', money($live->sum('grand_total'))], ['Average order', money($live->count() ? $live->avg('grand_total') : 0)]],
            'headings' => ['Order', 'Date', 'Customer', 'Status', 'Payment', 'Items', 'Total'],
            'rows' => $all->map(fn ($o) => [$o->order_number, $o->created_at->format('d M Y'), $o->customer?->name ?? $o->shipping_name, Order::STATUSES[$o->status] ?? $o->status, Order::PAYMENT_STATUSES[$o->payment_status] ?? $o->payment_status, $o->items_count, number_format($o->grand_total, 2)]),
        ];
    }

    private function buildProducts(Carbon $from, Carbon $to, string $q): array
    {
        $cats = Category::query()->withCount([
            'products', 'products as active_count' => fn ($p) => $p->where('is_active', true),
            'products as verified_count' => fn ($p) => $p->where('is_verified', true),
            'products as rx_count' => fn ($p) => $p->where('requires_prescription', true),
        ])->when($q !== '', fn ($c) => $c->where('name', 'like', "%$q%"))->orderBy('name')->get();
        $total = Product::query()->count();
        $verified = Product::query()->where('is_verified', true)->count();

        return [
            'cards' => [['Products', number_format($total)], ['Verified by pharmacist', number_format($verified)], ['Awaiting verification', number_format($total - $verified)], ['Inactive', number_format(Product::query()->where('is_active', false)->count())]],
            'headings' => ['Category', 'Products', 'Active', 'Verified', 'Prescription-only'],
            'rows' => $cats->map(fn ($c) => [$c->name, $c->products_count, $c->active_count, $c->verified_count, $c->rx_count]),
        ];
    }

    private function buildStock(Carbon $from, Carbon $to, string $q): array
    {
        $query = Product::query()->with('category')->where('track_inventory', true)->orderBy('name');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%"));
        }
        $all = $query->get();
        $label = ['out' => 'Out of stock', 'low' => 'Low', 'ok' => 'OK', 'untracked' => 'Not tracked'];

        return [
            'cards' => [['Tracked products', number_format($all->count())], ['Low stock', number_format($all->filter->isLowStock()->count())], ['Out of stock', number_format($all->where('stock_quantity', '<=', 0)->count())], ['Units on hand', number_format($all->sum('stock_quantity'))]],
            'headings' => ['Product', 'SKU', 'Category', 'In stock', 'Minimum', 'Status'],
            'rows' => $all->map(fn ($p) => [$p->name, $p->sku, $p->category?->name, $p->stock_quantity, $p->min_quantity, $label[$p->stockStatus()]]),
        ];
    }

    private function buildMovements(Carbon $from, Carbon $to, string $q): array
    {
        $query = InventoryTransaction::query()->with(['product', 'user', 'batch'])->whereBetween('created_at', [$from, $to])->orderBy('id');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('reference', 'like', "%$q%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%$q%")->orWhere('sku', 'like', "%$q%")));
        }
        $all = $query->get();

        return [
            'cards' => [['Units in', number_format($all->where('type', 'in')->sum('quantity'))], ['Units out', number_format(abs($all->where('type', 'out')->sum('quantity')))], ['Adjustments', number_format($all->where('type', 'adjustment')->count())], ['Entries', number_format($all->count())]],
            'headings' => ['Date', 'Product', 'Type', 'Change', 'Balance', 'Batch', 'Reason', 'Reference', 'By'],
            'rows' => $all->map(fn ($t) => [$t->created_at->format('d M Y H:i'), $t->product?->name, ucfirst($t->type), ($t->quantity > 0 ? '+' : '').$t->quantity, $t->balance_after, $t->batch?->batch_number, str_replace('_', ' ', (string) $t->reason), $t->reference, $t->user?->name]),
        ];
    }

    private function buildExpiry(Carbon $from, Carbon $to, string $q, Request $request): array
    {
        $days = $request->integer('days') ?: (int) setting('expiry_alert_days', 60);
        $query = ProductBatch::query()->with(['product', 'supplier'])->where('quantity', '>', 0)->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))->orderBy('expiry_date');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('batch_number', 'like', "%$q%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%$q%")));
        }
        $all = $query->get();
        $expired = $all->filter(fn ($b) => $b->expiryState() === 'expired');

        return [
            'cards' => [['Expired batches', number_format($expired->count())], ['Expiring within '.$days.' days', number_format($all->count() - $expired->count())], ['Units affected', number_format($all->sum('quantity'))]],
            'headings' => ['Product', 'Batch', 'Expiry', 'Days left', 'Units', 'Supplier'],
            'rows' => $all->map(fn ($b) => [$b->product?->name, $b->batch_number, $b->expiry_date->format('d M Y'), $b->daysToExpiry() < 0 ? 'Expired' : $b->daysToExpiry(), $b->quantity, $b->supplier?->name]),
            'note' => 'Expired stock should be removed from sale. Record it with a stock-out (reason: expired).',
        ];
    }

    private function buildEnquiries(Carbon $from, Carbon $to, string $q): array
    {
        $query = Enquiry::query()->with('handler')->whereBetween('created_at', [$from, $to])->orderBy('created_at');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('product_name', 'like', "%$q%"));
        }
        $all = $query->get();
        $cards = [['Enquiries', number_format($all->count())]];
        foreach (['new' => 'New', 'pending' => 'Pending', 'resolved' => 'Resolved'] as $k => $l) {
            $cards[] = [$l, number_format($all->where('status', $k)->count())];
        }

        return [
            'cards' => $cards,
            'headings' => ['Received', 'Name', 'Phone', 'Type', 'Product / topic', 'Status', 'Handled by'],
            'rows' => $all->map(fn ($e) => [$e->created_at->format('d M Y H:i'), $e->name, $e->phone, Enquiry::TYPES[$e->type] ?? $e->type, $e->product_name, Enquiry::STATUSES[$e->status] ?? $e->status, $e->handler?->name]),
        ];
    }

    private function buildCustomers(Carbon $from, Carbon $to, string $q): array
    {
        $query = Customer::query()->withCount(['orders', 'enquiries'])->whereBetween('created_at', [$from, $to])->orderBy('created_at');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('email', 'like', "%$q%"));
        }
        $all = $query->get();

        return [
            'cards' => [['New customers', number_format($all->count())], ['All customers', number_format(Customer::query()->count())], ['Blocked', number_format(Customer::query()->where('status', 'blocked')->count())]],
            'headings' => ['Joined', 'Name', 'Phone', 'Email', 'City', 'Status', 'Enquiries', 'Orders'],
            'rows' => $all->map(fn ($c) => [$c->created_at->format('d M Y'), $c->name, $c->phone, $c->email, $c->city, ucfirst($c->status), $c->enquiries_count, $c->orders_count]),
        ];
    }
}
