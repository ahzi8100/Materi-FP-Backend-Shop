<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        return response()->json([
            'success'   => true,
            'message'   => 'List Data Products',
            'products'  => $products
        ], 200);
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message'   => 'Data Product Tidak Ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message'   => 'Detail Data Product',
            'product' => $product
        ], 200);
    }
}
