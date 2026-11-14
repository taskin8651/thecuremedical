<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CrudRequest;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staff-recorded payments only. There is no payment gateway and no card data is ever stored.
 * A payment counts towards revenue only after a staff member verifies it.
 */
class PaymentController extends CrudController
{
    protected string $modelClass = Payment::class;
    protected string $route = 'admin.payments';
    protected string $title = 'Payments';
    protected string $singular = 'Payment';
    protected array $with = ['order', 'verifier'];
    protected array $search = ['transaction_reference', 'method', 'order.order_number'];
    protected bool $allowDelete = false;

    public static function methods(): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) setting('payment_methods', '')))));

        return array_combine($lines, $lines) ?: ['Cash at store' => 'Cash at store'];
    }

    /** What is still owed on an order: total minus verified payments plus verified refunds. */
    public static function outstanding(Order $order): float
    {
        $paid = (float) $order->payments()->where('type', 'payment')->where('status', 'verified')->sum('amount');
        $refunded = (float) $order->payments()->where('type', 'refund')->where('status', 'verified')->sum('amount');

        return round(max(0, (float) $order->grand_total - ($paid - $refunded)), 2);
    }

    public static function syncOrder(Order $order): void
    {
        $paid = (float) $order->payments()->where('type', 'payment')->where('status', 'verified')->sum('amount');
        $refunded = (float) $order->payments()->where('type', 'refund')->where('status', 'verified')->sum('amount');
        $net = round($paid - $refunded, 2);

        $status = match (true) {
            $refunded > 0 && $net <= 0 => 'refunded',
            $refunded > 0 => 'partially_refunded',
            $paid > 0 && $net >= (float) $order->grand_total => 'paid',
            default => 'unpaid',
        };
        if ($order->payment_status !== $status) {
            $order->forceFill(['payment_status' => $status])->save();
        }
    }

    protected function gate()
    {
        if (! feature('payments')) {
            return view('admin.partials.disabled', [
                'title' => 'Payments', 'what' => 'Payment recording', 'setting' => 'payments_enabled',
                'text' => 'No payment gateway is connected. When enabled, staff can only record payments received at the store and verify them. Keep this off until the owner confirms the legal and accounting requirements.',
            ]);
        }

        return null;
    }

    public function index(Request $request)
    {
        return $this->gate() ?? parent::index($request);
    }

    public function create()
    {
        return $this->gate() ?? parent::create();
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Date', 'value' => fn ($r) => ($r->paid_at ?? $r->created_at), 'type' => 'datetime'],
            ['label' => 'Order', 'value' => fn ($r) => $r->order?->order_number, 'link' => true],
            ['label' => 'Type', 'value' => fn ($r) => ucfirst($r->type), 'type' => 'pill', 'tone' => fn ($r) => $r->type === 'refund' ? 'warning' : 'info'],
            ['label' => 'Method', 'key' => 'method'],
            ['label' => 'Amount', 'value' => fn ($r) => ($r->type === 'refund' ? '- ' : '').money($r->amount)],
            ['label' => 'Reference', 'key' => 'transaction_reference'],
            ['label' => 'Status', 'value' => fn ($r) => Payment::STATUSES[$r->status] ?? $r->status, 'type' => 'pill', 'tone' => fn ($r) => ['verified' => 'success', 'pending' => 'warning', 'failed' => 'danger'][$r->status] ?? 'muted'],
        ];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'status', 'label' => 'Status', 'options' => Payment::STATUSES],
            ['name' => 'type', 'label' => 'Type', 'options' => ['payment' => 'Payment', 'refund' => 'Refund']],
        ];
    }

    protected function rowActions(): array
    {
        $user = auth()->user();

        return [
            ['label' => 'Verify', 'icon' => 'bi-patch-check', 'method' => 'post', 'url' => fn ($r) => route('admin.payments.verify', $r->id), 'can' => fn ($r) => $r->status === 'pending' && $user->can('payments.edit')],
            ['label' => 'Refund', 'icon' => 'bi-arrow-counterclockwise', 'url' => fn ($r) => route('admin.payments.refund', $r->id), 'can' => fn ($r) => $r->type === 'payment' && $r->status === 'verified' && $user->can('payments.edit')],
        ];
    }

    protected function fields(?Model $model = null): array
    {
        $orders = Order::query()->whereNotIn('status', ['cancelled', 'refunded'])->latest('id')->limit(500)->get()
            ->mapWithKeys(fn ($o) => [$o->id => $o->order_number.' · '.money($o->grand_total)])->all();

        return [
            ['name' => 'notice', 'type' => 'notice', 'text' => 'Record payments received at the store only. Never enter card numbers, CVV or UPI PINs here.'],
            ['name' => 'order_id', 'label' => 'Order', 'type' => 'select', 'options' => $orders, 'rules' => 'required|exists:orders,id'],
            ['name' => 'method', 'type' => 'select', 'options' => self::methods(), 'rules' => 'required|string|max:60'],
            ['name' => 'amount', 'type' => 'money', 'rules' => 'required|numeric|gt:0|max:9999999'],
            ['name' => 'paid_at', 'label' => 'Received on', 'type' => 'datetime', 'default' => now()->format('Y-m-d\TH:i'), 'rules' => 'required|date|before_or_equal:now'],
            ['name' => 'transaction_reference', 'label' => 'Reference (UPI ref / receipt no.)', 'rules' => 'nullable|string|max:120', 'col' => 12],
            ['name' => 'notes', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:500'],
        ];
    }

    public function store(CrudRequest $request)
    {
        abort_unless(feature('payments'), 403, 'Payment recording is disabled.');
        $order = Order::query()->findOrFail($request->input('order_id'));
        if ((float) $request->input('amount') > self::outstanding($order) + 0.001) {
            return back()->withInput()->with('error', 'The amount is more than the balance due ('.money(self::outstanding($order)).') on this order.');
        }

        return parent::store($request);
    }

    protected function beforeSave(array &$data, Model $model, Request $request): void
    {
        $data['type'] = 'payment';
        $data['status'] = 'pending';
    }

    protected function redirectAfterSave(Model $model, bool $isNew): string
    {
        return route('admin.payments.index');
    }

    public function verify(Request $request, $record)
    {
        abort_unless(feature('payments'), 403);
        $payment = Payment::query()->with('order')->findOrFail($record);
        $this->authorize('update', $payment);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be verified.');
        }
        DB::transaction(function () use ($payment) {
            $payment->forceFill(['status' => 'verified', 'verified_by' => auth()->id(), 'verified_at' => now(), 'paid_at' => $payment->paid_at ?? now()])->save();
            self::syncOrder($payment->order);
        });
        ActivityLog::record('verified', $payment, ucfirst($payment->type).' of '.money($payment->amount).' verified for '.$payment->order->order_number);

        return back()->with('success', ucfirst($payment->type).' verified.');
    }

    public function refundForm($record)
    {
        abort_unless(feature('payments'), 403);
        $payment = Payment::query()->with('order')->findOrFail($record);
        $this->authorize('update', $payment);
        abort_unless($payment->type === 'payment' && $payment->status === 'verified', 404);

        return view('admin.payments.refund', ['payment' => $payment, 'refundable' => $this->refundable($payment)]);
    }

    public function refund(Request $request, $record)
    {
        abort_unless(feature('payments'), 403);
        $payment = Payment::query()->with('order')->findOrFail($record);
        $this->authorize('update', $payment);
        abort_unless($payment->type === 'payment' && $payment->status === 'verified', 404);

        $max = $this->refundable($payment);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:'.$max],
            'refund_reason' => ['required', 'string', 'max:250'],
            'transaction_reference' => ['nullable', 'string', 'max:120'],
        ], ['amount.lte' => 'The refund cannot be more than '.money($max).'.']);

        $refund = Payment::query()->create([
            'order_id' => $payment->order_id, 'parent_id' => $payment->id, 'type' => 'refund', 'method' => $payment->method,
            'amount' => $data['amount'], 'status' => 'pending', 'refund_reason' => $data['refund_reason'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
        ]);
        ActivityLog::record('refund_recorded', $refund, 'Refund of '.money($data['amount']).' recorded for '.$payment->order->order_number.'. It counts after verification.');

        return redirect()->route('admin.payments.index')->with('success', 'Refund recorded as pending. Verify it once the money has been returned.');
    }

    private function refundable(Payment $payment): float
    {
        $done = (float) $payment->refunds()->whereIn('status', ['pending', 'verified'])->sum('amount');

        return round(max(0, (float) $payment->amount - $done), 2);
    }
}
