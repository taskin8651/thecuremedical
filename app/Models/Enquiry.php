<?php

namespace App\Models;

use App\Support\Notifier;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use SoftDeletes, Auditable;

    public const STATUSES = ['new' => 'New', 'pending' => 'Pending', 'contacted' => 'Contacted', 'resolved' => 'Resolved', 'closed' => 'Closed'];

    public const TYPES = [
        'availability' => 'Product availability', 'equipment' => 'Healthcare equipment', 'store_visit' => 'Store visit',
        'general' => 'General', 'newsletter' => 'Newsletter signup', 'other' => 'Other',
    ];

    protected $guarded = [];

    protected $casts = ['contacted_at' => 'datetime'];

    protected static function booted(): void
    {
        static::created(function (Enquiry $e) {
            if ($e->source === 'website') {
                Notifier::toPermission('enquiries.view', 'New enquiry', $e->name.' · '.($e->product_name ?: (self::TYPES[$e->type] ?? $e->type)), route('admin.enquiries.show', $e->id), 'bi-chat-left-text');
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function telLink(): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $this->phone);

        return $d ? 'tel:+'.(strlen($d) === 10 ? '91'.$d : (strlen($d) === 11 && $d[0] === '0' ? '91'.substr($d, 1) : $d)) : null;
    }

    public function whatsappLink(): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $this->phone);
        if (! $d) {
            return null;
        }
        $d = strlen($d) === 10 ? '91'.$d : (strlen($d) === 11 && $d[0] === '0' ? '91'.substr($d, 1) : $d);

        return 'https://wa.me/'.$d.'?text='.rawurlencode('Hello '.$this->name.', this is '.setting('business_name').' regarding your enquiry.');
    }
}
