<?php

namespace App\Support;

/**
 * Legacy pattern: request-scoped data kept in a static property.
 * Fine under PHP-FPM (the process dies after each request).
 * Leaks between requests under a long-lived worker (Octane/Swoole).
 */
class TenantContext
{
    private static ?string $tenant = null;

    public static function set(string $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function id(): string
    {
        return self::$tenant ?? 'public';
    }
}
