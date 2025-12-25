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
 *   @OA\Response(
 *     response=200,
 *     description="Paginated product list",
 *     @OA\JsonContent(
 *       type="object",
 *       @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/StorefrontProductListItem")
 *       ),
 *       @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer"),
 *         @OA\Property(property="last_page", type="integer"),
 *         @OA\Property(property="per_page", type="integer"),
 *         @OA\Property(property="total", type="integer")
 *       )
 *     )
 *   )
 * )
 */
    public function index(ProductListItemTransformer $transformer): JsonResponse
    {
        $currency = request()->query('currency', 'EUR');
        
        $products = Product::query()
            ->where('status', 'published')
            ->orderBy('name')
            ->paginate(12);
            

        return response()->json([
            'data' => $products->getCollection()
                ->map(fn ($product) => 
                    $transformer->transform($product, $currency)->toArray(),
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
    *   summary="Get storefront product",
    *   tags={"Storefront Catalog"},
    *   @OA\Parameter(
    *     name="slug",
    *     in="path",
    *     required=true,
    *     @OA\Schema(type="string")
    *   ),
    *   @OA\Parameter(
    *     name="currency",
    *     in="query",
    *     required=true,
    *     @OA\Schema(type="string", example="EUR")
    *   ),
    *   @OA\Response(
    *     response=200,
    *     description="Product detail",
    *     @OA\JsonContent(ref="#/components/schemas/StorefrontProductDetail")
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
