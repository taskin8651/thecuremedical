<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CrudRequest;
use App\Models\ActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Generic, permission-checked CRUD for a single Eloquent model.
 *
 * A module controller declares the model, route prefix, table columns and form fields.
 * Listing, search, filters, validation (via CrudRequest), media uploads, bulk actions and
 * deletion with audit logging are handled here, and every write runs in a DB transaction.
 *
 * Field definition keys: name, label, type, rules, group, col, help, options, default,
 * value, virtual, readonly, collection (media), primary (media), rows, placeholder.
 * Types: text email tel url number money date datetime color password textarea select
 *        multiselect checkbox image images files hidden
 */
abstract class CrudController extends Controller
{
    protected string $modelClass;
    protected string $route;           // e.g. admin.categories
    protected string $title;           // e.g. Categories
    protected string $singular;        // e.g. Category
    protected array $with = [];
    protected array $search = [];      // columns (or relation.column) searched by ?q=
    protected int $perPage = 20;
    protected string $orderBy = 'id';
    protected string $orderDir = 'desc';
    protected bool $allowCreate = true;
    protected bool $allowDelete = true;

    abstract protected function columns(): array;

    abstract protected function fields(?Model $model = null): array;

    protected function filters(): array
    {
        return [];
    }

    /** key => ['label' => '', 'ability' => 'update|delete', 'icon' => 'bi-..'] */
    protected function bulkActions(): array
    {
        return [];
    }

    /** [['label','icon','url' => fn($row), 'method' => 'get|post', 'can' => fn($row)]] */
    protected function rowActions(): array
    {
        return [];
    }

    /** [['label','icon','url','can' => bool]] */
    protected function headerActions(): array
    {
        return [];
    }

    protected function scopeQuery(Builder $query, Request $request): void
    {
    }

    protected function beforeSave(array &$data, Model $model, Request $request): void
    {
    }

    protected function afterSave(Model $model, Request $request, bool $isNew): void
    {
    }

    protected function blockDelete(Model $model): ?string
    {
        return null;
    }

    protected function redirectAfterSave(Model $model, bool $isNew): string
    {
        return route($this->route.'.index');
    }

    protected function formView(): string
    {
        return 'admin.crud.'.(request()->routeIs($this->route.'.edit') ? 'edit' : 'create');
    }

    // ------------------------------------------------------------------ helpers used by CrudRequest

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    public function findRecord(int|string $id): Model
    {
        return $this->modelClass::query()->findOrFail($id);
    }

    public function validationRules(?Model $model = null): array
    {
        $rules = [];
        foreach ($this->normalizedFields($model) as $f) {
            $rule = $f['rules'];
            $rules[$f['name']] = $rule instanceof Closure ? $rule($model) : $rule;
            if (! empty($f['item_rules'])) {
                $rules[$f['name'].'.*'] = $f['item_rules'];
            }
        }

        return $rules;
    }

    public function fieldLabels(?Model $model = null): array
    {
        return collect($this->normalizedFields($model))->mapWithKeys(fn ($f) => [$f['name'] => Str::lower($f['label'])])->all();
    }

    protected function normalizedFields(?Model $model = null): array
    {
        $defaults = [
            'image' => ['rules' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096'],
            'images' => ['rules' => 'nullable|array|max:10', 'item_rules' => 'image|mimes:jpg,jpeg,png,webp|max:4096'],
            'files' => ['rules' => 'nullable|array|max:10', 'item_rules' => 'file|mimes:pdf,jpg,jpeg,png|max:8192'],
        ];

        return array_map(function (array $f) use ($defaults) {
            $f['type'] ??= 'text';
            $f['label'] ??= Str::headline($f['name']);
            $f['col'] ??= 6;

            return $f + ($defaults[$f['type']] ?? []) + ['rules' => 'nullable'];
        }, $this->fields($model));
    }

    // ------------------------------------------------------------------ listing

    protected function queryFromRequest(Request $request): Builder
    {
        $query = $this->modelClass::query()->with($this->with);
        $this->scopeQuery($query, $request);

        if (($q = trim((string) $request->input('q'))) !== '') {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function (Builder $w) use ($like) {
                foreach ($this->search as $col) {
                    if (str_contains($col, '.')) {
                        [$rel, $c] = explode('.', $col, 2);
                        $w->orWhereHas($rel, fn ($r) => $r->where($c, 'like', $like));
                    } else {
                        $w->orWhere($col, 'like', $like);
                    }
                }
            });
        }

        foreach ($this->filters() as $f) {
            if (! empty($f['custom'])) {
                continue; // handled in scopeQuery()
            }
            $value = $request->input($f['name']);
            if ($value !== null && $value !== '') {
                $query->where($f['column'] ?? $f['name'], $value);
            }
        }

        return $query->orderBy($this->orderBy, $this->orderDir);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', $this->modelClass);

        return view('admin.crud.index', $this->viewData([
            'rows' => $this->queryFromRequest($request)->paginate($this->perPage)->withQueryString(),
        ]));
    }

    protected function viewData(array $extra = []): array
    {
        return $extra + [
            'title' => $this->title,
            'singular' => $this->singular,
            'route' => $this->route,
            'modelClass' => $this->modelClass,
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'bulk' => $this->bulkActions(),
            'rowActions' => $this->rowActions(),
            'headerActions' => $this->headerActions(),
            'allowCreate' => $this->allowCreate,
            'allowDelete' => $this->allowDelete,
            'hasSearch' => ! empty($this->search),
        ];
    }

