<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Product;

it('places an order, reserves stock and computes the total', function () {
    $pen = Product::factory()->create(['tenant' => 'acme', 'price_cents' => 250, 'stock' => 10]);
    $book = Product::factory()->create(['tenant' => 'acme', 'price_cents' => 4990, 'stock' => 2]);

    $this->postJson('/orders', [
        'customer_email' => 'ana@example.com',
        'items' => [
            ['product_id' => $pen->id, 'quantity' => 4],
            ['product_id' => $book->id, 'quantity' => 1],
        ],
    ], asTenant('acme'))
        ->assertCreated()
        ->assertJsonPath('total_cents', 4 * 250 + 4990)
        ->assertJsonPath('status', 'pending')
        ->assertJsonCount(2, 'items');

    expect($pen->fresh()->stock)->toBe(6)
        ->and($book->fresh()->stock)->toBe(1);
});

it('rejects the whole order when one item has no stock', function () {
    $pen = Product::factory()->create(['tenant' => 'acme', 'stock' => 10]);
    $book = Product::factory()->create(['tenant' => 'acme', 'stock' => 1]);

    $this->postJson('/orders', [
        'customer_email' => 'ana@example.com',
        'items' => [
            ['product_id' => $pen->id, 'quantity' => 3],
            ['product_id' => $book->id, 'quantity' => 2],
        ],
    ], asTenant('acme'))->assertUnprocessable();

    expect($pen->fresh()->stock)->toBe(10)
        ->and(Order::count())->toBe(0);
});

it('validates the payload', function () {
    $this->postJson('/orders', ['customer_email' => 'not-an-email', 'items' => []], asTenant('acme'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_email', 'items']);
});

it('does not sell products of another tenant', function () {
    $product = Product::factory()->create(['tenant' => 'globex', 'stock' => 10]);

    $this->postJson('/orders', [
        'customer_email' => 'ana@example.com',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], asTenant('acme'))->assertNotFound();

    expect($product->fresh()->stock)->toBe(10);
});

it('shows an order with its items only to the same tenant', function () {
    $product = Product::factory()->create(['tenant' => 'acme', 'stock' => 5]);
    $orderId = $this->postJson('/orders', [
        'customer_email' => 'ana@example.com',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ], asTenant('acme'))->json('id');

    $this->getJson("/orders/{$orderId}", asTenant('acme'))
        ->assertOk()
        ->assertJsonPath('items.0.product.sku', $product->sku);

    $this->getJson("/orders/{$orderId}", asTenant('globex'))->assertNotFound();
});
