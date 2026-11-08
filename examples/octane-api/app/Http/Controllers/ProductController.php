<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\PriceFormatter;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private PriceFormatter $formatter) {}

    public function index(Request $request, TenantContext $tenant): JsonResponse
    {
        $locale = PriceFormatter::localeFrom($request->header('Accept-Language'));

        $products = Product::query()
            ->where('tenant', $tenant->id())
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('id')
            ->paginate(20);

        return response()->json([
            'tenant' => $tenant->id(),
            'locale' => $locale,
            'data' => collect($products->items())->map(fn (Product $p) => $this->present($p, $locale)),
            'page' => $products->currentPage(),
            'total' => $products->total(),
        ]);
    }

    public function show(Request $request, TenantContext $tenant, int $id): JsonResponse
    {
        $product = Product::where('tenant', $tenant->id())->findOrFail($id);
        $locale = PriceFormatter::localeFrom($request->header('Accept-Language'));

        return response()->json($this->present($product, $locale));
    }

    private function present(Product $product, string $locale): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $this->formatter->format($product->price_cents, $locale),
            'stock' => $product->stock,
        ];
    }
}
