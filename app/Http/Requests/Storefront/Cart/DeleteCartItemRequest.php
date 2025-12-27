<?php

namespace App\Http\Requests\Storefront\Cart;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCartItemRequest extends FormRequest
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
            //
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->hasHeader('X-cart-token')) {
            $this->headers->set(
                'X-Cart-Token',
                $this->header('X-cart-token')
            );
        }
    }
}
