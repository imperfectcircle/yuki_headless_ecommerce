<?php

namespace App\Http\Controllers\Storefront\Catalog;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Transformers\ProductTransformer;
use App\Domains\Storefront\Queries\GetStorefrontProduct;
use App\Domains\Storefront\Transformers\Product\ProductListItemTransformer;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Request;

/**
* @OA\Tag(
*   name="Storefront Catalog",
*   description="Read-only product catalog for storefronts"   
*)
*/

class ProductController extends Controller
{
    /**
    * @OA\Get(
    *   path="/api/storefront/v1/products",
    *   tags={"Storefront Catalog"},
    *   summary="List published products",
    *   @OA\Parameter(
    *     name="currency",
    *     in="query",
    *     required=false,
    *     @OA\Schema(type="string", example="EUR")
    *   ),
    *   @OA\Response(
    *     response=200,
    *     description="Paginated list of products",
    *     @OA\JsonContent(ref="#/components/schemas/StorefrontProductIndexResponse")
    *   )
    * )
    */
    public function index(Request $request): JsonResponse
    {
        $currency = request()->query('currency', 'EUR');
        
        $products = Product::query()
            ->where('status', 'published')
            ->with('variants.price')
            ->orderBy('name')
            ->paginate(12);
            

        return response()->json([
            'data' => $products->getCollection()
                ->map(fn ($product) => 
                    ProductListItemTransformer::transform($product, $currency),
                ),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
    * @OA\Get(
    *   path="/api/storefront/v1/products/{slug}",
    *   tags={"Storefront Catalog"},
    *   summary="Get product detail",
    *   @OA\Parameter(
    *     name="slug",
    *     in="path",
    *     required=true,
    *     @OA\Schema(type="string")
    *   ),
    *   @OA\Parameter(
    *     name="currency",
    *     in="query",
    *     required=false,
    *     @OA\Schema(type="string", example="EUR")
    *   ),
    *   @OA\Response(
    *     response=200,
    *     description="Product detail",
    *     @OA\JsonContent(
    *       type="object",
    *       @OA\Property(
    *         property="data",
    *         ref="#/components/schemas/StorefrontProductDetail"
    *       )
    *     )
    *   )
    * )
    */
    public function show(string $slug, Request $request, GetStorefrontProduct $query): JsonResponse
    {
        $currency = $request->query('currency', 'EUR');

        return response()->json(
            $query->execute($slug, $currency)
        );
    }
}
