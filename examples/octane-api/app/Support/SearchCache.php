<?php

namespace App\Support;

/**
 * Legacy pattern: "memoize" results in a static array keyed by the full URL.
 * Under PHP-FPM it is emptied on every request. Under a long-lived worker it
 * grows forever (memory leak) and serves stale data across tenants.
 */
class SearchCache
{
    private static array $results = [];

    public static function remember(string $key, callable $callback): mixed
    {
        if (! array_key_exists($key, self::$results)) {
            self::$results[$key] = $callback();
        }

        return self::$results[$key];
    }

    public static function size(): int
    {
        return count(self::$results);
    }
}
