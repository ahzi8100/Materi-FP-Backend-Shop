<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
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

    public function store(Request $request)
    {
        // pastikan ada product_id dan quantity
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'customer_id' => 'required|exists:customers,id',
            'quantity'    => 'nullable|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);

        // ambil item cart berdasarkan customer & produk
        $item = Cart::where('product_id', $product->id)
            ->where('customer_id', $request->customer_id)
            ->first();

        if ($item) {
            // increment quantity
            $item->increment('quantity', $request->quantity ?? 1);

            // update price & weight berdasarkan relasi product
            $item->update([
                'price'  => $request->price * $item->quantity,
                'weight' => $product->weight * $item->quantity,
            ]);
        } else {
            $item = Cart::create([
                'product_id'  => $product->id,
                'customer_id' => $request->customer_id,
                'quantity'    => $request->quantity ?? 1,
                'price'       => $request->price * ($request->quantity ?? 1),
                'weight'      => $product->weight * ($request->quantity ?? 1),
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Success Add To Cart',
            'quantity' => $item->quantity,
            'product'  => $item->product, // pastikan ada relasi product() di model Cart
        ]);
    }

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

    /**
     * getCartTotalWeight
     *
     * @return void
     */
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

    /**
     * removeCart
     *
     * @param  mixed $request
     * @return void
     */
    public function removeCart(Cart $cart)
    {
        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Remove Item Cart',
        ]);
    }

    /**
     * removeAllCart
     *
     * @param  mixed $request
     * @return void
     */
    public function removeAllCart()
    {
        Cart::where('customer_id', Auth::user()->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Remove All Item in Cart',
        ]);
    }
}
