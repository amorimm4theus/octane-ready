<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'customer_email' => 'required|email',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = DB::transaction(function () use ($request) {
            $order = Order::create([
                'tenant' => TenantContext::id(),
                'customer_email' => $request->input('customer_email'),
                'total_cents' => 0,
                'status' => 'pending',
            ]);

            $total = 0;
            foreach ($request->input('items') as $item) {
                $product = Product::where('tenant', TenantContext::id())
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    abort(422, "Not enough stock for {$product->sku}");
                }

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

    public function show(int $id)
    {
        $order = Order::with('items.product')
            ->where('tenant', TenantContext::id())
            ->findOrFail($id);

        return response()->json($order);
    }
}
