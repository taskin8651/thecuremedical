<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class ContentBlock extends Model
{
    use Auditable;

    public const SECTIONS = ['trust' => 'Trust indicators', 'why' => 'Why choose us'];

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeSection($q, string $section)
    {
        return $q->where('section', $section)->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
