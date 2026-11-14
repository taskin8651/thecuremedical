<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['properties' => 'array', 'created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit entry. Never throws: logging must not break a request.
     */
    public static function record(string $action, ?Model $subject = null, ?string $description = null, array $properties = []): void
    {
        try {
            if ($subject && method_exists($subject, 'auditHidden')) {
                $hidden = $subject->auditHidden();
                if (isset($properties['changes'])) {
                    foreach ($hidden as $key) {
                        if (array_key_exists($key, $properties['changes'])) {
                            $properties['changes'][$key] = '[hidden]';
                        }
                    }
                    $properties['changes'] = array_map(
                        fn ($v) => is_string($v) ? Str::limit($v, 200) : $v,
                        $properties['changes']
                    );
                }
            }
            $label = $subject && method_exists($subject, 'auditLabel') ? $subject->auditLabel() : null;
            $request = app()->bound('request') ? request() : null;

            static::query()->create([
                'user_id' => auth()->id(),
                'action' => $action,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description ?? ($subject ? Str::headline(class_basename($subject))." \"{$label}\" {$action}" : $action), 300, ''),
                'properties' => $properties ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
