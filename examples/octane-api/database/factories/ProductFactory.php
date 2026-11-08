<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'tenant' => 'acme',
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####-??')),
            'name' => rtrim($this->faker->sentence(3, false), '.'),
            'price_cents' => $this->faker->numberBetween(500, 50000),
            'stock' => $this->faker->numberBetween(0, 500),
        ];
    }
}
