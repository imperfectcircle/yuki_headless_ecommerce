<?php

namespace App\Http\Requests\Storefront\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999']
        ];
    }

    public function messages(): array
    {
        return [
            'variant_id.required' => 'Product variant is required.',
            'variant_id.exists' => 'The selected product variant does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 999.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasHeader('X-cart-token')) {
            $this->headers->set(
                'X-Cart-Token',
                $this->header('X-cart-token')
            );
        }
    }
}
