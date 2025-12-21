<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function execute(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
            ]);

            $variant = $product->variants()->create([
                'sku' => $data['sku'],
                'attributes' => $data['attributes'] ?? [],
            ]);

            $variant->prices()->create([
                'currency' => 'EUR',
                'amount' => $data['price'],
                'vat_rate' => $data['vat_rate'] ?? 22,
            ]);

            return $product;
        });
    }
}
