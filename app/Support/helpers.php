<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('setting_url')) {
    function setting_url(string $key): ?string
    {
        return Settings::url($key);
    }
}

if (! function_exists('feature')) {
    /** feature('ordering') => ordering_enabled setting */
    function feature(string $name): bool
    {
        return Settings::bool($name.'_enabled');
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $value): string
    {
        return $value === null || $value === '' ? '-' : '₹'.number_format((float) $value, 2);
    }
}

if (! function_exists('markdown_safe')) {
    /** Render admin-written Markdown with raw HTML stripped (XSS-safe). */
    function markdown_safe(?string $text): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString(
            \Illuminate\Support\Str::markdown((string) $text, ['html_input' => 'strip', 'allow_unsafe_links' => false])
        );
    }
}

if (! function_exists('public_price')) {
    /** Price text for the public site, or null while "Show prices" is off or no confirmed price exists. */
    function public_price(\App\Models\Product $p): ?array
    {
        if (! \App\Support\Settings::bool('show_prices')) {
            return null;
        }
        $price = $p->sale_price ?? $p->mrp;
        if ($price === null) {
            return null;
        }

        return ['now' => money($price), 'mrp' => ($p->sale_price !== null && $p->mrp !== null && $p->mrp > $p->sale_price) ? money($p->mrp) : null];
    }
}

if (! function_exists('public_availability')) {
    /** [label, css class]. Real stock is only shown when "Show availability" is on; otherwise "ask store". */
    function public_availability(\App\Models\Product $p): array
    {
        if (! \App\Support\Settings::bool('show_availability') || ! $p->track_inventory) {
            return ['Availability: ask store', 'off'];
        }

        return match ($p->stockStatus()) {
            'out' => ['Out of stock', 'off'],
            'low' => ['Limited stock', 'off'],
            default => ['In stock', ''],
        };
    }
}

if (! function_exists('public_cart_unavailable_reason')) {
    /** Explain why a catalogue item cannot be added to an online order. */
    function public_cart_unavailable_reason(\App\Models\Product $product): ?string
    {
        if (! $product->is_active) {
            return 'This product is currently unavailable.';
        }
        if ($product->requires_prescription) {
            return 'Prescription products must be handled by the pharmacist in store.';
        }
        if (! $product->is_verified) {
            return 'The store must verify this product before it can be ordered online.';
        }
        if (($product->sale_price ?? $product->mrp) === null) {
            return 'The store has not confirmed a price for this product yet.';
        }
        if ($product->track_inventory && $product->stock_quantity < 1) {
            return 'This product is currently out of stock.';
        }
        if (! feature('ordering')) {
            return 'Online ordering has not been enabled by the store yet.';
        }
        if (! \App\Support\Settings::bool('show_prices')) {
            return 'Online ordering will be available when the store publishes prices.';
        }

        return null;
    }
}
