<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\Gallery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Only upload photos the shop owns or has permission to use. */
class GalleryController extends CrudController
{
    protected string $modelClass = Gallery::class;
    protected string $route = 'admin.gallery';
    protected string $title = 'Gallery';
    protected string $singular = 'Photo';
    protected array $with = ['media'];
    protected array $search = ['title', 'category', 'description'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';
    protected int $perPage = 24;

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->getFirstMediaUrl('image', 'thumb') ?: null],
            ['label' => 'Title', 'key' => 'title', 'link' => true],
            ['label' => 'Category', 'key' => 'category'],
            ['label' => 'Order', 'key' => 'sort_order'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Visible', 'no' => 'Hidden'],
        ];
    }

    protected function headerActions(): array
    {
        return [['label' => 'Upload several', 'icon' => 'bi-cloud-arrow-up', 'url' => route('admin.gallery.upload'), 'can' => auth()->user()->can('gallery.create')]];
    }

    protected function bulkActions(): array
    {
        return [
            'activate' => ['label' => 'Show on website'],
            'deactivate' => ['label' => 'Hide from website'],
            'delete' => ['label' => 'Delete', 'ability' => 'delete'],
        ];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'title', 'rules' => 'required|string|max:150'],
            ['name' => 'category', 'rules' => 'nullable|string|max:80', 'placeholder' => 'Store, Team, Products'],
            ['name' => 'description', 'type' => 'textarea', 'col' => 12, 'rules' => 'nullable|string|max:500'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'col' => 3, 'rules' => 'required|integer|min:0|max:9999'],
            ['name' => 'is_active', 'label' => 'Visible on website', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
            [
                'name' => 'image', 'label' => 'Photo', 'type' => 'image', 'collection' => 'image',
                'rules' => ($model && $model->exists && $model->hasMedia('image')) ? 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144' : 'required|image|mimes:jpg,jpeg,png,webp|max:6144',
            ],
        ];
    }

    public function uploadForm()
    {
        $this->authorize('create', Gallery::class);

        return view('admin.gallery.upload', ['categories' => Gallery::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')]);
    }

    public function upload(Request $request)
    {
        $this->authorize('create', Gallery::class);
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'category' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['images' => 'photos', 'images.*' => 'photo']);

        $next = (int) Gallery::query()->max('sort_order') + 1;
        $count = 0;
        DB::transaction(function () use ($request, $data, &$next, &$count) {
            foreach ($request->file('images') as $file) {
                $title = Str::limit(Str::headline(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)), 140, '');
                $item = Gallery::query()->create([
                    'title' => $title !== '' ? $title : 'Photo',
                    'category' => $data['category'] ?? null,
                    'is_active' => $request->boolean('is_active', true),
                    'sort_order' => $next++,
                ]);
                $item->addMedia($file)->usingFileName(Str::uuid().'.'.$file->extension())->toMediaCollection('image');
                $count++;
            }
        });
        ActivityLog::record('uploaded', null, "Uploaded {$count} gallery photo(s)");

        return redirect()->route('admin.gallery.index')->with('success', "{$count} photo(s) uploaded. Edit titles from the list.");
    }
}
