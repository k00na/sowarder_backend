<?php

namespace App\Support;

final class PublicDiskUrl
{
    /**
     * Prefix for Storage::disk('public')->url().
     *
     * Localhost stays relative so browsers resolve against the guest Vite origin
     * (`/storage` is proxied). Any other APP_URL keeps the historical absolute
     * `{APP_URL}/storage` used in production / pilot.
     */
    public static function resolve(?string $override, string $appUrl): string
    {
        if (is_string($override) && $override !== '') {
            return rtrim($override, '/');
        }

        $appUrl = rtrim($appUrl, '/');
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (in_array($host, ['localhost', '127.0.0.1'], true)) {
            return '/storage';
        }

        return $appUrl.'/storage';
    }
}
