<?php

declare(strict_types=1);

use App\Models\Product;

/*
 * Under Octane one worker serves many requests. These tests send two requests
 * to the same application instance and, in between, reset exactly what Octane
 * resets (scoped instances and cached controller instances). Anything else that
 * survives is a leak.
 *
 * Stricter than Octane on purpose: there is no per-request sandbox clone here,
 * so a singleton that captured the Request also fails (see docs/adr/0003).
 */

function nextRequestOnSameWorker(): void
{
    app()->forgetScopedInstances();

    foreach (app('router')->getRoutes() as $route) {
        $route->flushController();
    }
}

it('does not leak the tenant to the next request', function () {
    $this->getJson('/health', asTenant('acme'))->assertJsonPath('tenant', 'acme');

    nextRequestOnSameWorker();

    $this->getJson('/health')->assertJsonPath('tenant', 'public');
});

it('does not leak the locale to the next request', function () {
    $product = Product::factory()->create(['tenant' => 'acme', 'price_cents' => 1000]);

    $this->getJson("/products/{$product->id}", asTenant('acme', ['Accept-Language' => 'en']))
        ->assertJsonPath('price', '$10.00');

    nextRequestOnSameWorker();

    $this->getJson("/products/{$product->id}", asTenant('acme', ['Accept-Language' => 'pt-BR']))
        ->assertJsonPath('price', 'R$ 10,00');
});

it('does not serve another tenant the previous tenant\'s listing', function () {
    Product::factory()->count(3)->create(['tenant' => 'acme']);
    Product::factory()->count(7)->create(['tenant' => 'globex']);

    $this->getJson('/products', asTenant('acme'))->assertJsonPath('total', 3);

    nextRequestOnSameWorker();

    $this->getJson('/products', asTenant('globex'))->assertJsonPath('total', 7);
});
