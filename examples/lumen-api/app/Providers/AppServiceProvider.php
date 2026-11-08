<?php

namespace App\Providers;

use App\Support\PriceFormatter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PriceFormatter::class, function ($app) {
            return new PriceFormatter($app['request']);
        });
    }
}
