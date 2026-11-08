<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

final class HealthController extends Controller
{
    public function __invoke(TenantContext $tenant): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'tenant' => $tenant->id(),
            'memory_mb' => round(memory_get_usage(true) / 1048576, 1),
        ]);
    }
}
