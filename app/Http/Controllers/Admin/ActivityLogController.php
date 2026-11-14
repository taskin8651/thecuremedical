<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/** Read-only audit trail. */
class ActivityLogController extends CrudController
{
    protected string $modelClass = ActivityLog::class;
    protected string $route = 'admin.activity';
    protected string $title = 'Activity log';
    protected string $singular = 'Entry';
    protected array $with = ['user'];
    protected array $search = ['description', 'ip_address'];
    protected int $perPage = 40;
    protected bool $allowCreate = false;
    protected bool $allowDelete = false;

    protected function columns(): array
    {
        return [
            ['label' => 'When', 'key' => 'created_at', 'type' => 'datetime'],
            ['label' => 'User', 'value' => fn ($r) => $r->user?->name ?? 'System'],
            ['label' => 'Action', 'type' => 'html', 'value' => fn ($r) => '<span class="pill '.(in_array($r->action, ['deleted', 'login_failed'], true) ? 'pill-danger' : (in_array($r->action, ['created', 'login'], true) ? 'pill-success' : 'pill-info')).'">'.e(str_replace('_', ' ', $r->action)).'</span>'],
            ['label' => 'Description', 'key' => 'description'],
            ['label' => 'IP address', 'key' => 'ip_address'],
        ];
    }

    protected function filters(): array
    {
        $actions = ActivityLog::query()->distinct()->orderBy('action')->pluck('action')->mapWithKeys(fn ($a) => [$a => str_replace('_', ' ', $a)])->all();

        return [['name' => 'action', 'label' => 'Action', 'options' => $actions]];
    }

    protected function fields(?Model $model = null): array
    {
        return [];
    }
}
