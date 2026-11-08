<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\PriceFormatter;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Request state: a new instance per request (Octane flushes scoped instances).
        $this->app->scoped(TenantContext::class);

        // Stateless: safe to share for the whole worker lifetime.
        $this->app->singleton(PriceFormatter::class);
    }

    public function boot(): void
    {
        // Fail fast in development on lazy loading (N+1), silent attribute drops and missing attributes.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Keep the Lumen response shape: resources are not wrapped in "data".
        JsonResource::withoutWrapping();
    }
}
