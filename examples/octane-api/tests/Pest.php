<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/**
 * Headers for a request made by the given tenant.
 *
 * @return array<string, string>
 */
function asTenant(string $tenant, array $extra = []): array
{
    return ['X-Tenant' => $tenant, ...$extra];
}
