<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The tenant of the current request.
 *
 * Bound with $app->scoped(): Octane builds a fresh instance for every request,
 * so a request without the tenant header never inherits the previous tenant.
 */
final class TenantContext
{
    public const DEFAULT = 'public';

    private string $tenant = self::DEFAULT;

    public function set(string $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function id(): string
    {
        return $this->tenant;
    }
}
