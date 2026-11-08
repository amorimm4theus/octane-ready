<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader('X-Tenant')) {
            TenantContext::set($request->header('X-Tenant'));
        }

        return $next($request);
    }
}
