<?php

namespace App\Http\Swagger\Storefront;

/**
 * @OA\Schema(
 *   schema="StorefrontProductListItem",
 *   type="object",
 *   required={"id","slug","name","price_from","currency"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="slug", type="string", example="basic-tshirt"),
 *   @OA\Property(property="name", type="string", example="Basic T-Shirt"),
 *   @OA\Property(property="price_from", type="integer", example=1990),
 *   @OA\Property(property="currency", type="string", example="EUR")
 * )
 *
 * @OA\Schema(
 *   schema="StorefrontVariant",
 *   type="object",
 *   required={"id","sku","attributes","price","currency","available"},
 *   @OA\Property(property="id", type="integer", example=10),
 *   @OA\Property(property="sku", type="string", example="TSHIRT-BLK-M"),
 *   @OA\Property(
 *     property="attributes",
 *     type="object",
 *     example={"color":"black","size":"M"}
 *   ),
 *   @OA\Property(property="price", type="integer", example=1990),
 *   @OA\Property(property="currency", type="string", example="EUR"),
 *   @OA\Property(property="available", type="boolean", example=true)
 * )
 *
 * @OA\Schema(
 *   schema="StorefrontProductDetail",
 *   type="object",
 *   required={"id","slug","name","description","variants"},
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="slug", type="string", example="basic-tshirt"),
 *   @OA\Property(property="name", type="string", example="Basic T-Shirt"),
 *   @OA\Property(property="description", type="string", example="100% cotton t-shirt"),
 *   @OA\Property(
 *     property="variants",
 *     type="array",
 *     @OA\Items(ref="#/components/schemas/StorefrontVariant")
 *   )
 * )
 *
 * @OA\Schema(
 *   schema="PaginationMeta",
 *   type="object",
 *   required={"current_page","last_page","per_page","total"},
 *   @OA\Property(property="current_page", type="integer", example=1),
 *   @OA\Property(property="last_page", type="integer", example=3),
 *   @OA\Property(property="per_page", type="integer", example=12),
 *   @OA\Property(property="total", type="integer", example=30)
 * )
 *
 * @OA\Schema(
 *   schema="StorefrontProductIndexResponse",
 *   type="object",
 *   required={"data","meta"},
 *   @OA\Property(
 *     property="data",
 *     type="array",
 *     @OA\Items(ref="#/components/schemas/StorefrontProductListItem")
 *   ),
 *   @OA\Property(
 *     property="meta",
 *     ref="#/components/schemas/PaginationMeta"
 *   )
 * )
 */
final class CatalogSchemas {}
