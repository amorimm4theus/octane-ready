<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed seed: both apps in the benchmark get identical data.
        fake()->seed(2026);

        foreach (['acme', 'globex', 'public'] as $tenant) {
            Product::factory()->count(1000)->create(['tenant' => $tenant]);
        }
    }
}
