<?php

namespace App\Providers;

use App\Support\PriceFormatter;
use App\Support\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // New instance per request (Octane flushes scoped instances between requests).
        $this->app->scoped(TenantContext::class);

        // Stateless, safe to keep for the whole worker lifetime.
        $this->app->singleton(PriceFormatter::class);
    }
}
