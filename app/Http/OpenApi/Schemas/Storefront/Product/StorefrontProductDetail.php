<?php

namespace App\Http\OpenApi\Schemas\Storefront\Product;

/**
 * @OA\Schema(
 *   schema="StorefrontProductDetail",
 *   type="object",
 *   required={"id","slug","name","description","variants"}
 * )
 */
class StorefrontProductDetail
{
    /**
     * @OA\Property(example=1)
     */
    public int $id;

    /**
     * @OA\Property(example="t-shirt-basic")
     */
    public string $slug;

    /**
     * @OA\Property(example="Basic T-Shirt")
     */
    public string $name;

    /**
     * @OA\Property(example="Comfortable cotton t-shirt")
     */
    public string $description;

    /**
     * @OA\Property(
     *   type="array",
     *   @OA\Items(ref="#/components/schemas/StorefrontVariant")
     * )
     */
    public array $variants;
}
