<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Every number on the dashboard is queried from the database. Nothing is hard-coded.
 * Money figures use verified payment records only, and only when payments are enabled.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user->can('dashboard.view'), 403);

        $ordering = feature('ordering');
        $payments = feature('payments');
        $inventory = feature('inventory');
        $monthStart = now()->startOfMonth();

        $stats = [
            'products' => Product::query()->count(),
            'categories' => Category::query()->count(),
            'enquiries' => Enquiry::query()->count(),
            'customers' => Customer::query()->count(),
            'pending_enquiries' => Enquiry::query()->whereIn('status', ['new', 'pending'])->count(),
            'orders' => $ordering ? Order::query()->count() : null,
            'low_stock' => $inventory ? Product::query()->lowStock()->count() + Product::query()->outOfStock()->count() : null,
            'expiring' => $inventory ? ProductBatch::query()->where('quantity', '>', 0)->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', now()->addDays((int) setting('expiry_alert_days', 60)))->count() : null,
            'revenue' => $payments ? $this->netRevenue($monthStart) : null,
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'flags' => compact('ordering', 'payments', 'inventory'),
            'enquiryChart' => $this->monthly(Enquiry::query()->where('created_at', '>=', now()->subMonths(11)->startOfMonth())->pluck('created_at')),
            'orderChart' => $ordering ? $this->monthly(Order::query()->where('created_at', '>=', now()->subMonths(11)->startOfMonth())->pluck('created_at')) : null,
            'revenueChart' => $payments ? $this->revenueByMonth() : null,
            'categoryChart' => $this->categoryDistribution(),
            'recentEnquiries' => $user->can('enquiries.view') ? Enquiry::query()->latest()->limit(6)->get() : collect(),
            'recentOrders' => $ordering && $user->can('orders.view') ? Order::query()->with('customer')->latest()->limit(6)->get() : collect(),
            'latestCustomers' => $user->can('customers.view') ? Customer::query()->latest()->limit(6)->get() : collect(),
            'popularProducts' => $user->can('products.view') ? Product::query()->where('views', '>', 0)->orderByDesc('views')->limit(6)->get() : collect(),
            'lowStockProducts' => $inventory && $user->can('inventory.view')
                ? Product::query()->where(fn ($q) => $q->lowStock()->orWhere(fn ($w) => $w->outOfStock()))->orderBy('stock_quantity')->limit(6)->get() : collect(),
            'activities' => $user->can('activity.view') ? ActivityLog::query()->with('user')->latest('id')->limit(8)->get() : collect(),
        ]);
    }

    /** @return array{labels:array<int,string>,values:array<int,int>} last 12 months, oldest first */
    private function monthly(Collection $dates): array
    {
        $buckets = $this->emptyMonths();
        foreach ($dates as $d) {
            $key = Carbon::parse($d)->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]++;
            }
        }

        return ['labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->format('M y'), array_keys($buckets)), 'values' => array_values($buckets)];
    }

    private function emptyMonths(): array
    {
        $out = [];
        for ($i = 11; $i >= 0; $i--) {
            $out[now()->subMonths($i)->format('Y-m')] = 0;
        }

        return $out;
    }

    private function netRevenue(Carbon $from): float
    {
        $in = Payment::query()->where('type', 'payment')->where('status', 'verified')->where('paid_at', '>=', $from)->sum('amount');
        $out = Payment::query()->where('type', 'refund')->where('status', 'verified')->where('paid_at', '>=', $from)->sum('amount');

        return (float) $in - (float) $out;
    }

    private function revenueByMonth(): array
    {
        $buckets = array_map(fn () => 0.0, $this->emptyMonths());
        $rows = Payment::query()->where('status', 'verified')->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())->get(['type', 'amount', 'paid_at']);
        foreach ($rows as $p) {
            $key = $p->paid_at->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key] += $p->type === 'refund' ? -$p->amount : $p->amount;
            }
        }

        return ['labels' => array_map(fn ($k) => Carbon::createFromFormat('Y-m', $k)->format('M y'), array_keys($buckets)), 'values' => array_map(fn ($v) => round($v, 2), array_values($buckets))];
    }

    private function categoryDistribution(): array
    {
        $rows = Category::query()->whereNull('parent_id')->withCount('products')->orderByDesc('products_count')->limit(8)->get();

        return ['labels' => $rows->pluck('name')->all(), 'values' => $rows->pluck('products_count')->all()];
    }
}
