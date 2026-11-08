<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public const HEADER = 'X-Tenant';

    public function __construct(private readonly TenantContext $tenant) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->header(self::HEADER);

        if (is_string($tenant) && $tenant !== '') {
            $this->tenant->set($tenant);
        }

        return $next($request);
    }
}
