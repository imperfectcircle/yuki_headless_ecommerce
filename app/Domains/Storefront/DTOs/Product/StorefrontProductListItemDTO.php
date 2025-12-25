<?php

namespace App\Domains\Storefront\DTOs\Product;

final readonly class StorefrontProductListItemDTO
{
    /**
    * @OA\Schema(
    *   schema="StorefrontProductListItem",
    *   type="object",
    *   required={"id","slug","name","price_from","currency"},
    *   @OA\Property(property="id", type="integer", example=1),
    *   @OA\Property(property="slug", type="string", example="t-shirt-black"),
    *   @OA\Property(property="name", type="string", example="Black T-Shirt"),
    *   @OA\Property(property="price_from", type="integer", example=1999),
    *   @OA\Property(property="currency", type="string", example="EUR")
    * )
    */
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public int $priceFrom,
        public string $currency,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'price_from' => $this->priceFrom,
            'currency' => $this->currency,
        ];
    }
}
