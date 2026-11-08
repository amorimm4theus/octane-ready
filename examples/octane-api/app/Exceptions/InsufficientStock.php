<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class InsufficientStock extends RuntimeException
{
    public static function for(Product $product, int $requested): self
    {
        return new self("Not enough stock for {$product->sku}: requested {$requested}, available {$product->stock}.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
