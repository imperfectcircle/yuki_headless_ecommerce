<?php

namespace App\Domains\Storefront\DTOs\Cart;

final readonly class StorefrontCartDTO
{
    /**
    * @param StorefrontCartItemDTO[] $items
    */
    public function __construct(
        public int $id,
        public string $token,
        public string $currency,
        public array $items,
        public StorefrontCartTotalsDTO $totals,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'currency' => $this->currency,
            'items' => array_map(
                fn ($item) => $item->toArray(),
                $this->items
            ),
            'totals' => $this->totals->toArray(),
        ];
    }
}
