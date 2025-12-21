<?php

namespace App\Domains\Catalog\Actions;

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class GenerateProductVariants
{
    public static function run(Product $product, array $optionsByAttribute): void
    {
        // Sanity check: product must exist
        if (!$product->exists) {
            throw new \InvalidArgumentException('Product must be persisted before generating variants.');
        }

        // Normalize input (remove empty attributes)
        $optionsByAttribute = array_filter(
            $optionsByAttribute,
            fn ($options) => is_array($options) && count($options) > 0
        );

        // If no attributes provided, ensure a default variant exists
        if (empty($optionsByAttribute)) {
            self::ensureDefaultVariantExists($product);
            return;
        }

        DB::transaction(function () use ($product, $optionsByAttribute) {
            $combinations = self::cartesianProduct($optionsByAttribute);

            foreach ($combinations as $optionCombination) {
                if (self::variantExists($product, $optionCombination)) {
                    continue;
                }

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => self::generateSku($product, $optionCombination),
                    'is_active' => false, // important: disable by default
                ]);

                $variant->options()->sync($optionCombination);
            }
        });
    }

    /**
    * Ensure a single default variant exists for products without attributes.
    */
    protected static function ensureDefaultVariantExists(Product $product): void
    {
        if ($product->variants()->exists()) {
            return;
        }

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => self::generateSku($product),
            'is_active' => true,
        ]);
    }

    /**
    * Check if a variant with the same option combination already exists.
    */
    protected static function variantExists(Product $product, array $optionIds): bool
    {
        $optionIds = collect($optionIds)->sort()->values();

        return $product->variants()
            ->whereHas('options', function ($query) use ($optionIds) {
                $query->whereIn('attribute_option_id', $optionIds);
            }, '=', $optionIds->count())
            ->whereDoesntHave('options', function ($query) use ($optionIds) {
                $query->whereNotIn('attribute_option_id', $optionIds);
            })
            ->exists();
    }
    
    /**
    * Generate cartesian product of attribute options.
    */

    protected static function cartesianProduct(array $input): array
    {
        $result = [[]];

        foreach ($input as $options) {
            $append = [];

            foreach ($result as $product) {
                foreach ($options as $optionId) {
                    $append[] = array_merge($product, [$optionId]);
                }
            }
            $result = $append;
        }
        return $result;
    }

    /**
    * Generate SKU (placeholder logic, can be overridden later).
    */
    protected static function generateSku(Product $product, array $optionIds = []): string
    {
        return strtoupper(
            $product->id .
            '-' .
            implode('-', $optionIds ?: ['default'])
        );
    }
}
