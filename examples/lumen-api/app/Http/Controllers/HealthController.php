<?php

namespace App\Http\Controllers;

use App\Support\SearchCache;
use App\Support\TenantContext;

class HealthController extends Controller
{
    public function show()
    {
        return response()->json([
            'status' => 'ok',
            'tenant' => TenantContext::id(),
            'search_cache_entries' => SearchCache::size(),
            'memory_mb' => round(memory_get_usage(true) / 1048576, 1),
        ]);
    }
}
