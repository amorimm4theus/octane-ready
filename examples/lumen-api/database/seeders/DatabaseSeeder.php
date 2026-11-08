<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['acme', 'globex', 'public'] as $tenant) {
            Product::factory()->count(1000)->create(['tenant' => $tenant]);
        }
    }
}
