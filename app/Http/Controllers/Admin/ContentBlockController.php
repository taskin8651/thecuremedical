<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContentBlock;
use Illuminate\Database\Eloquent\Model;

/** Homepage "Trust indicators" and "Why choose us" items. */
class ContentBlockController extends CrudController
{
    protected string $modelClass = ContentBlock::class;
    protected string $route = 'admin.content.blocks';
    protected string $title = 'Content blocks';
    protected string $singular = 'Content block';
    protected array $search = ['title', 'text'];
    protected string $orderBy = 'section';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'Section', 'value' => fn ($r) => ContentBlock::SECTIONS[$r->section] ?? $r->section],
            ['label' => 'Title', 'key' => 'title', 'link' => true],
            ['label' => 'Text', 'value' => fn ($r) => \Illuminate\Support\Str::limit((string) $r->text, 70)],
            ['label' => 'Order', 'key' => 'sort_order'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Visible', 'no' => 'Hidden'],
        ];
    }

    protected function filters(): array
    {
        return [['name' => 'section', 'label' => 'Section', 'options' => ContentBlock::SECTIONS]];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Show'], 'deactivate' => ['label' => 'Hide'], 'delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'section', 'type' => 'select', 'options' => ContentBlock::SECTIONS, 'rules' => 'required|in:'.implode(',', array_keys(ContentBlock::SECTIONS))],
            ['name' => 'title', 'rules' => 'required|string|max:150'],
            ['name' => 'text', 'type' => 'textarea', 'rows' => 2, 'col' => 12, 'rules' => 'nullable|string|max:400'],
            ['name' => 'icon', 'rules' => 'nullable|regex:/^bi-[a-z0-9-]+$/|max:60', 'placeholder' => 'bi-star', 'help' => 'A Bootstrap Icons class, for example bi-star.'],
            ['name' => 'url', 'label' => 'Link (optional)', 'rules' => 'nullable|string|max:300'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'col' => 3, 'rules' => 'required|integer|min:0|max:9999'],
            ['name' => 'is_active', 'label' => 'Visible on website', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
        ];
    }
}
