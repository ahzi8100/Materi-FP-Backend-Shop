<?php

namespace App\Http\Controllers\Api;

use Midtrans\Snap;
use App\Models\Cart;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    protected $request;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        // Fungsi ini berjalan setiap kali controller ini dipanggil. Tujuannya adalah untuk melakukan persiapan awal.
        $this->request = $request;
        // Set midtrans configuration
        \Midtrans\Config::$serverKey    = config('services.midtrans.serverKey');
        \Midtrans\Config::$isProduction = config('services.midtrans.isProduction');
        \Midtrans\Config::$isSanitized  = config('services.midtrans.isSanitized');
        \Midtrans\Config::$is3ds        = config('services.midtrans.is3ds');
    }

    public function store()
    {
        $snapToken = DB::transaction(function () {

            // Membuat Nomor Invoice Unik:
            $length = 10;
            $random = '';
            for ($i = 0; $i < $length; $i++) { // loop untuk membuat string acak
                $random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
            }

            $no_invoice = 'INV-' . Str::upper($random);

            $invoice = Invoice::create([
                'invoice'       => $no_invoice,
                'customer_id'   => Auth::user()->id,
                'courier'       => $this->request->courier,
                'service'       => $this->request->service,
                'cost_courier'  => $this->request->cost_courier,
                'weight'        => $this->request->weight,
                'name'          => $this->request->name,
                'phone'         => $this->request->phone,
                'address'       => $this->request->address,
                'grand_total'   => $this->request->grand_total,
                'status'        => 'pending',
            ]);

            $carts = Cart::with('product')->where('customer_id', Auth::user()->id)->get();
            $orders = [];

            foreach ($carts as $cart) {
                $invoice->orders()->create([
                    'invoice_id'    => $invoice->id,
                    'invoice'       => $invoice->invoice,
                    'product_id'    => $cart->product_id,
                    'product_name'  => $cart->product->title,
                    'image'         => $cart->product->image,
                    'qty'           => $cart->quantity,
                    'price'         => $cart->price,
                ]);

                $cart->product->decrement('stock', $cart->quantity);
            }

            // insert product ke table order
            $invoice->orders()->createMany($orders);

            // hapus semua product di cart
            Cart::where('customer_id', Auth::user()->id)->delete();

            // Menyiapkan data untuk membuat transaksi ke midtrans kemudian save snap tokennya.
            $payload = [
                'transaction_details' => [
                    'order_id'      => $invoice->invoice,
                    'gross_amount'  => $invoice->grand_total,
                ],
                'customer_details' => [
                    'first_name'       => $invoice->name,
                    'email'            => Auth::user()->email,
                    'phone'            => $invoice->phone,
                    'shipping_address' => $invoice->address
                ]
            ];

            //buat transaksi ke midtrans dengan meminta snap token (sesi pembayaran midtrans)
            $snapTokenResult = Snap::getSnapToken($payload);
            $invoice->snap_token = $snapTokenResult;
            $invoice->save();

            return $snapTokenResult;
        });

        return response()->json([
            'success' => true,
            'message' => 'Order Successfully',
            'snap_token' => $snapToken,
        ], 200);
    }

    /**
     * notificationHandler (Menerima Notifikasi dari Midtrans)
     *
     * Metode ini TIDAK dipanggil oleh pengguna, melainkan oleh server Midtrans setelah status transaksi berubah.
     */
    public function notificationHandler(Request $request)
    {
        $payload      = $request->getContent(); // Midtrans mengirimkan notifikasi dalam format JSON
        $notification = json_decode($payload); // String JSON mentah tadi diubah menjadi sebuah objek PHP

        $validSignatureKey = hash("sha512", $notification->order_id . $notification->status_code . $notification->gross_amount . config('services.midtrans.serverKey'));

        if ($notification->signature_key != $validSignatureKey) {
            return response(['message' => 'Invalid signature'], 403);
        }

        $transaction  = $notification->transaction_status;
        $type         = $notification->payment_type;
        $orderId      = $notification->order_id;
        $fraud        = $notification->fraud_status;

        //data tranaction
        $data_transaction = Invoice::where('invoice', $orderId)->first();

        if ($transaction == 'capture') {

            // For credit card transaction, we need to check whether transaction is challenge by FDS or not
            if ($type == 'credit_card') {

                if ($fraud == 'challenge') {

                    /**
                     *  artinya sistem pendeteksi penipuan
                     *   update invoice to pending
                     */
                    $data_transaction->update([
                        'status' => 'pending'
                    ]);
                } else {

                    /**
                     *   update invoice to success
                     */
                    $data_transaction->update([
                        'status' => 'success'
                    ]);
                }
            }
        } elseif ($transaction == 'settlement') {
            /**
             * untuk metode pembayaran lain seperti transfer bank atau e-wallet
             *   update invoice to success
             */
            $data_transaction->update([
                'status' => 'success'
            ]);
        } elseif ($transaction == 'pending') {


            /**
             *   update invoice to pending
             */
            $data_transaction->update([
                'status' => 'pending'
            ]);
        } elseif ($transaction == 'deny') {


            /**
             *   update invoice to failed
             */
            $data_transaction->update([
                'status' => 'failed'
            ]);
        } elseif ($transaction == 'expire') {


            /**
             *   update invoice to expired
             */
            $data_transaction->update([
                'status' => 'expired'
            ]);
        } elseif ($transaction == 'cancel') {

            /**
             *   update invoice to failed
             */
            $data_transaction->update([
                'status' => 'failed'
            ]);
        }
    }
}
