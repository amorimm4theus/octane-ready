<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InsufficientStock;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Places an order and reserves stock atomically.
 *
 * Stock is taken with one conditional UPDATE (stock >= quantity), so the check and
 * the decrement are a single atomic statement: two concurrent orders cannot sell
 * the same last unit. The transaction makes a multi-item order all or nothing.
 */
final class PlaceOrder
{
    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function handle(string $tenant, string $customerEmail, array $items): Order
    {
        return DB::transaction(function () use ($tenant, $customerEmail, $items): Order {
            $order = Order::create([
                'tenant' => $tenant,
                'customer_email' => $customerEmail,
                'total_cents' => 0,
                'status' => Order::STATUS_PENDING,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $product = Product::query()
                    ->forTenant($tenant)
                    ->findOrFail($item['product_id']);

                $reserved = Product::query()
                    ->whereKey($product->id)
                    ->where('stock', '>=', $item['quantity'])
                    ->decrement('stock', $item['quantity']);

                if ($reserved === 0) {
                    throw InsufficientStock::for($product->refresh(), $item['quantity']);
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $product->price_cents,
                ]);

                $total += $product->price_cents * $item['quantity'];
            }

            $order->update(['total_cents' => $total]);

            return $order->load('items');
        });
    }
}
