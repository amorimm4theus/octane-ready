<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function show(TenantContext $tenant): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'tenant' => $tenant->id(),
            'search_cache_entries' => 0,
            'memory_mb' => round(memory_get_usage(true) / 1048576, 1),
        ]);
    }
}
