<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PrescriptionEnquiry extends Model implements HasMedia
{
    use SoftDeletes, Auditable, InteractsWithMedia;

    public const STATUSES = ['received' => 'Received', 'under_review' => 'Under review', 'verified' => 'Verified', 'rejected' => 'Rejected', 'completed' => 'Completed'];

    protected $guarded = [];

    protected $casts = ['verified_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->reference ??= 'RX-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        });
    }

    public function auditHidden(): array
    {
        return ['notes', 'staff_notes']; // clinical text is not copied into the audit log
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Stored on the PRIVATE local disk (storage/app), never publicly served. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('local')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }
}
