<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'tenant' => 'acme',
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####-??')),
            'name' => ucfirst($this->faker->words(3, true)),
            'price_cents' => $this->faker->numberBetween(500, 50000),
            'stock' => $this->faker->numberBetween(0, 500),
        ];
    }
}
