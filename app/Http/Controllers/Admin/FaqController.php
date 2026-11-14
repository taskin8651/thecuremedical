<?php

namespace App\Http\Controllers\Admin;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Model;

class FaqController extends CrudController
{
    protected string $modelClass = Faq::class;
    protected string $route = 'admin.faqs';
    protected string $title = 'FAQs';
    protected string $singular = 'FAQ';
    protected array $search = ['question', 'answer'];
    protected string $orderBy = 'sort_order';
    protected string $orderDir = 'asc';

    protected function columns(): array
    {
        return [
            ['label' => 'Question', 'key' => 'question', 'link' => true],
            ['label' => 'Answer', 'value' => fn ($r) => \Illuminate\Support\Str::limit($r->answer, 90)],
            ['label' => 'Order', 'key' => 'sort_order'],
            ['label' => 'Status', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Visible', 'no' => 'Hidden'],
        ];
    }

    protected function bulkActions(): array
    {
        return ['activate' => ['label' => 'Show'], 'deactivate' => ['label' => 'Hide'], 'delete' => ['label' => 'Delete', 'ability' => 'delete']];
    }

    protected function fields(?Model $model = null): array
    {
        return [
            ['name' => 'question', 'col' => 12, 'rules' => 'required|string|max:300'],
            ['name' => 'answer', 'type' => 'textarea', 'rows' => 5, 'col' => 12, 'rules' => 'required|string|max:3000'],
            ['name' => 'sort_order', 'label' => 'Display order', 'type' => 'number', 'default' => 0, 'col' => 3, 'rules' => 'required|integer|min:0|max:9999'],
            ['name' => 'is_active', 'label' => 'Visible on website', 'type' => 'checkbox', 'default' => true, 'col' => 3, 'rules' => 'boolean'],
        ];
    }
}
