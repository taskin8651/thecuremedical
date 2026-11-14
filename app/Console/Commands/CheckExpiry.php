<?php

namespace App\Console\Commands;

use App\Models\ProductBatch;
use App\Support\Notifier;
use Illuminate\Console\Command;

class CheckExpiry extends Command
{
    protected $signature = 'cure:check-expiry';

    protected $description = 'Notify staff about product batches that are expired or close to expiry';

    public function handle(): int
    {
        $days = (int) setting('expiry_alert_days', 60);

        $batches = ProductBatch::query()->with('product')
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereNull('expiry_notified_at')
            ->get();

        foreach ($batches as $batch) {
            $expired = $batch->expiry_date->isPast();
            Notifier::toPermission(
                'inventory.view',
                $expired ? 'Batch expired' : 'Batch expiring soon',
                ($batch->product?->name ?? 'Product').' · batch '.$batch->batch_number.' · '.$batch->expiry_date->format('d M Y').' · qty '.$batch->quantity,
                route('admin.inventory.batches', ['state' => $expired ? 'expired' : 'soon']),
                'bi-calendar-x',
                $expired ? 'danger' : 'warning'
            );
            $batch->forceFill(['expiry_notified_at' => now()])->save();
        }

        $this->info($batches->count().' batch alert(s) sent.');

        return self::SUCCESS;
    }
}
