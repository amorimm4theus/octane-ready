<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['tenant', 'name', 'sku', 'price_cents', 'stock'];

    protected $casts = ['price_cents' => 'integer', 'stock' => 'integer'];
}
