<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\PriceFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = PriceFormatter::localeFrom($request->header('Accept-Language'));

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => app(PriceFormatter::class)->format($this->price_cents, $locale),
            'stock' => $this->stock,
        ];
    }
}
