<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'email'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return list<array{product_id: int, quantity: int}>
     */
    public function items(): array
    {
        /** @var list<array{product_id: int|string, quantity: int|string}> $items */
        $items = $this->validated('items');

        return array_map(static fn (array $item): array => [
            'product_id' => (int) $item['product_id'],
            'quantity' => (int) $item['quantity'],
        ], $items);
    }
}
