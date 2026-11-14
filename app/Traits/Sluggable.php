<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Generates a unique slug when none is supplied.
 */
trait Sluggable
{
    public static function bootSluggable(): void
    {
        static::saving(function ($model) {
            $source = $model->slugSource();
            if (blank($model->slug) && filled($model->{$source})) {
                $model->slug = static::uniqueSlug((string) $model->{$source}, $model->getKey());
            }
        });
    }

    public function slugSource(): string
    {
        return 'name';
    }

    public static function uniqueSlug(string $text, $ignoreId = null): string
    {
        $base = Str::slug($text) ?: 'item';
        $slug = $base;
        $i = 2;

        // withoutGlobalScopes() also checks soft-deleted rows so a slug is never re-used.
        while (static::query()->withoutGlobalScopes()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
