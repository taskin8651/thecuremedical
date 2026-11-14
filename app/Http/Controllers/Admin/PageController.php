<?php

namespace App\Http\Controllers\Admin;

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** Static pages: Privacy, Terms, Return policy and custom pages. (About has its own screen.) */
class PageController extends CrudController
{
    protected string $modelClass = Page::class;
    protected string $route = 'admin.pages';
    protected string $title = 'Static pages';
    protected string $singular = 'Page';
    protected array $search = ['title', 'slug'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'Title', 'key' => 'title', 'link' => true],
            ['label' => 'Address', 'type' => 'html', 'value' => fn ($r) => '<span class="text-muted small">/p/'.e($r->slug).'</span>'],
            ['label' => 'Type', 'key' => 'is_system', 'type' => 'bool', 'yes' => 'System', 'no' => 'Custom'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Published', 'no' => 'Hidden'],
        ];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Publish'], 'deactivate' => ['label' => 'Hide'], 'delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        $system = (bool) $model?->is_system;

        return [
            ['name' => 'title', 'rules' => 'required|string|max:200', 'col' => 8],
            ['name' => 'slug', 'col' => 4, 'readonly' => $system, 'rules' => fn ($m) => ['nullable', 'alpha_dash', 'max:160', Rule::unique('pages', 'slug')->ignore($m?->id)], 'help' => $system ? 'System page: the address cannot change.' : 'Blank = generated from the title.'],
            ['name' => 'content', 'type' => 'textarea', 'rows' => 16, 'col' => 12, 'rules' => 'nullable|string|max:80000', 'help' => 'Markdown is supported. HTML is stripped. Legal text must be reviewed by the owner and a qualified adviser.'],
            ['name' => 'seo_title', 'label' => 'SEO title', 'rules' => 'nullable|string|max:120', 'group' => 'SEO'],
            ['name' => 'seo_description', 'label' => 'SEO description', 'rules' => 'nullable|string|max:250'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'col' => 3, 'rules' => 'required|integer|min:0|max:9999'],
            ['name' => 'is_active', 'label' => 'Published', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
        ];
    }

    protected function beforeSave(array &$data, Model $model, \Illuminate\Http\Request $request): void
    {
        if (blank($data['slug'] ?? null) && ! $model->exists) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['title']) ?: 'page';
            $base = $data['slug'];
            $i = 2;
            while (Page::query()->withTrashed()->where('slug', $data['slug'])->exists()) {
                $data['slug'] = $base.'-'.$i++;
            }
        } elseif (blank($data['slug'] ?? null)) {
            unset($data['slug']);
        }
    }

    protected function blockDelete(Model $model): ?string
    {
        return $model->is_system ? 'System pages cannot be deleted. Hide the page instead.' : null;
    }
}
