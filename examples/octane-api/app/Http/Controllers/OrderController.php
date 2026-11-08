<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;

final class OrderController extends Controller
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function store(StoreOrderRequest $request, PlaceOrder $placeOrder): JsonResponse
    {
        $order = $placeOrder->handle(
            tenant: $this->tenant->id(),
            customerEmail: $request->string('customer_email')->toString(),
            items: $request->items(),
        );

        return response()->json($order, 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = Order::query()
            ->with('items.product')
            ->forTenant($this->tenant->id())
            ->findOrFail($id);

        return response()->json($order);
    }
}
