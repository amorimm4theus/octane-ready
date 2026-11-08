<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $tenant
 * @property string $customer_email
 * @property int $total_cents
 * @property string $status
 */
final class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    protected $fillable = ['tenant', 'customer_email', 'total_cents', 'status'];

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeForTenant(Builder $query, string $tenant): void
    {
        $query->where('tenant', $tenant);
    }

    protected function casts(): array
    {
        return ['total_cents' => 'integer'];
    }
}
