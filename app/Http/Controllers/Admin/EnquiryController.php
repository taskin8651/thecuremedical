<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Enquiry;
use App\Support\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends CrudController
{
    protected string $modelClass = Enquiry::class;
    protected string $route = 'admin.enquiries';
    protected string $title = 'Enquiries';
    protected string $singular = 'Enquiry';
    protected array $with = ['handler'];
    protected array $search = ['name', 'phone', 'email', 'product_name', 'message'];

    protected function columns(): array
    {
        return [
            ['label' => 'Name', 'key' => 'name', 'link' => 'show'],
            ['label' => 'Phone', 'key' => 'phone'],
            ['label' => 'About', 'value' => fn ($r) => $r->product_name ?: (Enquiry::TYPES[$r->type] ?? $r->type)],
            ['label' => 'Status', 'value' => fn ($r) => Enquiry::STATUSES[$r->status] ?? $r->status, 'type' => 'pill', 'tone' => fn ($r) => ['new' => 'info', 'pending' => 'warning', 'contacted' => 'info', 'resolved' => 'success', 'closed' => 'muted'][$r->status] ?? 'muted'],
            ['label' => 'Received', 'key' => 'created_at', 'type' => 'datetime'],
        ];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'status', 'label' => 'Status', 'options' => Enquiry::STATUSES],
            ['name' => 'type', 'label' => 'Type', 'options' => Enquiry::TYPES],
        ];
    }

    protected function bulkActions(): array
    {
        $a = [];
        foreach (Enquiry::STATUSES as $k => $label) {
            $a['mark_'.$k] = ['label' => 'Mark as '.strtolower($label)];
        }
        $a['delete'] = ['label' => 'Delete', 'ability' => 'delete'];

        return $a;
    }

    protected function applyBulk(string $action, Model $record): bool
    {
        if (str_starts_with($action, 'mark_')) {
            $status = substr($action, 5);

            return isset(Enquiry::STATUSES[$status]) && $this->changeStatus($record, $status);
        }

        return parent::applyBulk($action, $record);
    }

    protected function headerActions(): array
    {
        return [['label' => 'Export CSV', 'icon' => 'bi-download', 'url' => route('admin.enquiries.export', request()->query()), 'can' => auth()->user()->can('enquiries.export')]];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'name', 'rules' => 'required|string|max:150'],
            ['name' => 'phone', 'type' => 'tel', 'rules' => 'nullable|string|max:30'],
            ['name' => 'email', 'type' => 'email', 'rules' => 'nullable|email|max:150'],
            ['name' => 'type', 'type' => 'select', 'options' => Enquiry::TYPES, 'default' => 'general', 'rules' => ['required', Rule::in(array_keys(Enquiry::TYPES))]],
            ['name' => 'product_name', 'label' => 'Product / topic', 'rules' => 'nullable|string|max:200'],
            ['name' => 'status', 'type' => 'select', 'options' => Enquiry::STATUSES, 'default' => 'new', 'rules' => ['required', Rule::in(array_keys(Enquiry::STATUSES))]],
            ['name' => 'message', 'type' => 'textarea', 'col' => 12, 'rows' => 4, 'rules' => 'nullable|string|max:2000'],
            ['name' => 'admin_notes', 'label' => 'Staff notes', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:2000', 'help' => 'Internal. Never shown on the website.'],
        ];
    }

    protected function scopeQuery(Builder $query, Request $request): void
    {
        if ($request->filled('from') && strtotime((string) $request->input('from'))) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to') && strtotime((string) $request->input('to'))) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }
    }

    protected function beforeSave(array &$data, Model $model, Request $request): void
    {
        if (! $model->exists) {
            $data['source'] = 'staff';
        }
        if (isset($data['status']) && $data['status'] !== 'new' && ! $model->handled_by) {
            $data['handled_by'] = auth()->id();
        }
        if (($data['status'] ?? null) === 'contacted' && ! $model->contacted_at) {
            $data['contacted_at'] = now();
        }
    }

    private function changeStatus(Model $e, string $status): bool
    {
        $e->status = $status;
        $e->handled_by ??= auth()->id();
        if ($status === 'contacted' && ! $e->contacted_at) {
            $e->contacted_at = now();
        }

        return $e->save();
    }

    public function show($record)
    {
        $enquiry = Enquiry::query()->with(['customer', 'handler'])->findOrFail($record);
        $this->authorize('view', $enquiry);

        // Opening a new enquiry marks it as pending so teammates can see someone has looked at it.
        if ($enquiry->status === 'new' && auth()->user()->can('update', $enquiry)) {
            $this->changeStatus($enquiry, 'pending');
        }

        return view('admin.enquiries.show', ['enquiry' => $enquiry]);
    }

    public function status(Request $request, $record)
    {
        $enquiry = Enquiry::query()->findOrFail($record);
        $this->authorize('update', $enquiry);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Enquiry::STATUSES))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if (array_key_exists('admin_notes', $data)) {
            $enquiry->admin_notes = $data['admin_notes'];
        }
        $this->changeStatus($enquiry, $data['status']);

        return back()->with('success', 'Enquiry updated.');
    }

    public function export(Request $request)
    {
        abort_unless($request->user()->can('enquiries.export'), 403);
        $query = $this->modelClass::query()->with('handler');
        $this->scopeQuery($query, $request);
        foreach (['status', 'type'] as $k) {
            if ($request->filled($k)) {
                $query->where($k, $request->input($k));
            }
        }
        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like)->orWhere('product_name', 'like', $like));
        }
        ActivityLog::record('exported', null, 'Exported enquiries to CSV');

        return CsvExporter::download('enquiries-'.now()->format('Ymd-His').'.csv',
            ['ID', 'Received', 'Name', 'Phone', 'Email', 'Type', 'Product / topic', 'Status', 'Message', 'Staff notes', 'Handled by'],
            (function () use ($query) {
                foreach ($query->orderByDesc('id')->cursor() as $e) {
                    yield [$e->id, $e->created_at?->format('Y-m-d H:i'), $e->name, $e->phone, $e->email, Enquiry::TYPES[$e->type] ?? $e->type, $e->product_name,
                        Enquiry::STATUSES[$e->status] ?? $e->status, $e->message, $e->admin_notes, $e->handler?->name];
                }
            })());
    }
}
