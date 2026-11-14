<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

final class Notifier
{
    /** Notify every active staff member holding $permission. Never throws. */
    public static function toPermission(string $permission, string $title, string $body = '', ?string $url = null, string $icon = 'bi-bell', string $kind = 'info'): void
    {
        try {
            $users = User::query()->where('is_active', true)->permission($permission)->get();
            if ($users->isNotEmpty()) {
                Notification::send($users, new AdminAlert($title, $body, $url, $icon, $kind));
            }
        } catch (\Throwable $e) {
            Log::warning('Notification failed: '.$e->getMessage());
        }
    }
}
