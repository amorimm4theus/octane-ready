<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request, TenantContext $tenant): JsonResponse
    {
        $data = $request->validate([
            'customer_email' => 'required|email',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($data, $tenant) {
            $order = Order::create([
                'tenant' => $tenant->id(),
                'customer_email' => $data['customer_email'],
                'total_cents' => 0,
                'status' => 'pending',
            ]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::where('tenant', $tenant->id())
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                abort_if($product->stock < $item['quantity'], 422, "Not enough stock for {$product->sku}");

                $product->decrement('stock', $item['quantity']);
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $product->price_cents,
                ]);
                $total += $product->price_cents * $item['quantity'];
            }

            $order->update(['total_cents' => $total]);

            return $order;
        });

        return response()->json($order->load('items'), 201);
    }

    public function show(TenantContext $tenant, int $id): JsonResponse
    {
        $order = Order::with('items.product')
            ->where('tenant', $tenant->id())
            ->findOrFail($id);

        return response()->json($order);
    }
}
