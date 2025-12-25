<?php

namespace App\Domains\Storefront\DTOs\Cart;

final readonly class StorefrontCartDTO
{
    /**
    * @param StorefrontCartItemDTO[] $items
    */
    public function __construct(
        public string $token,
        public string $currency,
        public array $items,
        public int $subtotal,
    ) {}

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'items' => array_map(
                fn (StorefrontCartItemDTO $item) => $item->toArray(),
                $this->items
            ),
        ];
    }
}
