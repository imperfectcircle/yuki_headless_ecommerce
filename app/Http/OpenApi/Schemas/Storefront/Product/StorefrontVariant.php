<?php

namespace App\Http\OpenApi\Schemas\Storefront\Product;

/**
 * @OA\Schema(
 *   schema="StorefrontVariant",
 *   type="object",
 *   required={"id","sku","attributes","price","currency","available"}
 * )
 */
class StorefrontVariant
{
    /**
     * @OA\Property(example=12)
     */
    public int $id;

    /**
     * @OA\Property(example="TSHIRT-BLACK-M")
     */
    public string $sku;

    /**
     * @OA\Property(
     *   type="object",
     *   example={"size":"M","color":"black"}
     * )
     */
    public array $attributes;

    /**
     * @OA\Property(
     *   example=1990,
     *   description="Price in minor units (e.g. cents)"
     * )
     */
    public int $price;

    /**
     * @OA\Property(example="EUR")
     */
    public string $currency;

    /**
     * @OA\Property(example=true)
     */
    public bool $available;
}