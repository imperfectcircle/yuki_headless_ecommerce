<?php

namespace App\Http\OpenApi\Schemas\Storefront\Product;

/**
 * @OA\Schema(
 *   schema="StorefrontProductListItem",
 *   type="object",
 *   required={"id","slug","name","price_from","currency"}
 * )
 */
class StorefrontProductListItem
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
     * @OA\Property(
     *   example=1990,
     *   description="Lowest variant price in minor units"
     * )
     */
    public int $price_from;

    /**
     * @OA\Property(example="EUR")
     */
    public string $currency;
}
