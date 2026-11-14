<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Testimonial extends Model implements HasMedia
{
    use SoftDeletes, Auditable, InteractsWithMedia;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'rating' => 'integer'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(160)->height(160)->nonQueued();
    }
}