    // ------------------------------------------------------------------ create / edit

    protected function form(Model $model)
    {
        return view($this->formView(), $this->viewData([
            'model' => $model,
            'fields' => $this->normalizedFields($model->exists ? $model : null),
        ]));
    }

    public function create()
    {
        abort_unless($this->allowCreate, 404);
        $this->authorize('create', $this->modelClass);

        return $this->form(new $this->modelClass);
    }

    public function edit($record)
    {
        $model = $this->findRecord($record);
        $this->authorize('update', $model);

        return $this->form($model);
    }

    public function store(CrudRequest $request)
    {
        abort_unless($this->allowCreate, 404);
        $this->authorize('create', $this->modelClass);

        return $this->persist($request, null);
    }

    public function update(CrudRequest $request, $record)
    {
        $model = $this->findRecord($record);
        $this->authorize('update', $model);

        return $this->persist($request, $model);
    }

    protected function persist(CrudRequest $request, ?Model $model)
    {
        $isNew = $model === null;
        $model ??= new $this->modelClass;
        $fields = $this->normalizedFields($isNew ? null : $model);
        $validated = $request->validated();
        $data = [];

        foreach ($fields as $f) {
            $name = $f['name'];
            if (! empty($f['virtual']) || ! empty($f['readonly']) || in_array($f['type'], ['image', 'images', 'files'], true)) {
                continue;
            }
            if ($f['type'] === 'password') {
                if (! empty($validated[$name])) {
                    $data[$name] = Hash::make($validated[$name]);
                }
            } elseif ($f['type'] === 'checkbox') {
                $data[$name] = $request->boolean($name);
            } elseif (array_key_exists($name, $validated)) {
                $data[$name] = $validated[$name];
            }
        }

        DB::transaction(function () use (&$data, $model, $request, $fields, $isNew) {
            $this->beforeSave($data, $model, $request);
            $model->forceFill($data)->save();
            $this->syncMedia($model, $fields, $request);
            $this->afterSave($model, $request, $isNew);
        });

        return redirect($this->redirectAfterSave($model, $isNew))
            ->with('success', $this->singular.($isNew ? ' created.' : ' updated.'));
    }

    /**
     * Uploads use random file names and are validated for MIME type and size in the field rules.
     */
    protected function syncMedia(Model $model, array $fields, Request $request): void
    {
        foreach ($fields as $f) {
            if (! in_array($f['type'], ['image', 'images', 'files'], true)) {
                continue;
            }
            $collection = $f['collection'];

            if ($ids = $request->input("remove_media.$collection")) {
                $model->media()->where('collection_name', $collection)->whereIn('id', (array) $ids)->get()->each->delete();
            }

            $files = $request->file($f['name']);
            $newMedia = collect();
            if ($files) {
                foreach (is_array($files) ? $files : [$files] as $file) {
                    $newMedia->push($model->addMedia($file)
                        ->usingName(Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 100, ''))
                        ->usingFileName(Str::uuid().'.'.$file->extension())
                        ->toMediaCollection($collection));
                }
            }

            if (! empty($f['primary']) && $request->filled('primary_new')) {
                $selected = $newMedia->get((int) $request->input('primary_new'));
                if ($selected) {
                    $model->unsetRelation('media');
                    foreach ($model->getMedia($collection) as $m) {
                        $m->setCustomProperty('primary', (int) $m->id === (int) $selected->id);
                        $m->save();
                    }
                }
            } elseif (! empty($f['primary']) && $request->filled('primary_media')) {
                $model->unsetRelation('media');
                foreach ($model->getMedia($collection) as $m) {
                    $m->setCustomProperty('primary', (int) $m->id === (int) $request->input('primary_media'));
                    $m->save();
                }
            }
        }
    }

    // ------------------------------------------------------------------ delete & bulk

    public function destroy($record)
    {
        abort_unless($this->allowDelete, 404);
        $model = $this->findRecord($record);
        $this->authorize('delete', $model);

        if ($reason = $this->blockDelete($model)) {
            return back()->with('error', $reason);
        }
        $model->delete();

        return redirect()->route($this->route.'.index')->with('success', $this->singular.' deleted.');
    }

    public function bulk(Request $request)
    {
        $actions = $this->bulkActions();
        $data = $request->validate([
            'action' => ['required', Rule::in(array_keys($actions))],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ]);

        $this->authorize(($actions[$data['action']]['ability'] ?? 'update') === 'delete' ? 'delete' : 'update', $this->modelClass);

        $done = 0;
        $skipped = 0;
        DB::transaction(function () use ($data, &$done, &$skipped) {
            foreach ($this->modelClass::query()->whereKey($data['ids'])->get() as $record) {
                $this->applyBulk($data['action'], $record) ? $done++ : $skipped++;
            }
        });

        ActivityLog::record('bulk', null, "Bulk '{$data['action']}' on {$done} {$this->title}");

        return back()->with('success', "{$done} item(s) updated".($skipped ? ", {$skipped} skipped." : '.'));
    }

    protected function applyBulk(string $action, Model $record): bool
    {
        switch ($action) {
            case 'activate':
                return $record->update(['is_active' => true]);
            case 'deactivate':
                return $record->update(['is_active' => false]);
            case 'feature':
                return $record->update(['is_featured' => true]);
            case 'unfeature':
                return $record->update(['is_featured' => false]);
            case 'delete':
                if ($this->blockDelete($record)) {
                    return false;
                }

                return (bool) $record->delete();
        }

        return false;
    }
}
