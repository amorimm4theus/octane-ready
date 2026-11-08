<?php

declare(strict_types=1);

use App\Models\Product;

it('lists only the products of the current tenant, 20 per page', function () {
    Product::factory()->count(25)->create(['tenant' => 'acme']);
    Product::factory()->count(5)->create(['tenant' => 'globex']);

    $this->getJson('/products', asTenant('acme'))
        ->assertOk()
        ->assertJsonPath('tenant', 'acme')
        ->assertJsonPath('total', 25)
        ->assertJsonPath('page', 1)
        ->assertJsonCount(20, 'data')
        ->assertJsonStructure(['data' => [['id', 'sku', 'name', 'price', 'stock']]]);
});

it('filters products by name', function () {
    Product::factory()->create(['tenant' => 'acme', 'name' => 'Blue notebook']);
    Product::factory()->create(['tenant' => 'acme', 'name' => 'Red pen']);

    $this->getJson('/products?search=notebook', asTenant('acme'))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.name', 'Blue notebook');
});

it('formats prices from the Accept-Language header', function (string $language, string $expected) {
    $product = Product::factory()->create(['tenant' => 'acme', 'price_cents' => 123456]);

    $this->getJson("/products/{$product->id}", asTenant('acme', ['Accept-Language' => $language]))
        ->assertOk()
        ->assertJsonPath('price', $expected);
})->with([
    'english' => ['en-US', '$1,234.56'],
    'portuguese' => ['pt-BR', 'R$ 1.234,56'],
]);

it('returns 404 for a product of another tenant', function () {
    $product = Product::factory()->create(['tenant' => 'globex']);

    $this->getJson("/products/{$product->id}", asTenant('acme'))->assertNotFound();
});
