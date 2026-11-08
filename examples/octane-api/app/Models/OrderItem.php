<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $quantity
 * @property int $unit_price_cents
 */
final class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price_cents'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price_cents' => 'integer'];
    }
}
