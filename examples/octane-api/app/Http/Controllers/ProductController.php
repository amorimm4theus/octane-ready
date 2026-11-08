<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Support\PriceFormatter;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    private const PER_PAGE = 20;

    public function __construct(private readonly TenantContext $tenant) {}

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->forTenant($this->tenant->id())
            ->search($request->string('search')->toString())
            ->orderBy('id')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'tenant' => $this->tenant->id(),
            'locale' => PriceFormatter::localeFrom($request->header('Accept-Language')),
            'data' => ProductResource::collection($products->items()),
            'page' => $products->currentPage(),
            'total' => $products->total(),
        ]);
    }

    public function show(int $id): ProductResource
    {
        return ProductResource::make(
            Product::query()->forTenant($this->tenant->id())->findOrFail($id)
        );
    }
}
