<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    // Mengambil seluruh cart yang dimiliki per customer
    public function index()
    {
        $carts = Cart::with('product')
            ->where('customer_id', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Cart',
            'cart'    => $carts
        ]);
    }

    // menambahkan product ke cart
    public function store(Request $request)
    {
        // pastikan ada product_id dan quantity
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'quantity'    => 'required|integer|min:1'
        ]);

        $userId = Auth::user()->id;
        $product = Product::findOrFail($request->product_id);

        // Hitung harga final setelah diskon
        $finalPrice = $product->price;
        if ($product->discount > 0) {
            $finalPrice = $product->price - ($product->price * $product->discount / 100);
        }

        // cari item cart berdasarkan customer & produk
        $item = Cart::where('product_id', $product->id)
            ->where('customer_id', $userId)
            ->first();

        // jika item sudah ada di cart maka tambah qty dan hitung ulang harga, berat
        if ($item) {
            // increment quantity
            $item->increment('quantity', $request->quantity ?? 1);

            // update price & weight berdasarkan relasi product
            $item->update([
                'price'  => $finalPrice * $item->quantity,
                'weight' => $product->weight * $item->quantity,
            ]);
        } else {
            $item = Cart::create([
                'product_id'  => $product->id,
                'customer_id' => $userId,
                'quantity'    => $request->quantity ?? 1,
                'price'       => $finalPrice * ($request->quantity ?? 1),
                'weight'      => $product->weight * ($request->quantity ?? 1),
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Success Add To Cart',
            'cart' => $item,
        ]);
    }

    // mengambil total harga di cart
    public function getCartTotal()
    {
        $carts = Cart::with('product')
            ->where('customer_id', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->sum('price');

        return response()->json([
            'success' => true,
            'message' => 'Total Cart Price ',
            'total'   => $carts
        ]);
    }

    // mengambil total weight di cart
    public function getCartTotalWeight()
    {
        $carts = Cart::with('product')
            ->where('customer_id', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->sum('weight');

        return response()->json([
            'success' => true,
            'message' => 'Total Cart Weight ',
            'total'   => $carts
        ]);
    }

    // menghapus item di cart
    public function removeCart(Cart $cart)
    {
        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Remove Item Cart',
        ]);
    }

    // menghapus semua item di cart
    // public function removeAllCart()
    // {
    //     Cart::where('customer_id', Auth::user()->id)
    //         ->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Remove All Item in Cart',
    //     ]);
    // }
}
