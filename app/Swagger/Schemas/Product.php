<?php

/**
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="sku", type="string"),
 *     @OA\Property(
 *         property="attributes",
 *         type="object",
 *         additionalProperties=true
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     required={"id","name","slug"},
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="slug", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(
 *         property="variants",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ProductVariant")
 *     ),
 *     @OA\Property(
 *         property="seo",
 *         type="object",
 *         @OA\Property(property="title", type="string"),
 *         @OA\Property(property="description", type="string")
 *     )
 * )
 */
