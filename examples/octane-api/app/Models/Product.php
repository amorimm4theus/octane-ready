<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $tenant
 * @property string $sku
 * @property string $name
 * @property int $price_cents
 * @property int $stock
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['tenant', 'name', 'sku', 'price_cents', 'stock'];

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForTenant(Builder $query, string $tenant): void
    {
        $query->where('tenant', $tenant);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->when($term !== '', fn (Builder $q) => $q->where('name', 'like', "%{$term}%"));
    }

    protected function casts(): array
    {
        return ['price_cents' => 'integer', 'stock' => 'integer'];
    }
}
