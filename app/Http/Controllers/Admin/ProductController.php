<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = $request->q;

        $products = Product::latest()->when($q, function ($query) use ($q) {
            $query->where('title', 'LIKE', '%' . $q . '%');
        })->paginate(10);

        confirmDelete('Delete Product!', "Are you sure you want to delete?");
        return view('admin.product.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::latest()->get();
        return view('admin.product.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image'          => 'required|image|mimes:jpeg,jpg,png|max:2000',
            'title'          => 'required|unique:products',
            'category_id'    => 'required',
            'content'        => 'required',
            'weight'         => 'required',
            'price'          => 'required',
            'stock'          => 'required',
            'discount'       => 'required',
        ]);

        //upload image
        $image = $request->file('image');
        $image->storeAs('products', $image->hashName(), 'public');

        //save to DB
        $product = Product::create([
            'image'          => $image->hashName(),
            'title'          => $request->title,
            'slug'           => Str::slug($request->title, '-'),
            'category_id'    => $request->category_id,
            'content'        => $request->content,
            'weight'         => $request->weight,
            'price'          => $request->price,
            'stock'          => $request->stock,
            'discount'       => $request->discount,
            'keywords'       => $request->keywords,
            'description'    => $request->description
        ]);

        if (!$product) {
            Alert::error('Create Failed', 'Data Gagal Disimpan!');
            return redirect()->route('admin.product.index');
        }

        Alert::success('Create Successfully', 'Data Berhasil Disimpan!');
        return redirect()->route('admin.product.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::latest()->get();
        return view('admin.product.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'title'          => 'required|unique:products,title,' . $product->id,
            'category_id'    => 'required',
            'content'        => 'required',
            'weight'         => 'required',
            'stock'          => 'required',
            'price'          => 'required',
            'discount'       => 'required',
        ]);

        //cek jika image kosong
        if ($request->file('image') == '') {

            //update tanpa image
            $product = Product::findOrFail($product->id);
            $product->update([
                'title'          => $request->title,
                'slug'           => Str::slug($request->title, '-'),
                'category_id'    => $request->category_id,
                'content'        => $request->content,
                'weight'         => $request->weight,
                'price'          => $request->price,
                'stock'          => $request->stock,
                'discount'       => $request->discount,
                'keywords'       => $request->keywords,
                'description'    => $request->description
            ]);
        } else {

            //hapus image lama
            Storage::disk('public')->delete('products/' . basename($product->image));

            //upload image baru
            $image = $request->file('image');
            $image->storeAs('products', $image->hashName(), 'public');

            //update dengan image
            $product = Product::findOrFail($product->id);
            $product->update([
                'image'          => $image->hashName(),
                'title'          => $request->title,
                'slug'           => Str::slug($request->title, '-'),
                'category_id'    => $request->category_id,
                'content'        => $request->content,
                'weight'         => $request->weight,
                'price'          => $request->price,
                'stock'          => $request->stock,
                'discount'       => $request->discount,
                'keywords'       => $request->keywords,
                'description'    => $request->description
            ]);
        }

        if (!$product) {
            Alert::error('Updated Failed', 'Data Gagal Diupdate!');
            return redirect()->route('admin.product.index');
        }

        Alert::success('Updated Successfully', 'Data Berhasil Diupdate!');
        return redirect()->route('admin.product.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if (!$product) {
            Alert::error('Deleted Failed', 'Data Gagal Dihapus!');
            return redirect()->route('admin.product.index');
        }

        Storage::disk('public')->delete('products/' . basename($product->image));
        $product->delete();

        Alert::success('Deleted Successfully', 'Data Berhasil Dihapus!');
        return redirect()->route('admin.product.index');
    }
}

