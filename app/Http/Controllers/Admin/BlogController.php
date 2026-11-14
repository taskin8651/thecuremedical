<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogController extends CrudController
{
    protected string $modelClass = Blog::class;
    protected string $route = 'admin.blogs';
    protected string $title = 'Blog';
    protected string $singular = 'Article';
    protected array $with = ['media', 'author'];
    protected array $search = ['title', 'category', 'excerpt'];

    protected function columns(): array
    {
        return [
            ['label' => '', 'type' => 'image', 'value' => fn ($r) => $r->getFirstMediaUrl('featured', 'thumb') ?: null],
            ['label' => 'Title', 'key' => 'title', 'link' => true],
            ['label' => 'Category', 'key' => 'category'],
            ['label' => 'Author', 'value' => fn ($r) => $r->author?->name ?? '-'],
            ['label' => 'Published', 'key' => 'published_at', 'type' => 'date'],
            ['label' => 'Status', 'key' => 'status', 'type' => 'bool', 'value' => fn ($r) => $r->status === 'published', 'yes' => 'Published', 'no' => 'Draft', 'tones' => ['warning']],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'status', 'label' => 'Status', 'options' => ['published' => 'Published', 'draft' => 'Draft']]];
    }

    protected function bulkActions(): array
    {
        return ['delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'title', 'col' => 8, 'rules' => 'required|string|max:200', 'group' => 'Article'],
            ['name' => 'category', 'col' => 4, 'rules' => 'nullable|string|max:80', 'placeholder' => 'Medicine safety'],
            ['name' => 'slug', 'rules' => fn ($m) => ['nullable', 'alpha_dash', 'max:220', Rule::unique('blogs', 'slug')->ignore($m?->id)], 'help' => 'Leave blank to generate from the title.'],
            ['name' => 'featured', 'label' => 'Featured image', 'type' => 'image', 'collection' => 'featured'],
            ['name' => 'excerpt', 'label' => 'Short description', 'type' => 'textarea', 'rows' => 2, 'col' => 12, 'rules' => 'nullable|string|max:400'],
            ['name' => 'content', 'type' => 'textarea', 'rows' => 14, 'col' => 12, 'rules' => 'nullable|string|max:60000', 'help' => 'Markdown is supported. HTML is stripped for safety. Health content must be general information reviewed by the owner or pharmacist, never personal medical advice.'],
            ['name' => 'status', 'label' => 'Publish status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published'], 'default' => 'draft', 'col' => 4, 'group' => 'Publishing', 'rules' => 'required|in:draft,published'],
            ['name' => 'published_at', 'label' => 'Publish date', 'type' => 'datetime', 'col' => 4, 'rules' => 'nullable|date', 'help' => 'A future date schedules the article.'],
            ['name' => 'seo_title', 'label' => 'SEO title', 'col' => 6, 'group' => 'SEO', 'rules' => 'nullable|string|max:120'],
            ['name' => 'seo_description', 'label' => 'SEO description', 'col' => 6, 'rules' => 'nullable|string|max:250'],
        ];
    }

    protected function beforeSave(array &$data, Model $model, Request $request): void
    {
        if (! $model->exists) {
            $data['author_id'] = $request->user()->id;
        }
        if (($data['status'] ?? '') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = $model->published_at ?? now();
        }
    }
}
