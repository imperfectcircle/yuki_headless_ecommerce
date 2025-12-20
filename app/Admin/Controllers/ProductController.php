<?php

namespace App\Admin\Controllers;

use App\Domains\Catalog\Actions\CreateProduct;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        app(CreateProduct::class)->execute($request->validate([
            'name' => 'required|string',
            'sku' => 'required|string|unique:product_variants,sku',
            'price' => 'required|integer',
        ]));

        return redirect()->route('products.index');
    }
}
