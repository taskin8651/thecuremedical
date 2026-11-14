<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Cached access to the `settings` table with defaults from config/cure.php.
 */
final class Settings
{
    private const CACHE_KEY = 'cure.settings';

    public static function all(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            return []; // database not migrated yet
        }
    }

    public static function definitions(): array
    {
        return config('cure.settings', []);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        if (array_key_exists($key, $all) && $all[$key] !== null) {
            return $all[$key];
        }
        $def = self::definitions()[$key] ?? null;

        return $def !== null && $def[2] !== null ? $def[2] : $default;
    }

    public static function bool(string $key): bool
    {
        return filter_var(self::get($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value, 'group' => $group]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Public URL of an uploaded image setting (stored on the public disk). */
    public static function url(string $key): ?string
    {
        $path = self::all()[$key] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** "tel:" number built from the phone setting (Indian numbers get +91). */
    public static function tel(): string
    {
        $digits = preg_replace('/\D+/', '', (string) self::get('phone', ''));
        if (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = '91'.substr($digits, 1);
        } elseif (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return '+'.$digits;
    }

    public static function whatsapp(?string $text = null): string
    {
        $n = preg_replace('/\D+/', '', (string) self::get('whatsapp_number', ''));
        $url = 'https://wa.me/'.$n;

        return $text ? $url.'?text='.rawurlencode($text) : $url;
    }

    public static function mapsDirections(): string
    {
        return 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode((string) self::get('business_name').', '.self::get('address'));
    }

    public static function mapsEmbed(): string
    {
        return (string) (self::get('maps_embed_url')
            ?: 'https://www.google.com/maps?q='.rawurlencode((string) self::get('business_name').', '.self::get('address')).'&output=embed');
    }

    public static function mapsView(): string
    {
        return (string) (self::get('maps_url')
            ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode((string) self::get('business_name').', '.self::get('address')));
    }
}
