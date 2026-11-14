<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use SoftDeletes, Auditable, Sluggable, InteractsWithMedia;

    protected $guarded = [];

    /** Fields that carry medical information. Changing them clears the "verified" flag. */
    public const MEDICAL_FIELDS = [
        'generic_name', 'composition', 'strength', 'dosage_form', 'pack_size', 'pack_type', 'manufacturer',
        'storage_conditions', 'shelf_life', 'side_effects', 'contraindications', 'requires_prescription',
    ];

    protected $casts = [
        'is_active' => 'boolean', 'is_featured' => 'boolean', 'is_verified' => 'boolean',
        'requires_prescription' => 'boolean', 'track_inventory' => 'boolean',
        'verified_at' => 'datetime', 'gst_rate' => 'float', 'mrp' => 'float', 'sale_price' => 'float',
        'ptr' => 'float', 'pts' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function attributesList()
    {
        return $this->hasMany(ProductAttribute::class)->orderBy('sort_order');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeLowStock($q)
    {
        return $q->where('track_inventory', true)->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'min_quantity');
    }

    public function scopeOutOfStock($q)
    {
        return $q->where('track_inventory', true)->where('stock_quantity', '<=', 0);
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory && $this->stock_quantity > 0 && $this->stock_quantity <= $this->min_quantity;
    }

    public function stockStatus(): string
    {
        if (! $this->track_inventory) {
            return 'untracked';
        }

        return $this->stock_quantity <= 0 ? 'out' : ($this->isLowStock() ? 'low' : 'ok');
    }

    /** Primary image: media flagged "primary", otherwise the first one. */
    public function primaryImage(): ?Media
    {
        $images = $this->getMedia('images');

        return $images->first(fn ($m) => $m->getCustomProperty('primary')) ?? $images->first();
    }

    public function imageUrl(string $conversion = 'thumb'): ?string
    {
        $m = $this->primaryImage();

        return $m ? $m->getUrl($conversion) : null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('documents')->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(640)->height(640)->performOnCollections('images')->nonQueued();
    }
}
