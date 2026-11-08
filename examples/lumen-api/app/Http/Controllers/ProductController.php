<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\PriceFormatter;
use App\Support\SearchCache;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, PriceFormatter $formatter)
    {
        $products = SearchCache::remember($request->fullUrl(), function () use ($request) {
            return Product::query()
                ->where('tenant', TenantContext::id())
                ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->orderBy('id')
                ->paginate(20);
        });

        return response()->json([
            'tenant' => TenantContext::id(),
            'locale' => $formatter->locale(),
            'data' => collect($products->items())->map(fn (Product $p) => $this->present($p, $formatter)),
            'page' => $products->currentPage(),
            'total' => $products->total(),
        ]);
    }

    public function show(int $id, PriceFormatter $formatter)
    {
        $product = Product::where('tenant', TenantContext::id())->findOrFail($id);

        return response()->json($this->present($product, $formatter));
    }

    private function present(Product $product, PriceFormatter $formatter): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $formatter->format($product->price_cents),
            'stock' => $product->stock,
        ];
    }
}
