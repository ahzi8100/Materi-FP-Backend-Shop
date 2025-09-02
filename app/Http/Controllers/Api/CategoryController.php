<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    // Get Seluruh data kategori
    public function index()
    {
        $categories = Category::latest()->get();
        return response()->json([
            'success'       => true,
            'message'       => 'List Data Category',
            'categories'    => $categories
        ]);
    }

    // Get Seluruh data produk dari suatu kategori
    public function show($slug)
    {
        $category = Category::where('slug', $slug)->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Data Product By Category Tidak Ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'List Product By Category: ' . $category->name,
            "product" => $category->products()->latest()->get()
        ], 200);
    }

    // Get 5 data kategri
    public function categoryHeader()
    {
        $categories = Category::latest()->take(3)->get();
        return response()->json([
            'success'       => true,
            'message'       => 'List Data Category Header',
            'categories'    => $categories
        ]);
    }
}
