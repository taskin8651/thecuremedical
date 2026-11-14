<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes created / updated / deleted events to the activity log.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => ActivityLog::record('created', $m));
        static::updated(function (Model $m) {
            $changes = array_diff_key($m->getChanges(), array_flip(['updated_at']));
            if ($changes) {
                ActivityLog::record('updated', $m, null, ['changes' => $changes]);
            }
        });
        static::deleted(fn (Model $m) => ActivityLog::record('deleted', $m));
    }

    /** Attributes never written to the log. */
    public function auditHidden(): array
    {
        return ['password', 'remember_token', 'value'];
    }

    public function auditLabel(): string
    {
        foreach (['name', 'title', 'code', 'order_number', 'reference', 'question', 'slug'] as $f) {
            if (! empty($this->{$f})) {
                return (string) $this->{$f};
            }
        }

        return '#'.$this->getKey();
    }
}
