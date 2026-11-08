<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['tenant', 'customer_email', 'total_cents', 'status'];

    protected $casts = ['total_cents' => 'integer'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
