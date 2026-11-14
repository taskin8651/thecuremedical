<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Blog extends Model implements HasMedia
{
    use SoftDeletes, Auditable, Sluggable, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = ['published_at' => 'datetime'];

    public function slugSource(): string
    {
        return 'title';
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Published and not scheduled for the future. */
    public function scopePublished($q)
    {
        return $q->where('status', 'published')->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(800)->height(450)->nonQueued();
    }
}
